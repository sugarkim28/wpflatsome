"""Kiểm thử với cổng giả lập: python -m unittest test_taihoadon.py"""

import io
import json
import os
import random
import shutil
import tempfile
import threading
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from datetime import date
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

import taihoadon as t

# Hình ký tự giả (toạ độ tuyệt đối từ gốc 0,0); captcha dịch chuyển từng ký tự tới vị trí ngẫu nhiên.
GLYPHS = {
    "a": "M0 10 L5 0 L10 10 Z",
    "b": "M0 0 L0 12 Q8 12 8 6 Q8 0 0 0 Z",
    "c": "M9 1 Q0 -1 0 6 Q0 13 9 11",
    "D": "M0 0 L0 12 Q11 12 11 6 Q11 0 0 0 Z",
    "7": "M0 0 L9 0 L3 12",
}
ACCOUNTS = {"0309999999": "pw1", "0101234567": "pw2"}
STATE = {"tthai": 1}


def shift(d, dx, dy):
    out, i = [], 0
    for tok in d.replace("M", " M ").replace("L", " L ").replace("Q", " Q ").replace("Z", " Z ").split():
        if tok.isalpha():
            out.append(tok)
            continue
        out.append(str(round(float(tok) + (dx if i % 2 == 0 else dy), 2)))
        i += 1
    return " ".join(out)


def make_captcha(text):
    parts = ['<svg xmlns="http://www.w3.org/2000/svg" width="200" height="50" viewBox="0,0,200,50">',
             '<path d="M10 40 C60 5 120 45 190 10" stroke="#888" fill="none"/>']
    x = 12
    for ch in text:
        parts.append('<path fill="#%06x" d="%s"/>' % (random.randint(0, 0x777777),
                                                      shift(GLYPHS[ch], x + random.random() * 4, 15 + random.random() * 15)))
        x += 30
    parts.append("</svg>")
    return "".join(parts)


def make_invoice(i, day, buyer="0309999999"):
    return {"nbmst": "0101234567", "nbten": "Cong ty Ban %d" % i, "nmmst": buyer, "nmten": "Cong ty Mua",
            "khmshdon": 1, "khhdon": "C26TAA", "shdon": i, "tdlap": "%sT08:00:00.000+0000" % day,
            "tgtcthue": 1000000 * i, "tgtthue": 100000 * i, "tgtttbso": 1100000 * i,
            "tthai": STATE["tthai"] if i == 1 else 1, "ttxly": 5, "dvtte": "VND", "mhdon": "M%d" % i}


XML = ('<?xml version="1.0" encoding="UTF-8"?><HDon><DLHDon><NDHDon><DSHHDVu>'
       '<HHDVu><TChat>1</TChat><STT>1</STT><THHDVu>Giay A4</THHDVu><DVTinh>Ram</DVTinh><SLuong>2</SLuong>'
       '<DGia>50000</DGia><ThTien>100000</ThTien><TSuat>10%%</TSuat></HHDVu>'
       '<HHDVu><TChat>1</TChat><STT>2</STT><THHDVu>But bi</THHDVu><DVTinh>Cay</DVTinh><SLuong>10</SLuong>'
       '<DGia>5000</DGia><ThTien>50000</ThTien><TSuat>10%%</TSuat></HHDVu>'
       '</DSHHDVu></NDHDon></DLHDon><SHDon>%s</SHDon></HDon>')


class FakePortal(BaseHTTPRequestHandler):
    calls = []
    captchas = {}
    logins = []

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
        FakePortal.logins.append(body["username"])
        if FakePortal.captchas.pop(body["ckey"], None) != body["cvalue"]:
            return self._json(400, {"message": "Mã captcha không đúng"})
        if ACCOUNTS.get(body["username"]) != body["password"]:
            return self._json(400, {"message": "Tên đăng nhập hoặc mật khẩu không đúng"})
        self._json(200, {"token": "TOKEN-" + body["username"]})

    def do_GET(self):
        u = urllib.parse.urlparse(self.path)
        q = dict(urllib.parse.parse_qsl(u.query))
        FakePortal.calls.append((u.path, q))
        if u.path == "/captcha":
            text = "".join(random.sample(list(GLYPHS), len(GLYPHS)))
            key = "K%d" % len(FakePortal.calls)
            FakePortal.captchas[key] = text
            return self._json(200, {"key": key, "content": make_captcha(text)})
        auth = self.headers.get("Authorization", "")
        if not auth.startswith("Bearer TOKEN-"):
            return self._json(401, {"message": "unauthorized"})
        mst = auth[len("Bearer TOKEN-"):]
        if u.path == "/query/invoices/purchase":
            if "ttxly==5" not in q["search"]:
                return self._json(200, {"datas": [], "total": 0})
            if "01/09/2026" in q["search"]:
                all_ = [make_invoice(i, "2026-09-%02d" % (i % 28 + 1), mst) for i in range(1, 61)]
                page = all_[50:] if q.get("state") == "S2" else all_[:50]
                return self._json(200, {"datas": page, "total": 60, "state": None if q.get("state") else "S2"})
            return self._json(200, {"datas": [make_invoice(99, "2026-10-02", mst)], "total": 1})
        if u.path in ("/query/invoices/sold", "/sco-query/invoices/purchase", "/sco-query/invoices/sold"):
            return self._json(200, {"datas": [], "total": 0})
        if u.path == "/query/invoices/export-xml":
            buf = io.BytesIO()
            with zipfile.ZipFile(buf, "w") as z:
                z.writestr("invoice.xml", XML % q["shdon"])
                z.writestr("details.js", "x")
            data = buf.getvalue()
            self.send_response(200)
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            return self.wfile.write(data)
        self._json(404, {"message": "not found"})


