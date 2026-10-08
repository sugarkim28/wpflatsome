"""Kiểm thử với cổng giả lập: python -m unittest test_taihoadon.py"""

import io
import json
import os
import shutil
import tempfile
import threading
import unittest
import urllib.parse
import urllib.request
import zipfile
from datetime import date
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import taihoadon as t


def make_invoice(i, day, prefix_tag=""):
    return {"nbmst": "0101234567", "nbten": "Cong ty Ban %d" % i, "nmmst": "0309999999", "nmten": "Cong ty Mua",
            "khmshdon": 1, "khhdon": "C26TAA" + prefix_tag, "shdon": i, "tdlap": "%sT08:00:00.000+0000" % day,
            "tgtcthue": 1000000 * i, "tgtthue": 100000 * i, "tgtttbso": 1100000 * i, "tthai": 1, "ttxly": 5,
            "dvtte": "VND", "mhdon": "M%d" % i}


class FakePortal(BaseHTTPRequestHandler):
    calls = []

    def log_message(self, *a):
        pass

    def _json(self, code, data):
        body = json.dumps(data).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_POST(self):
        body = json.loads(self.rfile.read(int(self.headers["Content-Length"])))
        if body["cvalue"] != "abcd":
            return self._json(400, {"message": "Mã captcha không đúng"})
        self._json(200, {"token": "TOKEN123"})

    def do_GET(self):
        u = urllib.parse.urlparse(self.path)
        q = dict(urllib.parse.parse_qsl(u.query))
        FakePortal.calls.append((u.path, q))
        if u.path == "/captcha":
            return self._json(200, {"key": "K1", "content": "<svg></svg>"})
        if self.headers.get("Authorization") != "Bearer TOKEN123":
            return self._json(401, {"message": "unauthorized"})
        if u.path == "/query/invoices/purchase":
            if "ttxly==5" not in q["search"]:
                return self._json(200, {"datas": [], "total": 0})
            # 60 hoá đơn trong tháng 9 → 2 trang (50 + 10)
            if "01/09/2026" in q["search"]:
                all_ = [make_invoice(i, "2026-09-%02d" % (i % 28 + 1)) for i in range(1, 61)]
                page = all_[50:] if q.get("state") == "S2" else all_[:50]
                return self._json(200, {"datas": page, "total": 60, "state": None if q.get("state") else "S2"})
            return self._json(200, {"datas": [make_invoice(99, "2026-10-02")], "total": 1})
        if u.path in ("/query/invoices/sold", "/sco-query/invoices/purchase", "/sco-query/invoices/sold"):
            return self._json(200, {"datas": [], "total": 0})
        if u.path == "/query/invoices/export-xml":
            buf = io.BytesIO()
            with zipfile.ZipFile(buf, "w") as z:
                z.writestr("invoice.xml", "<HDon><SHDon>%s</SHDon></HDon>" % q["shdon"])
                z.writestr("details.js", "x")
            data = buf.getvalue()
            self.send_response(200)
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            return self.wfile.write(data)
        self._json(404, {"message": "not found"})


class Tests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.srv = ThreadingHTTPServer(("127.0.0.1", 0), FakePortal)
        threading.Thread(target=cls.srv.serve_forever, daemon=True).start()
        cls.base = "http://127.0.0.1:%d" % cls.srv.server_port

    @classmethod
    def tearDownClass(cls):
        cls.srv.shutdown()

    def setUp(self):
        self.tmp = tempfile.mkdtemp()
        FakePortal.calls = []

    def tearDown(self):
        shutil.rmtree(self.tmp)

    def client(self):
        return t.HoaDonClient(self.base, delay=0)

    def test_month_ranges(self):
        r = t.month_ranges(date(2026, 1, 15), date(2026, 3, 10))
        self.assertEqual(r, [(date(2026, 1, 15), date(2026, 1, 31)), (date(2026, 2, 1), date(2026, 2, 28)),
                             (date(2026, 3, 1), date(2026, 3, 10))])
        self.assertEqual(t.parse_date("05/09/2026"), date(2026, 9, 5))
        self.assertEqual(t.parse_date("2026-09-05"), date(2026, 9, 5))

    def test_login_errors(self):
        c = self.client()
        cap = c.get_captcha()
        with self.assertRaisesRegex(t.PortalError, "captcha"):
            c.login("0309999999", "pw", "zzzz", cap["key"])
        c.login("0309999999", "pw", "abcd", cap["key"])
        self.assertEqual(c.token, "TOKEN123")

    def test_full_job(self):
        c = self.client()
        c.login("0309999999", "pw", "abcd", "K1")
        job = t.Job()
        job.running = True
        t.run_job(job, c, ["purchase", "sold"], date(2026, 9, 1), date(2026, 10, 31), True, True, self.tmp)
        snap = job.snapshot()
        self.assertIsNone(snap["error"], snap["log"])
        self.assertEqual(snap["count"], 61)
        self.assertEqual(snap["done"], 61)
        self.assertAlmostEqual(snap["sum_tt"], 1100000 * (sum(range(1, 61)) + 99))
        # Phân trang dùng state
        self.assertTrue(any(q.get("state") == "S2" for _, q in FakePortal.calls))
        folder = os.path.join(self.tmp, "0309999999", "mua-vao_20260901_20261031")
        xmls = os.listdir(os.path.join(folder, "xml"))
        self.assertEqual(len(xmls), 61)
        self.assertTrue(all(x.endswith(".xml") for x in xmls))
        csv_path = os.path.join(folder, "bang-ke-mua-vao_20260901_20261031.csv")
        with open(csv_path, encoding="utf-8-sig") as f:
            self.assertEqual(len(f.read().strip().splitlines()), 62)

        # Chạy lại: XML đã có thì không tải lại
        n_before = sum(1 for p, _ in FakePortal.calls if p.endswith("export-xml"))
        job2 = t.Job()
        t.run_job(job2, c, ["purchase"], date(2026, 9, 1), date(2026, 10, 31), True, True, self.tmp)
        self.assertEqual(sum(1 for p, _ in FakePortal.calls if p.endswith("export-xml")), n_before)

    def test_expired_token(self):
        c = self.client()
        c.token = "bad"
        with self.assertRaisesRegex(t.PortalError, "hết hạn"):
            c.list_invoices("sold", date(2026, 9, 1), date(2026, 9, 30))

    def test_local_ui_guard(self):
        app = t.App(self.client(), self.tmp)
        srv = ThreadingHTTPServer(("127.0.0.1", 0), t.make_handler(app, 0))
        port = srv.server_port
        srv.RequestHandlerClass = t.make_handler(app, port)
        threading.Thread(target=srv.serve_forever, daemon=True).start()
        try:
            base = "http://127.0.0.1:%d" % port
            page = urllib.request.urlopen(base + "/").read().decode()
            self.assertIn(app.key, page)
            with self.assertRaises(urllib.error.HTTPError) as cm:
                urllib.request.urlopen(base + "/api/session")
            self.assertEqual(cm.exception.code, 403)
            req = urllib.request.Request(base + "/api/captcha", headers={"X-App-Key": app.key})
            self.assertEqual(json.loads(urllib.request.urlopen(req).read())["key"], "K1")
        finally:
            srv.shutdown()


if __name__ == "__main__":
    unittest.main()
