"""Kiểm thử với cổng giả lập: python -m unittest test_taihoadon.py"""

import io
import json
import os
import random
import re
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
    "A": "M0 10 Q2 5 5 0 Q7 5 10 10 Z M3 7 Q5 6 7 7 Z",
    "B": "M0 0 Q0 6 0 12 Q8 12 8 6 Q8 0 0 0 Z",
    "C": "M9 1 Q0 -1 0 6 Q0 13 9 11 Q8 11 9 1 Z",
    "D": "M0 0 Q0 6 0 12 Q11 12 11 6 Q11 0 0 0 Z",
    "7": "M0 0 Q4 0 9 0 Q6 6 3 12 Z",
}
ACCOUNTS = {"0309999999": "pw1", "0101234567": "pw2"}
STATE = {"tthai": 1, "k_invoice": False, "timeout_months": False}


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
        parts.append('<path fill="none" stroke="#%06x" d="%s"/>' % (random.randint(0, 0x777777),
                                                      shift(GLYPHS[ch], x + random.random() * 4, 15 + random.random() * 15)))
        x += 30
    parts.append("</svg>")
    return "".join(parts)


def make_invoice(i, day, buyer="0309999999"):
    return {"nbmst": "0101234567", "nbten": "Cong ty Ban %d" % i, "nmmst": buyer, "nmten": "Cong ty Mua",
            "khmshdon": 1, "khhdon": "C26TAA", "shdon": i, "tdlap": "%sT08:00:00.000+0000" % day,
            "tgtcthue": 1000000 * i, "tgtthue": 100000 * i, "tgtttbso": 1100000 * i,
            "tthai": STATE["tthai"] if i == 1 else 1, "ttxly": 5, "dvtte": "VND", "mhdon": "M%d" % i}


XML = ('<?xml version="1.0" encoding="UTF-8"?><HDon><DLHDon><NDHDon>'
       '<NBan><Ten>Cong ty Ban</Ten><MST>0101234567</MST><DChi>So 1 Le Loi</DChi></NBan>'
       '<NMua><Ten>Cong ty Mua</Ten><DChi>So 2 Hai Ba Trung</DChi></NMua><DSHHDVu>'
       '<HHDVu><TChat>1</TChat><STT>1</STT><THHDVu>Giay A4</THHDVu><DVTinh>Ram</DVTinh><SLuong>2</SLuong>'
       '<DGia>50000</DGia><ThTien>100000</ThTien><TSuat>10%%</TSuat></HHDVu>'
       '<HHDVu><TChat>1</TChat><STT>2</STT><THHDVu>But bi</THHDVu><DVTinh>Cay</DVTinh><SLuong>10</SLuong>'
       '<DGia>5000</DGia><ThTien>50000</ThTien><TSuat>8%%</TSuat></HHDVu>'
       '<HHDVu><TChat>4</TChat><STT>3</STT><THHDVu>Ghi chu giao hang</THHDVu></HHDVu>'
       '</DSHHDVu><TToan><THTTLTSuat><LTSuat><TSuat>10%%</TSuat><ThTien>100000</ThTien><TThue>10000</TThue></LTSuat>'
       '<LTSuat><TSuat>8%%</TSuat><ThTien>50000</ThTien><TThue>4000</TThue></LTSuat></THTTLTSuat></TToan>'
       '</NDHDon><TTChung><THDon>HÓA ĐƠN GIÁ TRỊ GIA TĂNG</THDon><HTTToan>TM/CK</HTTToan><MSTTCGP>0101243150</MSTTCGP><TTKhac><TTin><TTruong>TransactionID</TTruong><KDLieu>string</KDLieu><DLieu>MISA%sABC</DLieu></TTin></TTKhac></TTChung></DLHDon><SHDon>%s</SHDon></HDon>')