class AutoAnswer(threading.Thread):
    """Đóng vai người dùng: thấy captcha thì gõ đáp án đúng."""

    def __init__(self, job):
        super().__init__(daemon=True)
        self.job = job
        self.asked = 0

    def run(self):
        while self.job.running:
            need = self.job.snapshot()["need_captcha"]
            if need:
                for text in list(FakePortal.captchas.values()):
                    if make_captcha_text_matches(need["svg"], text):
                        self.asked += 1
                        self.job.answer(text)
                        break
            time.sleep(0.02)


def make_captcha_text_matches(svg, text):
    glyphs = t.captcha_glyphs(svg)
    if len(glyphs) != len(text):
        return False
    ref = {ch: t.captcha_glyphs(make_captcha(ch))[0][1] for ch in set(text)}
    return all(t._dist(g[1], ref[ch]) < 0.5 for g, ch in zip(glyphs, text))


class Tests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.srv = ThreadingHTTPServer(("127.0.0.1", 0), FakePortal)
        threading.Thread(target=cls.srv.serve_forever, daemon=True).start()
        cls.base = "http://127.0.0.1:%d" % cls.srv.server_port

    @classmethod
    def tearDownClass(cls):
        cls.srv.shutdown()
        cls.srv.server_close()

    def setUp(self):
        self.tmp = tempfile.mkdtemp()
        FakePortal.calls, FakePortal.captchas, FakePortal.logins = [], {}, []
        STATE["tthai"] = 1
        self.app = t.App(self.tmp, lambda: t.HoaDonClient(self.base, delay=0))

    def tearDown(self):
        shutil.rmtree(self.tmp)

    def run_job(self, msts, kinds=("purchase", "sold"), test=False, start=date(2026, 9, 1), end=date(2026, 10, 31)):
        job = t.Job()
        job.running = True
        helper = AutoAnswer(job)
        helper.start()
        t.run_batch(job, self.app.store, self.app.solver, msts, list(kinds), start, end, True, True,
                    self.tmp, test, self.app.client_factory)
        helper.join(1)
        return job, helper

    def test_month_ranges(self):
        r = t.month_ranges(date(2026, 1, 15), date(2026, 3, 10))
        self.assertEqual(r, [(date(2026, 1, 15), date(2026, 1, 31)), (date(2026, 2, 1), date(2026, 2, 28)),
                             (date(2026, 3, 1), date(2026, 3, 10))])
        self.assertEqual(t.parse_date("05/09/2026"), date(2026, 9, 5))
        self.assertEqual(t.parse_date("2026-09-05"), date(2026, 9, 5))

    def test_captcha_learning(self):
        solver = t.CaptchaSolver(os.path.join(self.tmp, "c.json"))
        svg = make_captcha("abcD7")
        self.assertIsNone(solver.solve(svg))
        self.assertTrue(solver.learn(svg, "abcD7"))
        for _ in range(20):
            text = "".join(random.choice(list(GLYPHS)) for _ in range(6))
            self.assertEqual(solver.solve(make_captcha(text)), text)
        # Đọc lại từ file
        self.assertEqual(t.CaptchaSolver(os.path.join(self.tmp, "c.json")).solve(make_captcha("7Dcba")), "7Dcba")

    def test_store_and_import(self):
        added, errors = self.app.store.import_text("0309999999\tCONG TY A\tpw1\n0101234567 | CONG TY B | pw2\nabc\tX\n")
        self.assertEqual(added, 2)
        self.assertEqual(len(errors), 1)
        c = self.app.store.get("0309999999")
        self.assertEqual(t.unprotect(c["pw"]), "pw1")
        self.assertNotIn("pw1", open(self.app.store.path, encoding="utf-8").read())
        self.assertTrue(all("pw" not in x for x in self.app.store.public()))

    def test_batch_sync(self):
        self.app.store.import_text("0309999999\tA\tpw1\n0101234567\tB\tSAI\n")
        job, helper = self.run_job(["0309999999", "0101234567"])
        log = "\n".join(job.log)
        a, b = self.app.store.get("0309999999"), self.app.store.get("0101234567")
        # DN A: đủ 61 HĐ mua vào, XML, chi tiết hàng hoá
        self.assertEqual(a["so_hd"]["purchase"], 61, log)
        self.assertEqual(a["lich_su"]["purchase"]["moi"], 61)
        self.assertEqual(a["loi"], "")
        folder = os.path.join(self.tmp, "0309999999", "mua-vao_20260901_20261031")
        self.assertEqual(len([f for f in os.listdir(os.path.join(folder, "xml")) if f.endswith(".xml")]), 61)
        from openpyxl import load_workbook
        wb = load_workbook(os.path.join(folder, "bang-ke-mua-vao_20260901_20261031.xlsx"))
        self.assertEqual(wb["Bang ke"].max_row, 63)          # tiêu đề + 61 + tổng
        self.assertEqual(wb["Chi tiet hang hoa"].max_row, 123)  # tiêu đề + 61×2 dòng hàng
        self.assertEqual(wb["Chi tiet hang hoa"].cell(2, 8).value, "Giay A4")
        # DN B: sai mật khẩu → ghi lỗi, chỉ thử đăng nhập đúng 1 lần, không làm hỏng DN khác
        self.assertIn("mật khẩu", b["loi"])
        self.assertEqual(FakePortal.logins.count("0101234567"), 1)
        # Chỉ DN đầu tiên phải gõ captcha; DN thứ hai đã tự giải
        self.assertEqual(helper.asked, 1)

        # Đồng bộ lại: HĐ số 1 bị huỷ → phát hiện đổi trạng thái, tải lại XML của HĐ đó thôi
        STATE["tthai"] = 6
        n_xml = sum(1 for p, _ in FakePortal.calls if p.endswith("export-xml"))
        job2, helper2 = self.run_job(["0309999999"], kinds=("purchase",))
        self.assertEqual(helper2.asked, 0, "lần 2 phải tự giải captcha")
        self.assertEqual(sum(1 for p, _ in FakePortal.calls if p.endswith("export-xml")), n_xml + 1)
        a = self.app.store.get("0309999999")
        self.assertEqual(a["lich_su"]["purchase"]["moi"], 0)
        self.assertEqual(job2.results[0]["doi"], 1)
        wb = load_workbook(os.path.join(folder, "bang-ke-mua-vao_20260901_20261031.xlsx"))
        self.assertEqual(wb["Doi trang thai"].cell(2, 7).value, "Đã bị huỷ")

    def test_expired_token(self):
        c = t.HoaDonClient(self.base, delay=0)
        c.token = "bad"
        with self.assertRaisesRegex(t.PortalError, "hết hạn"):
            c.list_invoices("sold", date(2026, 9, 1), date(2026, 9, 30))

    def test_local_ui(self):
        srv = ThreadingHTTPServer(("127.0.0.1", 0), t.make_handler(self.app, 0))
        port = srv.server_port
        srv.RequestHandlerClass = t.make_handler(self.app, port)
        threading.Thread(target=srv.serve_forever, daemon=True).start()
        base = "http://127.0.0.1:%d" % port

        def call(path, body=None, key=True):
            req = urllib.request.Request(base + path, data=json.dumps(body).encode() if body is not None else None,
                                         headers={"X-App-Key": self.app.key} if key else {})
            return json.loads(urllib.request.urlopen(req).read())
        try:
            self.assertIn(self.app.key, urllib.request.urlopen(base + "/").read().decode())
            with self.assertRaises(urllib.error.HTTPError) as cm:
                call("/api/state", key=False)
            self.assertEqual(cm.exception.code, 403)
            call("/api/company/save", {"mst": "0309999999", "ten": "A", "password": "pw1", "vao": True, "ra": False})
            call("/api/company/save", {"mst": "0309999999", "ghichu": "goi lai"})
            st = call("/api/state")
            self.assertEqual(st["companies"][0]["ghichu"], "goi lai")
            self.assertTrue(st["companies"][0]["co_mk"])
            self.assertFalse(st["companies"][0]["ra"])
            self.assertNotIn("pw1", json.dumps(st))
        finally:
            srv.shutdown()
            srv.server_close()


if __name__ == "__main__":
    unittest.main()
