#!/usr/bin/env python3
"""Phần mềm tải hoá đơn điện tử từ cổng hoadondientu.gdt.gov.vn – quản lý nhiều doanh nghiệp.

Chạy: python taihoadon.py  → trình duyệt mở http://127.0.0.1:8765
- Danh sách doanh nghiệp (MST, tên, mật khẩu cổng HĐĐT mã hoá trên máy, ghi chú, bật/tắt đồng bộ vào/ra, ẩn).
- Xử lý hàng loạt: đăng nhập từng DN, tải hoá đơn mua vào / bán ra / máy tính tiền theo khoảng ngày
  (tự chia theo tháng), XML gốc, bảng kê Excel + chi tiết hàng hoá, phát hiện HĐ đổi trạng thái (huỷ, thay thế...).
- Captcha tự học: vài lần đầu người dùng gõ, sau đó phần mềm tự giải.

Chỉ dùng thư viện chuẩn Python 3.8+. openpyxl là tuỳ chọn (pip install openpyxl) để xuất .xlsx.
"""

import argparse
import calendar
import http.cookiejar
import csv
import io
import json
import os
import re
import secrets
import ssl
import sys
import threading
import time
import uuid
import urllib.error
import urllib.parse
import urllib.request
import webbrowser
import zipfile
from datetime import date, datetime, timedelta
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

__version__ = "2.0.2"

BASE_URL = os.environ.get("HDDT_BASE_URL", "https://hoadondientu.gdt.gov.vn/api")
PAGE_SIZE = 50
# Cổng có tường lửa nhận dạng hành vi: đăng nhập chỉ mang header tối giản như trình duyệt gọi qua proxy
# của trang; tra cứu/tải XML mang header của trang tra cứu (Action, End-Point). Gửi sai bộ → 403
# "Hệ thống phát hiện hành vi không hợp lệ".
UA_LOGIN = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
UA_QUERY = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"

# Trạng thái xử lý (ttxly) của CQT dùng khi tra hoá đơn mua vào.
TTXLY_LABELS = {
    5: "Đã cấp mã hoá đơn",
    6: "CQT đã nhận, không mã",
    8: "CQT đã nhận, máy tính tiền",
}
TTHAI_LABELS = {
    1: "Hoá đơn mới",
    2: "Hoá đơn thay thế",
    3: "Hoá đơn điều chỉnh",
    4: "Đã bị thay thế",
    5: "Đã bị điều chỉnh",
    6: "Đã bị huỷ",
}

# (cột, khoá dữ liệu) cho bảng kê.
COLUMNS = [
    ("STT", "_stt"),
    ("Ngày lập", "_ngay"),
    ("Ký hiệu mẫu số", "khmshdon"),
    ("Ký hiệu hoá đơn", "khhdon"),
    ("Số hoá đơn", "shdon"),
    ("MST người bán", "nbmst"),
    ("Tên người bán", "nbten"),
    ("MST người mua", "nmmst"),
    ("Tên người mua", "nmten"),
    ("Tiền chưa thuế", "tgtcthue"),
    ("Tiền thuế", "tgtthue"),
    ("Tổng thanh toán", "tgtttbso"),
    ("Tiền tệ", "dvtte"),
    ("Trạng thái hoá đơn", "_tthai"),
    ("Kết quả xử lý", "_ttxly"),
    ("Nguồn", "_nguon"),
    ("Mã CQT", "mhdon"),
    ("File XML", "_xml"),
]
MONEY_KEYS = {"tgtcthue", "tgtthue", "tgtttbso"}


class PortalError(Exception):
    """Lỗi trả về từ cổng hoá đơn điện tử (thông báo hiển thị cho người dùng)."""


# ---------------------------------------------------------------------------
# Tiện ích ngày tháng
# ---------------------------------------------------------------------------

def parse_date(text):
    """Nhận 'dd/mm/yyyy' hoặc 'yyyy-mm-dd'."""
    text = (text or "").strip()
    for fmt in ("%d/%m/%Y", "%Y-%m-%d"):
        try:
            return datetime.strptime(text, fmt).date()
        except ValueError:
            pass
    raise ValueError("Ngày không hợp lệ: %r (dùng dd/mm/yyyy)" % text)


def month_ranges(start, end):
    """Chia [start, end] thành các đoạn không vượt quá một tháng dương lịch."""
    if end < start:
        raise ValueError("Ngày kết thúc phải sau ngày bắt đầu")
    ranges = []
    cur = start
    while cur <= end:
        last = date(cur.year, cur.month, calendar.monthrange(cur.year, cur.month)[1])
        stop = min(last, end)
        ranges.append((cur, stop))
        cur = stop + timedelta(days=1)
    return ranges