class FakePortal(BaseHTTPRequestHandler):
    calls = []
    captchas = {}
    logins = []
    headers = []

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
        FakePortal.headers.append(("POST", self.path, dict(self.headers)))
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
        FakePortal.headers.append(("GET", u.path, dict(self.headers)))
        if u.path == "/":
            body = b"<html>portal</html>"
            self.send_response(200)
            self.send_header("Set-Cookie", "TS01=abc; Path=/")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            return self.wfile.write(body)
        if u.path.startswith("/category/public/dsdkts/"):
            mst = u.path.split("/")[4]
            if mst == "0309999999":
                return self._json(200, {"mst": mst, "tennnt": "CÔNG TY TNHH MUA", "tthai": "00", "tencqt": "Thuế TP HCM",
                                        "dctsdchi": "So 2 Hai Ba Trung", "dctsxaten": "Phường Bến Nghé", "dctstinhten": "TP HCM"})
            return self._json(400, {"message": "Không tìm thấy thông tin MST"})
        if u.path == "/captcha":
            text = "".join(random.sample(list(GLYPHS), len(GLYPHS)))
            key = "K%d" % len(FakePortal.calls)
            FakePortal.captchas[key] = text
            return self._json(200, {"key": key, "content": make_captcha(text)})
        auth = self.headers.get("Authorization", "")
        if not auth.startswith("Bearer TOKEN-"):
            return self._json(401, {"message": "unauthorized"})
        mst = auth[len("Bearer TOKEN-"):]
        if u.path.endswith(("/invoices/purchase", "/invoices/sold")) and "," in q.get("sort", ""):
            return self._json(400, {"message": "Không hỗ trợ sắp xếp theo nhiều trường"})
        if STATE["timeout_months"] and u.path.endswith("/invoices/purchase"):
            a, b = re.findall(r"(\d\d)/(\d\d)/(\d{4})", q["search"])[:2]
            if (date(int(b[2]), int(b[1]), int(b[0])) - date(int(a[2]), int(a[1]), int(a[0]))).days > 7:
                return self._json(500, {"message": "Row retrieval response timeout of 10000, missing responses from nodes"})
        if u.path == "/query/invoices/purchase":
            if "ttxly==5" not in q["search"]:
                return self._json(200, {"datas": [], "total": 0})
            if "01/09/2026" in q["search"]:
                all_ = [make_invoice(i, "2026-09-%02d" % (i % 28 + 1), mst) for i in range(1, 61)]
                page = all_[50:] if q.get("state") == "S2" else all_[:50]
                return self._json(200, {"datas": page, "total": 60, "state": None if q.get("state") else "S2"})
            datas = [make_invoice(99, "2026-10-02", mst)]
            if STATE["k_invoice"]:  # HĐ không mã: cổng không có XML gốc
                k = make_invoice(100, "2026-10-01", mst)
                k.update(khhdon="K26THA", tdlap="2026-10-01T17:00:00Z", ttxly=6)
                datas.append(k)
            return self._json(200, {"datas": datas, "total": len(datas)})
        if u.path in ("/query/invoices/sold", "/sco-query/invoices/purchase", "/sco-query/invoices/sold"):
            return self._json(200, {"datas": [], "total": 0})
        if u.path == "/query/invoices/export-xml" and q.get("khhdon", "").startswith("K"):
            return self._json(500, {"message": "Không tồn tại hồ sơ gốc của hóa đơn."})
        if u.path == "/query/invoices/export-xml":
            buf = io.BytesIO()
            with zipfile.ZipFile(buf, "w") as z:
                z.writestr("invoice.xml", XML % (q["shdon"], q["shdon"]))
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
                        self.job.answer(text.lower())  # người dùng gõ chữ thường vẫn được
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
        FakePortal.calls, FakePortal.captchas, FakePortal.logins, FakePortal.headers = [], {}, [], []
        STATE["tthai"] = 1
        STATE["k_invoice"] = False
        STATE["timeout_months"] = False
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
        svg = make_captcha("ABCD7")
        self.assertIsNone(solver.solve(svg))
        self.assertTrue(solver.learn(svg, "ABCD7"))
        for _ in range(20):
            text = "".join(random.choice(list(GLYPHS)) for _ in range(6))
            self.assertEqual(solver.solve(make_captcha(text)), text)
        # Đọc lại từ file
        self.assertEqual(t.CaptchaSolver(os.path.join(self.tmp, "c.json")).solve(make_captcha("7DCBA")), "7DCBA")

    def test_real_captcha_builtin_table(self):
        """Captcha thật của cổng: giải ngay bằng bảng chữ ký có sẵn, không cần học."""
        solver = t.CaptchaSolver(os.path.join(self.tmp, "c.json"))
        folder = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_data")
        for name in sorted(os.listdir(folder)):
            if name.startswith("captcha_"):
                svg = open(os.path.join(folder, name), encoding="utf-8").read()
                self.assertEqual(solver.solve(svg), name[8:-4])

    def test_mst_lookup(self):
        c = t.HoaDonClient(self.base, delay=0)
        d = c.lookup_company("0309999999")
        self.assertEqual((d["ten"], d["tthai_text"]), ("CÔNG TY TNHH MUA", "Đang hoạt động"))
        self.assertIn("Phường Bến Nghé", d["dia_chi"])
        with self.assertRaisesRegex(t.PortalError, "Không tìm thấy"):
            c.lookup_company("0101234567")
        with self.assertRaises(ValueError):
            c.lookup_company("123")
        # Nhập danh sách bỏ trống tên → tự lấy tên
        added, errors = self.app.store.import_text("0309999999\t\tpw1\n")
        self.assertEqual(self.app.store.get("0309999999")["ten"], "")

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
        xlsx = os.path.join(folder, "MUA_VAO_0309999999_20260901_20261031.xlsx")
        wb = load_workbook(xlsx)
        self.assertEqual(wb.sheetnames, ["HoaDon_TongQuat", "Smart_KTSC_OK", "BangKe_MuaVao", "BangKe_HoanThue_OK"])
        tq = wb["HoaDon_TongQuat"]
        self.assertEqual(tq["A1"].value, "HÓA ĐƠN MUA VÀO")
        self.assertEqual(tq["A3"].value, "Tên DN: A")
        self.assertEqual((tq["A5"].value, tq["D5"].value, tq["I5"].value, tq["L5"].value, tq["M5"].value),
                         ("V", "So 1 Le Loi", "TM/CK", "HĐ Mới", "Đã cấp MST"))
        self.assertEqual(tq["A66"].value, "Total")             # 61 HĐ ở dòng 5..65
        self.assertEqual(tq["R66"].value, "=SUBTOTAL(109,R5:R65)")
        sm = wb["Smart_KTSC_OK"]
        self.assertEqual(sm.max_row, 1 + 61 * 2)                # bỏ dòng ghi chú (TChat 4)
        row = {h.value: c.value for h, c in zip(sm[1], sm[2])}
        self.assertEqual((row["MATHANG"], row["DONVI"], row["LUONG"], row["TTVND"], row["TS_GTGT"], row["THUEVND"],
                          row["TKTHUE"], row["MADTPNCO"]), ("Giay A4", "Ram", 2, 100000, "10", 10000, "1331", "0101234567"))
        bk = wb["BangKe_MuaVao"]
        self.assertEqual(bk["B7"].value, "Kỳ tính thuế: Từ ngày 01/09/2026 đến ngày 31/10/2026")
        self.assertEqual([bk.cell(18, c).value for c in (8, 9, 10, 11, 12)],
                         ["0101234567", "Giay A4", 100000, "10", 10000])   # gom theo HĐ + thuế suất
        self.assertEqual(bk.cell(19, 11).value, "8")
        self.assertEqual(bk["B140"].value, "Tổng")              # 122 dòng: 18..139
        self.assertEqual(bk["L140"].value, "=SUM(L18:L139)")
        ht = wb["BangKe_HoanThue_OK"]
        self.assertEqual([ht.cell(17, c).value for c in (9, 10, 11, 12, 13)], ["Giay A4", "Ram", 2, 50000, 100000])
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
        wb = load_workbook(xlsx)
        self.assertEqual(wb["Doi_TrangThai"].cell(2, 7).value, "Đã bị huỷ")
        self.assertEqual(wb["Smart_KTSC_CAN_XEM_XET"]["A2"].value, "HĐ Đã bị hủy")  # HĐ huỷ → cần xem xét
        self.assertEqual(wb["BangKe_MuaVao"]["B138"].value, "Tổng")                # chỉ còn 60 HĐ × 2

    def test_request_profiles(self):
        """Đăng nhập: trang chủ → captcha → authenticate, chung cookie, header tối giản.
        Tra cứu/XML: header trang tra cứu có Action, không cookie."""
        self.app.store.import_text("0309999999\tA\tpw1\n")
        self.run_job(["0309999999"], kinds=("purchase",), start=date(2026, 10, 1), end=date(2026, 10, 31))
        h = FakePortal.headers
        self.assertEqual([x[1] for x in h[:3]], ["/", "/captcha", "/security-taxpayer/authenticate"])
        for _, _, hd in h[1:3]:
            self.assertIn("TS01=abc", hd.get("Cookie", ""))
            self.assertNotIn("Origin", hd)
            self.assertNotIn("Referer", hd)
            self.assertTrue(hd.get("Request-Id") or hd.get("request-id"))
        query = next(hd for m, p, hd in h if p == "/query/invoices/purchase")
        self.assertEqual(urllib.parse.unquote(query["Action"]), "Tìm kiếm")
        self.assertEqual(query["End-Point"], "/tra-cuu/tra-cuu-hoa-don")
        self.assertNotIn("Cookie", query)
        xml = next(hd for m, p, hd in h if p == "/query/invoices/export-xml")
        self.assertEqual(urllib.parse.unquote(xml["Action"]), "Xuất xml (hóa đơn mua vào)")

    def test_invoice_store(self):
        self.app.store.import_text("0309999999\tA\tpw1\n")
        self.run_job(["0309999999"], kinds=("purchase",))
        f = {"kind": "purchase", "from": "2026-09-01", "to": "2026-09-30"}
        rows = t.query_invoices(self.tmp, "0309999999", f)
        self.assertEqual(len(rows), 60)
        r = rows[0]
        self.assertEqual((r["mst"], r["tthai"], r["kq"], r["duyet"], r["mat_hang"]),
                         ("0101234567", "HĐ Mới", "Đã cấp MST", "Chờ duyệt", "Giay A4; But bi"))
        self.assertTrue(r["xml"].endswith(".xml"))
        self.assertEqual(len(t.query_invoices(self.tmp, "0309999999", dict(f, shdon="5"))), 1)
        self.assertEqual(len(t.query_invoices(self.tmp, "0309999999", dict(f, file="no_xml"))), 0)
        # Ghi chú / duyệt / HĐ dịch vụ được giữ lại sau khi đồng bộ lại
        t.update_invoice(self.tmp, "0309999999", "purchase", r["key"], note="da doi chieu", duyet="Đã duyệt", dv=True)
        self.run_job(["0309999999"], kinds=("purchase",))
        dv = t.query_invoices(self.tmp, "0309999999", dict(f, kind="purchase_dv"))
        self.assertEqual([(x["key"], x["note"], x["duyet"]) for x in dv], [(r["key"], "da doi chieu", "Đã duyệt")])
        self.assertEqual(len(t.query_invoices(self.tmp, "0309999999", dict(f, q="doi chieu"))), 1)
        # Kết xuất theo bộ lọc
        x = t.export_invoices(self.tmp, "0309999999", "A", dict(f, kind="purchase_dv"), "xlsx")
        from openpyxl import load_workbook
        tq = load_workbook(x)["HoaDon_TongQuat"]
        self.assertEqual((tq["S5"].value, tq["T5"].value, tq["A6"].value), ("Đã duyệt", "da doi chieu", "Total"))
        z = t.export_invoices(self.tmp, "0309999999", "A", f, "xml")
        self.assertEqual(len(zipfile.ZipFile(z).namelist()), 60)
        with self.assertRaises(ValueError):
            t.export_invoices(self.tmp, "0309999999", "A", f, "pdf")

    def test_lookup_pdf_view(self):
        self.app.store.import_text("0309999999\tA\tpw1\n")
        self.run_job(["0309999999"], kinds=("purchase",), start=date(2026, 10, 1), end=date(2026, 10, 31))
        f = {"kind": "purchase", "from": "2026-10-01", "to": "2026-10-31"}
        r = t.query_invoices(self.tmp, "0309999999", f)[0]
        self.assertEqual(r["tra_cuu"], {"ncc": "MISA meInvoice", "url": "https://www.meinvoice.vn/tra-cuu/",
                                        "field": "TransactionID", "code": "MISA99ABC", "ncc_mst": "0101243150"})
        # Viettel: "Mã số bí mật"
        self.assertEqual(t.lookup_info({"msttcgp": "0100109106", "ttkhac": {"Mã số bí mật": "X1Y2"}})["code"], "X1Y2")
        # Gắn PDF gốc
        with self.assertRaises(ValueError):
            t.attach_pdf(self.tmp, "0309999999", "purchase", r["key"], b"not a pdf")
        t.attach_pdf(self.tmp, "0309999999", "purchase", r["key"], b"%PDF-1.4 test")
        self.assertEqual(len(t.query_invoices(self.tmp, "0309999999", dict(f, file="pdf"))), 1)
        z = t.export_invoices(self.tmp, "0309999999", "A", f, "pdf")
        self.assertEqual(len(zipfile.ZipFile(z).namelist()), 1)
        # Bản in dựng từ XML
        page = t.render_invoice_html(self.tmp, "0309999999", "purchase", r["key"])
        for txt in ("HÓA ĐƠN GIÁ TRỊ GIA TĂNG", "So 1 Le Loi", "Giay A4", "But bi", "MISA99ABC"):
            self.assertIn(txt, page)
        self.assertIn("Ghi chu giao hang", page)  # dòng ghi chú hiển thị như hoá đơn gốc

    def test_session_reuse_and_progress(self):
        """Đồng bộ lần 2 dùng lại phiên đăng nhập: không hỏi captcha, không gọi authenticate."""
        self.app.store.import_text("0309999999\tA\tpw1\n")
        clients = {}

        def run():
            job = t.Job()
            job.running = True
            helper = AutoAnswer(job)
            helper.start()
            t.run_batch(job, self.app.store, self.app.solver, ["0309999999"], ["purchase"], date(2026, 10, 1),
                        date(2026, 10, 31), True, True, self.tmp, False, self.app.client_factory, clients)
            return job, helper
        job1, h1 = run()
        snap = job1.snapshot()
        self.assertEqual((snap["phase"], snap["found"], len(snap["rows"])), ("đồng bộ xong", {"purchase": 1}, 1))
        self.assertEqual(snap["rows"][0]["dongbo"], "Mới")
        self.assertTrue(snap["auth"].startswith("thành công"))
        logins = len(FakePortal.logins)
        job2, h2 = run()
        self.assertEqual((h2.asked, len(FakePortal.logins)), (0, logins))
        self.assertEqual(job2.snapshot()["rows"][0]["dongbo"], "Đã có")
        self.assertIn("dùng lại phiên", job2.snapshot()["auth"])
        # Token hết hạn → đăng nhập lại
        clients["0309999999"].login_at = 0
        run()
        self.assertEqual(len(FakePortal.logins), logins + 1)

    def test_no_original_xml_and_vn_date(self):
        self.assertEqual(t.invoice_date({"tdlap": "2026-10-01T17:00:00Z"}), date(2026, 10, 2))
        self.assertEqual(t.invoice_date({"tdlap": "2026-10-01T16:59:00Z"}), date(2026, 10, 1))
        self.assertEqual(t.invoice_date({"tdlap": "2026-10-01"}), date(2026, 10, 1))
        STATE["k_invoice"] = True
        self.app.store.import_text("0309999999\tA\tpw1\n")
        start = time.time()
        job, _ = self.run_job(["0309999999"], kinds=("purchase",), start=date(2026, 10, 1), end=date(2026, 10, 31))
        self.assertLess(time.time() - start, 5, "không được thử lại khi cổng báo không có XML gốc")
        rows = {r["so"]: r for r in job.snapshot()["rows"]}
        self.assertEqual(rows["100"]["ketqua"], "OK (HĐ không có XML gốc)")
        self.assertEqual(rows["100"]["ngay"], "02/10/2026")
        self.assertEqual(rows["99"]["ketqua"], "OK")
        xml_calls = lambda: sum(1 for p, q in FakePortal.calls if p.endswith("export-xml") and q["khhdon"].startswith("K"))
        self.assertEqual(xml_calls(), 1)
        self.run_job(["0309999999"], kinds=("purchase",), start=date(2026, 10, 1), end=date(2026, 10, 31))
        self.assertEqual(xml_calls(), 1, "lần sau không hỏi lại XML của HĐ không mã")

    def test_portal_timeout_splits_range(self):
        """Cổng timeout khi tra cả tháng → tự chia theo tuần, vẫn lấy đủ hoá đơn."""
        STATE["timeout_months"] = True
        c = t.HoaDonClient(self.base, delay=0)
        c.token = "TOKEN-0309999999"
        import unittest.mock as m
        with m.patch("time.sleep"):
            invs = c.list_invoices("purchase", date(2026, 10, 1), date(2026, 10, 31), include_mtt=False)
        self.assertEqual([i["shdon"] for i in invs], [99])

    def test_tools_xml_view(self):
        page = t.render_xml_invoice((XML % (5, 5)).encode("utf-8"))
        for txt in ("HÓA ĐƠN GIÁ TRỊ GIA TĂNG", "Cong ty Ban", "So 1 Le Loi", "Cong ty Mua", "Giay A4", "But bi",
                    "100,000", "TM/CK", "MISA meInvoice", "MISA5ABC", "Không có chữ ký số"):
            self.assertIn(txt, page)
        with self.assertRaises(ValueError):
            t.render_xml_invoice(b"khong phai xml")
        with self.assertRaises(ValueError):
            t.render_xml_invoice(b"<a><b/></a>")

    def test_tools_pdf(self):
        import pypdf

        def pdf(n):
            w = pypdf.PdfWriter()
            for _ in range(n):
                w.add_blank_page(width=200, height=200)
            b = io.BytesIO()
            w.write(b)
            return b.getvalue()
        merged = t.merge_pdfs([("a.pdf", pdf(2)), ("b.pdf", pdf(3))])
        self.assertEqual(len(pypdf.PdfReader(io.BytesIO(merged)).pages), 5)
        with self.assertRaises(ValueError):
            t.merge_pdfs([("a.pdf", pdf(1))])
        with self.assertRaisesRegex(ValueError, "b.pdf"):
            t.merge_pdfs([("a.pdf", pdf(1)), ("b.pdf", b"hong")])
        self.assertEqual(t.parse_page_ranges("1-3, 5", 6), [[1, 2, 3], [5]])
        self.assertEqual(t.parse_page_ranges("", 2), [[1], [2]])
        for bad in ("0-2", "4-9", "a"):
            with self.assertRaises(ValueError):
                t.parse_page_ranges(bad, 5)
        z = zipfile.ZipFile(io.BytesIO(t.split_pdf("hoa don.pdf", merged, "1-2, 5")))
        self.assertEqual(sorted(z.namelist()), ["hoa don_trang_1-2.pdf", "hoa don_trang_5.pdf"])
        self.assertEqual(len(pypdf.PdfReader(io.BytesIO(z.read("hoa don_trang_1-2.pdf"))).pages), 2)

    def test_misa_original_pdf(self):
        import unittest.mock as m
        calls = []

        def fake_get(url, timeout=30):
            calls.append(url)
            if url.endswith("GetRequestTimeEnCode"):
                return b'"AB12CD34"'
            if "ext=AB12CD34" in url and "code=MISA99ABC" in url:
                return b"%PDF-1.4 misa goc"
            return b"<html>not found</html>"
        self.app.store.import_text("0309999999\tA\tpw1\n")
        with m.patch.object(t, "_http_get", fake_get), m.patch("time.sleep"):
            job = t.Job()
            job.running = True
            job.want_pdf = True
            helper = AutoAnswer(job)
            helper.start()
            t.run_batch(job, self.app.store, self.app.solver, ["0309999999"], ["purchase"], date(2026, 10, 1),
                        date(2026, 10, 31), True, True, self.tmp, False, self.app.client_factory)
        self.assertEqual(job.snapshot()["rows"][0]["ketqua"], "OK + PDF gốc")
        r = t.query_invoices(self.tmp, "0309999999", {"kind": "purchase", "file": "pdf"})
        self.assertEqual(len(r), 1)
        with open(os.path.join(self.tmp, "0309999999", r[0]["pdf"]), "rb") as fh:
            self.assertEqual(fh.read(), b"%PDF-1.4 misa goc")
        self.assertTrue(calls[1].startswith("https://download.meinvoice.vn/downloadhandler.ashx?type=pdf&code=MISA99ABC"))
        # Không phải PDF → báo lỗi rõ ràng
        with m.patch.object(t, "_http_get", lambda u, timeout=30: b'"ZZZZZZZZ"' if "Time" in u else b"<html/>"):
            with self.assertRaisesRegex(t.PortalError, "MISA không trả file PDF"):
                t.misa_pdf("XYZ")
        self.assertFalse(t.can_fetch_pdf({"ncc": "Viettel S-Invoice", "ncc_mst": "0100109106", "code": "A"})[0])

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
            d = call("/api/company/import", {"text": "0309999999\t\tpw1\n0101234567\t\tpw2"})
            self.assertEqual(self.app.store.get("0309999999")["ten"], "CÔNG TY TNHH MUA")   # tự lấy tên theo MST
            self.assertEqual(len(d["errors"]), 1)                                          # MST không tra được
            self.assertEqual(call("/api/mst-lookup", {"mst": "0309999999"})["ten"], "CÔNG TY TNHH MUA")
            rows = call("/api/tool/mst", {"text": "0309999999\n0101234567"})["rows"]
            self.assertEqual([(r["ten"], bool(r["loi"])) for r in rows], [("CÔNG TY TNHH MUA", False), ("", True)])
            import base64
            v = call("/api/tool/xml-view", {"data": base64.b64encode((XML % (7, 7)).encode()).decode()})
            self.assertIn("Giay A4", v["html"])
            call("/api/company/save", {"mst": "0309999999", "ten": "A", "password": "pw1", "vao": True, "ra": False})
            call("/api/company/save", {"mst": "0309999999", "ghichu": "goi lai"})
            st = call("/api/state")
            self.assertEqual(st["companies"][0]["ghichu"], "goi lai")
            self.assertTrue(st["companies"][0]["co_mk"])
            self.assertFalse(st["companies"][0]["ra"])
            self.assertNotIn("pw1", json.dumps(st))
            # Mở file: cần khoá phiên, không cho đi ra ngoài thư mục DN
            d = os.path.join(self.tmp, "0309999999")
            os.makedirs(d, exist_ok=True)
            open(os.path.join(d, "a.xml"), "w").write("<x/>")
            q = lambda p, k=self.app.key: base + "/file?" + urllib.parse.urlencode({"k": k, "mst": "0309999999", "p": p})
            self.assertEqual(urllib.request.urlopen(q("a.xml")).read(), b"<x/>")
            for bad in (q("a.xml", "sai"), q("../_cau-hinh/doanh-nghiep.json")):
                with self.assertRaises(urllib.error.HTTPError):
                    urllib.request.urlopen(bad)
        finally:
            srv.shutdown()
            srv.server_close()


if __name__ == "__main__":
    unittest.main()
