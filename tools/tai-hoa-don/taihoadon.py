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

__version__ = "2.8.2"

BASE_URL = os.environ.get("HDDT_BASE_URL", "https://hoadondientu.gdt.gov.vn/api")
PAGE_SIZE = 50
XML_WORKERS = 4  # số file XML tải cùng lúc
MST_STATUS = {"00": "Đang hoạt động"}  # mã khác: hiện nguyên mã
NO_XML_MSG = "khongtontaihosogoc"  # cổng báo HĐ không có XML gốc (HĐ không mã), dạng đã bỏ dấu
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
    """Ngày lập theo giờ Việt Nam (cổng trả giờ UTC, vd 2026-10-01T17:00:00Z = 02/10/2026)."""
    raw = str(inv.get("tdlap") or "")
    m = re.match(r"(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?", raw)
    if m:
        d = datetime(int(m.group(1)), int(m.group(2)), int(m.group(3)), int(m.group(4) or 0), int(m.group(5) or 0))
        if m.group(4) and re.search(r"(Z|[+-]00:?00)$", raw):
            d += timedelta(hours=7)
        return d.date()
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
    def __init__(self, base_url=BASE_URL, timeout=60, verify_ssl=True, delay=0.15):
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
                # Chỉ thử lại khi cổng quá tải; cổng đã trả thông báo cụ thể (vd "Không tồn tại hồ sơ gốc") thì không.
                if e.code in (429, 500, 502, 503, 504) and attempt < retries and \
                        NO_XML_MSG not in _plain(self._error_message(content) or ""):
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

    def lookup_company(self, mst):
        """Tra thông tin người nộp thuế theo MST (API công khai của cổng, không cần đăng nhập)."""
        mst = re.sub(r"\s+", "", str(mst or ""))
        if not re.fullmatch(r"\d{10}(-\d{3})?", mst):
            raise ValueError("MST không hợp lệ")
        self._visit_portal()
        d = self._request("GET", "/category/public/dsdkts/%s/manager" % mst, profile="login", retries=1)
        if not isinstance(d, dict) or not d.get("tennnt"):
            msg = d.get("message") if isinstance(d, dict) else None
            raise PortalError(msg or "Không tìm thấy MST %s" % mst)
        dia_chi = ", ".join(x for x in (d.get("dctsdchi"), d.get("dctsxaten"), d.get("dctshuyenten"),
                                        d.get("dctstinhten")) if x)
        tthai = str(d.get("tthai") or "")
        return {"mst": mst, "ten": d.get("tennnt", "").strip(), "dia_chi": dia_chi, "cqt": d.get("tencqt", ""),
                "tthai": tthai, "tthai_text": MST_STATUS.get(tthai, "mã " + tthai if tthai else "")}

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
        self.login_at = time.time()
        return token

    def token_valid(self, margin=120):
        """Token còn hạn? Đọc 'exp' trong JWT; không đọc được thì coi hạn 30 phút kể từ lúc đăng nhập."""
        if not self.token:
            return False
        import base64
        try:
            part = self.token.split(".")[1]
            exp = json.loads(base64.urlsafe_b64decode(part + "=" * (-len(part) % 4))).get("exp")
        except (IndexError, ValueError, AttributeError):
            exp = None
        if not exp:
            exp = getattr(self, "login_at", 0) + 1800
        return time.time() < exp - margin

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
                for inv in self._fetch_range(prefix, kind, a, b, ttxly, progress):
                    key = (inv.get("nbmst"), inv.get("khmshdon"), inv.get("khhdon"), inv.get("shdon"))
                    if key in seen:
                        continue
                    seen.add(key)
                    inv["_prefix"] = prefix
                    inv["_nguon"] = label
                    inv["_kind"] = kind
                    results.append(inv)
                time.sleep(self.delay)
        results.sort(key=lambda i: (str(i.get("tdlap") or ""), str(i.get("khhdon") or ""), _num(i.get("shdon"))))
        return results

    def _fetch_range(self, prefix, kind, a, b, ttxly, progress=None):
        """Tra một khoảng ngày (có lật trang). Cổng quá tải (timeout) thì chia nhỏ theo tuần rồi tra lại."""
        try:
            out, state = [], None
            while True:
                params = {"sort": "tdlap:desc", "size": PAGE_SIZE, "search": search_query(a, b, ttxly)}
                if state:
                    params["state"] = state
                data = self._request("GET", "%s/invoices/%s" % (prefix, kind), params=params, retries=2)
                page = data.get("datas") or []
                out.extend(page)
                state = data.get("state")
                if not page or not state or len(page) < PAGE_SIZE:
                    return out
                time.sleep(self.delay)
        except PortalError as e:
            busy = any(w in str(e).lower() for w in ("timeout", "quá tải", "http 5"))
            if not busy or (b - a).days < 1:
                if busy:
                    raise PortalError("Cổng thuế đang quá tải, không trả được dữ liệu ngày %s – %s. Vui lòng đồng bộ "
                                      "lại sau ít phút. (%s)" % (a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y"), e))
                raise
            step = 7 if (b - a).days > 7 else 1
            if progress:
                progress("Cổng thuế quá tải, chia nhỏ %s – %s theo %s để tra lại" % (
                    a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y"), "tuần" if step == 7 else "ngày"))
            out, cur = [], a
            while cur <= b:
                stop = min(cur + timedelta(days=step - 1), b)
                out.extend(self._fetch_range(prefix, kind, cur, stop, ttxly, progress))
                cur = stop + timedelta(days=1)
                time.sleep(self.delay)
            return out

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
            if ext not in (".xml", ".html", ".htm", ".pdf"):
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


def _local(tag):
    return tag.rsplit("}", 1)[-1]


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


def write_reports(invoices, folder, basename, csv_only=True):
    """Ghi bảng kê dạng .csv (mở bằng Excel, đúng tiếng Việt). Trả về list đường dẫn."""
    os.makedirs(folder, exist_ok=True)
    csv_path = os.path.join(folder, basename + ".csv")
    with open(csv_path, "w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f)
        w.writerow([c[0] for c in COLUMNS])
        w.writerows(table_rows(invoices))
    return [csv_path]


# ---------------------------------------------------------------------------
# Xuất Excel theo mẫu Nibot: HoaDon_TongQuat, Smart_KTSC (import Smart Pro), BangKe_MuaVao,
# BangKe_MuaVao_KCT_HDBH, BangKe_HoanThue. Số liệu chi tiết đọc từ XML (chuẩn TT78).
# ---------------------------------------------------------------------------

LOAI_HD = {1: "V", 2: "B"}
TTHAI_NIBOT = {1: "HĐ Mới", 2: "HĐ Thay thế", 3: "HĐ Điều chỉnh", 4: "HĐ Đã bị thay thế",
               5: "HĐ Đã bị điều chỉnh", 6: "HĐ Đã bị hủy"}
TTXLY_NIBOT = {0: "TCT đã nhận", 1: "Đang k.tra", 2: "CQT t.chối HĐ", 3: "HĐ đủ đ.kiện", 4: "HĐ k đủ đ.kiện",
               5: "Đã cấp MST", 6: "TCT k nhận mã", 7: "Đã k.tra định kỳ", 8: "HĐ có mã từ máy tính tiền"}
VAT_RATES = {"0", "5", "8", "10"}
SMART_HEAD = ["NIBOT_GHICHU", "LCTG", "SR_HD", "SOCT", "NGAY_KY", "NGAYCT", "SO_HD", "NGAY_HD", "DIENGIAI",
              "HTTT", "TKNO", "MADTPNNO", "TKCO", "MADTPNCO", "MADMNO", "MADMCO", "TENDM", "MATHANG",
              "LUONG_CTU", "DONVI_CTU", "DONVI", "LUONG", "DGUSD", "TTUSD", "TYGIA", "DGVND", "TTVND", "PT_CK",
              "CHIETKHAU", "HDVAT", "TKTHUE", "TS_GTGT", "THUEUSD", "THUEVND", "TTUSD_TT", "TTVND_TT", "MAKH",
              "TENKH", "KHACHHANG", "DIACHI_NGD", "MS_DN", "DIACHI", "TK_XUATKHO", "ID_NGHIEPVU", "GHICHU", "GUID"]


def _float(v):
    try:
        return float(str(v).replace(",", ""))
    except (TypeError, ValueError):
        return 0.0


def norm_rate(v):
    """'8%' → '8'; 'KCT', 'KKKNT', 'KHAC:5.26%' giữ nguyên dạng chữ hoa."""
    s = str(v or "").strip().upper().replace(" ", "")
    if s.endswith("%"):
        s = s[:-1]
    try:
        f = float(s)
        return ("%g" % f)
    except ValueError:
        return s


def _to_dt(v):
    """Chuỗi ISO/dd-mm-yyyy → datetime (bỏ múi giờ)."""
    if not v:
        return None
    s = str(v)
    m = re.match(r"(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}):(\d{2}))?", s)
    if m:
        y, mo, d, hh, mm, ss = m.groups()
        dt = datetime(int(y), int(mo), int(d), int(hh or 0), int(mm or 0), int(ss or 0))
        if s.endswith("Z") or "+0000" in s or "+00:00" in s:
            dt += timedelta(hours=7)  # giờ Việt Nam
        return dt
    m = re.match(r"(\d{2})/(\d{2})/(\d{4})", s)
    if m:
        return datetime(int(m.group(3)), int(m.group(2)), int(m.group(1)))
    return None


# Nhà cung cấp giải pháp HĐĐT (MSTTCGP trong XML) → (tên, trang tra cứu hoá đơn gốc).
PROVIDERS = {
    "0101243150": ("MISA meInvoice", "https://www.meinvoice.vn/tra-cuu/?sc="),  # ?sc=<mã> mở thẳng hoá đơn
    "0105987432": ("EasyInvoice (SoftDreams)", "http://{nbmst}hd.easyinvoice.com.vn"),  # trang riêng từng người bán
    "0100109106": ("Viettel S-Invoice", "https://vinvoice.viettel.vn/utilities/invoice-search"),
    "0100684378": ("VNPT Invoice", ""),
    "0101360697": ("BKAV eHoadon", "https://van.ehoadon.vn/Lookup?InvoiceGUID="),
    "0100686209": ("MobiFone Invoice", "http://tracuuhoadon.mobifoneinvoice.vn/trang-chu"),
    "0101300842": ("Thái Sơn E-invoice", "https://einvoice.vn/tra-cuu"),
    "0100727825": ("FAST e-Invoice", "https://einvoice.fast.com.vn/"),
}
# Tên trường "mã tra cứu" trong phần thông tin khác (TTKhac) của XML, đã bỏ dấu, chữ thường, bỏ khoảng trắng.
LOOKUP_FIELDS = ("transactionid", "masobimat", "reservationcode", "matracuu", "fkey", "keysearch", "matc",
                 "searchkey", "magiaodich")


def _plain(text):
    import unicodedata
    t = unicodedata.normalize("NFD", str(text or "")).replace("đ", "d").replace("Đ", "D")
    return "".join(c for c in t if unicodedata.category(c) != "Mn").lower().replace(" ", "").replace("_", "")


def lookup_info(info):
    """{'ncc', 'url', 'field', 'code'} để tra cứu hoá đơn gốc trên trang của nhà cung cấp."""
    mst = info.get("msttcgp", "")
    name, url = PROVIDERS.get(mst.split("-")[0], ("", ""))
    field = code = ""
    for want in LOOKUP_FIELDS:
        for k, v in info.get("ttkhac", {}).items():
            if _plain(k) == want and v:
                field, code = k, v
                break
        if code:
            break
    if mst.startswith("0101360697") and not code:
        field, code = "InvoiceGUID", info.get("dlhdon_id", "")
    if url.endswith("=") and code:
        url += urllib.parse.quote(code)
    elif url.endswith("=") or "{nbmst}" in url:
        url = url.split("?")[0] if url.endswith("=") else url
    url = url.replace("{nbmst}", (info.get("nb") or {}).get("MST", "").split("-")[0])
    return {"ncc": name or (("MST " + mst) if mst else ""), "url": url, "field": field, "code": code,
            "ncc_mst": mst.split("-")[0]}


# ---------------------------------------------------------------------------
# Tải PDF gốc từ trang tra cứu của nhà cung cấp (bằng mã tra cứu trong XML)
# ---------------------------------------------------------------------------

def _http_get(url, timeout=30):
    req = urllib.request.Request(url, headers={"User-Agent": UA_LOGIN, "Accept": "*/*"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return resp.read()
    except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
        raise PortalError("Không kết nối được trang của nhà cung cấp: %s" % getattr(e, "reason", e))


MISA_WWW, MISA_APEX, MISA_DL = "https://www.meinvoice.vn", "https://meinvoice.vn", "https://download.meinvoice.vn"


def misa_pdf(code):
    """MISA meInvoice – tải PDF gốc bằng mã tra cứu, không cần tài khoản. Thử lần lượt:
    1) mở trang tra cứu ?sc=<mã> như trình duyệt (giữ cookie) và lấy link DownloadHandler.ashx có sẵn trong trang;
    2) lấy mã thời gian 'ext' từ GetRequestTimeEnCode (theo tài liệu MISA) rồi dựng link
       www.meinvoice.vn/tra-cuu/tra-cuu/DownloadHandler.ashx?Type=pdf&Viewer=1&ext=<ext>&Code=<mã>
       (dạng thấy trong trang tra cứu thật, ext có thể chứa '_' – vd J1V4E6D_) và download.meinvoice.vn."""
    import html as H
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    lookup = MISA_WWW + "/tra-cuu/?sc=" + urllib.parse.quote(code)

    def get(url, referer=lookup):
        req = urllib.request.Request(url, headers={"User-Agent": UA_LOGIN, "Accept": "*/*", "Referer": referer,
                                                   "Accept-Language": "vi-VN,vi;q=0.9"})
        try:
            with opener.open(req, timeout=30) as resp:
                return resp.read()
        except urllib.error.HTTPError as e:
            return e.read() or b""
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            raise PortalError("Không kết nối được meinvoice.vn: %s" % getattr(e, "reason", e))

    urls, diag = [], []
    page = get(lookup, referer=MISA_WWW + "/tra-cuu/").decode("utf-8", "replace")
    for link in re.findall(r"DownloadHandler\.ashx\?[^\"'<>\s]+", page, re.I):
        urls.append(urllib.parse.urljoin(MISA_WWW + "/tra-cuu/", "tra-cuu/" + H.unescape(link)))
    exts = []
    for src in (MISA_WWW + "/tra-cuu/GetRequestTimeEnCode", MISA_APEX + "/tra-cuu/GetRequestTimeEnCode"):
        raw = get(src).decode("utf-8", "replace")
        diag.append(re.sub(r"\s+", " ", raw)[:80])
        plain = re.sub(r"<[^>]+>", "", raw).strip().strip('"')
        if re.fullmatch(r"[\w-]{8}", plain):
            exts.append(plain)
        if len(raw) >= 63:
            exts.append(raw[55:63])  # cách lấy trong mã mẫu của MISA (Substring(55, 8))
        exts += re.findall(r"(?<![\w-])[\w-]{8}(?![\w-])", plain)[:3]
        if exts:
            break
    q = urllib.parse.quote(code)
    for ext in dict.fromkeys(exts):
        e = urllib.parse.quote(ext)
        urls.append(MISA_WWW + "/tra-cuu/tra-cuu/DownloadHandler.ashx?Type=pdf&Viewer=1&ext=%s&Code=%s" % (e, q))
        urls.append(MISA_DL + "/downloadhandler.ashx?type=pdf&code=%s&viewer=1&ext=%s" % (q, e))
    got = []
    for url in dict.fromkeys(urls):
        data = get(url)
        if data[:4] == b"%PDF":
            return data
        got.append(re.sub(r"\s+", " ", re.sub(rb"<[^>]+>", b" ", data[:600]).decode("utf-8", "replace")).strip()[:70])
    raise PortalError("MISA không trả file PDF cho mã tra cứu %s (đã thử %d đường dẫn; GetRequestTimeEnCode trả: %s; "
                      "trang tải trả: %s)" % (code, len(urls), " | ".join(diag) or "rỗng", " | ".join(got) or "rỗng"))


# MST nhà cung cấp → hàm tải PDF gốc. Nhà cung cấp có captcha ở trang tra cứu (Viettel, VNPT…) chưa tự động được.
PDF_FETCHERS = {"0101243150": misa_pdf}


def can_fetch_pdf(tra_cuu):
    t = tra_cuu or {}
    mst = t.get("ncc_mst") or ("0101243150" if str(t.get("ncc", "")).startswith("MISA") else "")
    return bool(t.get("code")) and mst in PDF_FETCHERS, mst


def fetch_original_pdf(out_root, mst, kind, key):
    """Tải PDF gốc của một hoá đơn trong kho rồi gắn vào hoá đơn. Trả về đường dẫn tương đối."""
    e = _load_json(index_file(out_root, mst), {}).get(kind, {}).get(key)
    if e is None:
        raise ValueError("Không tìm thấy hoá đơn")
    ok, prov = can_fetch_pdf(e.get("tra_cuu"))
    if not ok:
        raise ValueError("Chưa hỗ trợ tự tải PDF gốc của nhà cung cấp này – bấm 'Tra cứu' để tải tay rồi '+PDF'")
    return attach_pdf(out_root, mst, kind, key, PDF_FETCHERS[prov](e["tra_cuu"]["code"]))


def parse_invoice_xml(xml_path):
    """Đọc XML hoá đơn → {'items': [...], 'rates': [...], 'httt', 'nb_dchi', 'nm_dchi', 'nky'}."""
    import xml.etree.ElementTree as ET
    info = {"items": [], "rates": [], "httt": "", "nb_dchi": "", "nm_dchi": "", "nky": None, "msttcgp": "",
            "ttkhac": {}, "dlhdon_id": "", "nb": {}, "nm": {}, "ttchung": {}, "tong": {}}
    try:
        root = ET.parse(xml_path).getroot()
    except (ET.ParseError, OSError):
        return info

    def child(el, name):
        for c in el:
            if _local(c.tag) == name:
                return c
        return None

    def text(el, name):
        c = child(el, name) if el is not None else None
        return (c.text or "").strip() if c is not None and c.text else ""

    for el in root.iter():
        tag = _local(el.tag)
        if tag == "HTTToan" and not info["httt"]:
            info["httt"] = (el.text or "").strip()
        elif tag == "NBan":
            info["nb_dchi"] = info["nb_dchi"] or text(el, "DChi")
        elif tag == "NMua":
            info["nm_dchi"] = info["nm_dchi"] or text(el, "DChi")
        elif tag == "SigningTime" and not info["nky"]:
            info["nky"] = _to_dt(el.text)
        elif tag == "HHDVu":
            nature = text(el, "TChat")
            if nature == "4":  # dòng ghi chú/diễn giải
                continue
            amount = _float(text(el, "ThTien"))
            if nature == "3":  # chiết khấu thương mại giảm trừ
                amount = -abs(amount)
            rate = norm_rate(text(el, "TSuat"))
            tax_text = text(el, "TThue")
            if tax_text:
                tax = _float(tax_text)
            else:
                tax = round(amount * float(rate) / 100, 2) if rate in VAT_RATES else 0.0
            info["items"].append({
                "name": text(el, "THHDVu"), "unit": text(el, "DVTinh"), "qty": _float(text(el, "SLuong")),
                "price": _float(text(el, "DGia")), "amount": amount, "rate": rate, "tax": tax,
                "discount": _float(text(el, "STCKhau")), "nature": nature})
        elif tag == "LTSuat":
            info["rates"].append({"rate": norm_rate(text(el, "TSuat")), "base": _float(text(el, "ThTien")),
                                  "tax": _float(text(el, "TThue"))})
        elif tag == "MSTTCGP" and not info["msttcgp"]:
            info["msttcgp"] = (el.text or "").strip()
        elif tag == "TTin":
            k, v = text(el, "TTruong"), text(el, "DLieu")
            if k and v:
                info["ttkhac"].setdefault(k, v)
        elif tag == "DLHDon" and not info["dlhdon_id"]:
            info["dlhdon_id"] = el.get("Id", "")
        if tag in ("NBan", "NMua"):
            info["nb" if tag == "NBan" else "nm"] = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
        elif tag == "TTChung":
            info["ttchung"] = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
        elif tag == "TToan":
            info["tong"] = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
    return info


def invoice_lines(inv):
    """Danh sách dòng hàng của một hoá đơn; không có XML thì dựng từ tổng tiền của cổng."""
    info = inv.get("_xmlinfo") or {}
    if info.get("items"):
        return info["items"]
    rates = [{"rate": norm_rate(r.get("tsuat")), "base": _float(r.get("thtien")), "tax": _float(r.get("tthue"))}
             for r in (inv.get("thttltsuat") or []) if isinstance(r, dict)]
    if not rates:
        rates = [{"rate": "", "base": _float(inv.get("tgtcthue")), "tax": _float(inv.get("tgtthue"))}]
    return [{"name": "", "unit": "", "qty": 0.0, "price": 0.0, "amount": r["base"], "rate": r["rate"],
             "tax": r["tax"], "discount": 0.0, "nature": ""} for r in rates]


def rate_groups(inv):
    """Gom dòng hàng theo thuế suất (đúng cách lập bảng kê): [{rate, names, base, tax, items}]."""
    groups = {}
    for it in invoice_lines(inv):
        g = groups.setdefault(it["rate"], {"rate": it["rate"], "names": [], "base": 0.0, "tax": 0.0, "items": []})
        if it["name"]:
            g["names"].append(it["name"])
        g["base"] += it["amount"]
        g["tax"] += it["tax"]
        g["items"].append(it)
    # Tổng theo thuế suất trong XML (LTSuat) là số chính thức – ưu tiên dùng.
    official = {r["rate"]: r for r in (inv.get("_xmlinfo") or {}).get("rates", [])}
    for rate, g in groups.items():
        if rate in official:
            g["base"], g["tax"] = official[rate]["base"], official[rate]["tax"]
    if len(groups) == 1:
        g = next(iter(groups.values()))
        if not official and inv.get("tgtthue") is not None:
            g["base"], g["tax"] = _float(inv.get("tgtcthue")), _float(inv.get("tgtthue"))
    return list(groups.values())


def _status_note(inv):
    parts = []
    tthai = _num(inv.get("tthai"))
    if tthai != 1:
        parts.append(TTHAI_NIBOT.get(tthai, ""))
    if _num(inv.get("ttxly")) == 8 or inv.get("_prefix") == "/sco-query":
        parts.append(TTXLY_NIBOT[8])
    return " - ".join(p for p in parts if p)


def _is_bad(inv):
    """HĐ bị thay thế / bị huỷ: không đưa vào bảng kê, xếp vào nhóm cần xem xét."""
    return _num(inv.get("tthai")) in (4, 6)


def write_nibot_workbook(path, kind, invoices, mst, company_name, start, end, changes=None):
    from openpyxl import Workbook
    from openpyxl.styles import Alignment, Border, Font, PatternFill, Side
    from openpyxl.utils import get_column_letter

    thin = Side(style="thin", color="999999")
    box = Border(left=thin, right=thin, top=thin, bottom=thin)
    bold = Font(bold=True)
    head_fill = PatternFill("solid", fgColor="DDEBF7")
    wrap_c = Alignment(horizontal="center", vertical="center", wrap_text=True)
    money = "#,##0"
    dfmt = "dd/mm/yyyy"
    purchase = kind == "purchase"
    period = ("Tháng %d năm %d" % (start.month, start.year)
              if start.day == 1 and end == date(start.year, start.month, calendar.monthrange(start.year, start.month)[1])
              else "Từ ngày %s đến ngày %s" % (start.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y")))
    today = date.today()
    sign_date = "..............., ngày %02d tháng %02d năm %d" % (today.day, today.month, today.year)

    def header_row(ws, row, values, start_col=1):
        for i, v in enumerate(values):
            c = ws.cell(row=row, column=start_col + i, value=v)
            c.font, c.fill, c.alignment, c.border = bold, head_fill, wrap_c, box

    def widths(ws, ws_widths):
        for i, w in enumerate(ws_widths, 1):
            ws.column_dimensions[get_column_letter(i)].width = w

    wb = Workbook()

    # 1. HoaDon_TongQuat ---------------------------------------------------
    ws = wb.active
    ws.title = "HoaDon_TongQuat"
    ws["A1"] = "HÓA ĐƠN MUA VÀO" if purchase else "HÓA ĐƠN BÁN RA"
    ws["A1"].font = Font(bold=True, size=14)
    ws.merge_cells("A1:H1")
    ws["A2"] = "MST: %s" % mst
    ws["A3"] = "Tên DN: %s" % (company_name or "")
    heads = ["Loại HĐ", "MST người bán", "Người bán", "Địa chỉ người bán", "MST người mua", "Người Mua",
             "Địa chỉ người mua", "Ngày", "HTTT", "Ký hiệu", "Số", "Trạng thái HĐ", "Kết quả kiểm tra",
             "Tiền Chưa thuế", "Tiền Thuế", "Tiền CK TM", "Tiền Phí", "Tiền Thanh toán", "Duyệt nội bộ", "Ghi chú",
             "File XML"]
    header_row(ws, 4, heads)
    r = 5
    for inv in invoices:
        info = inv.get("_xmlinfo") or {}
        d = invoice_date(inv)
        row = [LOAI_HD.get(_num(inv.get("khmshdon")), str(inv.get("khmshdon") or "")),
               str(inv.get("nbmst") or ""), inv.get("nbten") or "", inv.get("nbdchi") or info.get("nb_dchi", ""),
               str(inv.get("nmmst") or ""), inv.get("nmten") or "", inv.get("nmdchi") or info.get("nm_dchi", ""),
               datetime(d.year, d.month, d.day) if d else None, inv.get("thtttoan") or info.get("httt", ""),
               "%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or ""), str(inv.get("shdon") or ""),
               TTHAI_NIBOT.get(_num(inv.get("tthai")), inv.get("tthai")),
               TTXLY_NIBOT.get(_num(inv.get("ttxly")), inv.get("ttxly")),
               _float(inv.get("tgtcthue")), _float(inv.get("tgtthue")), _float(inv.get("ttcktmai")),
               _float(inv.get("tgtphi")), _float(inv.get("tgtttbso")), inv.get("_duyet") or "Chờ duyệt",
               " - ".join(x for x in (_status_note(inv), inv.get("_note")) if x),
               inv.get("_xml", "")]
        for i, v in enumerate(row, 1):
            c = ws.cell(row=r, column=i, value=v)
            if i == 8:
                c.number_format = dfmt
            elif 14 <= i <= 18:
                c.number_format = money
        r += 1
    ws.cell(row=r, column=1, value="Total").font = bold
    if r > 5:
        ws.cell(row=r, column=11, value="=SUBTOTAL(103,K5:K%d)" % (r - 1)).font = bold
        for col in "NOPQR":
            c = ws["%s%d" % (col, r)]
            c.value, c.font, c.number_format = "=SUBTOTAL(109,%s5:%s%d)" % (col, col, r - 1), bold, money
    ws.auto_filter.ref = "A4:%s%d" % (get_column_letter(len(heads)), max(r - 1, 4))
    ws.freeze_panes = "A5"
    widths(ws, [7, 14, 36, 36, 14, 36, 36, 11, 14, 11, 9, 16, 18, 14, 13, 11, 10, 15, 11, 30, 40])

    if changes:
        wc = wb.create_sheet("Doi_TrangThai")
        header_row(wc, 1, CHANGE_HEAD)
        for row in changes:
            wc.append(row)
        widths(wc, [12, 12, 10, 14, 40, 20, 20])

    if not purchase:
        wi = wb.create_sheet("ChiTiet_HangHoa")
        header_row(wi, 1, ITEM_HEAD + ["Tên hàng hoá, dịch vụ", "ĐVT", "Số lượng", "Đơn giá", "Thành tiền",
                                       "Thuế suất", "Tiền thuế"])
        for inv in invoices:
            for it in invoice_lines(inv):
                wi.append(_inv_head(inv) + [it["name"], it["unit"], it["qty"], it["price"], it["amount"],
                                            it["rate"], it["tax"]])
        for row in wi.iter_rows(min_row=2):
            for idx in (10, 11, 13):
                row[idx].number_format = money
        widths(wi, [11, 11, 9, 14, 34, 14, 34, 40, 8, 9, 12, 14, 8, 12])
        wb.save(path)
        return path

    good = [i for i in invoices if not _is_bad(i)]
    bad = [i for i in invoices if _is_bad(i)]

    # 2. Smart_KTSC_OK / CAN_XEM_XET (mẫu import phần mềm kế toán Smart Pro) ------
    def smart_rows(inv, bad_inv):
        info = inv.get("_xmlinfo") or {}
        d = invoice_date(inv)
        ngay = datetime(d.year, d.month, d.day) if d else None
        total = _float(inv.get("tgtttbso"))
        big = total > 5000000
        sr = "%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or "")
        so = str(inv.get("shdon") or "")
        ngay_txt = d.strftime("%d/%m/%Y") if d else ""
        ghichu = []
        if big:
            ghichu.append("HĐ có giá trị > 5,000,000 đồng")
        note = _status_note(inv)
        if note:
            ghichu.append(note)
        lctg, tkco = ("PKT", "331") if big else ("PC", "1111")
        dien_giai = ("Chi phí theo hđơn ký hiệu số %s - %s - %s" if big else "Chi trả tiền theo hđơn số %s - %s - %s") % (
            sr, so, ngay_txt)
        nbmst, nbten = str(inv.get("nbmst") or ""), inv.get("nbten") or ""
        dchi = inv.get("nbdchi") or info.get("nb_dchi", "")
        guid = str(uuid.uuid5(uuid.NAMESPACE_URL, invoice_key(inv)))
        lines = invoice_lines(inv)
        if bad_inv:
            label = TTHAI_NIBOT.get(_num(inv.get("tthai")), "")
            lines = [{"name": label, "unit": "", "qty": 0.0, "price": 0.0, "amount": 0.0,
                      "rate": (lines[0]["rate"] if lines else ""), "tax": 0.0, "discount": 0.0}]
            ghichu, dien_giai, lctg = [label], "", ""
        out = []
        for it in lines:
            out.append(["; ".join(ghichu), lctg, sr, so, info.get("nky") or _to_dt(inv.get("nky")), ngay,
                        _num(so), ngay, dien_giai, inv.get("thtttoan") or info.get("httt", ""), "", "", tkco,
                        nbmst, "", "", it["name"], it["name"], 0, "", it["unit"], it["qty"], 0, 0, 0, it["price"],
                        it["amount"], 0, it.get("discount", 0.0), "V", "1331", it["rate"], 0, it["tax"], 0,
                        it["amount"] + it["tax"], nbmst, nbten, nbten, dchi, nbmst, dchi, "", "TIENHANG", "", guid])
        return out

    for title, src, is_bad in (("Smart_KTSC_OK", good, False), ("Smart_KTSC_CAN_XEM_XET", bad, True)):
        if is_bad and not src:
            continue
        wsm = wb.create_sheet(title)
        wsm.append(SMART_HEAD)
        for c in wsm[1]:
            c.font = bold
        for inv in src:
            for row in smart_rows(inv, is_bad):
                wsm.append(row)
        for row in wsm.iter_rows(min_row=2):
            for idx in (4, 5, 7):
                row[idx].number_format = dfmt
            for idx in (25, 26, 33, 35):
                row[idx].number_format = money
        widths(wsm, [28, 6, 11, 9, 18, 11, 9, 11, 40, 14, 6, 6, 6, 14, 6, 6, 30, 30] + [8] * 28)

    # 3. Bảng kê mua vào (kèm tờ khai 01/GTGT) ---------------------------------
    def bang_ke(title, heading, groups, with_tax):
        wsb = wb.create_sheet(title)
        last = "M"
        wsb["A4"] = heading
        wsb["A4"].font = Font(bold=True, size=13)
        wsb["A4"].alignment = Alignment(horizontal="center")
        wsb.merge_cells("A4:%s4" % last)
        wsb["B6"] = "(Kèm theo tờ khai thuế GTGT theo mẫu số 01/GTGT)"
        wsb.merge_cells("B6:%s6" % last)
        wsb["B7"] = "Kỳ tính thuế: %s" % period
        wsb.merge_cells("B7:%s7" % last)
        for cell in ("B6", "B7"):
            wsb[cell].alignment = Alignment(horizontal="center")
        wsb["B9"] = "Người nộp thuế: %s" % (company_name or "")
        wsb["B10"] = "Mã số thuế: %s" % mst
        wsb["B12"] = "Đơn vị tiền: đồng Việt Nam"
        header_row(wsb, 13, ["STT", "Hoá đơn, chứng từ, biên lai nộp thuế", "", "", "", "Tên người bán",
                             "Mã số thuế người bán", "Mặt hàng", "Doanh số mua chưa có thuế", "Thuế suất",
                             "Thuế GTGT\nđủ điều kiện khấu trừ thuế", "Ghi chú"], 2)
        header_row(wsb, 15, ["KH mẫu HĐ", "Ký hiệu hoá đơn", "Số hoá đơn", "Ngày, tháng, năm lập hóa đơn"], 3)
        for col in "BGHIJKLM":
            wsb.merge_cells("%s13:%s15" % (col, col))
        wsb.merge_cells("C13:F14")
        header_row(wsb, 16, ["[1]", "[2]", "[3]", "[4]", "[5]", "[6]", "[7]", "[8]", "[9]", "[10]", "[11]", "[12]"], 2)
        wsb["B17"] = ("1. HH, DV dùng riêng cho SXKD chịu thuế GTGT và sử dụng cho các hoạt động cung cấp HH, DV "
                      "không kê khai, nộp thuế GTGT đủ điều kiện khấu trừ thuế: ")
        wsb["B17"].font = bold
        r = 18
        for n, (inv, g) in enumerate(groups, 1):
            d = invoice_date(inv)
            vals = [n, _num(inv.get("khmshdon")), inv.get("khhdon") or "", _num(inv.get("shdon")),
                    datetime(d.year, d.month, d.day) if d else None, inv.get("nbten") or "",
                    str(inv.get("nbmst") or ""), g["names"][0] if g["names"] else "", g["base"],
                    g["rate"], g["tax"] if with_tax else None, _status_note(inv)]
            for i, v in enumerate(vals, 2):
                c = wsb.cell(row=r, column=i, value=v)
                c.border = box
                if i == 6:
                    c.number_format = dfmt
                elif i in (10, 12):
                    c.number_format = money
            r += 1
        wsb.cell(row=r, column=2, value="Tổng").font = bold
        if r > 18:
            for col in ("J", "L") if with_tax else ("J",):
                c = wsb["%s%d" % (col, r)]
                c.value, c.font, c.number_format = "=SUM(%s18:%s%d)" % (col, col, r - 1), bold, money
        total_row = r
        r += 1
        wsb.cell(row=r, column=2, value="2. HH, DV dùng chung cho SXKD chịu thuế và không chịu thuế đủ điều kiện "
                                         "khấu trừ thuế:").font = bold
        wsb.cell(row=r + 2, column=2, value="Tổng").font = bold
        wsb.cell(row=r + 3, column=2, value="3. HH, DV dùng cho dự án đầu tư đủ điều kiện được khấu trừ thuế (*):").font = bold
        wsb.cell(row=r + 5, column=2, value="Tổng").font = bold
        r += 7
        wsb.cell(row=r, column=2, value="Tổng giá trị HHDV mua vào phục vụ SXKD được khấu trừ thuế GTGT (**):")
        c = wsb.cell(row=r, column=10, value="=J%d" % total_row)
        c.number_format, c.font = money, bold
        if with_tax:
            wsb.cell(row=r + 1, column=2, value="Tổng số thuế GTGT của HHDV mua vào đủ điều kiện được khấu trừ (***):")
            c = wsb.cell(row=r + 1, column=12, value="=L%d" % total_row)
            c.number_format, c.font = money, bold
        r += 3
        for i, t in enumerate([sign_date, "NGƯỜI NỘP THUẾ hoặc", "ĐẠI DIỆN HỢP PHÁP CỦA NGƯỜI NỘP THUẾ",
                               " Ký tên, đóng dấu (ghi rõ họ tên và chức vụ)"]):
            c = wsb.cell(row=r + i, column=10, value=t)
            c.alignment = Alignment(horizontal="center")
            c.font = Font(bold=0 < i < 3, italic=i in (0, 3))
            wsb.merge_cells("J%d:M%d" % (r + i, r + i))
        wsb.freeze_panes = "I17"
        widths(wsb, [2, 6, 6, 10, 10, 11, 36, 14, 34, 15, 8, 14, 26])
        return wsb

    vat_groups, kct_groups = [], []
    for inv in good:
        for g in rate_groups(inv):
            if _num(inv.get("khmshdon")) == 1 and g["rate"] in VAT_RATES:
                vat_groups.append((inv, g))
            else:
                kct_groups.append((inv, g))
    bang_ke("BangKe_MuaVao", "BẢNG KÊ HOÁ ĐƠN, CHỨNG TỪ HÀNG HOÁ, DỊCH VỤ MUA VÀO", vat_groups, True)
    if kct_groups:
        bang_ke("BangKe_MuaVao_KCT_HDBH", "BẢNG KÊ HOÁ ĐƠN, CHỨNG TỪ HÀNG HOÁ, DỊCH VỤ MUA VÀO KHÔNG CHỊU THUẾ, "
                "HÓA ĐƠN BÁN HÀNG", kct_groups, False)

    # 4. Bảng kê hoàn thuế (kèm Giấy đề nghị hoàn trả) ---------------------------
    wsh = wb.create_sheet("BangKe_HoanThue_OK")
    wsh["B1"] = ("BẢNG KÊ HOÁ ĐƠN, CHỨNG TỪ HÀNG HOÁ, DỊCH VỤ MUA VÀO\n(Kèm theo Giấy đề nghị hoàn trả khoản thu "
                 "NSNN số            ngày      tháng     năm    )")
    wsh["B1"].font = Font(bold=True, size=12)
    wsh["B1"].alignment = wrap_c
    wsh.merge_cells("B1:P3")
    wsh["B4"] = "[01] Kỳ đề nghị hoàn thuế: %s" % period
    wsh["B4"].alignment = Alignment(horizontal="center")
    wsh.merge_cells("B4:P4")
    wsh["B7"] = "[02] Tên người nộp thuế: %s" % (company_name or "")
    wsh["B8"] = "[03] Mã số thuế: %s" % mst
    wsh["B9"] = "[04] Tên đại lý thuế (nếu có):"
    wsh["B10"] = "[05] Mã số thuế: "
    wsh["B11"] = "Đơn vị tiền: Đồng Việt Nam"
    header_row(wsh, 13, ["STT", "Hoá đơn, chứng từ nộp thuế", "", "", "", "Tên người bán", "Mã số thuế \nngười bán",
                         "Tên hàng hóa, dịch vụ ", "Đơn \nvị \ntính", "Số \nlượng", "Đơn giá",
                         "Giá trị HHDV\nmua vào chưa có thuế GTGT", "Thuế suất GTGT (%)", "Thuế GTGT", "Ghi chú"], 2)
    header_row(wsh, 15, ["Mẫu \nsố", "Ký hiệu", "Số", "Ngày, tháng, năm"], 3)
    for col in "BGHIJKLMNOP":
        wsh.merge_cells("%s13:%s15" % (col, col))
    wsh.merge_cells("C13:F14")
    header_row(wsh, 16, ["[%d]" % i for i in range(1, 16)], 2)
    r = 17
    for inv, g in vat_groups:
        d = invoice_date(inv)
        single = len(g["items"]) == 1 and g["items"][0]["name"]
        it = g["items"][0]
        vals = [r - 16, str(inv.get("khmshdon") or ""), inv.get("khhdon") or "", str(inv.get("shdon") or ""),
                datetime(d.year, d.month, d.day) if d else None, inv.get("nbten") or "", str(inv.get("nbmst") or ""),
                "| ".join(g["names"]), it["unit"] if single else None, it["qty"] if single else 0,
                it["price"] if single else 0, g["base"], g["rate"], g["tax"], _status_note(inv)]
        for i, v in enumerate(vals, 2):
            c = wsh.cell(row=r, column=i, value=v)
            c.border = box
            if i == 6:
                c.number_format = dfmt
            elif i in (12, 13, 15):
                c.number_format = money
        r += 1
    wsh.cell(row=r, column=5, value="TỔNG CỘNG").font = bold
    if r > 17:
        for col in "MO":
            c = wsh["%s%d" % (col, r)]
            c.value, c.font, c.number_format = "=SUM(%s17:%s%d)" % (col, col, r - 1), bold, money
    r += 2
    wsh.cell(row=r, column=4, value="Tôi cam đoan số liệu khai trên là đúng và chịu trách nhiệm trước pháp luật về "
                                    "số liệu đã khai./.").font = Font(italic=True)
    for i, (left, right) in enumerate([("", sign_date), ("NHÂN VIÊN ĐẠI LÝ THUẾ", "NGƯỜI NỘP THUẾ hoặc "),
                                       ("Họ và tên:.............................", "ĐẠI DIỆN HỢP PHÁP CỦA NGƯỜI NỘP THUẾ"),
                                       ("Chứng chỉ hành nghề số:......",
                                        "(Chữ ký, ghi rõ họ tên; chức vụ và đóng dấu (nếu có)/Ký điện tử)")]):
        row = r + 1 + i
        if left:
            wsh.cell(row=row, column=4, value=left)
        c = wsh.cell(row=row, column=11, value=right)
        c.alignment = Alignment(horizontal="center")
        wsh.merge_cells("K%d:O%d" % (row, row))
    widths(wsh, [2, 6, 6, 10, 10, 11, 36, 14, 40, 8, 8, 12, 15, 9, 13, 24])

    wb.save(path)
    return path


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


# Chữ ký M/Q/Z của 30 ký tự trong font captcha của cổng (đã kiểm chứng trên captcha thật; captcha không dùng
# I, L, O, U, 0, 1). Bảng giống bảng trong công cụ cộng đồng hddt-downloader-windows. Ký tự lạ vẫn tự học thêm.
CAPTCHA_SIGNATURES = {
    "MQQQQQZMQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQZMQQZ": "A",
    "MQQQQQQQQQZMQQQQQQZMQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQZMQQQQQQQQZMQQQQQQQQZ": "B",
    "MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQZ": "C",
    "MQQQQQQQQZMQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQZ": "D",
    "MQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "E",
    "MQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZ": "F",
    "MQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "G",
    "MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQZ": "H",
    "MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQZ": "J",
    "MQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZ": "K",
    "MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQZ": "M",
    "MQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQZ": "N",
    "MQQQQQQZMQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQZ": "P",
    "MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQZ": "Q",
    "MQQQQQQZMQQQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQZ": "R",
    "MQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "S",
    "MQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQZ": "T",
    "MQQQQQQQQQQZMQQQQQQQQQQQQQQQQZ": "V",
    "MQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "W",
    "MQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZ": "X",
    "MQQQQQQQQQZMQQQQQQQQQQQQQZ": "Y",
    "MQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZ": "Z",
    "MQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "2",
    "MQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "3",
    "MQQQQZMQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZMQQQQQZ": "4",
    "MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ": "5",
    "MQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQZ": "6",
    "MQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQZ": "7",
    "MQQQQQQQQZMQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQZMQQQQQQQZ": "8",
    "MQQQQQQQQZMQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQZ": "9",
}


def captcha_glyphs(svg):
    """Trả về [(chữ lệnh, toạ độ tương đối, x trái)] của các ký tự, xếp từ trái sang phải."""
    glyphs = []
    for m in _PATH_RE.finditer(svg or ""):
        attrs = dict(_ATTR_RE.findall(m.group(1)))
        d = attrs.get("d", "")
        letters = "".join(re.findall(r"[A-Za-z]", d)).upper()
        if "Z" not in letters:
            continue  # đường nhiễu là nét cong hở; ký tự là hình khép kín (có Z), dù tô màu hay chỉ vẽ viền
        letters = re.sub(r"[^MQZ]", "", letters)  # bỏ L (nét rung ngẫu nhiên), giữ chữ ký M/Q/Z của ký tự
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
            return sum(len(v) for v in self.templates.values()) + len(CAPTCHA_SIGNATURES)

    def chars(self):
        with self.lock:
            return sorted({t[0] for v in self.templates.values() for t in v} | set(CAPTCHA_SIGNATURES.values()))

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
                ch = self._match(letters, rel) or CAPTCHA_SIGNATURES.get(letters)
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

    def save_sample(self, svg, answer):
        """Lưu vài captcha gần nhất (ảnh + đáp án) vào _cau-hinh/captcha-mau/ để chẩn đoán khi không tự học được."""
        try:
            folder = os.path.join(os.path.dirname(self.path), "captcha-mau")
            os.makedirs(folder, exist_ok=True)
            files = sorted(os.listdir(folder))
            for old in files[:-19]:
                os.remove(os.path.join(folder, old))
            with open(os.path.join(folder, "%s_%s.svg" % (datetime.now().strftime("%Y%m%d%H%M%S%f"), safe_name(answer))),
                      "w", encoding="utf-8") as fh:
                fh.write(svg)
        except OSError:
            pass

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
        self.started = time.time()
        self.finished = None
        self.auth = ""          # trạng thái chứng thực tài khoản
        self.found = {}         # {'purchase': n, 'sold': n}
        self.rows = []          # kết quả đồng bộ từng hoá đơn
        self.phase = "chờ đồng bộ"

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
                    "need_captcha": self.need_captcha, "auth": self.auth, "found": dict(self.found),
                    "rows": self.rows[-1500:], "phase": self.phase,
                    "elapsed": round((self.finished or time.time()) - self.started, 1)}


def login_company(job, client, company, solver):
    """Đăng nhập một doanh nghiệp: tự giải captcha nếu đã học, không thì hỏi người dùng.
    Sai mật khẩu → dừng ngay (không thử lại để tránh bị khoá tài khoản)."""
    if client.token_valid() and client.username == company["mst"]:
        job.auth = "thành công (dùng lại phiên đăng nhập)"
        job.say("%s: dùng lại phiên đăng nhập còn hạn" % company["mst"])
        return
    password = unprotect(company.get("pw"))
    if not password:
        raise PortalError("Chưa nhập mật khẩu hoadondientu.gdt.gov.vn")
    job.auth = "đang đăng nhập"
    auto_failed = False
    for _ in range(4):
        cap = client.get_captcha()
        text = None if auto_failed else solver.solve(cap["content"])
        auto = text is not None
        if not auto:
            glyphs = captcha_glyphs(cap["content"])
            unknown = sum(1 for g in glyphs if g[0] not in solver.templates)
            job.say("%s: cần nhập captcha (ảnh có %d ký tự, %d ký tự chưa học)" % (company["mst"], len(glyphs), unknown))
            job.auth = "chờ nhập captcha"
            text = job.ask_captcha(company, cap["content"]).upper()  # captcha chỉ gồm A-Z, 0-9
            solver.save_sample(cap["content"], text)
        try:
            client.login(company["mst"], password, text, cap["key"])
        except PortalError as e:
            if "captcha" in str(e).lower():
                if auto:
                    solver.forget(cap["content"])
                    auto_failed = True  # tự giải sai → lần sau hỏi người dùng
                job.say("%s: captcha sai, thử lại" % company["mst"])
                continue
            raise
        if not auto and not solver.learn(cap["content"], text):
            job.say("Không học được captcha này (số ký tự trong ảnh khác số ký tự đã gõ) – đã lưu mẫu để kiểm tra")
        job.auth = "thành công" + (" (tự giải captcha)" if auto else "")
        job.say("%s: đăng nhập thành công%s" % (company["mst"], " (tự giải captcha)" if auto else ""))
        return
    raise PortalError("Nhập sai captcha quá nhiều lần")


def sync_kind(job, client, company, kind, start, end, include_mtt, want_xml, out_root):
    mst = company["mst"]
    label = "mua-vao" if kind == "purchase" else "ban-ra"
    period = "%s_%s" % (start.strftime("%Y%m%d"), end.strftime("%Y%m%d"))
    base = os.path.join(out_root, safe_name(mst))
    folder = os.path.join(base, "%s_%s" % (label, period))
    known = _load_json(index_file(out_root, mst), {}).get(kind, {})

    job.phase = "đang tra danh sách hoá đơn"
    invoices = client.list_invoices(kind, start, end, include_mtt, progress=lambda m: job.say("%s: %s" % (mst, m)))
    loai = "Mua vào" if kind == "purchase" else "Bán ra"
    with job.lock:
        job.found[kind] = len(invoices)
    job.phase = "đang đồng bộ hoá đơn"
    new, changes, todo, status = 0, [], [], {}
    xml_dir = os.path.join(folder, "xml")
    for inv in invoices:
        key = invoice_key(inv)
        old = known.get(key)
        tthai = _num(inv.get("tthai"))
        if old is None:
            new += 1
            status[key] = "Mới"
        elif old.get("tthai") != tthai:
            status[key] = "Đổi trạng thái"
            changes.append(_inv_head(inv)[:5] + [TTHAI_LABELS.get(old.get("tthai"), old.get("tthai")),
                                                 TTHAI_LABELS.get(tthai, tthai)])
            job.say("%s: HĐ %s/%s đổi trạng thái → %s" % (mst, inv.get("khhdon"), inv.get("shdon"),
                                                          TTHAI_LABELS.get(tthai, tthai)))
        else:
            status[key] = "Đã có"
        if want_xml:
            existing = os.path.join(xml_dir, invoice_basename(inv) + ".xml")
            if (old or {}).get("no_xml") and status[key] != "Đổi trạng thái":
                inv["_noxml"] = True  # đã biết cổng không có XML gốc của HĐ này
            elif os.path.exists(existing) and status[key] != "Đổi trạng thái":
                inv["_xml"] = os.path.relpath(existing, folder)
                inv["_xmlinfo"] = parse_invoice_xml(existing)
            else:
                todo.append(inv)
    errors = {}
    with job.lock:
        job.total += len(todo)

    def fetch(inv):
        if job.cancel:
            return
        try:
            path = save_xml(client.export_xml(inv), xml_dir, invoice_basename(inv))
            inv["_xml"] = os.path.relpath(path, folder)
            inv["_xmlinfo"] = parse_invoice_xml(path)
        except PortalError as e:
            if NO_XML_MSG in _plain(str(e)):
                inv["_noxml"] = True  # HĐ không mã: cổng chỉ có dữ liệu tổng hợp, không có file XML gốc
            else:
                inv["_xml"] = "Lỗi: %s" % e
                errors[invoice_key(inv)] = str(e)
        with job.lock:
            job.done += 1

    if todo:
        # Tải XML song song (vừa phải để cổng không chặn).
        from concurrent.futures import ThreadPoolExecutor
        with ThreadPoolExecutor(max_workers=XML_WORKERS) as pool:
            list(pool.map(fetch, todo))
    if job.cancel:
        raise Cancelled()
    if any("hết hạn" in m for m in errors.values()):
        raise PortalError("Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.")
    for key, msg in errors.items():
        job.say("%s: không tải được XML %s: %s" % (mst, key.split("|", 2)[-1].replace("|", "/"), msg))

    rows = []
    for inv in invoices:
        key = invoice_key(inv)
        d = invoice_date(inv)
        rows.append({"loai": loai, "ngay": d.strftime("%d/%m/%Y") if d else "", "mau": str(inv.get("khmshdon") or ""),
                     "kh": inv.get("khhdon") or "", "so": str(inv.get("shdon") or ""), "dongbo": status[key],
                     "ketqua": ("Lỗi XML: " + errors[key]) if key in errors else
                     ("OK (HĐ không có XML gốc)" if inv.get("_noxml") else "OK")})
        old = known.get(key)
        tthai = _num(inv.get("tthai"))
        entry = dict(old or {})
        inv["_kind"] = kind
        entry.update(tthai=tthai, tdlap=inv.get("tdlap"),
                     inv={k: inv.get(k) for k in INV_FIELDS if inv.get(k) is not None})
        if inv.get("_noxml"):
            entry["no_xml"] = True
        if inv.get("_xmlinfo") is not None:
            entry["xml"] = os.path.relpath(os.path.join(folder, inv["_xml"]), base)
            names = [it["name"] for it in inv["_xmlinfo"]["items"] if it["name"]]
            entry["mat_hang"] = "; ".join(names)[:500]
            entry["tra_cuu"] = lookup_info(inv["_xmlinfo"])
        known[key] = entry
    save_index_kind(out_root, mst, kind, known)
    if getattr(job, "want_pdf", False):
        by_key = {invoice_key(i): r for i, r in zip(invoices, rows)}
        base_dir = company_dir(out_root, mst)
        todo = [k for k in by_key if can_fetch_pdf(known[k].get("tra_cuu"))[0] and not (
            known[k].get("pdf") and os.path.exists(os.path.join(base_dir, known[k]["pdf"])))]
        if todo:
            job.say("%s: tải PDF gốc %d hoá đơn" % (mst, len(todo)))
        for k in todo:
            if job.cancel:
                raise Cancelled()
            try:
                fetch_original_pdf(out_root, mst, kind, k)
                by_key[k]["ketqua"] += " + PDF gốc"
            except (PortalError, ValueError) as e:
                by_key[k]["ketqua"] += " (PDF gốc: lỗi)"
                job.say("%s: không tải được PDF gốc %s: %s" % (mst, k.split("|", 2)[-1].replace("|", "/"), e))
            time.sleep(0.3)
    with job.lock:
        job.rows.extend(rows)

    paths = write_reports(invoices, folder, "bang-ke-%s_%s" % (label, period), csv_only=True)
    try:
        xlsx = os.path.join(folder, "%s_%s_%s.xlsx" % ("MUA_VAO" if kind == "purchase" else "BAN_RA", mst, period))
        paths.append(write_nibot_workbook(xlsx, kind, invoices, mst, company.get("ten"), start, end, changes))
    except ImportError:
        job.say("Chưa cài openpyxl nên chỉ xuất CSV (chạy: pip install openpyxl)")
    with job.lock:
        job.files.extend(os.path.abspath(p) for p in paths)
        job.results.append({"mst": mst, "ten": company.get("ten"), "loai": label, "so_hd": len(invoices),
                            "moi": new, "doi": len(changes), "thu_muc": os.path.abspath(folder)})
    job.say("%s: %s %d HĐ (%d mới, %d đổi trạng thái)" % (mst, label, len(invoices), new, len(changes)))
    return len(invoices), new, len(known)


def run_batch(job, store, solver, msts, kinds, start, end, include_mtt, want_xml, out_root,
              test_only=False, client_factory=None, clients=None):
    """clients: dict MST → HoaDonClient để giữ phiên đăng nhập giữa các lần đồng bộ (không phải nhập lại captcha)."""
    client_factory = client_factory or HoaDonClient
    clients = {} if clients is None else clients
    try:
        for mst in msts:
            if job.cancel:
                break
            company = store.get(mst)
            if not company:
                continue
            with job.lock:
                job.current = mst
            client = clients.get(mst)
            if client is None:
                client = clients[mst] = client_factory()
            try:
                job.phase = "đang chứng thực tài khoản"
                login_company(job, client, company, solver)
                store.update(mst, lambda c: c.update(loi=""))
                if test_only:
                    continue
                for kind in kinds:
                    if not company.get("vao" if kind == "purchase" else "ra", True):
                        continue
                    try:
                        total, new, known = sync_kind(job, client, company, kind, start, end, include_mtt,
                                                      want_xml, out_root)
                    except PortalError as e:
                        if "hết hạn" not in str(e):
                            raise
                        job.say("%s: phiên hết hạn, đăng nhập lại" % mst)
                        client.token = None
                        login_company(job, client, company, solver)
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
                if not job.auth.startswith("thành công"):
                    job.auth = "thất bại – " + msg
                job.say("%s: LỖI %s" % (mst, msg))
                store.update(mst, lambda c: c.update(loi=msg))
        job.say("Hoàn tất.")
        job.phase = "đồng bộ xong" if not job.cancel else "đã dừng"
    finally:
        with job.lock:
            job.finished = time.time()
            if job.phase not in ("đồng bộ xong", "đã dừng"):
                job.phase = "lỗi"
            job.running = False
            job.current = None
            job.need_captcha = None


# ---------------------------------------------------------------------------
# Kho hoá đơn đã tải (HoaDon/<MST>/_chi-muc.json): xem, lọc, ghi chú, duyệt, kết xuất lại
# ---------------------------------------------------------------------------

INV_FIELDS = ("khmshdon", "khhdon", "shdon", "tdlap", "nky", "nbmst", "nbten", "nbdchi", "nmmst", "nmten", "nmdchi",
              "thtttoan", "tgtcthue", "tgtthue", "ttcktmai", "tgtphi", "tgtttbso", "dvtte", "tthai", "ttxly",
              "mhdon", "thttltsuat", "_prefix", "_nguon", "_kind")
USER_FIELDS = ("note", "duyet", "dv", "pdf")
DUYET_OPTIONS = ("Chờ duyệt", "Đã duyệt", "Không duyệt")
_INDEX_LOCK = threading.Lock()


def company_dir(out_root, mst):
    return os.path.join(out_root, safe_name(mst))


def index_file(out_root, mst):
    return os.path.join(company_dir(out_root, mst), "_chi-muc.json")


def save_index_kind(out_root, mst, kind, entries):
    """Ghi lại một loại HĐ của chỉ mục, giữ ghi chú/duyệt do người dùng sửa trong lúc đang đồng bộ."""
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        old = cur.get(kind, {})
        for key, e in entries.items():
            for f in USER_FIELDS:
                if f in old.get(key, {}):
                    e[f] = old[key][f]
        cur[kind] = entries
        _save_json(path, cur)


def update_invoice(out_root, mst, kind, key, **fields):
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        e = cur.get(kind, {}).get(key)
        if e is None:
            raise ValueError("Không tìm thấy hoá đơn")
        for f in USER_FIELDS:
            if fields.get(f) is not None:
                e[f] = fields[f]
        _save_json(path, cur)


def attach_pdf(out_root, mst, kind, key, data):
    """Gắn file PDF gốc (người dùng tải từ trang tra cứu) vào hoá đơn."""
    if not data.startswith(b"%PDF"):
        raise ValueError("File không phải PDF")
    base = company_dir(out_root, mst)
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        e = cur.get(kind, {}).get(key)
        if e is None:
            raise ValueError("Không tìm thấy hoá đơn")
        if e.get("xml"):
            rel = os.path.splitext(e["xml"])[0] + ".pdf"
        else:
            rel = os.path.join("pdf", invoice_basename(e.get("inv") or {}) + ".pdf")
        os.makedirs(os.path.dirname(os.path.join(base, rel)), exist_ok=True)
        with open(os.path.join(base, rel), "wb") as fh:
            fh.write(data)
        e["pdf"] = rel
        _save_json(path, cur)
    return rel


def _pdf_text(data):
    import pypdf
    try:
        r = pypdf.PdfReader(io.BytesIO(data))
        return "\n".join((p.extract_text() or "") for p in r.pages[:3])
    except Exception as e:
        raise ValueError("Không đọc được PDF: %s" % e)


def match_pdf_text(text, entries_by_kind):
    """Tìm hoá đơn trong kho khớp với nội dung PDF: cùng ký hiệu, MST người bán/mua có trong PDF, và số hoá đơn có
    trong PDF. Chấm điểm số hoá đơn: đứng sau nhãn "Số:"/"Số (No.):" hoặc trước "(No)" = 4; in dạng 0000xxxx = 3; số dài ≥ 6
    chữ số = 2; số ngắn bất kỳ = 1 (dễ trùng số nhà, số tiền). Chỉ trả về hoá đơn có điểm cao nhất duy nhất."""
    flat = re.sub(r"\s+", " ", text or "")
    # Số có dấu chấm/phẩy ngăn cách (số tiền 308.448) không được tách thành 308 và 448.
    tokens = [t for t in re.findall(r"\d[\d.,]*\d|\d", flat) if t.isdigit() and len(t) <= 10]
    score = {}
    for tkn in tokens:
        n = int(tkn)
        s_ = 3 if (tkn.startswith("0") and len(tkn) >= 7) else (2 if len(tkn) >= 6 else 1)
        score[n] = max(score.get(n, 0), s_)
    for m in re.finditer(r"Số\s*(?:\(No\.?\)\s*:?|:)\s*0*(\d{1,10})(?![\d.,]\d)|(?<![\d.,])(\d{1,10})\s*\(No\)", flat):
        n = int(m.group(1) or m.group(2))
        score[n] = 4  # đúng vị trí nhãn số hoá đơn – hơn số nhắc tới trong nội dung (vd HĐ bị điều chỉnh)
    msts = set(re.findall(r"(?<!\d)(\d{10}(?:-\d{3})?)(?!\d)", flat))
    msts |= {re.sub(r"\s", "", m) for m in re.findall(r"(?<!\d)((?:\d ){9}\d)(?!\d)", flat)}
    msts |= {m.split("-")[0] for m in msts}
    khs = {k[-6:] for k in re.findall(r"(?<![A-Z0-9])[12]?[CK]\d{2}[A-Z]{2,3}(?![A-Z0-9])", flat)}
    found = []
    for kind, entries in entries_by_kind.items():
        for key in entries:
            nbmst, _, khhdon, shdon = (key.split("|") + ["", "", "", ""])[:4]
            sc = score.get(_num(shdon), 0)
            if str(khhdon)[-6:] not in khs or not sc:
                continue
            inv = entries[key].get("inv") or {}
            parties = {p for p in (str(nbmst), str(inv.get("nmmst") or "")) if p}
            if parties & msts or {p.split("-")[0] for p in parties} & msts:
                found.append((sc, kind, key))
    if not found:
        return []
    best = max(f[0] for f in found)
    return [(k, key) for sc, k, key in found if sc == best]


def import_pdfs(out_root, mst, files):
    """Gắn hàng loạt PDF gốc có sẵn trên máy vào đúng hoá đơn (đọc ký hiệu, số, MST trong PDF)."""
    idx = _load_json(index_file(out_root, mst), {})
    results = []
    for name, data in files:
        try:
            if not data.startswith(b"%PDF"):
                raise ValueError("không phải file PDF")
            hits = match_pdf_text(_pdf_text(data), {k: idx.get(k, {}) for k in ("purchase", "sold")})
            if not hits:
                raise ValueError("không khớp hoá đơn nào trong kho (đã đồng bộ kỳ này chưa?)")
            if len(hits) > 1:
                raise ValueError("khớp %d hoá đơn, không chắc gắn vào đâu" % len(hits))
            kind, key = hits[0]
            attach_pdf(out_root, mst, kind, key, data)
            nb, _, kh, so = key.split("|")
            results.append({"file": name, "ok": True, "hd": "%s %s – %s" % (
                "Mua vào" if kind == "purchase" else "Bán ra", kh, so)})
        except ValueError as e:
            results.append({"file": name, "ok": False, "loi": str(e)})
    return results


def render_invoice_html(out_root, mst, kind, key):
    """Bản thể hiện hoá đơn dựng từ XML (in hoặc lưu PDF từ trình duyệt)."""
    import html as H
    e = _load_json(index_file(out_root, mst), {}).get(kind, {}).get(key)
    if e is None:
        raise ValueError("Không tìm thấy hoá đơn")
    inv = e.get("inv") or {}
    xml_path = os.path.join(company_dir(out_root, mst), e["xml"]) if e.get("xml") else ""
    if xml_path and os.path.exists(xml_path):
        try:
            with open(xml_path, "rb") as fh:
                return render_xml_invoice(fh.read())
        except ValueError:
            pass
    info = parse_invoice_xml(xml_path) if xml_path else {}
    nb, nm, tc, tong = info.get("nb", {}), info.get("nm", {}), info.get("ttchung", {}), info.get("tong", {})
    d = invoice_date(inv)
    m = lambda v: "{:,.0f}".format(_float(v)).replace(",", ".")
    esc = lambda v: H.escape(str(v or ""))
    rows = "".join("<tr><td>%d</td><td>%s</td><td>%s</td><td class=n>%s</td><td class=n>%s</td><td class=n>%s</td>"
                   "<td class=n>%s</td></tr>" % (i, esc(it["name"]), esc(it["unit"]), ("%g" % it["qty"]) if it["qty"] else "",
                                                 m(it["price"]) if it["price"] else "", esc(it["rate"]), m(it["amount"]))
                   for i, it in enumerate(invoice_lines(dict(inv, _xmlinfo=info)), 1))
    tcu = e.get("tra_cuu") or {}
    party = lambda title, p, mst_, ten, dchi: (
        "<div class=box><b>%s</b><br>Tên: %s<br>MST: %s<br>Địa chỉ: %s</div>" % (title, esc(p.get("Ten") or ten),
                                                                          esc(p.get("MST") or mst_), esc(p.get("DChi") or dchi)))
    return """<!doctype html><html lang=vi><head><meta charset=utf-8><title>HĐ %s-%s</title><style>
body{font:14px/1.5 Arial,sans-serif;color:#111;max-width:900px;margin:20px auto;padding:0 16px}h1{text-align:center;font-size:20px;margin:4px}
.c{text-align:center}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:14px 0}.box{border:1px solid #999;padding:8px}
table{border-collapse:collapse;width:100%%}td,th{border:1px solid #999;padding:5px}.n{text-align:right}.mute{color:#666;font-size:12px}
@media print{.np{display:none}}</style></head><body>
<p class=np><button onclick="print()">In / Lưu PDF</button> <span class=mute>Bản thể hiện dựng từ XML – không thay thế bản PDF gốc của người bán.</span></p>
<h1>%s</h1><p class=c>Ký hiệu: <b>%s%s</b> &nbsp; Số: <b>%s</b> &nbsp; Ngày: <b>%s</b><br>Mã CQT: %s</p>
<div class=grid>%s%s</div><p>Hình thức thanh toán: %s</p>
<table><tr><th>STT</th><th>Tên hàng hoá, dịch vụ</th><th>ĐVT</th><th>SL</th><th>Đơn giá</th><th>TS</th><th>Thành tiền</th></tr>%s
<tr><td colspan=6 class=n>Cộng tiền hàng</td><td class=n>%s</td></tr><tr><td colspan=6 class=n>Tiền thuế GTGT</td><td class=n>%s</td></tr>
<tr><td colspan=6 class=n><b>Tổng tiền thanh toán</b></td><td class=n><b>%s</b></td></tr></table>
<p>Số tiền viết bằng chữ: %s</p><p class=mute>Nhà cung cấp HĐĐT: %s %s</p></body></html>""" % (
        esc(inv.get("khhdon")), esc(inv.get("shdon")), esc(tc.get("THDon") or "HÓA ĐƠN"),
        esc(inv.get("khmshdon")), esc(inv.get("khhdon")), esc(inv.get("shdon")), d.strftime("%d/%m/%Y") if d else "",
        esc(inv.get("mhdon")), party("Người bán", nb, inv.get("nbmst"), inv.get("nbten"), inv.get("nbdchi")),
        party("Người mua", nm, inv.get("nmmst"), inv.get("nmten"), inv.get("nmdchi")),
        esc(tc.get("HTTToan") or inv.get("thtttoan")), rows, m(tong.get("TgTCThue") or inv.get("tgtcthue")),
        m(tong.get("TgTThue") or inv.get("tgtthue")), m(tong.get("TgTTTBSo") or inv.get("tgtttbso")),
        esc(tong.get("TgTTTBChu")), esc(tcu.get("ncc")),
        ("– %s: %s" % (esc(tcu.get("field")), esc(tcu.get("code")))) if tcu.get("code") else "")


def _sibling(base, rel, ext):
    if not rel:
        return ""
    cand = os.path.splitext(rel)[0] + ext
    return cand if os.path.exists(os.path.join(base, cand)) else ""


def query_invoices(out_root, mst, f):
    """Lọc hoá đơn trong kho theo bộ lọc của màn hình Hoá đơn. Trả về list dòng (dict)."""
    kind = f.get("kind") or "purchase"
    only_dv = kind.endswith("_dv")
    kind = kind.replace("_dv", "")
    base = company_dir(out_root, mst)
    entries = _load_json(index_file(out_root, mst), {}).get(kind, {})
    start = parse_date(f["from"]) if f.get("from") else None
    end = parse_date(f["to"]) if f.get("to") else None
    q = (f.get("q") or "").strip().lower()
    only = set(f.get("keys") or [])  # các dòng người dùng đã tích chọn
    rows = []
    for key, e in entries.items():
        if only and key not in only:
            continue
        inv = e.get("inv") or {}
        d = invoice_date(inv) or invoice_date({"tdlap": e.get("tdlap")})
        if (start and (not d or d < start)) or (end and (not d or d > end)):
            continue
        if only_dv and not e.get("dv"):
            continue
        xml = e.get("xml", "") if e.get("xml") and os.path.exists(os.path.join(base, e["xml"])) else ""
        html, pdf = _sibling(base, xml, ".html"), _sibling(base, xml, ".pdf")
        file_f = f.get("file") or ""
        if not pdf and e.get("pdf") and os.path.exists(os.path.join(base, e["pdf"])):
            pdf = e["pdf"]
        if (file_f == "no_xml" and xml) or (file_f == "xml" and not xml) or (file_f == "pdf" and not pdf) \
                or (file_f == "no_pdf" and pdf) or (file_f == "xml_pdf" and not (xml and pdf)):
            continue
        duyet = e.get("duyet") or DUYET_OPTIONS[0]
        if f.get("duyet") and f["duyet"] != duyet:
            continue
        tthai, ttxly = _num(e.get("tthai")), _num(inv.get("ttxly"))
        if f.get("tthai") not in (None, "") and _num(f["tthai"]) != tthai:
            continue
        if f.get("kq") not in (None, "") and _num(f["kq"]) != ttxly:
            continue
        if f.get("khhdon") and f["khhdon"].upper() not in ("%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or "")).upper():
            continue
        if f.get("shdon") and str(f["shdon"]).strip() != str(inv.get("shdon") or ""):
            continue
        mst_dt, ten_dt = ((inv.get("nbmst"), inv.get("nbten")) if kind == "purchase"
                          else (inv.get("nmmst"), inv.get("nmten")))
        if q and q not in " ".join(str(x or "") for x in (mst_dt, ten_dt, e.get("note"), e.get("mat_hang"))).lower():
            continue
        rows.append({
            "key": key, "mst": str(mst_dt or ""), "ten": ten_dt or "", "ngay": d.strftime("%d/%m/%Y") if d else "",
            "_sort": (d.isoformat() if d else "", _num(inv.get("shdon"))),
            "khhdon": "%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or ""), "shdon": str(inv.get("shdon") or ""),
            "cthue": _float(inv.get("tgtcthue")), "thue": _float(inv.get("tgtthue")), "ck": _float(inv.get("ttcktmai")),
            "phi": _float(inv.get("tgtphi")), "tt": _float(inv.get("tgtttbso")),
            "tthai": TTHAI_NIBOT.get(tthai, ""), "kq": TTXLY_NIBOT.get(ttxly, "") if inv else "",
            "duyet": duyet, "dv": bool(e.get("dv")), "mat_hang": e.get("mat_hang", ""), "note": e.get("note", ""),
            "xml": xml, "html": html, "pdf": pdf or (e.get("pdf") if e.get("pdf") and
                                                     os.path.exists(os.path.join(base, e["pdf"])) else ""),
            "tra_cuu": e.get("tra_cuu") or {}})
    rows.sort(key=lambda r: r.pop("_sort"))
    return rows


def export_invoices(out_root, mst, company_name, f, fmt):
    """Kết xuất các HĐ đang lọc: 'xlsx' (mẫu Nibot) hoặc 'xml' / 'html' / 'pdf' (gói .zip). Trả về đường dẫn."""
    kind = (f.get("kind") or "purchase").replace("_dv", "")
    rows = query_invoices(out_root, mst, f)
    if not rows:
        raise ValueError("Không có hoá đơn nào để kết xuất")
    base = company_dir(out_root, mst)
    entries = _load_json(index_file(out_root, mst), {}).get(kind, {})
    out_dir = os.path.join(base, "_ket-xuat")
    os.makedirs(out_dir, exist_ok=True)
    label = "MUA_VAO" if kind == "purchase" else "BAN_RA"
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    if fmt == "xlsx":
        invoices = []
        for r in rows:
            e = entries[r["key"]]
            inv = dict(e.get("inv") or {})
            inv.setdefault("tthai", e.get("tthai"))
            inv.setdefault("tdlap", e.get("tdlap"))
            inv["_duyet"], inv["_note"] = r["duyet"], r["note"]
            if r["xml"]:
                inv["_xml"] = r["xml"]
                inv["_xmlinfo"] = parse_invoice_xml(os.path.join(base, r["xml"]))
            invoices.append(inv)
        dates = [invoice_date(i) for i in invoices if invoice_date(i)]
        start = parse_date(f["from"]) if f.get("from") else min(dates)
        end = parse_date(f["to"]) if f.get("to") else max(dates)
        path = os.path.join(out_dir, "%s_%s_%s.xlsx" % (label, mst, stamp))
        return write_nibot_workbook(path, kind, invoices, mst, company_name, start, end)
    if fmt not in ("xml", "html", "pdf"):
        raise ValueError("Định dạng kết xuất không hợp lệ")
    files = [r[fmt] for r in rows if r[fmt]]
    if not files:
        raise ValueError("Các hoá đơn đang lọc chưa có file %s" % fmt.upper())
    path = os.path.join(out_dir, "%s_%s_%s_%s.zip" % (label, fmt.upper(), mst, stamp))
    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
        for rel in files:
            z.write(os.path.join(base, rel), os.path.basename(rel))
    return path


# ---------------------------------------------------------------------------
# Tiện ích: đọc hoá đơn XML, kiểm tra MST hàng loạt, nối / tách file PDF
# ---------------------------------------------------------------------------

def read_invoice_xml(xml_bytes):
    """Đọc toàn bộ nội dung hiển thị của một hoá đơn XML (chuẩn TT78/ND123)."""
    import xml.etree.ElementTree as ET
    try:
        root = ET.fromstring(xml_bytes)
    except ET.ParseError as e:
        raise ValueError("File không phải XML hoá đơn hợp lệ (%s)" % e)

    def find(el, name):
        for c in el.iter():
            if _local(c.tag) == name:
                return c
        return None

    def flat(el):
        return {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0} if el is not None else {}

    dl = find(root, "DLHDon")
    if dl is None:
        raise ValueError("Không thấy phần dữ liệu hoá đơn (DLHDon) trong file XML")
    tc, nd = find(dl, "TTChung"), find(dl, "NDHDon")
    nb, nm, tt = (find(nd, n) if nd is not None else None for n in ("NBan", "NMua", "TToan"))
    items = []
    for el in dl.iter():
        if _local(el.tag) == "HHDVu":
            f = flat(el)
            items.append({k: f.get(k, "") for k in ("STT", "TChat", "THHDVu", "DVTinh", "SLuong", "DGia", "TLCKhau",
                                                    "STCKhau", "TSuat", "ThTien")})
    rates = [flat(el) for el in dl.iter() if _local(el.tag) == "LTSuat"]
    mccqt = find(root, "MCCQT")
    sigs = [_local(el.tag) for el in root.iter() if _local(el.tag) == "Signature"]
    info = {"ttchung": flat(tc), "nb": flat(nb), "nm": flat(nm), "tong": flat(tt), "items": items, "rates": rates,
            "mccqt": (mccqt.text or "").strip() if mccqt is not None else "", "so_chu_ky": len(sigs),
            "ttkhac": {}, "msttcgp": "", "dlhdon_id": dl.get("Id", "")}
    info["msttcgp"] = info["ttchung"].get("MSTTCGP", "")
    for el in dl.iter():
        if _local(el.tag) == "TTin":
            f = flat(el)
            if f.get("TTruong") and f.get("DLieu"):
                info["ttkhac"].setdefault(f["TTruong"], f["DLieu"])
    return info


def render_xml_invoice(xml_bytes, title_note=""):
    """Bản thể hiện hoá đơn (HTML) dựng từ file XML – xem, in hoặc lưu PDF."""
    import html as H
    x = read_invoice_xml(xml_bytes)
    tc, nb, nm, tong = x["ttchung"], x["nb"], x["nm"], x["tong"]
    esc = lambda v: H.escape(str(v or ""))

    def m(v):
        if v in (None, ""):
            return ""
        f = _float(v)
        return ("{:,.0f}" if f == int(f) else "{:,.2f}").format(f)
    d = _to_dt(tc.get("NLap"))
    ngay = "Ngày %02d tháng %02d năm %d" % (d.day, d.month, d.year) if d else esc(tc.get("NLap"))
    rows = "".join(
        "<tr><td class=c>%s</td><td>%s</td><td class=c>%s</td><td class=n>%s</td><td class=n>%s</td>"
        "<td class=n>%s</td><td class=c>%s</td><td class=n>%s</td></tr>" % (
            esc(it["STT"]), esc(it["THHDVu"]), esc(it["DVTinh"]), m(it["SLuong"]), m(it["DGia"]),
            m(it["STCKhau"]) or "0", esc(it["TSuat"]), m(it["ThTien"]))
        for it in x["items"])
    rate_rows = "".join("<tr><td class=c>%s</td><td class=n>%s</td><td class=n>%s</td></tr>" % (
        esc(r.get("TSuat")), m(r.get("ThTien")), m(r.get("TThue"))) for r in x["rates"])
    lk = lookup_info(x)

    def party(title, p):
        rows_ = [("Tên " + title, p.get("Ten")), ("Mã số thuế", p.get("MST") or p.get("CCCDan")), ("Địa chỉ", p.get("DChi")),
                 ("Điện thoại", p.get("SDThoai")), ("Số tài khoản", p.get("STKNHang"))]
        return "".join("<div>%s: <b>%s</b></div>" % (k, esc(v)) for k, v in rows_ if v or k.startswith("Tên"))
    tot = [("Tổng tiền chưa thuế", tong.get("TgTCThue")), ("Tổng tiền thuế", tong.get("TgTThue")),
           ("Tổng tiền phí", tong.get("TgTPhi") or "0"), ("Tổng tiền CKTM", tong.get("TTCKTMai") or "0"),
           ("Tổng tiền thanh toán", tong.get("TgTTTBSo"))]
    sig = ("Có %d chữ ký số trong file (phần mềm chưa kiểm tra tính hợp lệ của chữ ký)" % x["so_chu_ky"]
           if x["so_chu_ky"] else "Không có chữ ký số trong file")
    return """<!doctype html><html lang=vi><head><meta charset=utf-8><title>%s %s-%s</title><style>
body{font:14px/1.55 Arial,sans-serif;color:#111;max-width:960px;margin:16px auto;padding:0 16px;background:#fff}
.top{display:grid;grid-template-columns:1fr auto;gap:12px;border-bottom:1px solid #ccc;padding-bottom:8px}
h1{font-size:21px;margin:0;text-align:center}.c{text-align:center}.n{text-align:right}.sec{border-bottom:1px solid #ccc;padding:6px 0}
table{border-collapse:collapse;width:100%%;margin-top:8px}td,th{border:1px solid #888;padding:4px 6px}th{background:#f3f3f3}
.two{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}.mute{color:#666;font-size:12px}.np{margin-bottom:8px}
@media print{.np{display:none}}</style></head><body>
<div class=np><button onclick="print()">In / Lưu PDF</button> <span class=mute>%s</span></div>
<div class=top><div><h1>%s</h1><p class=c>%s<br>MCCQT: %s</p></div>
<div>Mẫu số: <b>%s</b><br>Ký hiệu: <b>%s</b><br>Số: <b>%s</b></div></div>
<div class=sec>%s</div><div class=sec>%s<div class=two><div>Hình thức thanh toán: <b>%s</b></div><div>Đơn vị tiền tệ: <b>%s</b></div></div></div>
<table><tr><th>STT</th><th>Tên hàng hoá, dịch vụ</th><th>ĐVT</th><th>SL</th><th>Đơn giá</th><th>Tiền CK</th><th>Thuế suất</th><th>Thành tiền</th></tr>%s</table>
<div class=two><table><tr><th>Thuế suất</th><th>Tổng tiền chưa thuế</th><th>Tiền thuế</th></tr>%s</table>
<table>%s<tr><td colspan=2><b>Bằng chữ:</b> %s</td></tr></table></div>
<p class=mute>%s<br>Nhà cung cấp HĐĐT: %s%s</p></body></html>""" % (
        esc(tc.get("KHHDon")), esc(tc.get("SHDon")), esc(tc.get("THDon")), esc(title_note) or "Bản thể hiện dựng từ XML",
        esc(tc.get("THDon") or "HÓA ĐƠN"), ngay, esc(x["mccqt"] or "(không mã)"), esc(tc.get("KHMSHDon")),
        esc(tc.get("KHHDon")), esc(tc.get("SHDon")), party("người bán", nb), party("người mua", nm),
        esc(tc.get("HTTToan")), esc(tc.get("DVTTe") or "VND"), rows, rate_rows,
        "".join("<tr><td>%s</td><td class=n>%s</td></tr>" % (k, m(v)) for k, v in tot), esc(tong.get("TgTTTBChu")), sig,
        esc(lk["ncc"]), (" – %s: %s" % (esc(lk["field"]), esc(lk["code"]))) if lk["code"] else "")


def _pypdf():
    try:
        import pypdf
        return pypdf
    except ImportError:
        raise ValueError("Chưa cài thư viện pypdf. Đóng phần mềm và chạy lại chay.bat (hoặc: pip install pypdf)")


def merge_pdfs(files):
    """files: list (tên, bytes) theo thứ tự → bytes PDF đã nối."""
    pypdf = _pypdf()
    if len(files) < 2:
        raise ValueError("Chọn ít nhất 2 file PDF để nối")
    w = pypdf.PdfWriter()
    for name, data in files:
        try:
            r = pypdf.PdfReader(io.BytesIO(data))
            for page in r.pages:
                w.add_page(page)
        except Exception as e:  # file hỏng / có mật khẩu
            raise ValueError("Không đọc được file %s: %s" % (name, e))
    out = io.BytesIO()
    w.write(out)
    return out.getvalue()


def parse_page_ranges(text, total):
    """'1-3, 5, 7-8' → [[1,2,3],[5],[7,8]]; để trống → mỗi trang một file."""
    text = (text or "").strip()
    if not text:
        return [[i] for i in range(1, total + 1)]
    groups = []
    for part in re.split(r"[;,]", text):
        part = part.strip()
        if not part:
            continue
        m = re.fullmatch(r"(\d+)\s*-\s*(\d+)|(\d+)", part)
        if not m:
            raise ValueError("Khoảng trang không hợp lệ: %r (ví dụ đúng: 1-3, 5, 7-8)" % part)
        a, b = (int(m.group(1)), int(m.group(2))) if m.group(1) else (int(m.group(3)), int(m.group(3)))
        if not (1 <= a <= b <= total):
            raise ValueError("Trang %s nằm ngoài file (file có %d trang)" % (part, total))
        groups.append(list(range(a, b + 1)))
    return groups


def split_pdf(name, data, ranges_text):
    """Tách PDF theo khoảng trang → bytes file .zip chứa các PDF con."""
    pypdf = _pypdf()
    try:
        r = pypdf.PdfReader(io.BytesIO(data))
        total = len(r.pages)
    except Exception as e:
        raise ValueError("Không đọc được file %s: %s" % (name, e))
    base = os.path.splitext(os.path.basename(name or "file"))[0]
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
        for pages in parse_page_ranges(ranges_text, total):
            w = pypdf.PdfWriter()
            for p in pages:
                w.add_page(r.pages[p - 1])
            part = io.BytesIO()
            w.write(part)
            label = str(pages[0]) if len(pages) == 1 else "%d-%d" % (pages[0], pages[-1])
            z.writestr("%s_trang_%s.pdf" % (base, label), part.getvalue())
    return buf.getvalue()


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
nav{display:flex;gap:4px;margin-left:12px}nav a{color:#fff;opacity:.7;text-decoration:none;padding:6px 12px;border-radius:6px;font-weight:600}
nav a.on{opacity:1;background:rgba(255,255,255,.15)}
.flt{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;margin-bottom:8px}
.flt select,.flt input,select.sm{padding:6px 8px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit;width:100%}
#hTbl td,#hTbl th{font-size:13px;padding:6px}#hTbl select{min-width:112px}#hTbl td.mh{max-width:260px;white-space:normal}
#hTbl tfoot td{font-weight:700;color:var(--acc)}.pager{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:8px}
.pager button{padding:3px 9px}
.tools{display:grid;grid-template-columns:200px 1fr;gap:20px}@media(max-width:800px){.tools{grid-template-columns:1fr}}
.tmenu a{display:block;padding:10px 12px;border-radius:8px;color:var(--fg);text-decoration:none;margin-bottom:4px;background:var(--bg)}
.tmenu a.on{background:var(--pri);color:#fff}.tpane h3{margin:0 0 10px;color:var(--acc)}#mergeList li{margin:4px 0}
.sync{display:grid;grid-template-columns:minmax(260px,360px) 1fr;gap:20px}@media(max-width:800px){.sync{grid-template-columns:1fr}}
.sync label{margin-top:10px}.sync input,.sync select{width:100%}.sync .chk label{margin:0}.sync .chk input{width:auto}.bigbtn{display:block;width:100%;margin-top:8px;padding:10px;font-weight:600;border:0}
.b-vao{background:#4361ee}.b-ra{background:#e5534b}.b-vr{background:#8c0f9d}.advice{background:#eef0ff;border:1px solid #c9ceff;border-radius:8px;padding:12px;margin-top:12px;color:#222;font-size:14px;line-height:1.7}
.advice h4{color:#5503ca;margin:0 0 6px}.badge{display:inline-block;padding:2px 8px;border-radius:5px;color:#fff;font-size:12px;background:#6c757d}
.badge.run{background:#f0a500}.badge.ok{background:#1e8449}.badge.bad{background:#c0392b}.steps{line-height:1.9;margin:10px 0}
.crumb{font-size:13px;color:var(--mute);margin-bottom:10px}.crumb b{color:var(--fg)}#sTbl td,#sTbl th{font-size:13px;padding:5px}.pager .cur{background:var(--err);border-color:var(--err)}a.lk{color:var(--acc);margin-right:6px}
</style></head><body>
<header><b>Tải hoá đơn</b><nav><a href="#" id="navDN" class="on" onclick="tab('DN');return false">Doanh nghiệp</a>
<a href="#" id="navHD" onclick="tab('HD');return false">Hoá đơn</a>
<a href="#" id="navTI" onclick="tab('TI');return false">Tiện ích</a></nav><span style="flex:1"></span><small id="capStat"></small></header>
<main>
<section class="card hide" id="tabTI">
  <div class="tools">
    <div class="tmenu">
      <a href="#" data-t="xml" class="on">Đọc hoá đơn XML</a><a href="#" data-t="mst">Kiểm tra MST DN</a>
      <a href="#" data-t="merge">Nối file PDF</a><a href="#" data-t="split">Tách file PDF</a>
    </div>
    <div>
      <div class="tpane" id="t-xml"><h3>Đọc và xem hoá đơn XML</h3>
        Chọn file hoá đơn XML: <input type="file" id="xmlFile" accept=".xml,text/xml">
        <button class="sec sm hide" id="xmlPrint" onclick="$('xmlFrame').contentWindow.print()">In / Lưu PDF</button>
        <div class="err" id="xmlErr"></div>
        <iframe id="xmlFrame" class="hide" style="width:100%;height:75vh;border:1px solid var(--line);border-radius:8px;margin-top:10px;background:#fff"></iframe></div>
      <div class="tpane hide" id="t-mst"><h3>Kiểm tra thông tin / tình trạng MST</h3>
        <textarea id="mstText" rows="5" class="full" placeholder="Dán danh sách MST, mỗi dòng một MST (tối đa 200)"></textarea>
        <div class="bar"><button onclick="toolMst()">Kiểm tra</button></div><div class="err" id="mstErr"></div>
        <div class="tbl"><table id="mstTbl"></table></div></div>
      <div class="tpane hide" id="t-merge"><h3>Nối nhiều file PDF thành một</h3>
        <input type="file" id="mergeFiles" accept="application/pdf" multiple>
        <div class="hint">Chọn các file theo đúng thứ tự muốn nối (có thể chọn thêm nhiều lần; kéo thứ tự bằng nút ↑ ↓).</div>
        <ol id="mergeList"></ol><div class="bar"><button onclick="toolMerge()">Nối file</button>
        <button class="sec" onclick="mergeQueue=[];renderMerge()">Xoá danh sách</button></div><div class="err" id="mergeErr"></div></div>
      <div class="tpane hide" id="t-split"><h3>Tách file PDF</h3>
        <input type="file" id="splitFile" accept="application/pdf">
        <label>Khoảng trang (để trống = mỗi trang một file)</label><input type="text" id="splitRanges" placeholder="vd: 1-3, 4, 5-8">
        <div class="bar"><button onclick="toolSplit()">Tách file</button></div><div class="err" id="splitErr"></div></div>
    </div>
  </div>
</section>
<section class="card hide" id="tabSYNC">
  <div class="crumb"><a href="#" onclick="tab('DN');return false">Doanh nghiệp</a> › <b id="sName"></b> › Đồng bộ</div>
  <div class="sync">
    <div>
      <label>Chọn khoảng thời gian</label><div style="display:flex;gap:8px"><select id="sPer"></select>
        <input type="number" id="sYear" style="width:90px"></div>
      <label>Từ ngày</label><input type="date" id="sFrom"><label>Đến ngày</label><input type="date" id="sTo">
      <div class="chk"><label><input type="checkbox" id="sMtt" checked> Gồm máy tính tiền</label>
        <label><input type="checkbox" id="sXml" checked> Tải XML</label>
        <label><input type="checkbox" id="sPdf"> Tải PDF gốc (MISA)</label></div>
      <button class="bigbtn b-vao" onclick="runSync(['purchase'])">Đồng bộ HĐĐT ĐẦU VÀO</button>
      <button class="bigbtn b-ra" onclick="runSync(['sold'])">Đồng bộ HĐĐT ĐẦU RA</button>
      <button class="bigbtn b-vr" onclick="runSync(['purchase','sold'])">Đồng bộ HĐĐT VÀO/RA</button>
      <div class="advice"><h4>KHUYẾN CÁO SỬ DỤNG HIỆU QUẢ</h4>
        <b>Nên bấm Đồng bộ trước khi kiểm tra số liệu vì:</b><br>
        1. Có hoá đơn người bán ký trễ, hoặc hoá đơn không mã (ký hiệu K) gửi lên cơ quan thuế trễ 15–45 ngày – đồng bộ lại để lấy <b>đủ số lượng hoá đơn</b>.<br>
        2. Hoá đơn tải về là HĐ mới nhưng sau đó người bán huỷ / điều chỉnh / thay thế – đồng bộ lại để có <b>trạng thái mới nhất</b>.<br>
        <b style="color:#1f3fb0">Hoá đơn đã tải rồi sẽ không tải lại XML, nên đồng bộ nhiều lần không tốn thời gian.</b></div>
    </div>
    <div>
      <div>Trạng thái: <span class="badge" id="sBadge">chờ đồng bộ</span> <button class="red sm hide" id="sStop" onclick="post('/api/stop',{})">Dừng</button></div>
      <div id="sCapBox" class="card hide" style="border-color:var(--acc);margin-top:10px">
        <div>Nhập captcha:</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px">
          <span class="capimg" id="sCapImg"></span><input type="text" id="sCapIn" placeholder="ký tự trong ảnh" autocomplete="off">
          <button onclick="sendCap('sCapIn')">Gửi</button></div>
        <div class="hint">Chỉ cần nhập khi đăng nhập lần đầu hoặc khi phiên đã hết hạn.</div>
      </div>
      <div class="steps" id="sSteps"></div>
      <div class="prog"><i id="sProg"></i></div>
      <div class="bar"><span style="flex:1"></span><button class="sec" onclick="openInvoicesFromSync()">Xem hoá đơn</button></div>
      <div class="tbl" style="max-height:460px"><table id="sTbl"><thead><tr><th>Loại HĐ</th><th>Ngày lập</th><th>Mẫu số</th>
        <th>Ký hiệu</th><th class="n">Số HĐ</th><th>Loại đồng bộ</th><th>Kết quả đồng bộ</th></tr></thead><tbody id="sRows"></tbody></table></div>
    </div>
  </div>
</section>
<section class="card hide" id="tabHD">
  <div class="flt">
    <select id="hMst" style="grid-column:span 2"></select>
    <select id="hKind"><option value="purchase">Mua vào</option><option value="sold">Bán ra</option>
      <option value="purchase_dv">Mua vào - HĐDV</option><option value="sold_dv">Bán ra - HĐDV</option></select>
    <select id="hFile"><option value="">--Lọc file--</option><option value="no_xml">Không có XML</option><option value="xml">Có XML</option>
      <option value="pdf">Có PDF</option><option value="no_pdf">Không có PDF</option><option value="xml_pdf">Có XML và PDF</option></select>
    <select id="hDuyet"><option value="">--Duyệt n.bộ--</option><option>Chờ duyệt</option><option>Đã duyệt</option><option>Không duyệt</option></select>
    <select id="hTthai"><option value="">--Trạng thái HĐ--</option></select>
    <select id="hKq"><option value="">--K.quả k.tra--</option></select>
  </div>
  <div class="flt">
    <input id="hKh" placeholder="Ký hiệu HĐ"><input id="hSo" placeholder="Số HĐ"><input id="hQ" placeholder="MST, tên DN, mặt hàng, ghi chú">
    <select id="hPer"></select><input type="date" id="hFrom"><input type="date" id="hTo">
  </div>
  <div class="bar">
    <button class="sec" onclick="openSync($('hMst').value)">Đồng bộ</button><button onclick="hPage=0;loadInv()">Tìm kiếm</button>
    <span class="mute" id="hSelInfo"></span>
    <button class="sec" id="hBulkPdf" onclick="bulkPdf()">Tải HĐ gốc hàng loạt</button>
    <button class="sec" id="hImpPdf" onclick="$('hImpFiles').click()" title="Chọn các file PDF hoá đơn gốc có sẵn trên máy – phần mềm tự gắn vào đúng hoá đơn">Gắn PDF gốc có sẵn</button>
    <input type="file" id="hImpFiles" accept="application/pdf" multiple class="hide">
    <span style="flex:1"></span>
    <select class="sm" id="hExp" style="width:auto" onchange="exportInv(this.value);this.value=''">
      <option value="">Kết xuất…</option><option value="xlsx">EXCEL.XLSX</option><option value="xml">XML.ZIP</option>
      <option value="html">HTML.ZIP</option><option value="pdf">PDF.ZIP</option></select>
  </div>
  <div class="err" id="hErr"></div>
  <div class="tbl"><table id="hTbl"><thead><tr><th title="chọn tất cả"><input type="checkbox" id="hAll"></th><th id="hMstH">MST</th><th id="hTenH">Người bán</th><th>Ngày</th><th>Ký hiệu HĐ</th>
    <th class="n">Số HĐ</th><th class="n">Tiền C.Thuế</th><th class="n">Tiền Thuế</th><th class="n">Tiền CK.TM</th><th class="n">Tiền phí</th>
    <th class="n">Tiền T.Toán</th><th>T.thái HĐ</th><th>Kết quả k.tra</th><th>Duyệt Nội Bộ</th><th>HĐ DV</th><th>Mặt hàng</th>
    <th>Ghi chú</th><th>Chi tiết</th></tr></thead><tbody id="hRows"></tbody><tfoot><tr id="hFoot"></tr></tfoot></table></div>
  <div class="pager" id="hPager"></div>
</section>
<section class="card" id="tabDN">
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
  <label>Mã số thuế – tên đăng nhập trang hoadondientu.gdt.gov.vn</label>
  <div style="display:flex;gap:8px"><input type="text" id="eMst" style="flex:1"><button type="button" class="sec" id="eLookup" onclick="lookupMst()">Lấy tên DN</button></div>
  <div class="hint" id="eInfo"></div>
  <label>Tên doanh nghiệp (hiển thị trong bảng kê – theo giấy phép hoặc tên gợi nhớ)</label><input type="text" class="full" id="eTen">
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
  <div class="hint">Mỗi dòng: MST, Tên, Mật khẩu – copy 3 cột từ Excel dán vào (hoặc ngăn cách bằng dấu |). Để trống tên thì phần mềm tự lấy tên theo MST; mật khẩu có thể để trống.</div>
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
    const b1 = el('button', 'sm', 'Đồng bộ'); b1.onclick = () => openSync(c.mst);
    const b2 = el('button', 'sm sec', 'Sửa'); b2.onclick = () => openEdit(c); b2.style.marginLeft = '4px';
    const b3 = el('button', 'sm sec', 'Xem HĐ'); b3.onclick = () => { $('hMst').value = c.mst; tab('HD'); }; b3.style.marginLeft = '4px';
    act.append(b1, b2, b3);
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
  $('eInfo').textContent = ''; $('eLookup').classList.toggle('hide', !!c);
  $('ePwHint').textContent = c && c.co_mk ? 'Đã lưu mật khẩu – để trống nếu không đổi. Mật khẩu này khác mật khẩu đăng nhập phần mềm.' : 'Mật khẩu được mã hoá và chỉ lưu trên máy này.';
  $('eVao').checked = c ? c.vao : true; $('eRa').checked = c ? c.ra : true; $('eAn').checked = c ? c.an : false;
  $('eDel').classList.toggle('hide', !c); show('mEdit'); (c ? $('ePw') : $('eMst')).focus();
}
async function lookupMst() {
  const mst = $('eMst').value.trim(); if (!mst) return;
  $('eInfo').textContent = 'Đang tra cứu…'; $('eErr').textContent = '';
  try { const d = await post('/api/mst-lookup', {mst});
        $('eTen').value = d.ten; $('eInfo').textContent = [d.dia_chi, d.cqt, d.tthai_text && ('Tình trạng: ' + d.tthai_text)].filter(Boolean).join(' · '); }
  catch (e) { $('eInfo').textContent = ''; $('eErr').textContent = e.message; }
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
async function sendCap(id) { id = id || 'capIn'; const v = $(id).value.trim(); if (!v) return; $(id).value = ''; hide('capBox'); hide('sCapBox');
  await post('/api/captcha', {answer: v}); poll(); }
let lastCap = null;
function renderJob(j) {
  if (curTab === 'SYNC') { hide('jobCard'); renderSync(j); return; }
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
let wasRunning = false;
async function poll() {
  clearTimeout(timer);
  let s; try { s = await refresh(); } catch (e) { timer = setTimeout(poll, 2000); return; }
  if (wasRunning && !s.job.running && curTab === 'HD') loadInv();
  wasRunning = s.job.running;
  if (s.job.running) timer = setTimeout(poll, 1500);
}

// ---- Tiện ích ----
document.querySelectorAll('.tmenu a').forEach(a => a.onclick = ev => { ev.preventDefault();
  document.querySelectorAll('.tmenu a').forEach(x => x.classList.toggle('on', x === a));
  document.querySelectorAll('.tpane').forEach(p => p.classList.toggle('hide', p.id !== 't-' + a.dataset.t)); });
const readB64 = f => new Promise((res, rej) => { const r = new FileReader(); r.onload = () => res(String(r.result).split(',')[1]); r.onerror = rej; r.readAsDataURL(f); });
function download(name, b64, type) {
  const bin = atob(b64), u = new Uint8Array(bin.length); for (let i = 0; i < bin.length; i++) u[i] = bin.charCodeAt(i);
  const a = el('a'); a.href = URL.createObjectURL(new Blob([u], {type})); a.download = name; document.body.append(a); a.click(); a.remove();
}
$('xmlFile').onchange = async () => {
  const f = $('xmlFile').files[0]; if (!f) return; $('xmlErr').textContent = '';
  try { const d = await post('/api/tool/xml-view', {data: await readB64(f)});
        $('xmlFrame').srcdoc = d.html; show('xmlFrame'); show('xmlPrint'); }
  catch (e) { $('xmlErr').textContent = e.message; hide('xmlFrame'); hide('xmlPrint'); }
};
async function toolMst() {
  $('mstErr').textContent = 'Đang kiểm tra…'; const t = $('mstTbl'); t.innerHTML = '';
  try { const d = await post('/api/tool/mst', {text: $('mstText').value}); $('mstErr').textContent = '';
    const h = t.insertRow(); ['MST', 'Tên người nộp thuế', 'Tình trạng', 'Địa chỉ', 'Cơ quan thuế'].forEach(x => { const th = el('th', '', x); h.append(th); });
    d.rows.forEach(r => { const tr = t.insertRow(); tr.insertCell().textContent = r.mst; tr.insertCell().textContent = r.ten || r.loi;
      const s = tr.insertCell(); s.textContent = r.tthai_text; s.className = r.tthai_text === 'Đang hoạt động' ? 'ok' : 'err';
      tr.insertCell().textContent = r.dia_chi; tr.insertCell().textContent = r.cqt; });
  } catch (e) { $('mstErr').textContent = e.message; }
}
let mergeQueue = [];
function renderMerge() {
  const ol = $('mergeList'); ol.innerHTML = '';
  mergeQueue.forEach((f, i) => { const li = el('li', '', f.name + ' ');
    const up = el('button', 'sm sec', '↑'); up.onclick = () => { if (i) { [mergeQueue[i - 1], mergeQueue[i]] = [mergeQueue[i], mergeQueue[i - 1]]; renderMerge(); } };
    const dn = el('button', 'sm sec', '↓'); dn.onclick = () => { if (i < mergeQueue.length - 1) { [mergeQueue[i + 1], mergeQueue[i]] = [mergeQueue[i], mergeQueue[i + 1]]; renderMerge(); } };
    const rm = el('button', 'sm red', '×'); rm.onclick = () => { mergeQueue.splice(i, 1); renderMerge(); };
    li.append(up, dn, rm); ol.append(li); });
}
$('mergeFiles').onchange = () => { mergeQueue.push(...$('mergeFiles').files); $('mergeFiles').value = ''; renderMerge(); };
async function toolMerge() {
  $('mergeErr').textContent = 'Đang nối…';
  try { const files = []; for (const f of mergeQueue) files.push({name: f.name, data: await readB64(f)});
        const d = await post('/api/tool/pdf-merge', {files}); download(d.name, d.data, 'application/pdf'); $('mergeErr').textContent = ''; }
  catch (e) { $('mergeErr').textContent = e.message; }
}
async function toolSplit() {
  const f = $('splitFile').files[0]; if (!f) { $('splitErr').textContent = 'Chọn file PDF'; return; }
  $('splitErr').textContent = 'Đang tách…';
  try { const d = await post('/api/tool/pdf-split', {name: f.name, data: await readB64(f), ranges: $('splitRanges').value});
        download(d.name, d.data, 'application/zip'); $('splitErr').textContent = ''; }
  catch (e) { $('splitErr').textContent = e.message; }
}

// ---- Trang Đồng bộ ----
let syncMst = '', syncRange = null, syncRows = [];
const SPER = [['today', 'Hôm nay'], ['week', '1 tuần'], ['month', 'Tháng này']]
  .concat([...Array(12).keys()].map(i => ['m' + (i + 1), 'Tháng ' + (i + 1)])).concat([1, 2, 3, 4].map(i => ['q' + i, 'Quý ' + i]));
SPER.forEach(([v, t]) => $('sPer').append(new Option(t, v)));
function sPeriod() {
  const v = $('sPer').value, now = new Date(), y = +$('sYear').value || now.getFullYear(); let a, b;
  if (v === 'today') a = b = now;
  else if (v === 'week') { a = new Date(now); a.setDate(now.getDate() - 7); b = now; }
  else if (v === 'month') { a = new Date(now.getFullYear(), now.getMonth(), 1); b = now; }
  else if (v[0] === 'm') { const m = +v.slice(1) - 1; a = new Date(y, m, 1); b = new Date(y, m + 1, 0); }
  else { const q = +v.slice(1) - 1; a = new Date(y, q * 3, 1); b = new Date(y, q * 3 + 3, 0); }
  if (b > now) b = now;
  $('sFrom').value = isoD(a); $('sTo').value = isoD(b);
  $('sYear').classList.toggle('hide', ['today', 'week', 'month'].includes(v));
}
$('sPer').onchange = sPeriod; $('sYear').onchange = sPeriod;
function openSync(mst) {
  if (!mst) return;
  syncMst = mst; const c = companies.find(x => x.mst === mst) || {};
  $('sName').textContent = (c.ten || '') + ' (' + mst + ')';
  if (!$('sFrom').value) { $('sYear').value = new Date().getFullYear(); $('sPer').value = 'week'; sPeriod(); }
  tab('SYNC'); poll();
}
async function runSync(kinds) {
  syncRange = [$('sFrom').value, $('sTo').value];
  try { await start({msts: [syncMst], kinds, from: syncRange[0], to: syncRange[1], mtt: $('sMtt').checked, xml: $('sXml').checked,
                     pdf: $('sPdf').checked}); }
  catch (e) { alert(e.message); }
}
function openInvoicesFromSync() {
  $('hMst').value = syncMst; tab('HD'); $('hMst').value = syncMst;
  if (syncRange) { $('hPer').value = ''; $('hFrom').value = syncRange[0]; $('hTo').value = syncRange[1]; }
  hPage = 0; loadInv();
}
const vnd = s => s ? s.split('-').reverse().join('/') : '';
function renderSync(j) {
  const b = $('sBadge');
  const phase = j.running ? (j.need_captcha ? 'chờ nhập captcha' : j.phase) : (j.log.length ? j.phase : 'chờ đồng bộ');
  b.textContent = phase; b.className = 'badge ' + (j.running ? 'run' : phase === 'đồng bộ xong' ? 'ok' : phase === 'lỗi' ? 'bad' : '');
  $('sStop').classList.toggle('hide', !j.running);
  if (j.need_captcha) {
    const key = j.need_captcha.svg.length + j.need_captcha.svg.slice(-40);
    if (key !== lastCap) { lastCap = key; $('sCapImg').innerHTML = j.need_captcha.svg; $('sCapIn').value = ''; show('sCapBox'); $('sCapIn').focus(); }
  } else { hide('sCapBox'); lastCap = null; }
  const st = $('sSteps'); st.innerHTML = '';
  const line = (html) => { const d = el('div'); d.innerHTML = html; st.append(d); };
  const esc = t => String(t).replace(/[&<>"]/g, ch => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[ch]));
  if (j.auth || j.running) {
    line('1. Chứng thực tài khoản: <b>' + esc(j.auth || 'đang chờ') + '</b>');
    const f = j.found || {};
    if ('purchase' in f || 'sold' in f) {
      line('2. Từ ngày ' + vnd((syncRange || [])[0]) + ' đến ngày ' + vnd((syncRange || [])[1]) + ' có:');
      if ('purchase' in f) line('&nbsp;&nbsp;- <b>' + f.purchase + '</b> hoá đơn mua vào');
      if ('sold' in f) line('&nbsp;&nbsp;- <b>' + f.sold + '</b> hoá đơn bán ra');
      line('3. Đồng bộ hoá đơn: <b>' + (j.running ? 'đang chạy' + (j.total ? ' – XML ' + j.done + '/' + j.total : '') : esc(j.phase)) + '</b>');
      line('&nbsp;&nbsp;- Tổng số HĐ: <b>' + ((f.purchase || 0) + (f.sold || 0)) + '</b> hoá đơn');
    }
    line('&nbsp;&nbsp;- Thời gian xử lý: ' + j.elapsed + ' giây');
    const errs = j.log.filter(x => x.includes('LỖI'));
    if (errs.length) line('<span class="err">' + esc(errs[errs.length - 1]) + '</span>');
  }
  $('sProg').style.width = (j.total ? Math.round(100 * j.done / j.total) : (j.running ? 5 : (j.log.length ? 100 : 0))) + '%';
  syncRows = j.rows || []; renderSyncRows();
}
function renderSyncRows() {
  const tb = $('sRows'); tb.innerHTML = '';
  syncRows.slice(0, 1500).forEach(r => {
    const tr = tb.insertRow();
    [r.loai, r.ngay, r.mau, r.kh].forEach(v => tr.insertCell().textContent = v);
    const so = tr.insertCell(); so.className = 'n'; so.textContent = r.so;
    tr.insertCell().textContent = r.dongbo;
    const k = tr.insertCell(); k.textContent = r.ketqua; k.className = r.ketqua.startsWith('OK') ? 'ok' : 'err';
  });
}
$('sCapIn').addEventListener('keydown', e => { if (e.key === 'Enter') sendCap('sCapIn'); });

// ---- Tab Hoá đơn ----
const TTHAI = ['', 'HĐ Mới', 'HĐ Thay thế', 'HĐ Điều chỉnh', 'HĐ Đã bị thay thế', 'HĐ Đã bị điều chỉnh', 'HĐ Đã bị hủy'];
const KQ = ['TCT đã nhận', 'Đang k.tra', 'CQT t.chối HĐ', 'HĐ đủ đ.kiện', 'HĐ k đủ đ.kiện', 'Đã cấp MST', 'TCT k nhận mã', 'Đã k.tra định kỳ', 'HĐ có mã từ máy tính tiền'];
TTHAI.forEach((t, i) => { if (t) $('hTthai').append(new Option(t, i)); });
KQ.forEach((t, i) => $('hKq').append(new Option(t, i)));
let curTab = 'DN', hRows = [], hPage = 0, hSize = 20, hSel = new Set();
const isoD = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
const PERIODS = [['', '--Tuỳ chọn ngày--'], ['today', 'Hôm nay'], ['month', 'Tháng này']]
  .concat([...Array(12).keys()].map(i => ['m' + (i + 1), 'Tháng ' + (i + 1)]))
  .concat([1, 2, 3, 4].map(i => ['q' + i, 'Quý ' + i])).concat([['year', 'Năm nay'], ['lastyear', 'Năm trước']]);
PERIODS.forEach(([v, t]) => $('hPer').append(new Option(t, v)));
$('hPer').onchange = () => {
  const v = $('hPer').value, now = new Date(), y = now.getFullYear(); let a, b;
  if (v === 'today') a = b = now;
  else if (v === 'month') { a = new Date(y, now.getMonth(), 1); b = now; }
  else if (v[0] === 'm') { const m = +v.slice(1) - 1; a = new Date(y, m, 1); b = new Date(y, m + 1, 0); }
  else if (v[0] === 'q') { const q = +v.slice(1) - 1; a = new Date(y, q * 3, 1); b = new Date(y, q * 3 + 3, 0); }
  else if (v === 'year') { a = new Date(y, 0, 1); b = now; }
  else if (v === 'lastyear') { a = new Date(y - 1, 0, 1); b = new Date(y - 1, 11, 31); }
  if (a) { $('hFrom').value = isoD(a); $('hTo').value = isoD(b); hPage = 0; loadInv(); }
};
function tab(t) {
  curTab = t; $('navDN').classList.toggle('on', t === 'DN'); $('navHD').classList.toggle('on', t === 'HD');
  $('tabDN').classList.toggle('hide', t !== 'DN'); $('tabHD').classList.toggle('hide', t !== 'HD');
  $('tabSYNC').classList.toggle('hide', t !== 'SYNC'); $('tabTI').classList.toggle('hide', t !== 'TI');
  $('navTI').classList.toggle('on', t === 'TI');
  if (t === 'HD') { fillMst(); loadInv(); }
}
function fillMst() {
  const cur = $('hMst').value; $('hMst').innerHTML = '';
  companies.filter(c => !c.an).forEach((c, i) => $('hMst').append(new Option(String(i + 1).padStart(3, '0') + '. ' + (c.ten || '') + ' (' + c.mst + ')', c.mst)));
  if (cur) $('hMst').value = cur;
}
function selInfo() {
  const n = hRows.filter(r => hSel.has(r.key)).length;
  $('hSelInfo').textContent = n ? ('Đã chọn ' + n + ' HĐ – tải gốc / kết xuất chỉ các HĐ này') : '';
  $('hAll').checked = n > 0 && n === hRows.length;
}
$('hAll').onchange = () => { hSel = new Set($('hAll').checked ? hRows.map(r => r.key) : []); renderInv(); };
function hFilters(useSel) {
  const keys = useSel ? hRows.filter(r => hSel.has(r.key)).map(r => r.key) : [];
  return {keys, kind: $('hKind').value, file: $('hFile').value, duyet: $('hDuyet').value, tthai: $('hTthai').value, kq: $('hKq').value,
          khhdon: $('hKh').value.trim(), shdon: $('hSo').value.trim(), q: $('hQ').value.trim(), from: $('hFrom').value, to: $('hTo').value};
}
async function loadInv() {
  $('hErr').textContent = '';
  if (!$('hMst').value) { hRows = []; renderInv(); return; }
  try { hRows = (await post('/api/invoices', {mst: $('hMst').value, filters: hFilters()})).rows; }
  catch (e) { $('hErr').textContent = e.message; hRows = []; }
  renderInv();
}
function fileLink(rel, text) {
  const a = el('a', 'lk', text); a.href = '/file?k=' + encodeURIComponent(KEY) + '&mst=' + encodeURIComponent($('hMst').value) + '&p=' + encodeURIComponent(rel);
  a.target = '_blank'; return a;
}
function renderInv() {
  const sold = $('hKind').value.startsWith('sold');
  $('hTenH').textContent = sold ? 'Người mua' : 'Người bán';
  const tb = $('hRows'); tb.innerHTML = '';
  const pages = Math.max(1, Math.ceil(hRows.length / hSize)); hPage = Math.min(hPage, pages - 1);
  selInfo();
  hRows.slice(hPage * hSize, hPage * hSize + hSize).forEach(r => {
    const tr = tb.insertRow();
    const pick = el('input'); pick.type = 'checkbox'; pick.checked = hSel.has(r.key);
    pick.onchange = () => { pick.checked ? hSel.add(r.key) : hSel.delete(r.key); selInfo(); }; tr.insertCell().append(pick);
    [r.mst, r.ten, r.ngay, r.khhdon].forEach(v => tr.insertCell().textContent = v);
    [r.shdon, fmt(r.cthue), fmt(r.thue), fmt(r.ck), fmt(r.phi), fmt(r.tt)].forEach(v => { const c = tr.insertCell(); c.className = 'n'; c.textContent = v; });
    tr.insertCell().textContent = r.tthai; tr.insertCell().textContent = r.kq;
    const sel = el('select', 'sm'); ['Chờ duyệt', 'Đã duyệt', 'Không duyệt'].forEach(o => sel.append(new Option(o, o))); sel.value = r.duyet;
    sel.onchange = () => updInv(r, {duyet: sel.value}); tr.insertCell().append(sel);
    const cb = el('input'); cb.type = 'checkbox'; cb.checked = r.dv; cb.onchange = () => updInv(r, {dv: cb.checked}); tr.insertCell().append(cb);
    const mh = tr.insertCell(); mh.className = 'mh'; mh.textContent = r.mat_hang;
    const note = el('input', 'note'); note.value = r.note; note.placeholder = 'ghi chú…'; note.onchange = () => updInv(r, {note: note.value});
    tr.insertCell().append(note);
    const ct = tr.insertCell(); ct.style.whiteSpace = 'nowrap';
    if (r.xml) ct.append(fileLink(r.xml, 'XML')); if (r.html) ct.append(fileLink(r.html, 'HTML')); if (r.pdf) ct.append(fileLink(r.pdf, 'PDF'));
    const kind = $('hKind').value.replace('_dv', '');
    const v = el('a', 'lk', 'In'); v.target = '_blank'; v.title = 'Bản thể hiện dựng từ XML – in hoặc lưu PDF';
    v.href = '/view?' + new URLSearchParams({k: KEY, mst: $('hMst').value, kind, key: r.key}); ct.append(v);
    const t = r.tra_cuu || {};
    if (t.url || t.code) {
      const a = el('a', 'lk', 'Tra cứu'); a.href = '#';
      a.title = (t.ncc || '') + (t.code ? '\n' + t.field + ': ' + t.code + ' (đã chép, dán vào trang tra cứu)' : '') +
                (t.ncc && t.ncc.startsWith('Viettel') ? '\nMST bên bán: ' + r.mst : '');
      a.onclick = ev => { ev.preventDefault(); if (t.code && navigator.clipboard) navigator.clipboard.writeText(t.code).catch(() => {});
        if (t.url) window.open(t.url, '_blank'); else prompt((t.ncc || '') + ' – mã tra cứu', t.code); };
      ct.append(a);
    }
    if (!r.pdf && t.code && (t.ncc_mst === '0101243150' || (t.ncc || '').startsWith('MISA'))) {
      const g = el('a', 'lk', 'Tải PDF gốc'); g.href = '#'; g.title = 'Tự tải PDF gốc từ ' + t.ncc + ' bằng mã ' + t.code;
      g.onclick = async ev => { ev.preventDefault(); g.textContent = 'Đang tải…';
        try { await post('/api/invoice/fetch-pdf', {mst: $('hMst').value, kind, key: r.key}); loadInv(); }
        catch (e) { g.textContent = 'Tải PDF gốc'; $('hErr').textContent = e.message + ' – có thể bấm "Tra cứu" để tải tay.'; } };
      ct.append(g);
    }
    const up = el('a', 'lk', r.pdf ? '↻PDF' : '+PDF'); up.href = '#'; up.title = 'Gắn file PDF gốc đã tải từ trang tra cứu';
    up.onclick = ev => { ev.preventDefault(); const fi = el('input'); fi.type = 'file'; fi.accept = 'application/pdf';
      fi.onchange = async () => { const f = fi.files[0]; if (!f) return;
        const b64 = await new Promise(res => { const rd = new FileReader(); rd.onload = () => res(String(rd.result).split(',')[1]); rd.readAsDataURL(f); });
        try { await post('/api/invoice/pdf', {mst: $('hMst').value, kind, key: r.key, data: b64}); loadInv(); }
        catch (e) { $('hErr').textContent = e.message; } };
      fi.click(); };
    ct.append(up);
  });
  const sum = k => hRows.reduce((a, r) => a + r[k], 0);
  const f = $('hFoot'); f.innerHTML = '';
  const c0 = f.insertCell(); c0.colSpan = 6; c0.textContent = hRows.length + ' HĐ';
  ['cthue', 'thue', 'ck', 'phi', 'tt'].forEach(k => { const c = f.insertCell(); c.className = 'n'; c.textContent = fmt(sum(k)); });
  f.insertCell().colSpan = 7;
  const pg = $('hPager'); pg.innerHTML = '';
  [10, 20, 50, 100].forEach(n => { const b = el('button', 'sm sec' + (n === hSize ? ' cur' : ''), String(n)); if (n === hSize) b.style.color = '#fff';
    b.onclick = () => { hSize = n; hPage = 0; renderInv(); }; pg.append(b); });
  pg.append(el('span', 'mute', ' Tổng cộng: ' + hRows.length + ' hoá đơn - Trang ' + (hPage + 1) + '/' + pages + ' '));
  const prev = el('button', 'sm sec', '‹'); prev.disabled = hPage === 0; prev.onclick = () => { hPage--; renderInv(); };
  const next = el('button', 'sm sec', '›'); next.disabled = hPage >= pages - 1; next.onclick = () => { hPage++; renderInv(); };
  pg.append(prev, next);
}
$('hImpFiles').onchange = async () => {
  const fs = [...$('hImpFiles').files]; $('hImpFiles').value = ''; if (!fs.length) return;
  const b = $('hImpPdf'); b.disabled = true; b.textContent = 'Đang gắn ' + fs.length + ' file…'; $('hErr').textContent = '';
  try { const files = []; for (const f of fs) files.push({name: f.name, data: await readB64(f)});
    const d = await post('/api/invoice/pdf-import', {mst: $('hMst').value, files});
    const ok = d.results.filter(r => r.ok), bad = d.results.filter(r => !r.ok);
    $('hErr').innerHTML = ''; $('hErr').append(el('div', 'ok', 'Đã gắn ' + ok.length + '/' + d.results.length + ' file PDF.'));
    bad.forEach(r => $('hErr').append(el('div', 'err', r.file + ': ' + r.loi)));
    loadInv(); }
  catch (e) { $('hErr').textContent = e.message; }
  b.disabled = false; b.textContent = 'Gắn PDF gốc có sẵn';
};
async function bulkPdf() {
  const b = $('hBulkPdf'); b.disabled = true; b.textContent = 'Đang tải PDF gốc…'; $('hErr').textContent = '';
  try { const d = await post('/api/invoice/fetch-pdf-bulk', {mst: $('hMst').value, filters: hFilters(true)});
        $('hErr').textContent = d.total ? ('Đã tải ' + d.ok + '/' + d.total + ' PDF gốc.' + (d.errors.length ? ' Lỗi: ' + d.errors.join('; ') : ''))
                                        : 'Không có hoá đơn nào cần tải PDF gốc (hiện hỗ trợ tự động: MISA).';
        loadInv(); }
  catch (e) { $('hErr').textContent = e.message; }
  b.disabled = false; b.textContent = 'Tải HĐ gốc hàng loạt';
}
async function updInv(r, fields) {
  try { await post('/api/invoice/update', Object.assign({mst: $('hMst').value, kind: $('hKind').value.replace('_dv', ''), key: r.key}, fields));
        Object.assign(r, fields); } catch (e) { $('hErr').textContent = e.message; }
}
async function exportInv(fmtx) {
  if (!fmtx) return; $('hErr').textContent = '';
  try { const d = await post('/api/export', {mst: $('hMst').value, filters: hFilters(true), fmt: fmtx}); window.open(d.url, '_blank'); }
  catch (e) { $('hErr').textContent = e.message; }
}
['hKind', 'hFile', 'hDuyet', 'hTthai', 'hKq', 'hMst'].forEach(id => $(id).onchange = () => { hPage = 0; if (id === 'hMst' || id === 'hKind') hSel.clear(); loadInv(); });
['hKh', 'hSo', 'hQ'].forEach(id => $(id).addEventListener('keydown', e => { if (e.key === 'Enter') { hPage = 0; loadInv(); } }));
$('q').oninput = render;
$('toggleAn').onclick = e => { e.preventDefault(); showHidden = !showHidden; render(); };
$('all').onchange = e => document.querySelectorAll('.pick').forEach(x => x.checked = e.target.checked);
$('capIn').addEventListener('keydown', e => { if (e.key === 'Enter') sendCap(); });
$('eMst').addEventListener('change', () => { if (!editing && !$('eTen').value.trim()) lookupMst(); });
(() => {
  const now = new Date(), first = new Date(now.getFullYear(), now.getMonth() - 1, 1), last = new Date(now.getFullYear(), now.getMonth(), 0);
  const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  $('bFrom').value = iso(first); $('bTo').value = iso(last);
  $('hPer').value = 'month'; $('hFrom').value = iso(new Date(now.getFullYear(), now.getMonth(), 1)); $('hTo').value = iso(now);
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
        self.clients = {}  # MST → client đã đăng nhập (giữ phiên)
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
            if n > 30000000:
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
            if path == "/file":
                return self._file()
            if path == "/view":
                q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(self.path).query))
                if not secrets.compare_digest(q.get("k", ""), app.key) or not app.store.get(q.get("mst", "")):
                    return self._send(403, {"error": "Không có quyền"})
                try:
                    page = render_invoice_html(app.out_root, q["mst"], q.get("kind"), q.get("key"))
                except (ValueError, OSError) as e:
                    return self._send(404, {"error": str(e)})
                return self._send(200, page.encode("utf-8"), "text/html; charset=utf-8")
            self._send(404, {"error": "Không tìm thấy"})

        def _file(self):
            """Mở file đã tải (XML/HTML/PDF/Excel/ZIP) – chỉ trong thư mục của doanh nghiệp."""
            q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(self.path).query))
            if not secrets.compare_digest(q.get("k", ""), app.key) or not app.store.get(q.get("mst", "")):
                return self._send(403, {"error": "Không có quyền"})
            base = os.path.realpath(company_dir(app.out_root, q["mst"]))
            full = os.path.realpath(os.path.join(base, q.get("p", "")))
            if not full.startswith(base + os.sep) or not os.path.isfile(full):
                return self._send(404, {"error": "Không tìm thấy file"})
            ext = os.path.splitext(full)[1].lower()
            ctype = {".xml": "application/xml", ".html": "text/html; charset=utf-8", ".htm": "text/html; charset=utf-8",
                     ".pdf": "application/pdf", ".zip": "application/zip",
                     ".xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"}.get(ext, "application/octet-stream")
            with open(full, "rb") as fh:
                body = fh.read()
            self.send_response(200)
            self.send_header("Content-Type", ctype)
            self.send_header("Content-Length", str(len(body)))
            if ext in (".zip", ".xlsx"):
                self.send_header("Content-Disposition", 'attachment; filename="%s"' % os.path.basename(full))
            if ext in (".html", ".htm"):
                self.send_header("Content-Security-Policy", "script-src 'none'")  # HTML hoá đơn chỉ để xem
            self.end_headers()
            self.wfile.write(body)

        def do_POST(self):
            if not self._guard():
                return
            path = self.path.split("?")[0]
            data = self._body()
            try:
                return self._post(path, data)
            except ValueError as e:
                return self._send(400, {"error": str(e)})

        def _tool(self, name, data):
            import base64

            def b64(v):
                try:
                    return base64.b64decode(v or "", validate=True)
                except ValueError:
                    raise ValueError("Dữ liệu file không hợp lệ")
            if name == "xml-view":
                return self._send(200, {"html": render_xml_invoice(b64(data.get("data")))})
            if name == "mst":
                msts = [m for m in re.split(r"[\s,;]+", data.get("text") or "") if m][:200]
                client, rows = app.client_factory(), []
                for m in msts:
                    try:
                        d = client.lookup_company(m)
                        rows.append(dict(d, loi=""))
                    except (PortalError, ValueError) as e:
                        rows.append({"mst": m, "ten": "", "dia_chi": "", "cqt": "", "tthai_text": "", "loi": str(e)})
                return self._send(200, {"rows": rows})
            if name == "pdf-merge":
                files = [(f.get("name"), b64(f.get("data"))) for f in (data.get("files") or [])]
                out = merge_pdfs(files)
                return self._send(200, {"name": "noi_file_%s.pdf" % datetime.now().strftime("%Y%m%d_%H%M%S"),
                                        "data": base64.b64encode(out).decode()})
            if name == "pdf-split":
                out = split_pdf(data.get("name"), b64(data.get("data")), data.get("ranges"))
                base = os.path.splitext(os.path.basename(data.get("name") or "file"))[0]
                return self._send(200, {"name": "%s_tach.zip" % safe_name(base), "data": base64.b64encode(out).decode()})
            self._send(404, {"error": "Không tìm thấy"})

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
            if path.startswith("/api/tool/"):
                return self._tool(path[len("/api/tool/"):], data)
            if path == "/api/mst-lookup":
                try:
                    return self._send(200, app.client_factory().lookup_company(data.get("mst")))
                except PortalError as e:
                    raise ValueError(str(e))
            if path == "/api/company/delete":
                app.store.delete(data.get("mst"))
                return self._send(200, {"ok": True})
            if path == "/api/company/import":
                added, errors = app.store.import_text(data.get("text"))
                client, filled = None, 0
                for c in app.store.public():
                    if c["ten"] or filled >= 100:
                        continue
                    try:
                        client = client or app.client_factory()
                        app.store.upsert(c["mst"], client.lookup_company(c["mst"])["ten"])
                        filled += 1
                    except (PortalError, ValueError) as e:
                        errors.append("%s: không lấy được tên (%s)" % (c["mst"], e))
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
                app.job.want_pdf = bool(data.get("pdf"))
                threading.Thread(target=run_batch, daemon=True, args=(
                    app.job, app.store, app.solver, msts, kinds, start, end, bool(data.get("mtt")),
                    bool(data.get("xml")), app.out_root, test, app.client_factory, app.clients)).start()
                return self._send(200, {"ok": True})
            if path == "/api/captcha":
                app.job.answer(data.get("answer"))
                return self._send(200, {"ok": True})
            if path in ("/api/invoices", "/api/export", "/api/invoice/update", "/api/invoice/pdf",
                        "/api/invoice/fetch-pdf", "/api/invoice/fetch-pdf-bulk", "/api/invoice/pdf-import"):
                mst = data.get("mst", "")
                company = app.store.get(mst)
                if not company:
                    raise ValueError("Chọn doanh nghiệp")
                if path == "/api/invoices":
                    return self._send(200, {"rows": query_invoices(app.out_root, mst, data.get("filters") or {})})
                if path == "/api/invoice/pdf-import":
                    import base64
                    files = []
                    for f in (data.get("files") or [])[:500]:
                        try:
                            files.append((f.get("name"), base64.b64decode(f.get("data") or "", validate=True)))
                        except ValueError:
                            files.append((f.get("name"), b""))
                    return self._send(200, {"results": import_pdfs(app.out_root, mst, files)})
                if path == "/api/invoice/fetch-pdf":
                    try:
                        return self._send(200, {"pdf": fetch_original_pdf(app.out_root, mst, data.get("kind"), data.get("key"))})
                    except PortalError as e:
                        raise ValueError(str(e))
                if path == "/api/invoice/fetch-pdf-bulk":
                    f = data.get("filters") or {}
                    kind = (f.get("kind") or "purchase").replace("_dv", "")
                    rows = [r for r in query_invoices(app.out_root, mst, f)
                            if not r["pdf"] and can_fetch_pdf(r.get("tra_cuu"))[0]][:300]
                    ok, errs = 0, []
                    for r in rows:
                        try:
                            fetch_original_pdf(app.out_root, mst, kind, r["key"])
                            ok += 1
                        except (PortalError, ValueError) as e:
                            errs.append("%s/%s: %s" % (r["khhdon"], r["shdon"], e))
                        time.sleep(0.3)
                    return self._send(200, {"ok": ok, "total": len(rows), "errors": errs[:20]})
                if path == "/api/invoice/pdf":
                    import base64
                    try:
                        raw = base64.b64decode(data.get("data") or "", validate=True)
                    except ValueError:
                        raise ValueError("Dữ liệu file không hợp lệ")
                    return self._send(200, {"pdf": attach_pdf(app.out_root, mst, data.get("kind"), data.get("key"), raw)})
                if path == "/api/invoice/update":
                    if data.get("duyet") is not None and data["duyet"] not in DUYET_OPTIONS:
                        raise ValueError("Trạng thái duyệt không hợp lệ")
                    update_invoice(app.out_root, mst, data.get("kind"), data.get("key"), note=data.get("note"),
                                   duyet=data.get("duyet"), dv=data.get("dv"))
                    return self._send(200, {"ok": True})
                out = export_invoices(app.out_root, mst, company.get("ten"), data.get("filters") or {}, data.get("fmt"))
                rel = os.path.relpath(out, company_dir(app.out_root, mst))
                return self._send(200, {"url": "/file?" + urllib.parse.urlencode({"k": app.key, "mst": mst, "p": rel})})
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