def search_query(start, end, ttxly=None):
    q = "tdlap=ge=%sT00:00:00;tdlap=le=%sT23:59:59" % (start.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y"))
    if ttxly is not None:
        q += ";ttxly==%d" % ttxly
    return q


def invoice_date(inv):
    raw = str(inv.get("tdlap") or "")
    m = re.match(r"(\d{4})-(\d{2})-(\d{2})", raw)
    if m:
        return date(int(m.group(1)), int(m.group(2)), int(m.group(3)))
    m = re.match(r"(\d{2})/(\d{2})/(\d{4})", raw)
    if m:
        return date(int(m.group(3)), int(m.group(2)), int(m.group(1)))
    return None


def safe_name(text):
    return re.sub(r"[^0-9A-Za-z._-]+", "_", str(text)).strip("_") or "x"


# ---------------------------------------------------------------------------
# Client cổng hoá đơn điện tử
# ---------------------------------------------------------------------------

class HoaDonClient:
    def __init__(self, base_url=BASE_URL, timeout=60, verify_ssl=True, delay=0.4):
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.delay = delay
        self.token = None
        self.username = None
        # Trang chủ của cổng = base_url bỏ đuôi /api
        self.site_url = self.base_url[:-4] if self.base_url.endswith("/api") else self.base_url
        ctx = None if verify_ssl else ssl._create_unverified_context()
        https = urllib.request.HTTPSHandler(context=ctx)
        # Đăng nhập dùng chung cookie với trang chủ + captcha; tra cứu không gửi cookie.
        self._jar = http.cookiejar.CookieJar()
        self._login_opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self._jar), https)
        self._query_opener = urllib.request.build_opener(https)
        self._visited = False

    # -- HTTP ---------------------------------------------------------------
    def _headers(self, profile, action=None, json_body=False):
        if profile == "portal":
            h = {"User-Agent": UA_LOGIN, "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                 "Accept-Language": "vi"}
        elif profile == "login":
            h = {"User-Agent": UA_LOGIN, "Accept": "application/json, text/plain, */*"}
            if json_body:
                h["Content-Type"] = "application/json"
        else:
            h = {"Authorization": "Bearer " + (self.token or ""), "Accept": "application/json, text/plain, */*",
                 "Accept-Language": "vi", "End-Point": "/tra-cuu/tra-cuu-hoa-don", "Origin": self.site_url,
                 "Referer": self.site_url + "/", "User-Agent": UA_QUERY,
                 "Action": urllib.parse.quote(action or "Tìm kiếm", safe="()")}
        h["request-id"] = str(uuid.uuid4())
        return h

    def _request(self, method, path, params=None, body=None, raw=False, retries=3, profile="query", action=None,
                 url=None):
        url = url or self.base_url + path
        if params:
            url += "?" + urllib.parse.urlencode(params, safe=":,;=/")
        data = json.dumps(body).encode("utf-8") if body is not None else None
        opener = self._query_opener if profile == "query" else self._login_opener

        for attempt in range(retries + 1):
            headers = self._headers(profile, action, body is not None)
            req = urllib.request.Request(url, data=data, headers=headers, method=method)
            try:
                with opener.open(req, timeout=self.timeout) as resp:
                    content = resp.read()
                break
            except urllib.error.HTTPError as e:
                content = e.read()
                if e.code in (429, 500, 502, 503, 504) and attempt < retries:
                    time.sleep(2 ** attempt * 2)
                    continue
                if e.code == 401:
                    self.token = None
                    raise PortalError("Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.")
                msg = self._error_message(content) or "Cổng hoá đơn trả lỗi HTTP %d" % e.code
                if "hành vi không hợp lệ" in msg:
                    msg += " (tường lửa của cổng chặn; chờ vài phút rồi thử lại, nếu vẫn bị hãy báo để cập nhật phần mềm)"
                raise PortalError(msg)
            except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
                if attempt < retries:
                    time.sleep(2 ** attempt * 2)
                    continue
                reason = getattr(e, "reason", e)
                raise PortalError("Không kết nối được cổng hoá đơn điện tử: %s" % reason)

        if raw:
            return content
        try:
            return json.loads(content.decode("utf-8")) if content else {}
        except ValueError:
            raise PortalError("Cổng hoá đơn trả dữ liệu không đọc được.")

    @staticmethod
    def _error_message(content):
        try:
            data = json.loads(content.decode("utf-8"))
        except (ValueError, UnicodeDecodeError, AttributeError):
            return None
        if isinstance(data, dict):
            return data.get("message") or data.get("error_description") or data.get("error")
        return None

    # -- Đăng nhập ----------------------------------------------------------
    def _visit_portal(self):
        """Mở trang chủ như trình duyệt để nhận cookie trước khi lấy captcha/đăng nhập."""
        if self._visited:
            return
        self._visited = True
        try:
            self._request("GET", "", raw=True, retries=1, profile="portal", url=self.site_url + "/")
        except PortalError:
            pass

    def get_captcha(self):
        """Trả về {'key': ..., 'content': '<svg ...>'}."""
        self._visit_portal()
        data = self._request("GET", "/captcha", profile="login")
        if not data.get("key") or not data.get("content"):
            raise PortalError("Không lấy được captcha.")
        return {"key": data["key"], "content": data["content"]}

    def login(self, username, password, captcha_value, captcha_key):
        self.token = None
        data = self._request(
            "POST",
            "/security-taxpayer/authenticate",
            body={"username": username, "password": password, "ckey": captcha_key, "cvalue": captcha_value},
            retries=0, profile="login",
        )
        token = data.get("token")
        if not token:
            raise PortalError(data.get("message") or "Đăng nhập không thành công.")
        self.token = token
        self.username = username
        return token

    # -- Tra cứu -------------------------------------------------------------
    def _sources(self, kind, include_mtt):
        """Danh sách (đường dẫn gốc, ttxly, nhãn nguồn) cần tra cho một loại hoá đơn."""
        if kind == "purchase":
            out = [("/query", 5, "Có mã / không mã"), ("/query", 6, "Có mã / không mã")]
            if include_mtt:
                out.append(("/sco-query", 8, "Máy tính tiền"))
        else:
            out = [("/query", None, "Có mã / không mã")]
            if include_mtt:
                out.append(("/sco-query", None, "Máy tính tiền"))
        return out

    def list_invoices(self, kind, start, end, include_mtt=True, progress=None):
        """kind: 'purchase' (mua vào) hoặc 'sold' (bán ra). Trả về list dict hoá đơn."""
        if kind not in ("purchase", "sold"):
            raise ValueError("kind phải là 'purchase' hoặc 'sold'")
        results, seen = [], set()
        for prefix, ttxly, label in self._sources(kind, include_mtt):
            for a, b in month_ranges(start, end):
                if progress:
                    progress("Tra %s %s → %s (%s)" % (
                        "mua vào" if kind == "purchase" else "bán ra",
                        a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y"), label))
                state = None
                while True:
                    params = {"sort": "tdlap:desc,khmshdon:asc,shdon:desc", "size": PAGE_SIZE,
                              "search": search_query(a, b, ttxly)}
                    if state:
                        params["state"] = state
                    data = self._request("GET", "%s/invoices/%s" % (prefix, kind), params=params)
                    page = data.get("datas") or []
                    for inv in page:
                        key = (inv.get("nbmst"), inv.get("khmshdon"), inv.get("khhdon"), inv.get("shdon"))
                        if key in seen:
                            continue
                        seen.add(key)
                        inv["_prefix"] = prefix
                        inv["_nguon"] = label
                        inv["_kind"] = kind
                        results.append(inv)
                    state = data.get("state")
                    if not page or not state or len(page) < PAGE_SIZE:
                        break
                    time.sleep(self.delay)
                time.sleep(self.delay)
        results.sort(key=lambda i: (str(i.get("tdlap") or ""), str(i.get("khhdon") or ""), _num(i.get("shdon"))))
        return results

    def _invoice_params(self, inv):
        return {"nbmst": inv.get("nbmst"), "khhdon": inv.get("khhdon"),
                "shdon": inv.get("shdon"), "khmshdon": inv.get("khmshdon")}

    def get_detail(self, inv):
        return self._request("GET", "%s/invoices/detail" % inv.get("_prefix", "/query"),
                             params=self._invoice_params(inv), action="Xem hóa đơn (%s)" % self._kind_label(inv))

    def export_xml(self, inv):
        """Trả về bytes file zip (invoice.xml, details.js, ...) do cổng cung cấp."""
        return self._request("GET", "%s/invoices/export-xml" % inv.get("_prefix", "/query"),
                             params=self._invoice_params(inv), raw=True,
                             action="Xuất xml (%s)" % self._kind_label(inv))

    @staticmethod
    def _kind_label(inv):
        return "hóa đơn bán ra" if inv.get("_kind") == "sold" else "hóa đơn mua vào"


def _num(v):
    try:
        return int(v)
    except (TypeError, ValueError):
        return 0


def _money(v):
    try:
        return float(v)
    except (TypeError, ValueError):
        return 0.0


# ---------------------------------------------------------------------------
# Lưu file
def invoice_key(inv):
    return "%s|%s|%s|%s" % (inv.get("nbmst"), inv.get("khmshdon"), inv.get("khhdon"), inv.get("shdon"))


def invoice_basename(inv):
    d = invoice_date(inv)
    return "_".join([
        d.strftime("%Y%m%d") if d else "nodate",
        safe_name(inv.get("nbmst")),
        safe_name("%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or "")),
        safe_name(inv.get("shdon")),
    ])


def save_xml(zip_bytes, folder, basename):
    """Giải nén XML (và HTML nếu có) từ zip của cổng. Trả về đường dẫn file XML."""
    os.makedirs(folder, exist_ok=True)
    try:
        zf = zipfile.ZipFile(io.BytesIO(zip_bytes))
    except zipfile.BadZipFile:
        # Một số trường hợp cổng trả thẳng XML.
        if zip_bytes.lstrip()[:1] == b"<":
            path = os.path.join(folder, basename + ".xml")
            with open(path, "wb") as f:
                f.write(zip_bytes)
            return path
        raise PortalError("File XML tải về không hợp lệ.")
    xml_path = None
    with zf:
        for name in zf.namelist():
            ext = os.path.splitext(name)[1].lower()
            if ext not in (".xml", ".html", ".htm"):
                continue
            target = os.path.join(folder, basename + ext)
            if ext == ".xml" and xml_path:
                target = os.path.join(folder, "%s_%s" % (basename, safe_name(os.path.basename(name))))
            with open(target, "wb") as f:
                f.write(zf.read(name))
            if ext == ".xml" and not xml_path:
                xml_path = target
    if not xml_path:
        raise PortalError("File tải về không chứa XML hoá đơn.")
    return xml_path


# Cột bảng chi tiết hàng hoá (đọc từ XML theo chuẩn TT78: DSHHDVu/HHDVu).
ITEM_FIELDS = [
    ("Tên hàng hoá, dịch vụ", "THHDVu"), ("Mã hàng", "MHHDVu"), ("ĐVT", "DVTinh"), ("Số lượng", "SLuong"),
    ("Đơn giá", "DGia"), ("Chiết khấu", "STCKhau"), ("Thành tiền", "ThTien"), ("Thuế suất", "TSuat"),
    ("Tính chất", "TChat"),
]
ITEM_NUMERIC = {"SLuong", "DGia", "STCKhau", "ThTien"}


def _local(tag):
    return tag.rsplit("}", 1)[-1]


def read_items(xml_path):
    """Đọc danh sách hàng hoá trong file XML hoá đơn. Lỗi đọc → []."""
    import xml.etree.ElementTree as ET
    try:
        root = ET.parse(xml_path).getroot()
    except (ET.ParseError, OSError):
        return []
    items = []
    for el in root.iter():
        if _local(el.tag) != "HHDVu":
            continue
        vals = {_local(c.tag): (c.text or "").strip() for c in el}
        row = []
        for _, key in ITEM_FIELDS:
            v = vals.get(key, "")
            if key in ITEM_NUMERIC and v:
                try:
                    v = float(v)
                except ValueError:
                    pass
            row.append(v)
        items.append(row)
    return items


def table_rows(invoices):
    rows = []
    for idx, inv in enumerate(invoices, 1):
        d = invoice_date(inv)
        extra = {
            "_stt": idx,
            "_ngay": d.strftime("%d/%m/%Y") if d else inv.get("tdlap"),
            "_tthai": TTHAI_LABELS.get(_num(inv.get("tthai")), inv.get("tthai")),
            "_ttxly": TTXLY_LABELS.get(_num(inv.get("ttxly")), inv.get("ttxly")),
            "_nguon": inv.get("_nguon"),
            "_xml": inv.get("_xml", ""),
        }
        row = []
        for _, key in COLUMNS:
            val = extra[key] if key in extra else inv.get(key)
            if key in MONEY_KEYS:
                val = _money(val)
            elif key != "_stt":
                val = "" if val is None else str(val)  # số HĐ, MST giữ dạng chữ (không thành 1.234)
            row.append("" if val is None else val)
        rows.append(row)
    return rows


ITEM_HEAD = ["Ngày lập", "Ký hiệu", "Số HĐ", "MST người bán", "Tên người bán", "MST người mua", "Tên người mua"]
CHANGE_HEAD = ["Ngày lập", "Ký hiệu", "Số HĐ", "MST người bán", "Tên người bán", "Trạng thái cũ", "Trạng thái mới"]


def _inv_head(inv):
    d = invoice_date(inv)
    return [d.strftime("%d/%m/%Y") if d else "", "%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or ""),
            str(inv.get("shdon") or ""), str(inv.get("nbmst") or ""), inv.get("nbten") or "",
            str(inv.get("nmmst") or ""), inv.get("nmten") or ""]


def write_reports(invoices, folder, basename, items=None, changes=None):
    """Ghi bảng kê .csv (luôn có) và .xlsx (nếu cài openpyxl; gồm sheet chi tiết hàng hoá và
    hoá đơn đổi trạng thái). Trả về list đường dẫn."""
    os.makedirs(folder, exist_ok=True)
    headers = [c[0] for c in COLUMNS]
    rows = table_rows(invoices)
    paths = []

    csv_path = os.path.join(folder, basename + ".csv")
    with open(csv_path, "w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f)
        w.writerow(headers)
        w.writerows(rows)
    paths.append(csv_path)

    try:
        from openpyxl import Workbook
        from openpyxl.styles import Font, PatternFill
        from openpyxl.utils import get_column_letter
    except ImportError:
        return paths

    def sheet(ws, head, data, widths, money_cols=(), total=False):
        ws.append(head)
        for cell in ws[1]:
            cell.font = Font(bold=True, color="FFFFFF")
            cell.fill = PatternFill("solid", fgColor="1F6FB2")
        for r in data:
            ws.append(r)
        if total and data:
            tr = len(data) + 2
            ws.cell(row=tr, column=1, value="Tổng cộng").font = Font(bold=True)
            for col in money_cols:
                letter = get_column_letter(col)
                ws.cell(row=tr, column=col, value="=SUM(%s2:%s%d)" % (letter, letter, tr - 1)).font = Font(bold=True)
        for col in money_cols:
            for cells in ws.iter_cols(min_col=col, max_col=col, min_row=2):
                for c in cells:
                    c.number_format = "#,##0"
        for i, wdt in enumerate(widths, 1):
            ws.column_dimensions[get_column_letter(i)].width = wdt
        ws.freeze_panes = "A2"
        ws.auto_filter.ref = ws.dimensions

    wb = Workbook()
    ws = wb.active
    ws.title = "Bang ke"
    sheet(ws, headers, rows, [6, 12, 10, 12, 10, 14, 40, 14, 40, 16, 14, 16, 8, 18, 22, 16, 30, 50],
          [i + 1 for i, (_, k) in enumerate(COLUMNS) if k in MONEY_KEYS], total=True)
    if items:
        n = len(ITEM_HEAD)
        sheet(wb.create_sheet("Chi tiet hang hoa"), ITEM_HEAD + [c[0] for c in ITEM_FIELDS], items,
              [12, 12, 10, 14, 36, 14, 36, 44, 12, 8, 10, 14, 12, 16, 10, 10],
              [n + 4, n + 5, n + 6, n + 7])
    if changes:
        sheet(wb.create_sheet("Doi trang thai"), CHANGE_HEAD, changes, [12, 12, 10, 14, 40, 20, 20])
    xlsx_path = os.path.join(folder, basename + ".xlsx")
    wb.save(xlsx_path)
    paths.append(xlsx_path)
    return paths


# ---------------------------------------------------------------------------
# Mật khẩu lưu trên máy: Windows mã hoá bằng DPAPI (chỉ tài khoản Windows này giải được)
# ---------------------------------------------------------------------------

def _dpapi(data, encrypt):
    import ctypes
    from ctypes import wintypes

    class BLOB(ctypes.Structure):
        _fields_ = [("cbData", wintypes.DWORD), ("pbData", ctypes.POINTER(ctypes.c_char))]

    buf = ctypes.create_string_buffer(data, len(data))
    blob_in = BLOB(len(data), ctypes.cast(buf, ctypes.POINTER(ctypes.c_char)))
    blob_out = BLOB()
    crypt32 = ctypes.windll.crypt32
    if encrypt:
        ok = crypt32.CryptProtectData(ctypes.byref(blob_in), "TaiHoaDon", None, None, None, 0, ctypes.byref(blob_out))
    else:
        ok = crypt32.CryptUnprotectData(ctypes.byref(blob_in), None, None, None, None, 0, ctypes.byref(blob_out))
    if not ok:
        raise OSError("DPAPI lỗi")
    try:
        return ctypes.string_at(blob_out.pbData, blob_out.cbData)
    finally:
        ctypes.windll.kernel32.LocalFree(blob_out.pbData)


def protect(text):
    import base64
    raw = text.encode("utf-8")
    if os.name == "nt":
        return "dpapi:" + base64.b64encode(_dpapi(raw, True)).decode()
    return "b64:" + base64.b64encode(raw).decode()


def unprotect(value):
    import base64
    if not value:
        return ""
    kind, _, data = value.partition(":")
    raw = base64.b64decode(data)
    if kind == "dpapi":
        raw = _dpapi(raw, False)
    return raw.decode("utf-8")


def _load_json(path, default):
    try:
        with open(path, encoding="utf-8") as f:
            return json.load(f)
    except (OSError, ValueError):
        return default


def _save_json(path, data):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    tmp = path + ".tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=1)
    os.replace(tmp, path)


# ---------------------------------------------------------------------------
# Danh sách doanh nghiệp
# ---------------------------------------------------------------------------

class Store:
    def __init__(self, path):
        self.path = path
        self.lock = threading.RLock()
        self.items = _load_json(path, [])

    def save(self):
        with self.lock:
            _save_json(self.path, self.items)

    def get(self, mst):
        with self.lock:
            return next((c for c in self.items if c["mst"] == mst), None)

    def upsert(self, mst, ten=None, password=None, **fields):
        mst = re.sub(r"\s+", "", str(mst or ""))
        if not re.fullmatch(r"\d{10}(-\d{3})?", mst):
            raise ValueError("MST không hợp lệ: %r" % mst)
        with self.lock:
            c = self.get(mst)
            if not c:
                c = {"mst": mst, "ten": "", "pw": "", "ghichu": "", "vao": True, "ra": True, "an": False,
                     "loi": "", "lich_su": {}, "so_hd": {}, "tao": date.today().strftime("%d/%m/%y")}
                self.items.append(c)
            if ten is not None:
                c["ten"] = ten.strip()
            if password:
                c["pw"] = protect(password)
                c["loi"] = ""
            for k in ("ghichu", "vao", "ra", "an", "loi"):
                if k in fields and fields[k] is not None:
                    c[k] = fields[k]
            self.save()
            return c

    def delete(self, mst):
        with self.lock:
            self.items = [c for c in self.items if c["mst"] != mst]
            self.save()

    def update(self, mst, fn):
        with self.lock:
            c = self.get(mst)
            if c:
                fn(c)
                self.save()

    def public(self):
        with self.lock:
            out = []
            for c in self.items:
                d = {k: v for k, v in c.items() if k != "pw"}
                d["co_mk"] = bool(c.get("pw"))
                out.append(d)
            return out

    def import_text(self, text):
        """Mỗi dòng: MST <tab hoặc |> Tên <tab hoặc |> Mật khẩu (dán thẳng từ Excel)."""
        added, errors = 0, []
        for n, line in enumerate((text or "").splitlines(), 1):
            if not line.strip():
                continue
            parts = [p.strip() for p in re.split(r"\t|\|", line)]
            try:
                self.upsert(parts[0], parts[1] if len(parts) > 1 and parts[1] else None,
                            parts[2] if len(parts) > 2 else None)
                added += 1
            except ValueError as e:
                errors.append("Dòng %d: %s" % (n, e))
        return added, errors


# ---------------------------------------------------------------------------
# Captcha tự học: cổng dùng captcha SVG, mỗi ký tự là một <path> có màu tô với hình dạng cố định.
# Mỗi lần người dùng gõ đúng captcha, phần mềm lưu hình từng ký tự; lần sau tự nhận ra.
# ---------------------------------------------------------------------------

_PATH_RE = re.compile(r"<path\b([^>]*)/?>", re.I)
_ATTR_RE = re.compile(r'([\w:-]+)\s*=\s*"([^"]*)"')
_NUM_RE = re.compile(r"-?\d*\.?\d+(?:e-?\d+)?", re.I)


def captcha_glyphs(svg):
    """Trả về [(chữ lệnh, toạ độ tương đối, x trái)] của các ký tự, xếp từ trái sang phải."""
    glyphs = []
    for m in _PATH_RE.finditer(svg or ""):
        attrs = dict(_ATTR_RE.findall(m.group(1)))
        fill = attrs.get("fill", "").strip().lower()
        d = attrs.get("d", "")
        if not d or not fill or fill == "none":
            continue  # đường nhiễu chỉ có stroke
        letters = "".join(re.findall(r"[A-Za-z]", d))
        nums = [float(x) for x in _NUM_RE.findall(d)]
        if len(nums) < 4:
            continue
        xs, ys = nums[0::2], nums[1::2]
        minx, miny = min(xs), min(ys)
        rel = []
        for i, v in enumerate(nums):
            rel.append(round(v - (minx if i % 2 == 0 else miny), 1))
        glyphs.append((letters, rel, minx))
    glyphs.sort(key=lambda g: g[2])
    return glyphs


def _dist(a, b):
    if len(a) != len(b):
        return float("inf")
    return sum(abs(x - y) for x, y in zip(a, b)) / max(len(a), 1)


class CaptchaSolver:
    THRESHOLD = 1.5

    def __init__(self, path):
        self.path = path
        self.lock = threading.Lock()
        self.templates = _load_json(path, {})  # chữ lệnh → [[ký tự, toạ độ], ...]

    def count(self):
        with self.lock:
            return sum(len(v) for v in self.templates.values())

    def chars(self):
        with self.lock:
            return sorted({t[0] for v in self.templates.values() for t in v})

    def _match(self, letters, rel):
        cands = self.templates.get(letters, [])
        if cands and len({c for c, _ in cands}) == 1:
            return cands[0][0]  # chuỗi lệnh vẽ chỉ ứng với một ký tự → chắc chắn
        best, best_d = None, float("inf")
        for ch, tpl in cands:
            dd = _dist(rel, tpl)
            if dd < best_d:
                best, best_d = ch, dd
        return best if best_d <= self.THRESHOLD else None

    def solve(self, svg):
        glyphs = captcha_glyphs(svg)
        if not glyphs:
            return None
        out = []
        with self.lock:
            for letters, rel, _ in glyphs:
                ch = self._match(letters, rel)
                if ch is None:
                    return None
                out.append(ch)
        return "".join(out)

    def learn(self, svg, answer):
        glyphs = captcha_glyphs(svg)
        if not answer or len(glyphs) != len(answer):
            return False
        with self.lock:
            for (letters, rel, _), ch in zip(glyphs, answer):
                lst = self.templates.setdefault(letters, [])
                if not any(c == ch and _dist(rel, t) <= 0.3 for c, t in lst):
                    lst.append([ch, rel])
            _save_json(self.path, self.templates)
        return True

    def forget(self, svg):
        """Captcha tự giải bị cổng báo sai → bỏ các mẫu đã dùng để lần sau hỏi lại người dùng."""
        with self.lock:
            for letters, _, _ in captcha_glyphs(svg):
                self.templates.pop(letters, None)
            _save_json(self.path, self.templates)


# ---------------------------------------------------------------------------
# Tác vụ chạy nền (đồng bộ hàng loạt / test đăng nhập); giao diện hỏi tiến độ
# ---------------------------------------------------------------------------

class Cancelled(Exception):
    pass


class Job:
    def __init__(self):
        self.lock = threading.Lock()
        self.running = False
        self.cancel = False
        self.log = []
        self.done = 0
        self.total = 0
        self.current = None
        self.results = []
        self.files = []
        self.need_captcha = None
        self._answer = None
        self._event = threading.Event()

    def say(self, msg):
        with self.lock:
            self.log.append("%s  %s" % (datetime.now().strftime("%H:%M:%S"), msg))
            self.log = self.log[-300:]

    def ask_captcha(self, company, svg, timeout=600):
        with self.lock:
            self._answer = None
            self._event.clear()
            self.need_captcha = {"mst": company["mst"], "ten": company.get("ten"), "svg": svg}
        self._event.wait(timeout)
        with self.lock:
            self.need_captcha = None
            ans = self._answer
        if self.cancel or not ans:
            raise Cancelled()
        return ans

    def answer(self, text):
        with self.lock:
            self._answer = (text or "").strip()
        self._event.set()

    def stop(self):
        self.cancel = True
        self._event.set()

    def snapshot(self):
        with self.lock:
            return {"running": self.running, "log": list(self.log), "done": self.done, "total": self.total,
                    "current": self.current, "results": list(self.results), "files": list(self.files),
                    "need_captcha": self.need_captcha}


def login_company(job, client, company, solver):
    """Đăng nhập một doanh nghiệp: tự giải captcha nếu đã học, không thì hỏi người dùng.
    Sai mật khẩu → dừng ngay (không thử lại để tránh bị khoá tài khoản)."""
    password = unprotect(company.get("pw"))
    if not password:
        raise PortalError("Chưa nhập mật khẩu hoadondientu.gdt.gov.vn")
    for _ in range(4):
        cap = client.get_captcha()
        text = solver.solve(cap["content"])
        auto = text is not None
        if not auto:
            job.say("%s: cần nhập captcha" % company["mst"])
            text = job.ask_captcha(company, cap["content"])
        try:
            client.login(company["mst"], password, text, cap["key"])
        except PortalError as e:
            if "captcha" in str(e).lower():
                if auto:
                    solver.forget(cap["content"])
                job.say("%s: captcha sai, thử lại" % company["mst"])
                continue
            raise
        if not auto:
            solver.learn(cap["content"], text)
        job.say("%s: đăng nhập thành công%s" % (company["mst"], " (tự giải captcha)" if auto else ""))
        return
    raise PortalError("Nhập sai captcha quá nhiều lần")


def sync_kind(job, client, company, kind, start, end, include_mtt, want_xml, out_root):
    mst = company["mst"]
    label = "mua-vao" if kind == "purchase" else "ban-ra"
    period = "%s_%s" % (start.strftime("%Y%m%d"), end.strftime("%Y%m%d"))
    base = os.path.join(out_root, safe_name(mst))
    folder = os.path.join(base, "%s_%s" % (label, period))
    index_path = os.path.join(base, "_chi-muc.json")
    index = _load_json(index_path, {})
    known = index.setdefault(kind, {})

    invoices = client.list_invoices(kind, start, end, include_mtt, progress=lambda m: job.say("%s: %s" % (mst, m)))
    new, changes, items = 0, [], []
    with job.lock:
        job.total += len(invoices) if want_xml else 0
    for inv in invoices:
        if job.cancel:
            raise Cancelled()
        key = invoice_key(inv)
        old = known.get(key)
        tthai = _num(inv.get("tthai"))
        if old is None:
            new += 1
        elif old.get("tthai") != tthai:
            changes.append(_inv_head(inv)[:5] + [TTHAI_LABELS.get(old.get("tthai"), old.get("tthai")),
                                                 TTHAI_LABELS.get(tthai, tthai)])
            job.say("%s: HĐ %s/%s đổi trạng thái → %s" % (mst, inv.get("khhdon"), inv.get("shdon"),
                                                          TTHAI_LABELS.get(tthai, tthai)))
        if want_xml:
            xml_dir = os.path.join(folder, "xml")
            name = invoice_basename(inv)
            existing = os.path.join(xml_dir, name + ".xml")
            try:
                if os.path.exists(existing) and (old or {}).get("tthai") == tthai:
                    path = existing
                else:
                    path = save_xml(client.export_xml(inv), xml_dir, name)
                    time.sleep(client.delay)
                inv["_xml"] = os.path.relpath(path, folder)
                head = _inv_head(inv)
                items.extend(head + it for it in read_items(path))
            except PortalError as e:
                inv["_xml"] = "Lỗi: %s" % e
                job.say("%s: không tải được XML %s/%s: %s" % (mst, inv.get("khhdon"), inv.get("shdon"), e))
                if "hết hạn" in str(e):
                    raise
            with job.lock:
                job.done += 1
        known[key] = {"tthai": tthai, "tdlap": inv.get("tdlap")}
    _save_json(index_path, index)

    paths = write_reports(invoices, folder, "bang-ke-%s_%s" % (label, period), items, changes)
    with job.lock:
        job.files.extend(os.path.abspath(p) for p in paths)
        job.results.append({"mst": mst, "ten": company.get("ten"), "loai": label, "so_hd": len(invoices),
                            "moi": new, "doi": len(changes), "thu_muc": os.path.abspath(folder)})
    job.say("%s: %s %d HĐ (%d mới, %d đổi trạng thái)" % (mst, label, len(invoices), new, len(changes)))
    return len(invoices), new, len(known)


def run_batch(job, store, solver, msts, kinds, start, end, include_mtt, want_xml, out_root,
              test_only=False, client_factory=None):
    client_factory = client_factory or HoaDonClient
    try:
        for mst in msts:
            if job.cancel:
                break
            company = store.get(mst)
            if not company:
                continue
            with job.lock:
                job.current = mst
            client = client_factory()
            try:
                login_company(job, client, company, solver)
                store.update(mst, lambda c: c.update(loi=""))
                if test_only:
                    continue
                for kind in kinds:
                    if not company.get("vao" if kind == "purchase" else "ra", True):
                        continue
                    total, new, known = sync_kind(job, client, company, kind, start, end, include_mtt,
                                                  want_xml, out_root)
                    stamp = datetime.now().strftime("%d.%m.%y %H:%M")

                    def upd(c, kind=kind, new=new, known=known, stamp=stamp):
                        c.setdefault("lich_su", {})[kind] = {"moi": new, "luc": stamp}
                        c.setdefault("so_hd", {})[kind] = known
                    store.update(mst, upd)
            except Cancelled:
                job.say("Đã dừng theo yêu cầu.")
                break
            except Exception as e:  # ghi lỗi của DN này rồi chuyển sang DN tiếp theo
                msg = str(e)
                job.say("%s: LỖI %s" % (mst, msg))
                store.update(mst, lambda c: c.update(loi=msg))
        job.say("Hoàn tất.")
    finally:
        with job.lock:
            job.running = False
            job.current = None
            job.need_captcha = None


# ---------------------------------------------------------------------------
# Giao diện web chạy trên máy (127.0.0.1)
# ---------------------------------------------------------------------------

PAGE = r"""<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tải hoá đơn điện tử</title>
<style>
:root{--bg:#f4f6f9;--card:#fff;--fg:#1d2733;--mute:#66758a;--line:#dde3ea;--pri:#2b3a8c;--acc:#5b5fe0;--err:#c0392b;--ok:#1e8449;--v:#4361ee;--r:#d63447}
@media (prefers-color-scheme:dark){:root{--bg:#12161c;--card:#1b2129;--fg:#e6ebf1;--mute:#93a1b3;--line:#2c3540;--pri:#3a4bb0;--acc:#8b8ff5;--err:#ff6b5b;--ok:#4cc38a;--v:#8aa2ff;--r:#ff7b8a}}
*{box-sizing:border-box}body{margin:0;font:14px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--fg)}
header{background:var(--pri);color:#fff;padding:10px 16px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}
header b{font-size:20px;letter-spacing:.5px}header small{opacity:.8}
main{max-width:1400px;margin:0 auto;padding:16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:14px;margin-bottom:14px}
.top{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between}
.stat{color:var(--fg);font-size:15px}.stat b{color:var(--acc)}
input[type=text],input[type=password],input[type=date],textarea{padding:8px 10px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
input.search{flex:1;min-width:220px;background:#fffbe6;color:#333}
button{padding:7px 14px;border:1px solid var(--acc);border-radius:6px;background:var(--acc);color:#fff;font:inherit;cursor:pointer}
button.sec{background:transparent;color:var(--acc)}button.red{background:transparent;color:var(--err);border-color:var(--err)}
button:disabled{opacity:.5;cursor:default}button.sm{padding:3px 9px;font-size:12px}
.bar{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}
table{border-collapse:collapse;width:100%}th,td{padding:8px;border-bottom:1px solid var(--line);text-align:left;vertical-align:top}
th{font-size:13px;color:var(--mute);font-weight:600}td.n,th.n{text-align:right;font-variant-numeric:tabular-nums}
.dn{font-weight:600}.dn .m{color:var(--acc);font-weight:400}.loi{color:var(--err);font-size:12px}
.v{color:var(--v);font-weight:600}.r{color:var(--r);font-weight:600}
.note{border:0;background:transparent;width:100%;color:var(--mute);font-size:13px;padding:2px}
.note:focus{background:var(--bg);color:var(--fg)}.tbl{overflow:auto}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9}
.modal .card{width:min(560px,100%);max-height:90vh;overflow:auto;margin:0}
label{display:block;font-size:13px;color:var(--mute);margin:8px 0 3px}.full{width:100%}
.chk{display:flex;gap:16px;flex-wrap:wrap;margin:10px 0}.chk label{display:flex;gap:6px;align-items:center;color:var(--fg);margin:0}
.capimg{background:#fff;border-radius:6px;display:inline-block;padding:2px}.capimg svg{height:50px;width:auto;display:block}
.prog{height:6px;background:var(--line);border-radius:3px;overflow:hidden;margin:8px 0}.prog i{display:block;height:100%;background:var(--acc);width:0}
pre{background:var(--bg);border:1px solid var(--line);border-radius:6px;padding:8px;max-height:200px;overflow:auto;font-size:12px;margin:0;white-space:pre-wrap}
.hide{display:none!important}.mute{color:var(--mute);font-size:12px}.err{color:var(--err)}.ok{color:var(--ok)}
.hint{font-size:12px;color:var(--mute);margin-top:6px}
</style></head><body>
<header><b>Tải hoá đơn</b><small>hoadondientu.gdt.gov.vn</small><span style="flex:1"></span><small id="capStat"></small></header>
<main>
<section class="card">
  <div class="top">
    <div class="stat">Số doanh nghiệp: <b id="nDN">0</b> hiển thị; <b id="nAn">0</b> bị ẩn
      <a href="#" id="toggleAn" class="mute">xem doanh nghiệp bị ẩn</a></div>
    <input class="search" id="q" placeholder="nhập MST hoặc tên doanh nghiệp cần làm việc nhanh">
  </div>
  <div class="bar">
    <button onclick="openEdit()">+ Thêm doanh nghiệp</button>
    <button class="sec" onclick="show('mImport')">Nhập danh sách từ Excel</button>
    <button class="red" onclick="openBatch()">Xử lý hàng loạt</button>
  </div>
  <div class="tbl"><table>
    <thead><tr><th><input type="checkbox" id="all"></th><th>Doanh nghiệp</th><th>Ghi chú công việc</th>
      <th>Lịch sử đồng bộ</th><th>Ngày tạo</th><th class="n">Số HĐ<br>Mua vào</th><th class="n">Số HĐ<br>Bán ra</th>
      <th class="n">Tổng HĐ</th><th></th></tr></thead>
    <tbody id="rows"></tbody></table></div>
</section>

<section class="card hide" id="jobCard">
  <div class="top"><b id="jobTitle">Đang xử lý</b><button class="red sm" id="btnStop" onclick="post('/api/stop',{})">Dừng</button></div>
  <div class="prog"><i id="progI"></i></div>
  <div id="capBox" class="card hide" style="border-color:var(--acc)">
    <div>Nhập captcha cho <b id="capFor"></b>:</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px">
      <span class="capimg" id="capImg"></span><input type="text" id="capIn" placeholder="ký tự trong ảnh" autocomplete="off">
      <button onclick="sendCap()">Gửi</button></div>
    <div class="hint">Phần mềm ghi nhớ hình từng ký tự bạn gõ; sau vài lần sẽ tự giải captcha.</div>
  </div>
  <div id="results"></div>
  <pre id="log"></pre>
</section>
</main>

<div class="modal hide" id="mEdit"><div class="card">
  <b id="editTitle">Doanh nghiệp</b>
  <label>Mã số thuế</label><input type="text" class="full" id="eMst">
  <label>Tên doanh nghiệp (hiển thị trong bảng kê)</label><input type="text" class="full" id="eTen">
  <label>Mật khẩu trang hoadondientu.gdt.gov.vn</label><input type="password" class="full" id="ePw" autocomplete="new-password">
  <div class="hint" id="ePwHint"></div>
  <div class="chk">
    <label><input type="checkbox" id="eVao"> Đồng bộ hoá đơn đầu vào</label>
    <label><input type="checkbox" id="eRa"> Đồng bộ hoá đơn đầu ra</label>
    <label><input type="checkbox" id="eAn"> Ẩn doanh nghiệp</label>
  </div>
  <div class="err" id="eErr"></div>
  <div class="bar"><button onclick="saveEdit()">Lưu</button><button class="sec" onclick="saveEdit(true)">Lưu &amp; Test đăng nhập</button>
    <span style="flex:1"></span><button class="red" id="eDel" onclick="delEdit()">Xoá</button><button class="sec" onclick="hide('mEdit')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mImport"><div class="card">
  <b>Nhập danh sách doanh nghiệp</b>
  <div class="hint">Mỗi dòng: MST, Tên, Mật khẩu – copy 3 cột từ Excel dán vào (hoặc ngăn cách bằng dấu |). Mật khẩu có thể để trống.</div>
  <textarea id="impText" rows="10" class="full" style="margin-top:8px" placeholder="0313046002	BẢO LỘC KT305	matkhau"></textarea>
  <div class="err" id="impErr"></div>
  <div class="bar"><button onclick="doImport()">Nhập</button><button class="sec" onclick="hide('mImport')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mBatch"><div class="card">
  <b>Xử lý hàng loạt</b>
  <div class="hint" id="bWho"></div>
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <div><label>Từ ngày</label><input type="date" id="bFrom"></div>
    <div><label>Đến ngày</label><input type="date" id="bTo"></div>
  </div>
  <div class="chk">
    <label><input type="checkbox" id="bVao" checked> Mua vào</label>
    <label><input type="checkbox" id="bRa" checked> Bán ra</label>
    <label><input type="checkbox" id="bMtt" checked> Gồm máy tính tiền</label>
    <label><input type="checkbox" id="bXml" checked> Tải XML + chi tiết hàng hoá</label>
  </div>
  <div class="hint">Chỉ đồng bộ chiều đầu vào/đầu ra đang bật trong cài đặt từng doanh nghiệp.</div>
  <div class="err" id="bErr"></div>
  <div class="bar"><button onclick="runBatch()">Bắt đầu</button><button class="sec" onclick="hide('mBatch')">Đóng</button></div>
</div></div>

<script>
const KEY = "__TOKEN__";
const $ = id => document.getElementById(id);
const show = id => $(id).classList.remove('hide'), hide = id => $(id).classList.add('hide');
const fmt = n => (n || 0).toLocaleString('en-US');
let companies = [], showHidden = false, editing = null, batchMsts = [], timer = null;

async function api(path, body) {
  const r = await fetch(path, {method: body ? 'POST' : 'GET', headers: {'X-App-Key': KEY, 'Content-Type': 'application/json'},
                               body: body ? JSON.stringify(body) : undefined});
  const d = await r.json().catch(() => ({error: 'Phản hồi không hợp lệ'}));
  if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
  return d;
}
const post = (p, b) => api(p, b);
function el(tag, cls, text) { const e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

function render() {
  const q = $('q').value.trim().toLowerCase();
  const vis = companies.filter(c => showHidden ? c.an : !c.an)
    .filter(c => !q || c.mst.includes(q) || (c.ten || '').toLowerCase().includes(q));
  $('nDN').textContent = companies.filter(c => !c.an).length; $('nAn').textContent = companies.filter(c => c.an).length;
  $('toggleAn').textContent = showHidden ? 'xem doanh nghiệp đang hiển thị' : 'xem doanh nghiệp bị ẩn';
  const tb = $('rows'); tb.innerHTML = '';
  vis.forEach((c, i) => {
    const tr = tb.insertRow();
    const cb = el('input'); cb.type = 'checkbox'; cb.className = 'pick'; cb.value = c.mst; tr.insertCell().append(cb);
    const td = tr.insertCell(); const dn = el('div', 'dn', String(i + 1).padStart(3, '0') + '. ' + (c.ten || '(chưa đặt tên)') + ' ');
    dn.append(el('span', 'm', '(' + c.mst + ')')); td.append(dn);
    if (!c.co_mk) td.append(el('div', 'loi', 'Chưa nhập mật khẩu'));
    if (c.loi) td.append(el('div', 'loi', c.loi));
    const note = el('input', 'note'); note.value = c.ghichu || ''; note.placeholder = 'nhập ghi chú công việc vào đây';
    note.onchange = () => post('/api/company/save', {mst: c.mst, ghichu: note.value}); tr.insertCell().append(note);
    const ls = tr.insertCell(), h = c.lich_su || {};
    if (h.purchase) ls.append(el('div', 'v', 'V: ' + h.purchase.moi + ' - ' + h.purchase.luc));
    if (h.sold) ls.append(el('div', 'r', 'R: ' + h.sold.moi + ' - ' + h.sold.luc));
    tr.insertCell().textContent = c.tao || '';
    const s = c.so_hd || {}, v = s.purchase || 0, r = s.sold || 0;
    [v, r, v + r].forEach(n => { const x = tr.insertCell(); x.className = 'n'; x.textContent = fmt(n); });
    const act = tr.insertCell(); act.style.whiteSpace = 'nowrap';
    const b1 = el('button', 'sm', 'Tải'); b1.onclick = () => openBatch([c.mst]);
    const b2 = el('button', 'sm sec', 'Sửa'); b2.onclick = () => openEdit(c); b2.style.marginLeft = '4px';
    act.append(b1, b2);
  });
}
async function refresh() {
  const s = await api('/api/state'); companies = s.companies; render();
  $('capStat').textContent = s.captcha.count ? ('Captcha đã học ' + s.captcha.chars.length + ' ký tự') : '';
  if (s.job.running || s.job.log.length) renderJob(s.job);
  return s;
}
function openEdit(c) {
  editing = c || null; $('eErr').textContent = '';
  $('editTitle').textContent = c ? 'Cập nhật thông tin' : 'Thêm doanh nghiệp';
  $('eMst').value = c ? c.mst : ''; $('eMst').disabled = !!c; $('eTen').value = c ? c.ten : ''; $('ePw').value = '';
  $('ePwHint').textContent = c && c.co_mk ? 'Đã lưu mật khẩu – để trống nếu không đổi. Mật khẩu này khác mật khẩu đăng nhập phần mềm.' : 'Mật khẩu được mã hoá và chỉ lưu trên máy này.';
  $('eVao').checked = c ? c.vao : true; $('eRa').checked = c ? c.ra : true; $('eAn').checked = c ? c.an : false;
  $('eDel').classList.toggle('hide', !c); show('mEdit'); (c ? $('ePw') : $('eMst')).focus();
}
async function saveEdit(test) {
  try {
    const mst = $('eMst').value.trim();
    await post('/api/company/save', {mst, ten: $('eTen').value, password: $('ePw').value,
      vao: $('eVao').checked, ra: $('eRa').checked, an: $('eAn').checked});
    hide('mEdit'); await refresh();
    if (test) await start({msts: [mst], test: true});
  } catch (e) { $('eErr').textContent = e.message; }
}
async function delEdit() {
  if (!editing || !confirm('Xoá doanh nghiệp ' + editing.mst + ' khỏi danh sách? (Hoá đơn đã tải vẫn giữ nguyên)')) return;
  await post('/api/company/delete', {mst: editing.mst}); hide('mEdit'); refresh();
}
async function doImport() {
  try { const d = await post('/api/company/import', {text: $('impText').value});
    $('impErr').textContent = d.errors.join('\n'); if (!d.errors.length) { hide('mImport'); $('impText').value = ''; } refresh();
  } catch (e) { $('impErr').textContent = e.message; }
}
function openBatch(msts) {
  batchMsts = msts || [...document.querySelectorAll('.pick:checked')].map(x => x.value);
  $('bErr').textContent = '';
  $('bWho').textContent = batchMsts.length ? ('Doanh nghiệp đã chọn: ' + batchMsts.length) : 'Chưa tích chọn doanh nghiệp nào → xử lý TẤT CẢ doanh nghiệp đang hiển thị.';
  show('mBatch');
}
async function runBatch() {
  const kinds = []; if ($('bVao').checked) kinds.push('purchase'); if ($('bRa').checked) kinds.push('sold');
  const msts = batchMsts.length ? batchMsts : companies.filter(c => !c.an).map(c => c.mst);
  try { await start({msts, kinds, from: $('bFrom').value, to: $('bTo').value, mtt: $('bMtt').checked, xml: $('bXml').checked});
        hide('mBatch'); } catch (e) { $('bErr').textContent = e.message; }
}
async function start(body) { await post('/api/run', body); show('jobCard'); poll(); }
async function sendCap() { const v = $('capIn').value.trim(); if (!v) return; $('capIn').value = ''; hide('capBox'); await post('/api/captcha', {answer: v}); poll(); }
let lastCap = null;
function renderJob(j) {
  show('jobCard');
  $('jobTitle').textContent = j.running ? ('Đang xử lý' + (j.current ? ' ' + j.current : '') + '…') : 'Kết quả lần chạy gần nhất';
  $('btnStop').classList.toggle('hide', !j.running);
  $('progI').style.width = (j.total ? Math.round(100 * j.done / j.total) : (j.running ? 5 : 100)) + '%';
  if (j.need_captcha) {
    const key = j.need_captcha.mst + j.need_captcha.svg.length + j.need_captcha.svg.slice(-40);
    if (key !== lastCap) { lastCap = key; $('capFor').textContent = (j.need_captcha.ten || '') + ' (' + j.need_captcha.mst + ')';
      $('capImg').innerHTML = j.need_captcha.svg; $('capIn').value = ''; show('capBox'); $('capIn').focus(); }
  } else { hide('capBox'); lastCap = null; }
  const res = $('results'); res.innerHTML = '';
  j.results.forEach(r => res.append(el('div', 'ok', r.mst + ' ' + (r.ten || '') + ' – ' + r.loai + ': ' + r.so_hd + ' HĐ (' + r.moi + ' mới, ' + r.doi + ' đổi trạng thái) → ' + r.thu_muc)));
  $('log').textContent = j.log.join('\n'); $('log').scrollTop = 1e9;
}
async function poll() {
  clearTimeout(timer);
  let s; try { s = await refresh(); } catch (e) { timer = setTimeout(poll, 2000); return; }
  if (s.job.running) timer = setTimeout(poll, 1500);
}
$('q').oninput = render;
$('toggleAn').onclick = e => { e.preventDefault(); showHidden = !showHidden; render(); };
$('all').onchange = e => document.querySelectorAll('.pick').forEach(x => x.checked = e.target.checked);
$('capIn').addEventListener('keydown', e => { if (e.key === 'Enter') sendCap(); });
(() => {
  const now = new Date(), first = new Date(now.getFullYear(), now.getMonth() - 1, 1), last = new Date(now.getFullYear(), now.getMonth(), 0);
  const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  $('bFrom').value = iso(first); $('bTo').value = iso(last);
  poll();
})();
</script></body></html>
"""


class App:
    def __init__(self, out_root, client_factory=None):
        self.out_root = out_root
        cfg = os.path.join(out_root, "_cau-hinh")
        self.store = Store(os.path.join(cfg, "doanh-nghiep.json"))
        self.solver = CaptchaSolver(os.path.join(cfg, "captcha-mau.json"))
        self.client_factory = client_factory or HoaDonClient
        self.key = secrets.token_urlsafe(24)
        self.job = Job()


def make_handler(app, port):
    allowed_hosts = {"127.0.0.1:%d" % port, "localhost:%d" % port}

    class Handler(BaseHTTPRequestHandler):
        server_version = "TaiHoaDon/" + __version__

        def log_message(self, fmt, *args):  # yên lặng trên console
            pass

        def _send(self, code, body, ctype="application/json; charset=utf-8"):
            if not isinstance(body, bytes):
                body = json.dumps(body, ensure_ascii=False).encode("utf-8")
            self.send_response(code)
            self.send_header("Content-Type", ctype)
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Cache-Control", "no-store")
            self.send_header("X-Frame-Options", "DENY")
            self.end_headers()
            self.wfile.write(body)

        def _guard(self):
            # Chặn DNS rebinding và yêu cầu giả mạo từ trang web khác.
            if self.headers.get("Host") not in allowed_hosts:
                self._send(403, {"error": "Host không hợp lệ"})
                return False
            if self.path.startswith("/api/") and not secrets.compare_digest(
                    self.headers.get("X-App-Key", ""), app.key):
                self._send(403, {"error": "Phiên giao diện không hợp lệ, hãy tải lại trang"})
                return False
            return True

        def _body(self):
            n = int(self.headers.get("Content-Length") or 0)
            if n > 2000000:
                return {}
            try:
                return json.loads(self.rfile.read(n).decode("utf-8") or "{}")
            except ValueError:
                return {}

        def do_GET(self):
            if not self._guard():
                return
            path = self.path.split("?")[0]
            if path == "/":
                return self._send(200, PAGE.replace("__TOKEN__", app.key).encode("utf-8"), "text/html; charset=utf-8")
            if path == "/api/state":
                return self._send(200, {"companies": app.store.public(), "job": app.job.snapshot(),
                                        "captcha": {"count": app.solver.count(), "chars": app.solver.chars()}})
            self._send(404, {"error": "Không tìm thấy"})

        def do_POST(self):
            if not self._guard():
                return
            path = self.path.split("?")[0]
            data = self._body()
            try:
                return self._post(path, data)
            except ValueError as e:
                return self._send(400, {"error": str(e)})

        def _post(self, path, data):
            if path == "/api/company/save":
                existing = app.store.get(str(data.get("mst", "")).strip())
                if not existing and not data.get("password"):
                    raise ValueError("Nhập mật khẩu trang hoadondientu.gdt.gov.vn")
                fields = {k: data.get(k) for k in ("ghichu", "an")}
                for k in ("vao", "ra"):
                    fields[k] = data.get(k)
                c = app.store.upsert(data.get("mst"), data.get("ten"), data.get("password") or None, **fields)
                return self._send(200, {"mst": c["mst"]})
            if path == "/api/company/delete":
                app.store.delete(data.get("mst"))
                return self._send(200, {"ok": True})
            if path == "/api/company/import":
                added, errors = app.store.import_text(data.get("text"))
                return self._send(200, {"added": added, "errors": errors})
            if path == "/api/run":
                if app.job.running:
                    return self._send(409, {"error": "Đang xử lý, vui lòng chờ hoặc bấm Dừng"})
                msts = [m for m in (data.get("msts") or []) if app.store.get(m)]
                if not msts:
                    raise ValueError("Chưa có doanh nghiệp nào để xử lý")
                test = bool(data.get("test"))
                start = end = None
                kinds = []
                if not test:
                    start, end = parse_date(data.get("from")), parse_date(data.get("to"))
                    if end < start:
                        raise ValueError("Ngày kết thúc phải sau ngày bắt đầu")
                    kinds = [k for k in (data.get("kinds") or []) if k in ("purchase", "sold")]
                    if not kinds:
                        raise ValueError("Chọn ít nhất mua vào hoặc bán ra")
                app.job = Job()
                app.job.running = True
                threading.Thread(target=run_batch, daemon=True, args=(
                    app.job, app.store, app.solver, msts, kinds, start, end, bool(data.get("mtt")),
                    bool(data.get("xml")), app.out_root, test, app.client_factory)).start()
                return self._send(200, {"ok": True})
            if path == "/api/captcha":
                app.job.answer(data.get("answer"))
                return self._send(200, {"ok": True})
            if path == "/api/stop":
                app.job.stop()
                return self._send(200, {"ok": True})
            self._send(404, {"error": "Không tìm thấy"})

    return Handler


def main(argv=None):
    ap = argparse.ArgumentParser(description="Phần mềm tải hoá đơn điện tử (hoadondientu.gdt.gov.vn)")
    ap.add_argument("--port", type=int, default=8765, help="cổng giao diện trên máy (mặc định 8765)")
    ap.add_argument("--out", default=os.path.join(os.getcwd(), "HoaDon"), help="thư mục lưu (mặc định ./HoaDon)")
    ap.add_argument("--no-browser", action="store_true", help="không tự mở trình duyệt")
    ap.add_argument("--insecure", action="store_true", help="bỏ kiểm tra chứng chỉ SSL (chỉ dùng khi máy báo lỗi SSL)")
    args = ap.parse_args(argv)

    app = App(os.path.abspath(args.out), lambda: HoaDonClient(verify_ssl=not args.insecure))
    server = ThreadingHTTPServer(("127.0.0.1", args.port), make_handler(app, args.port))
    url = "http://127.0.0.1:%d/" % args.port
    print("Phần mềm tải hoá đơn %s đang chạy tại %s" % (__version__, url))
    print("Hoá đơn lưu vào: %s" % app.out_root)
    if os.name != "nt":
        print("Lưu ý: ngoài Windows, mật khẩu lưu dạng mã hoá đơn giản – hãy bảo vệ thư mục _cau-hinh.")
    print("Đóng cửa sổ này (hoặc Ctrl+C) để thoát.")
    if not args.no_browser:
        threading.Timer(0.8, lambda: webbrowser.open(url)).start()
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == "__main__":
    sys.exit(main())
