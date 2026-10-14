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
import collections
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
import unicodedata
import uuid
import urllib.error
import urllib.parse
import urllib.request
import webbrowser
import zipfile
from datetime import date, datetime, timedelta
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

__version__ = "4.7.2"

BASE_URL = os.environ.get("HDDT_BASE_URL", "https://hoadondientu.gdt.gov.vn/api")
PAGE_SIZE = 50
XML_WORKERS = 4  # số file XML tải cùng lúc
ORIGINAL_WORKERS = 4  # số hoá đơn gốc tải cùng lúc từ trang nhà cung cấp
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

def _open_with_deadline(opener, req, seconds):
    """Mở URL với giới hạn TỔNG thời gian (timeout của socket chỉ tính từng lần chờ; cổng trả nhỏ giọt có thể kéo dài
    mãi). Quá hạn → TimeoutError, không treo cả lượt đồng bộ."""
    box = {}

    def work():
        try:
            with opener.open(req, timeout=seconds) as resp:
                box["data"] = resp.read()
        except BaseException as e:  # chuyển lỗi về luồng chính
            box["err"] = e
    th = threading.Thread(target=work, daemon=True)
    th.start()
    th.join(seconds + 5)
    if th.is_alive():
        raise TimeoutError("trang thuế không trả lời sau %d giây (timed out)" % seconds)
    if "err" in box:
        raise box["err"]
    return box["data"]


def _is_busy(e):
    """Lỗi do cổng quá tải / không trả lời kịp (khác lỗi nghiệp vụ như sai mật khẩu)."""
    t = str(e).lower()
    return any(w in t for w in ("timeout", "timed out", "quá tải", "http 5", "http 429", "không kết nối được",
                                "connection reset", "remote end closed", "service unavailable", "bad gateway",
                                "gateway time", "too many requests", "internal server error"))


class PortalHealth:
    """Theo dõi tình trạng cổng hoadondientu.gdt.gov.vn từ các lần gọi gần đây của mọi người dùng trên máy chủ:
    tỷ lệ lỗi quá tải / không kết nối và thời gian trả lời → cảnh báo "trang thuế chậm / quá tải"."""
    WINDOW = 300  # xét 5 phút gần nhất

    def __init__(self):
        self.lock = threading.Lock()
        self.events = []  # (thời điểm, ok, số giây)

    def record(self, ok, seconds):
        now = time.time()
        with self.lock:
            self.events = [e for e in self.events if now - e[0] < self.WINDOW][-500:]
            self.events.append((now, ok, seconds))

    def status(self):
        now = time.time()
        with self.lock:
            ev = [e for e in self.events if now - e[0] < self.WINDOW]
        if len(ev) < 5 or all(e[1] and e[2] < 15 for e in ev[-5:]):
            return {"level": "", "text": ""}  # 5 lần gọi mới nhất đều nhanh, thành công → cổng đã ổn lại, không báo nữa
        bad = sum(1 for e in ev if not e[1])
        slow = sorted(e[2] for e in ev if e[1])
        med = slow[len(slow) // 2] if slow else 0
        if bad / len(ev) >= 0.4:
            return {"level": "err", "text": "Trang hoadondientu.gdt.gov.vn đang quá tải hoặc lỗi (%d/%d lần gọi gần đây "
                    "không thành công) – đồng bộ có thể thiếu hoá đơn, nên thử lại sau (thường ổn hơn sau 17h)." % (bad, len(ev))}
        if med >= 15:
            return {"level": "warn", "text": "Trang hoadondientu.gdt.gov.vn đang chậm (trung bình %d giây mỗi lần gọi) – "
                    "đồng bộ sẽ lâu hơn bình thường." % med}
        if bad / len(ev) >= 0.15:
            return {"level": "warn", "text": "Trang hoadondientu.gdt.gov.vn chập chờn (%d/%d lần gọi gần đây bị lỗi, phần mềm "
                    "đã tự thử lại) – đồng bộ sẽ lâu hơn bình thường." % (bad, len(ev))}
        return {"level": "", "text": ""}


PORTAL_HEALTH = PortalHealth()


class MttHealth:
    """Phần tra hoá đơn máy tính tiền (/sco-query) của cổng hay treo riêng trong khi phần còn lại vẫn chạy.
    Một lượt tra MTT bị treo → tự bỏ qua MTT cho các lượt đồng bộ trong 5 phút rồi thử lại."""
    PAUSE = 300

    def __init__(self):
        self.down_at = 0.0

    def mark_down(self):
        self.down_at = time.time()

    def mark_up(self):
        self.down_at = 0.0

    def is_down(self):
        return bool(self.down_at) and time.time() - self.down_at < self.PAUSE

    def status(self):
        if not self.is_down():
            return ""
        return ("Phần tra hoá đơn MÁY TÍNH TIỀN của trang hoadondientu.gdt.gov.vn đang treo – phần mềm tạm bỏ qua phần này "
                "(tự thử lại sau %d phút). Các hoá đơn khác vẫn đồng bộ bình thường; đối chiếu kỹ số lượng HĐ máy tính tiền "
                "trước khi làm sổ sách." % max(1, int((self.PAUSE - (time.time() - self.down_at)) / 60)))


MTT_HEALTH = MttHealth()
MTT_TIMEOUT = 25  # giây – phần MTT treo thì bỏ nhanh, không chờ lâu
LIST_TIMEOUT = 45  # giây – mỗi lần tra danh sách hoá đơn (có mã / không mã)


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
                 url=None, timeout=None, health=True):
        url = url or self.base_url + path
        if params:
            url += "?" + urllib.parse.urlencode(params, safe=":,;=/")
        data = json.dumps(body).encode("utf-8") if body is not None else None
        opener = self._query_opener if profile == "query" else self._login_opener

        for attempt in range(retries + 1):
            headers = self._headers(profile, action, body is not None)
            req = urllib.request.Request(url, data=data, headers=headers, method=method)
            t0 = time.time()
            try:
                content = _open_with_deadline(opener, req, timeout or self.timeout)
                if health:
                    PORTAL_HEALTH.record(True, time.time() - t0)
                break
            except urllib.error.HTTPError as e:
                content = e.read()
                if health:
                    PORTAL_HEALTH.record(e.code not in (429, 500, 502, 503, 504), time.time() - t0)
                # Chỉ thử lại khi cổng quá tải; cổng đã trả thông báo cụ thể (vd "Không tồn tại hồ sơ gốc") thì không.
                if e.code in (429, 500, 502, 503, 504) and attempt < retries and \
                        NO_XML_MSG not in _plain(self._error_message(content) or ""):
                    time.sleep(2 ** attempt * 2)
                    continue
                if e.code == 401:
                    self.token = None
                    raise PortalError("Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại.")
                msg = self._error_message(content) or "Cổng hoá đơn trả lỗi HTTP %d" % e.code
                if e.code in (429, 500, 502, 503, 504) and "HTTP" not in msg:
                    msg += " (HTTP %d – trang thuế quá tải)" % e.code
                if "hành vi không hợp lệ" in msg:
                    msg += " (tường lửa của cổng chặn; chờ vài phút rồi thử lại, nếu vẫn bị hãy báo để cập nhật phần mềm)"
                raise PortalError(msg)
            except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
                if health:
                    PORTAL_HEALTH.record(False, time.time() - t0)
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

    SESSION_REUSE = 1200  # chỉ dùng lại phiên đăng nhập trong 20 phút (phiên cũ hơn cổng có thể trả lỗi 503)

    def token_valid(self, margin=120):
        """Token còn hạn? Đọc 'exp' trong JWT; không đọc được thì coi hạn 30 phút kể từ lúc đăng nhập.
        Phiên đăng nhập quá 20 phút thì coi như hết hạn để đăng nhập mới (giống trình duyệt / Nibot)."""
        if not self.token:
            return False
        if time.time() - getattr(self, "login_at", 0) > self.SESSION_REUSE:
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
        self.mtt_skipped = False
        self.gaps = []
        if include_mtt and MTT_HEALTH.is_down():
            include_mtt = False
            self.mtt_skipped = True
            if progress:
                progress("Bỏ qua HĐ máy tính tiền: phần này của trang thuế vừa treo – sẽ tự thử lại sau vài phút")
        sources = self._sources(kind, include_mtt)
        # Các nguồn (có mã/không mã, máy tính tiền, từng trạng thái) tra SONG SONG cho nhanh; mỗi nguồn vẫn tra lần lượt
        # từng tháng. Kết quả ghép theo đúng thứ tự nguồn như trước nên việc lọc trùng không đổi.
        from concurrent.futures import ThreadPoolExecutor
        with ThreadPoolExecutor(max_workers=max(1, len(sources))) as pool:
            parts = list(pool.map(lambda src: self._list_source(kind, start, end, src, progress), sources))
        results, seen = [], set()
        for (prefix, ttxly, label), (found, gaps) in zip(sources, parts):
            self.gaps.extend(gaps)
            for inv in found:
                key = (inv.get("nbmst"), inv.get("khmshdon"), inv.get("khhdon"), inv.get("shdon"))
                if key in seen:
                    continue
                seen.add(key)
                inv["_prefix"] = prefix
                inv["_nguon"] = label
                inv["_kind"] = kind
                results.append(inv)
        results.sort(key=lambda i: (str(i.get("tdlap") or ""), str(i.get("khhdon") or ""), _num(i.get("shdon"))))
        return results

    def _list_source(self, kind, start, end, src, progress=None):
        """Tra một nguồn qua các tháng → (danh sách HĐ, các khoảng ngày bị thiếu)."""
        prefix, ttxly, label = src
        mtt = prefix == "/sco-query"
        out, gaps = [], []
        for a, b in month_ranges(start, end):
            if progress:
                progress("Tra %s %s → %s (%s)%s" % (
                    "mua vào" if kind == "purchase" else "bán ra",
                    a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y"), label,
                    " – trang thuế không trả lời trong %d giây sẽ bỏ qua phần này" % MTT_TIMEOUT if mtt else ""))
            sub = []
            try:
                found = self._fetch_range(prefix, kind, a, b, ttxly, progress, mtt=mtt, gaps=sub)
            except PortalError as e:
                if not _is_busy(e):
                    raise
                if not mtt:  # phần có mã / không mã lỗi: ghi các khoảng còn lại là thiếu, tra tiếp phần khác
                    gaps.append("%s %s – %s" % (label, a.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y")))
                    if progress:
                        progress("CẢNH BÁO: trang thuế không trả lời khi tra %s từ %s – bỏ qua phần này tới %s (%s)" % (
                            label, a.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y"), e))
                    break
                # Phần MTT treo: không làm hỏng cả lượt – giữ các HĐ có mã / không mã đã tra được.
                MTT_HEALTH.mark_down()
                self.mtt_skipped = True
                if progress:
                    progress("CẢNH BÁO: phần HĐ máy tính tiền của trang thuế không trả lời (treo) – tạm bỏ qua, "
                             "các hoá đơn khác vẫn đồng bộ. Đồng bộ lại sau để lấy HĐ máy tính tiền.")
                break
            if mtt:
                MTT_HEALTH.mark_up()
            gaps.extend("%s %s" % (label, g) for g in sub)
            out.extend(found)
            time.sleep(self.delay)
        return out, gaps

    def _fetch_range(self, prefix, kind, a, b, ttxly, progress=None, mtt=False, _split=False, gaps=None):
        """Tra một khoảng ngày (có lật trang). Cổng quá tải (timeout) thì chia nhỏ theo tuần rồi tra lại.
        mtt=True (máy tính tiền): chờ ngắn, thử lại 1 lần, không chia nhỏ – phần này treo thì bỏ qua cho nhanh."""
        try:
            out, state = [], None
            while True:
                params = {"sort": "tdlap:desc", "size": PAGE_SIZE, "search": search_query(a, b, ttxly)}
                if state:
                    params["state"] = state
                data = self._request("GET", "%s/invoices/%s" % (prefix, kind), params=params, retries=1,
                                     timeout=MTT_TIMEOUT if mtt else LIST_TIMEOUT, health=not mtt)
                page = data.get("datas") or []
                out.extend(page)
                state = data.get("state")
                if not page or not state or len(page) < PAGE_SIZE:
                    return out
                time.sleep(self.delay)
        except PortalError as e:
            busy = _is_busy(e)
            if mtt or not busy or (b - a).days < 7 or _split:
                if busy and not mtt:
                    raise PortalError("Cổng thuế đang quá tải, không trả được dữ liệu ngày %s – %s (%s)" % (
                        a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y"), e))
                raise
            # Chia theo tuần, tra lại MỘT lần; tuần nào vẫn lỗi thì ghi là thiếu, không chia nhỏ thêm (tránh treo lâu).
            if progress:
                progress("Cổng thuế quá tải, chia nhỏ %s – %s theo tuần để tra lại" % (
                    a.strftime("%d/%m/%Y"), b.strftime("%d/%m/%Y")))
            out, cur, failed = [], a, 0
            while cur <= b:
                stop = min(cur + timedelta(days=6), b)
                try:
                    out.extend(self._fetch_range(prefix, kind, cur, stop, ttxly, progress, _split=True, gaps=gaps))
                except PortalError as e2:
                    if not _is_busy(e2):
                        raise
                    failed += 1
                    gaps = self.gaps if gaps is None else gaps
                    gaps.append("%s – %s" % (cur.strftime("%d/%m/%Y"), stop.strftime("%d/%m/%Y")))
                    if failed >= 1:  # cổng vẫn không trả lời → bỏ phần còn lại của khoảng này, không chờ thêm
                        if stop < b:
                            gaps.append("%s – %s" % ((stop + timedelta(days=1)).strftime("%d/%m/%Y"),
                                                          b.strftime("%d/%m/%Y")))
                        break
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
        # Giữ nguyên cả gói của cổng (HTML + details.js, css, hình…) để bản HTML hiển thị / in PDF đúng như trên thuế
        goc = os.path.join(folder, basename + "_goc")
        for name in zf.namelist():
            leaf = os.path.basename(name.replace("\\", "/"))
            if leaf and not name.endswith("/") and leaf not in (".", ".."):
                os.makedirs(goc, exist_ok=True)
                with open(os.path.join(goc, leaf), "wb") as f:
                    f.write(zf.read(name))
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
    # trang riêng từng người bán (đúng địa chỉ trang tra cứu thật: https, /Search/Index)
    "0105987432": ("EasyInvoice (SoftDreams)", "https://{nbmst}hd.easyinvoice.com.vn/Search/Index"),
    "0100109106": ("Viettel S-Invoice", "https://vinvoice.viettel.vn/utilities/invoice-search"),
    "0100684378": ("VNPT Invoice", ""),
    "0101360697": ("BKAV eHoadon", "https://van.ehoadon.vn/Lookup?InvoiceGUID="),
    "0100686209": ("MobiFone Invoice", "http://tracuuhoadon.mobifoneinvoice.vn/trang-chu"),
    "0101300842": ("Thái Sơn E-invoice", "https://einvoice.vn/tra-cuu"),
    "0100727825": ("FAST e-Invoice", "https://einvoice.fast.com.vn/"),
    "0309478306": ("TS24 (xuathoadon.vn)", "https://tracuu.xuathoadon.vn/"),  # mã tra cứu = mã của cơ quan thuế
    # tra bằng MST bên bán + "Số bảo mật" (TTKhac sobaomat), không captcha – tự tải PDF + XML
    "0106026495": ("M-invoice", "https://tracuuhoadon.minvoice.com.vn/tra-cuu-hoa-don"),
    # trang tra cứu bắt Google reCAPTCHA – chỉ mở trang + chép mã (TTKhac MTCuu)
    "0315638251": ("HTInvoice", "https://laphoadon.htinvoice.vn/TraCuu"),
    # mã tra cứu chỉ in trên PDF, không có trong XML – chỉ mở trang
    "0109282176": ("VinInvoice", "https://tracuu.vininvoice.vn/"),
}
# Tên trường "mã tra cứu" trong phần thông tin khác (TTKhac) của XML, đã bỏ dấu, chữ thường, bỏ khoảng trắng.
LOOKUP_FIELDS = ("transactionid", "masobimat", "reservationcode", "matracuu", "fkey", "keysearch", "matc",
                 "searchkey", "magiaodich", "sobaomat", "mtcuu")


def _plain(text):
    import unicodedata
    t = unicodedata.normalize("NFD", str(text or "")).replace("đ", "d").replace("Đ", "D")
    return "".join(c for c in t if unicodedata.category(c) != "Mn").lower().replace(" ", "").replace("_", "")


def lookup_info(info, nbmst=None):
    """{'ncc', 'url', 'field', 'code'} để tra cứu hoá đơn gốc trên trang của nhà cung cấp.
    nbmst: MST người bán theo dữ liệu trang thuế – dùng khi XML không có (trang EasyInvoice đặt theo MST người bán)."""
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
    field = {"sobaomat": "Số bảo mật", "mtcuu": "Mã tra cứu"}.get(_plain(field), field)
    if mst.startswith("0101360697") and not code:
        field, code = "InvoiceGUID", info.get("dlhdon_id", "")
    guid = info.get("dlhdon_id", "") if mst.startswith("0101360697") else ""
    if mst.startswith("0309478306") and not code and info.get("mccqt"):
        field, code = "Mã của cơ quan thuế", info["mccqt"]
    if url.endswith("=") and code:
        url += urllib.parse.quote(code)
    elif url.endswith("=") or "{nbmst}" in url:
        url = url.split("?")[0] if url.endswith("=") else url
    seller = ((info.get("nb") or {}).get("MST") or nbmst or "").strip()
    url = url.replace("{nbmst}", seller.split("-")[0])
    out = {"ncc": name or (("MST " + mst) if mst else ""), "url": url, "field": field, "code": code,
           "ncc_mst": mst.split("-")[0]}
    if mst.startswith("0106026495") and seller:
        out["nbmst"] = seller  # M-invoice tra bằng MST bên bán (đúng như ghi trong XML, có thể có đuôi -xxx) + số bảo mật
    if guid:
        out["guid"] = guid
        if GUID_RE.fullmatch(guid):  # BKAV: trang Lookup?InvoiceGUID= mở thẳng hoá đơn, không captcha
            out["url"] = PROVIDERS["0101360697"][1] + urllib.parse.quote(guid)
    return out


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


def _is_xml(data):
    head = data[:400].lstrip(b"\xef\xbb\xbf \r\n\t")
    return head[:5] == b"<?xml" or head[:5] in (b"<HDon", b"<TDie") or b"<HDon" in data[:2000]


def _pick_file(data, typ):
    """Nội dung tải về → file đúng loại (PDF / XML), kể cả khi nằm trong ZIP. Không đúng loại thì trả None."""
    ok = (lambda d: d[:4] == b"%PDF") if typ == "pdf" else _is_xml
    if data[:2] != b"PK" and ok(data):  # ZIP lưu không nén có thể chứa "<HDon" → xét ZIP trước
        return data
    if data[:2] == b"PK":
        try:
            with zipfile.ZipFile(io.BytesIO(data)) as z:
                for n in z.namelist():
                    if n.lower().endswith("." + typ) and ok(z.read(n)):
                        return z.read(n)
        except zipfile.BadZipFile:
            return None
    return None


def misa_pdf(code):
    return misa_file(code, "pdf")


def misa_xml(code):
    return misa_file(code, "xml")


def misa_file(code, typ="pdf"):
    """MISA meInvoice – tải PDF (typ='pdf') hoặc XML (typ='xml') gốc bằng mã tra cứu, không cần tài khoản. Thử lần lượt:
    0) đúng link nút "Tải hóa đơn dạng PDF" của trang tra cứu (Default.js của MISA):
       /tra-cuu/DownloadHandler.ashx?Type=pdf&Code=<mã> – không cần mã ext;
    1) mở trang tra cứu ?sc=<mã> như trình duyệt (giữ cookie) và lấy link DownloadHandler.ashx có sẵn trong trang;
    2) lấy mã thời gian 'ext' từ GetRequestTimeEnCode (theo tài liệu MISA) rồi dựng link
       www.meinvoice.vn/tra-cuu/tra-cuu/DownloadHandler.ashx?Type=pdf&Viewer=1&ext=<ext>&Code=<mã>
       (dạng thấy trong trang tra cứu thật, ext có thể chứa '_' – vd J1V4E6D_) và download.meinvoice.vn."""
    import html as H
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    lookup = MISA_WWW + "/tra-cuu/?sc=" + urllib.parse.quote(code)

    log = []  # chẩn đoán: mỗi lần gọi → mã HTTP, loại nội dung, độ dài, đầu nội dung

    def get(url, referer=lookup, browser=False):
        headers = {"User-Agent": UA_LOGIN, "Accept": "*/*", "Referer": referer, "Accept-Language": "vi-VN,vi;q=0.9"}
        if browser:  # y như trình duyệt mở link tải (cách người dùng tải được)
            headers = {"User-Agent": UA_LOGIN, "Accept-Language": "vi-VN,vi;q=0.9,en;q=0.8",
                       "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                       "Upgrade-Insecure-Requests": "1", "Sec-Fetch-Dest": "document", "Sec-Fetch-Mode": "navigate",
                       "Sec-Fetch-Site": "none", "Sec-Fetch-User": "?1"}
        req = urllib.request.Request(url, headers=headers)
        status, ctype, body = 0, "", b""
        try:
            with opener.open(req, timeout=30) as resp:
                status, ctype, body = resp.status, resp.headers.get("Content-Type", ""), resp.read()
                final = resp.geturl()
        except urllib.error.HTTPError as e:
            status, ctype, body, final = e.code, e.headers.get("Content-Type", "") if e.headers else "", e.read() or b"", url
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            log.append("%s → lỗi kết nối %s" % (url, getattr(e, "reason", e)))
            raise PortalError("Không kết nối được meinvoice.vn: %s" % getattr(e, "reason", e))
        log.append("%s%s → HTTP %s, %s, %d byte%s\n    %r" % (url, " (như trình duyệt)" if browser else "", status, ctype or "?",
                   len(body), "" if final == url else ", chuyển tới " + final, body[:300]))
        return body

    def done(data):
        _save_debug("misa-%s.txt" % typ, "\n".join(log) + "\n\nKẾT QUẢ: " + ("được file" if data else "không được file"))
        return data

    urls, diag = [], []
    page = get(lookup, referer=MISA_WWW + "/tra-cuu/").decode("utf-8", "replace")  # lấy cookie như trình duyệt
    link = MISA_WWW + "/tra-cuu/DownloadHandler.ashx?Type=%s&Code=" % typ + urllib.parse.quote(code)
    for browser in (False, True):  # đúng link trình duyệt tải được (link "Tải HĐ gốc" bản cũ)
        direct = _pick_file(get(link, browser=browser), typ)
        if direct:
            return done(direct)
    for link in re.findall(r"DownloadHandler\.ashx\?[^\"'<>\s]+", page, re.I):
        link = re.sub(r"(?i)type=pdf", "Type=" + typ, H.unescape(link))
        urls.append(urllib.parse.urljoin(MISA_WWW + "/tra-cuu/", "tra-cuu/" + link))
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
        urls.append(MISA_WWW + "/tra-cuu/tra-cuu/DownloadHandler.ashx?Type=%s&Viewer=1&ext=%s&Code=%s" % (typ, e, q))
        urls.append(MISA_DL + "/downloadhandler.ashx?type=%s&code=%s&viewer=1&ext=%s" % (typ, q, e))
    got = []
    for url in dict.fromkeys(urls):
        raw = get(url)
        data = _pick_file(raw, typ)
        if data:
            return done(data)
        data = raw
        got.append(re.sub(r"\s+", " ", re.sub(rb"<[^>]+>", b" ", data[:600]).decode("utf-8", "replace")).strip()[:70])
    done(None)
    raise PortalError("MISA không trả file %s cho mã tra cứu %s (đã thử %d đường dẫn; GetRequestTimeEnCode trả: %s; "
                      "trang tải trả: %s)" % (typ.upper(), code, len(urls), " | ".join(diag) or "rỗng", " | ".join(got) or "rỗng"))


# ---- EasyInvoice (SoftDreams): trang tra cứu riêng của từng người bán, captcha 4 chữ số ----

def png_pixels(data):
    """Giải mã PNG 8-bit (xám / RGB / RGBA, không xen kẽ) chỉ bằng thư viện chuẩn → (rộng, cao, hàng điểm ảnh RGB)."""
    import struct
    import zlib
    if data[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError("không phải ảnh PNG")
    pos, idat, w, h, ctype = 8, b"", 0, 0, 0
    while pos + 8 <= len(data):
        n, typ = struct.unpack(">I4s", data[pos:pos + 8])
        chunk = data[pos + 8:pos + 8 + n]
        pos += 12 + n
        if typ == b"IHDR":
            w, h, depth, ctype, _, _, inter = struct.unpack(">IIBBBBB", chunk)
            if depth != 8 or inter or ctype not in (0, 2, 4, 6):
                raise ValueError("định dạng PNG chưa hỗ trợ")
        elif typ == b"IDAT":
            idat += chunk
        elif typ == b"IEND":
            break
    bpp = {0: 1, 2: 3, 4: 2, 6: 4}[ctype]
    raw = zlib.decompress(idat)
    stride = w * bpp
    rows, prev, i = [], bytearray(stride), 0
    for _ in range(h):
        f, line = raw[i], bytearray(raw[i + 1:i + 1 + stride])
        i += 1 + stride
        for x in range(stride):
            a = line[x - bpp] if x >= bpp else 0
            b = prev[x]
            c = prev[x - bpp] if x >= bpp else 0
            if f == 1:
                line[x] = (line[x] + a) & 255
            elif f == 2:
                line[x] = (line[x] + b) & 255
            elif f == 3:
                line[x] = (line[x] + ((a + b) >> 1)) & 255
            elif f == 4:
                p = a + b - c
                pa, pb, pc = abs(p - a), abs(p - b), abs(p - c)
                line[x] = (line[x] + (a if pa <= pb and pa <= pc else b if pb <= pc else c)) & 255
        prev = line
        if ctype in (0, 4):
            rows.append([(line[k * bpp],) * 3 for k in range(w)])
        else:
            rows.append([tuple(line[k * bpp:k * bpp + 3]) for k in range(w)])
    return w, h, rows


# Mẫu chữ số của captcha EasyInvoice (chữ trắng font có chân trên nền xám): lưới 10x14 điểm, dạng hex.
# Tạo từ font Liberation Serif (cùng số đo với Times New Roman) và từ captcha thật đã kiểm chứng.
EASY_DIGITS = {
    "0": "1e0cce1f87c0f03c0f03c0f03e1f87738fc 1e18c61b06c1f07c1f07c1f07c1b86639fc 1f0ce63f87c1f07c1f07c1f07e1d8f73cfe 1f1cf61f87e1f03c0f03c0f87e1d8773cfe 1f1ff61d03c0701c0701c0701c0d877fcfe 3f1ce60b83e0f83e0f83e0f83e0d82738fc",
    "1": "060f8ee0380e0380e0380e0380e0380e3ff 0e0f8ee0380e0380e0380e0380e0380e3ff 0e0f8fe0781e0781e0781e0781e0781e3ff 0e1f86e0380e0380e0380e0380e0380e3ff 0f1fcef03c0f03c0f03c0f03c0f03c0f3ff 1e3f806018060180601806018060181e1ff 1e3f81e018060180601806018060181e1ff",
    "2": "1e0fe63c0300c020080c00000000007ffff 1f1ff41c0300c0701c0e0f0781c3c0fffff 7e10e41906018060380c06070381c0fffff 7f18e41d0701c070180c06070381c0fffff 7f19f43d0701c070381e0f078181c0fffff 7f38ec380f0380e0381c0e07038180fffff 7f38ec3c0300c0f0381e0f0783c1e0fffff",
    "3": "0f0fe01c010180e0f80f0040100401e1ffe 1f1fe01c030180e1f80f00c0300c03c1ff6 1f1ff61c0301c7f1f80700c0300d07ffffe 1f1ff61c0301c7f1f80700c0300f07ffffe 3e3fec1c030187e1f00600c0300e07ffbfc 7e10e419060180e1f00e01c0380e07c3ffe 7e30c818060380e1f07e0380781e07c3bfe 7f19e43d0f03c1e1f81e01c0781e07c3ffe 7f19f43d0701c0f1f87f03c0700f07c3fff 7f38ec380e0381e1f00e00c03c0f03e3bfc",
    "4": "02018020080200802008023ffffc0802008 0301c0f034390c471104fffffffc0401004 0301c0f03c0f04c331cc633ffffc0c0300c 0380e0783e1b8ee3b98e43bffffc0e0380e 0380e0783e1b8ee3b98e63bffffc0e0380e 0380e0783e1f86e338ce63bfffffff0380e 0381e0783e1b86e1388e43bffffc0e0380e",
    "5": "0fcfe601c0fe3fc0f8060040100000c03e0 7f9fc40100401f87f80e01c0781e07c3bfe 7f9fe40100401007f00601c0380f07c3ffe 7f9fe40100401007f00f01c0781f07c3ffe 7f9fe60180601807f00e00c03c0f03e39fc 7fdff40100401007fc0f01c0700f0763dff",
    "6": "0f8c260b80e037cffb03c0f83e0d8371cfe 0f9fe781c0c037cffb07c0f03c0dcf7f9fc 1f8c660980c037cffb87c1f03e0f87718fe 1f8e3707c0e0380ffb8fe0f83e0dc370c7c 1fce370d80e0380fffcfe1f8360dc779cff 1fce770d80e03fefff07c1f87e1d8773cff",
    "7": "fffff80e060180c060180c030180c030180 fffff81a060300c060100c060180c030180 fffffc0a060300c060180c03018060300c0 fffffc0c020380c070180e0301c060380c0 fffffc0f0e0381c070380c070380e0781c0",
    "8": "1f1cee3f87e1dcf7f9cee1f07c1f87e3dff 1f1cfe1f8761dcf7fdcfe1f07c0f87f3dff 3e18c61b06e198e7f1fee3b07c1f07e19fe 3f186c0701f1dfe7e0fc63b07c0701e05fc 3f186c0701f1dfe7e0fc63b07c0701e1dfe 3f18ee1f87e1d8e7f986c0f03c0f03e1dfe 3f1cee0f83e0dce3f1cee0f83e0f83f38fc",
    "9": "1e08441b02c0b03c0d861f806030181c1e0 1f1ffe1f0380701e0dff1ec0300c1f7f9fc 1f3cfe1f07c1f07f3dff3fc0701f0fc7bfe 3e18cc1b06c1f07e1dff3fc0601a0ec73fc 3e18cc1f07c0f03c0dc73ec0701e06c3bfc 3f1dee3f07c1f07e1dcf3fc0703e0fc7bfc 3f3cee0b83e0f83f0dfb00c0303f0ee73f8",
}


def _easy_glyphs(data, thr=200):
    """Tách các chữ số (điểm ảnh gần trắng) theo cột; đường nhiễu màu xám bị loại."""
    w, h, rows = png_pixels(data)
    on = [[min(p) > thr for p in r] for r in rows]
    cols = [any(on[y][x] for y in range(h)) for x in range(w)]
    out, x = [], 0
    while x < w:
        if not cols[x]:
            x += 1
            continue
        s = x
        while x < w and cols[x]:
            x += 1
        ys = [y for y in range(h) if any(on[y][s:x])]
        if ys and (x - s) * (ys[-1] - ys[0] + 1) >= 20:
            out.append([r[s:x] for r in on[ys[0]:ys[-1] + 1]])
    return out


def _easy_norm(g, W=10, H=14):
    gh, gw = len(g), len(g[0])
    return int("".join("1" if any(g[min(gh - 1, int((y + dy / 3) * gh / H))][min(gw - 1, int((x + dx / 3) * gw / W))]
                                  for dy in range(3) for dx in range(3)) else "0"
                       for y in range(H) for x in range(W)), 2)


def solve_easy_captcha(data, max_dist=34):
    """Đọc captcha EasyInvoice → chuỗi 4 chữ số, hoặc None nếu không chắc chắn (khi đó lấy captcha khác)."""
    try:
        glyphs = _easy_glyphs(data)
    except (ValueError, KeyError):
        return None
    if len(glyphs) != 4:
        return None
    templates = [(int(hx, 16), d) for d, hs in EASY_DIGITS.items() for hx in hs.split()]
    text = ""
    for g in glyphs:
        v = _easy_norm(g)
        dist, d = min((bin(v ^ t).count("1"), d) for t, d in templates)
        if dist > max_dist:
            return None
        text += d
    return text


EASY_BASE_RE = r"https?://[\w-]+\.easyinvoice\.(com\.)?vn"


def easyinvoice_pdf(tra_cuu, tries=8):
    return easyinvoice_files(tra_cuu, tries, want_xml=False)["pdf"]




DEBUG_DIR = None  # thư mục lưu trang lỗi của nhà cung cấp để chẩn đoán (đặt khi khởi động)


def _save_debug(name, text):
    if not DEBUG_DIR:
        return
    try:
        os.makedirs(DEBUG_DIR, exist_ok=True)
        if isinstance(text, bytes) and not name.endswith((".html", ".txt")):
            with open(os.path.join(DEBUG_DIR, name), "wb") as f:
                f.write(text)
            return
        with open(os.path.join(DEBUG_DIR, name), "w", encoding="utf-8") as f:
            f.write(text if isinstance(text, str) else text.decode("utf-8", "replace"))
    except OSError:
        pass


def easy_origin(url):
    """https://<MST>hd.easyinvoice.com.vn từ link tra cứu (kể cả link cũ dạng http://… không có /Search/Index)."""
    m = re.match(EASY_BASE_RE, url or "")
    if not m:
        return ""
    return re.sub(r"^http://", "https://", m.group(0)) if ".easyinvoice." in m.group(0) else m.group(0)


def easyinvoice_files(tra_cuu, tries=8, want_pdf=True, want_xml=True):
    """Trả {'pdf': bytes|None, 'xml': bytes|None, 'xml_err': str}. Nút "Tải tệp XML" của trang tra cứu gọi
    downloadXML('<token>') (BrowserDetectShare.js của trang thật): POST /Invoice/DownloadXML {token} → {fileGuid,
    fileName, msg} rồi tải /Invoice/Download?fileGuid=…&fileName=….
    EasyInvoice: mở trang tra cứu của người bán, tự giải captcha, tra bằng mã tra cứu rồi bấm
    "Tải PDF & đính kèm" như trình duyệt: gửi HTML hoá đơn lên /Invoice/DownloadPdfAndFileAttachFromAvailableHtml,
    nhận fileGuid và tải /Invoice/Download (PDF, hoặc ZIP gồm PDF + file đính kèm)."""
    import base64
    import html as H
    base = easy_origin(tra_cuu.get("url"))
    if re.match(r"https?://hd\.", base or ""):
        raise PortalError("Link tra cứu EasyInvoice thiếu MST người bán (%s)" % base)
    code = tra_cuu.get("code") or ""
    if not base:
        raise PortalError("Không xác định được trang tra cứu EasyInvoice của người bán")
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

    last_resp = {}

    def call(path, form=None, ajax=False):
        # Giống trình duyệt: trang tra cứu là /Search/Index, mọi yêu cầu sau đó có Referer là trang này
        headers = {"User-Agent": UA_LOGIN, "Accept-Language": "vi-VN,vi;q=0.9", "Referer": base + "/Search/Index",
                   "Accept": "*/*" if ajax or path.startswith(("/Captcha", "/Invoice")) else
                   "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"}
        if ajax:
            headers["X-Requested-With"] = "XMLHttpRequest"
        body = urllib.parse.urlencode(form).encode() if form is not None else None
        if body is not None:
            headers["Content-Type"] = ("application/x-www-form-urlencoded; charset=UTF-8" if ajax
                                       else "application/x-www-form-urlencoded")
            headers["Origin"] = base
        try:
            with opener.open(urllib.request.Request(base + path, data=body, headers=headers), timeout=60) as r:
                last_resp.update(code=r.status, url=r.geturl())
                return r.read()
        except urllib.error.HTTPError as e:
            last_resp.update(code=e.code, url=e.geturl())
            return e.read() or b""
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            raise PortalError("Không kết nối được %s: %s" % (base, getattr(e, "reason", e)))

    last = ""
    for _ in range(tries):
        page = call("/Search/Index").decode("utf-8", "replace")  # trang tra cứu thật
        form = re.search(r'<form[^>]*id="Search"[^>]*>(.*?)</form>', page, re.S | re.I)
        if not form:
            page = call("/").decode("utf-8", "replace")
            form = re.search(r'<form[^>]*id="Search"[^>]*>(.*?)</form>', page, re.S | re.I)
        fields = {}
        for tag in re.findall(r"<input[^>]*>", form.group(1) if form else page):
            name = re.search(r'name="([^"]*)"', tag)
            if name:
                val = re.search(r'value="([^"]*)"', tag)
                fields[name.group(1)] = H.unescape(val.group(1)) if val else ""
        cap_img = call("/Captcha/Show")
        cap = solve_easy_captcha(cap_img)
        if not cap:
            last = "không đọc được captcha"
            continue
        # Gửi đúng như trình duyệt: các ô của biểu mẫu giữ nguyên (typeSearch để trống như trang thật).
        fields.update(FKey=code, Capcha=cap)
        res = call("/Search/Search", fields).decode("utf-8", "replace")
        # Nút tải do JavaScript của trang tạo ra từ showInv(..., '<token>') (getToolbarHtml); trang cũ có sẵn nút trong HTML.
        token = (re.search(r"downloadPdfAndFileAttachFromAvailableHtml\('([^']+)'\)", res)
                 or re.search(r"showInv\(data\.str[^;]*?'([A-Za-z0-9+/=_-]{20,})'\s*\)", res))
        if not token:
            msg = re.search(r'id="msg"[^>]*value="([^"]*)"', res) or re.search(r'value="([^"]*)"[^>]*id="msg"', res)
            if msg and msg.group(1):
                last = H.unescape(msg.group(1))
            else:  # ghi lại chữ trên trang để biết lý do
                txt = re.sub(r"\s+", " ", H.unescape(re.sub(r"<script.*?</script>|<style.*?</style>|<[^>]+>", " ", res,
                                                             flags=re.S | re.I))).strip()
                # dòng báo lỗi trên trang (nếu có) – thường nằm ở khối thông báo / kiểm tra dữ liệu
                warn = re.findall(r'class="[^"]*(?:validation|error|danger|alert|red)[^"]*"[^>]*>([^<]{3,200})<', res)
                last = "trang tra cứu không trả kết quả – HTTP %s tại %s; captcha phần mềm đọc: %s; mã tra cứu: %s%s" % (
                    last_resp.get("code"), last_resp.get("url"), cap, code,
                    ("; trang báo: " + " | ".join(w.strip() for w in warn[:3])) if warn else "")
                _save_debug("easyinvoice-tra-cuu.html", res)
                _save_debug("easyinvoice-captcha.png", cap_img)
                _save_debug("easyinvoice-gui-di.txt", "POST %s/Search/Search\n%s\ncookie: %s\n" % (
                    base, urllib.parse.urlencode(fields), "; ".join(c.name for c in jar)))
            if re.search(r"x[aá]c th[ựu]c|captcha|capcha", _plain(last) + last.lower()):
                continue  # đọc sai captcha → thử captcha khác
            raise PortalError("EasyInvoice: %s" % last)
        inv = re.search(r'id="InvData"[^>]*value="([^"]*)"', res) or re.search(r'value="([^"]*)"[^>]*id="InvData"', res)
        try:
            html_doc = json.loads(H.unescape(inv.group(1))).get("str", "") if inv else ""
        except ValueError:
            html_doc = ""
        html_doc = re.sub(r"^\s*<\?xml[^>]*\?>", "", html_doc)
        out = {"pdf": None, "xml": None, "xml_err": ""}
        if want_xml:
            xt = re.search(r"downloadXML\('([^']+)'\)", res) or token  # cùng token với nút PDF
            if not xt:
                out["xml_err"] = "trang tra cứu không có nút Tải tệp XML"
            else:
                try:
                    jx = json.loads(call("/Invoice/DownloadXML", {"token": xt.group(1)}, ajax=True).decode("utf-8", "replace"))
                except ValueError:
                    jx = {}
                if jx.get("msg"):
                    out["xml_err"] = "EasyInvoice: %s" % jx["msg"]
                elif jx.get("fileGuid"):
                    out["xml"] = _pick_file(call("/Invoice/Download?" + urllib.parse.urlencode(
                        {"fileGuid": jx["fileGuid"], "fileName": jx.get("fileName", "")})), "xml")
                if not out["xml"] and not out["xml_err"]:
                    out["xml_err"] = "EasyInvoice không trả file XML"
        if not want_pdf:
            return out
        js = call("/Invoice/DownloadPdfAndFileAttachFromAvailableHtml",
                  {"token": token.group(1), "html": base64.b64encode(html_doc.encode("utf-8")).decode()}, ajax=True)
        try:
            j = json.loads(js.decode("utf-8", "replace"))
        except ValueError:
            raise PortalError("EasyInvoice không trả mã file PDF")
        if j.get("msg"):
            raise PortalError("EasyInvoice: %s" % j["msg"])
        data = call("/Invoice/Download?" + urllib.parse.urlencode({"fileGuid": j.get("fileGuid", ""),
                                                                   "fileName": j.get("fileName", "")}))
        out["pdf"] = _pick_file(data, "pdf")
        if out["pdf"]:
            return out
        raise PortalError("EasyInvoice trả file không phải PDF")
    raise PortalError("EasyInvoice: thử %d lần chưa được (%s)" % (tries, last))


# ---- BKAV eHoadon (van.ehoadon.vn): trang Lookup?InvoiceGUID=<GUID> (ASP.NET, không captcha) ----
GUID_RE = re.compile(r"[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}")
BKAV_BASE = "https://van.ehoadon.vn"


def bkav_files(t, want_pdf=True, want_xml=True):
    """Đúng như bấm nút trên trang tra cứu thật: mở Lookup?InvoiceGUID=<GUID>, rồi gửi lại biểu mẫu với
    __EVENTTARGET = 'lbtDownloadAsPdf' (Download file Hóa đơn dạng PDF) / 'LinkDownXML' (dạng XML)."""
    import html as H
    guid = next((g for g in (t.get("guid"), t.get("code")) if g and GUID_RE.fullmatch(str(g))), "")
    if not guid:
        raise PortalError("Không có mã InvoiceGUID của hoá đơn BKAV (lấy từ XML) – bấm Tra cứu để tải tay")
    url = BKAV_BASE + "/Lookup?InvoiceGUID=" + urllib.parse.quote(guid)
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

    def call(u, form=None):
        headers = {"User-Agent": UA_LOGIN, "Accept": "*/*", "Accept-Language": "vi-VN,vi;q=0.9", "Referer": url}
        body = None
        if form is not None:
            body = urllib.parse.urlencode(form).encode()
            headers.update({"Content-Type": "application/x-www-form-urlencoded", "Origin": BKAV_BASE})
        try:
            return _open_with_deadline(opener, urllib.request.Request(u, data=body, headers=headers), 60)
        except urllib.error.HTTPError as e:
            return e.read() or b""
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            raise PortalError("Không kết nối được van.ehoadon.vn: %s" % getattr(e, "reason", e))

    page = call(url).decode("utf-8", "replace")
    _save_debug("bkav-tra-cuu.html", page)
    form = re.search(r'<form[^>]*id="formMain"[^>]*>(.*)</form>', page, re.S | re.I)
    if not form or "__VIEWSTATE" not in page:
        raise PortalError("BKAV không mở được trang hoá đơn (InvoiceGUID %s)" % guid)
    fields = {}
    for tag in re.findall(r"<input[^>]*>", form.group(1)):
        name = re.search(r'name="([^"]*)"', tag)
        typ = (re.search(r'type="([^"]*)"', tag) or [None, "text"])[1].lower()
        if name and typ in ("hidden", "text"):
            val = re.search(r'value="([^"]*)"', tag)
            fields[H.unescape(name.group(1))] = H.unescape(val.group(1)) if val else ""

    def direct_links(data, typ):
        """Link tải có sẵn trong trang: nút PDF dạng onclick="DownloadFile('C2/6T/<tên>.pdf',1)" (hàm của
        InvoiceLookupDetails.js → /DownloadFile?FilePath=<đường dẫn>&BFType=<loại>) và khung xem
        <object data="/Invoice_View/<đường dẫn>.pdf?v=…">."""
        links = [H.unescape(m) for m in re.findall(r"(/DownloadFile\?FilePath=[^\"'<>\s]+)", data)]
        for fp, bf in re.findall(r"DownloadFile\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]?([^'\")]*)['\"]?\s*\)", data):
            links.append("/DownloadFile?FilePath=" + H.unescape(fp) + "&BFType=" + H.unescape(bf))
        links += [H.unescape(m) for m in re.findall(r"""<object[^>]*\bdata=["']([^"']+)["']""", data, re.I)]
        other = ".xml" if typ == "pdf" else ".pdf"
        return [u for u in dict.fromkeys(links) if "." + typ in u.lower() or other not in u.lower()]

    def fetch_links(links, typ):
        for link in links:
            got = _pick_file(call(urllib.parse.urljoin(BKAV_BASE + "/", link)), typ)
            if got:
                return got
        return None

    def grab(target, typ):
        got = fetch_links(direct_links(page, typ), typ)  # mẫu trang có sẵn link tải PDF – dùng trước
        if got:
            return got
        if target not in page:
            return None
        data = call(url, dict(fields, __EVENTTARGET=target, __EVENTARGUMENT=""))
        _save_debug("bkav-%s.html" % typ, data)  # chẩn đoán: trang BKAV trả về sau khi bấm nút tải
        got = _pick_file(data, typ)
        if got:
            return got
        tried = direct_links(page, typ)
        return fetch_links([u for u in direct_links(data.decode("utf-8", "replace"), typ) if u not in tried], typ)
    out = {"pdf": None, "xml": None, "xml_err": "", "pdf_check": True}
    if want_xml:
        out["xml"] = grab("LinkDownXML", "xml")
        if not out["xml"]:
            out["xml_err"] = "BKAV không trả file XML"
    if want_pdf:
        out["pdf"] = grab("lbtDownloadAsPdf", "pdf")  # không dùng "Hóa đơn chuyển đổi" (bản chuyển đổi, không phải gốc)
        if not out["pdf"]:
            raise PortalError("BKAV không trả file PDF cho hoá đơn %s" % guid)
    return out


# ---- TS24 (tracuu.xuathoadon.vn): tra bằng mã của cơ quan thuế, link tải trực tiếp ----
TS24_BASE = "https://tracuu.xuathoadon.vn"


def ts24_url(code, typ):
    """Đúng link các nút trên trang tra cứu (lấy từ trang thật): "Tải XML" = /invoice/download/xml/1/<mã CQT>;
    "Tải PDF" = /invoice/download/pdf/1/<mã CQT>?status_image=true (ô "In có hình trạng thái" mặc định được tích).
    Ô tra cứu của trang có Cloudflare Turnstile nhưng link tải là link trực tiếp theo mã CQT."""
    url = "%s/invoice/download/%s/1/%s" % (TS24_BASE, typ, urllib.parse.quote(code, safe=""))
    return url + "?status_image=true" if typ == "pdf" else url


def ts24_files(t, want_pdf=True, want_xml=True):
    out = {"pdf": None, "xml": None, "xml_err": "", "pdf_check": True}
    if want_xml:
        try:
            out["xml"] = _pick_file(_http_get(ts24_url(t["code"], "xml"), 60), "xml")
            if not out["xml"]:
                out["xml_err"] = "TS24 không trả file XML"
        except PortalError as e:
            out["xml_err"] = str(e)
    if want_pdf:
        out["pdf"] = _pick_file(_http_get(ts24_url(t["code"], "pdf"), 60), "pdf")
        if not out["pdf"]:
            raise PortalError("TS24 không trả file PDF cho mã %s" % t["code"])
    return out


# ---- M-invoice (tracuuhoadon.minvoice.com.vn): tra bằng MST bên bán + "Số bảo mật", không captcha ----
MINVOICE_API = "https://tracuuhoadon.minvoice.com.vn/api"


def minvoice_url(masothue, sobaomat, typ):
    """Đúng như mã trang tra cứu thật (index-CdCEbGmx.js / index-BPrvCfnD.js / index-CHqM4iCq.js):
    PDF: GET api/Search/SearchInvoice?masothue=<MST bên bán>&sobaomat=<số bảo mật>&type=PDF&inchuyendoi=false
    XML: GET api/Search/DownloadXml?masothue=<MST bên bán>&sobaomat=<số bảo mật> (trả file .zip chứa XML)."""
    if typ == "pdf":
        q = {"masothue": masothue, "sobaomat": sobaomat, "type": "PDF", "inchuyendoi": "false"}
        return MINVOICE_API + "/Search/SearchInvoice?" + urllib.parse.urlencode(q)
    return MINVOICE_API + "/Search/DownloadXml?" + urllib.parse.urlencode({"masothue": masothue, "sobaomat": sobaomat})


def minvoice_files(t, want_pdf=True, want_xml=True):
    seller = str(t.get("nbmst") or "").strip()
    if not seller or not t.get("code"):
        raise PortalError("Thiếu MST bên bán hoặc số bảo mật của hoá đơn M-invoice – bấm Tra cứu để xem")
    out = {"pdf": None, "xml": None, "xml_err": "", "pdf_check": True}
    if want_xml:
        try:
            out["xml"] = _pick_file(_http_get(minvoice_url(seller, t["code"], "xml"), 60), "xml")
            if not out["xml"]:
                out["xml_err"] = "M-invoice không trả file XML"
        except PortalError as e:
            out["xml_err"] = str(e)
    if want_pdf:
        out["pdf"] = _pick_file(_http_get(minvoice_url(seller, t["code"], "pdf"), 60), "pdf")
        if not out["pdf"]:
            raise PortalError("M-invoice không trả file PDF cho số bảo mật %s" % t["code"])
    return out


# MST nhà cung cấp → hàm tải PDF gốc (nhận thông tin tra cứu). Viettel, VNPT… chưa tự động được.
PDF_FETCHERS = {"0101243150": lambda t: misa_pdf(t["code"]), "0105987432": easyinvoice_pdf,
                "0309478306": lambda t: ts24_files(t, want_xml=False)["pdf"],
                "0101360697": lambda t: bkav_files(t, want_xml=False)["pdf"],
                "0106026495": lambda t: minvoice_files(t, want_xml=False)["pdf"]}


def _misa_both(t, want_pdf=True, want_xml=True):
    out = {"pdf": misa_pdf(t["code"]) if want_pdf else None, "xml": None, "xml_err": ""}
    if want_xml:
        try:
            out["xml"] = misa_xml(t["code"])
        except PortalError as e:
            out["xml_err"] = str(e)
    return out


# MST nhà cung cấp → hàm tải cả PDF và XML gốc từ trang tra cứu: trả {'pdf', 'xml', 'xml_err'}.
ORIGINAL_FETCHERS = {"0101243150": _misa_both, "0105987432": easyinvoice_files, "0309478306": ts24_files,
                     "0101360697": bkav_files, "0106026495": minvoice_files}


# Link tải PDF gốc mà trình duyệt của người dùng mở được (MISA chặn chương trình tự động nhưng không chặn trình duyệt).
PDF_BROWSER_URLS = {"0101243150": lambda code: MISA_WWW + "/tra-cuu/DownloadHandler.ashx?Type=pdf&Code=" +
                    urllib.parse.quote(code), "0309478306": lambda code: ts24_url(code, "pdf")}
XML_BROWSER_URLS = {"0101243150": lambda code: MISA_WWW + "/tra-cuu/DownloadHandler.ashx?Type=xml&Code=" +
                    urllib.parse.quote(code), "0309478306": lambda code: ts24_url(code, "xml")}


def browser_xml_url(tra_cuu):
    ok, prov = can_fetch_pdf(tra_cuu)
    return XML_BROWSER_URLS[prov](tra_cuu["code"]) if ok and prov in XML_BROWSER_URLS else ""


def browser_pdf_url(tra_cuu):
    ok, prov = can_fetch_pdf(tra_cuu)
    return PDF_BROWSER_URLS[prov](tra_cuu["code"]) if ok and prov in PDF_BROWSER_URLS else ""


def default_downloads_dir():
    """Thư mục Downloads của người dùng (Windows: theo cài đặt của Windows nếu đọc được)."""
    if os.name == "nt":
        try:
            import winreg
            with winreg.OpenKey(winreg.HKEY_CURRENT_USER,
                                r"Software\Microsoft\Windows\CurrentVersion\Explorer\Shell Folders") as k:
                return winreg.QueryValueEx(k, "{374DE290-123F-4565-9164-39C4925E467B}")[0]
        except OSError:
            pass
    return os.path.join(os.path.expanduser("~"), "Downloads")


def browser_download_settings(base=None):
    """Đọc cài đặt tải xuống của Chrome / Edge (mọi hồ sơ người dùng): thư mục lưu và có bật
    'Hỏi vị trí lưu từng tệp' không. base: thư mục LOCALAPPDATA (để kiểm thử)."""
    base = base or os.environ.get("LOCALAPPDATA") or ""
    dirs, prompt = [], False
    for app_dir in (os.path.join(base, "Google", "Chrome", "User Data"), os.path.join(base, "Microsoft", "Edge", "User Data")):
        try:
            profiles = [d for d in os.listdir(app_dir) if d == "Default" or d.startswith("Profile ")]
        except OSError:
            continue
        for prof in profiles:
            prefs = _load_json(os.path.join(app_dir, prof, "Preferences"), {})
            dl = prefs.get("download") or {}
            if dl.get("default_directory"):
                dirs.append(dl["default_directory"])
            if dl.get("prompt_for_download"):
                prompt = True
    return {"dirs": list(dict.fromkeys(dirs)), "prompt": prompt}


def watch_dirs(app):
    """Các thư mục có thể chứa PDF trình duyệt vừa tải: thư mục cấu hình, thư mục tải của Chrome/Edge, Downloads, Desktop."""
    st = browser_download_settings()
    cands = [app.downloads] + st["dirs"] + [default_downloads_dir(), os.path.join(os.path.expanduser("~"), "Desktop")]
    return [d for d in dict.fromkeys(os.path.abspath(c) for c in cands if c) if os.path.isdir(d)], st["prompt"]


def scan_downloads(out_root, mst, folders, since, seen):
    """Gắn các PDF mới xuất hiện trong các thư mục tải về (sau thời điểm 'since') vào đúng hoá đơn.
    seen: tập (đường dẫn, mtime) đã xử lý để không đọc lại."""
    files = []
    if isinstance(folders, str):
        folders = [folders]
    for folder in folders:
        try:
            names = os.listdir(folder)
        except OSError:
            continue
        files += _new_pdfs(folder, names, since, seen)
    return import_pdfs(out_root, mst, files) if files else []


def _new_pdfs(folder, names, since, seen):
    files = []
    for name in names:
        path = os.path.join(folder, name)
        if not name.lower().endswith((".pdf", ".xml", ".zip")) or not os.path.isfile(path):
            continue
        mt = os.path.getmtime(path)
        if mt < since - 5 or (path, mt) in seen:
            continue
        seen.add((path, mt))
        try:
            with open(path, "rb") as fh:
                files.append((name, fh.read()))
        except OSError:
            continue
    return files


def fix_lookup(t, nbmst=None):
    """Sửa link tra cứu đã lưu từ bản cũ: EasyInvoice phải là https://<MST người bán>hd.easyinvoice.com.vn/Search/Index
    (bản cũ có thể thiếu MST → https://hd.easyinvoice.com.vn, trang báo lỗi 404)."""
    t = dict(t or {})
    if t.get("ncc_mst") == "0106026495" and not t.get("nbmst") and nbmst:
        t["nbmst"] = str(nbmst).strip()
    if t.get("ncc_mst") == "0105987432":
        origin = easy_origin(t.get("url"))
        if (not origin or re.match(r"https?://hd\.", origin)) and nbmst:
            origin = "https://%shd.easyinvoice.com.vn" % str(nbmst).split("-")[0].strip()
        if origin:
            t["url"] = origin + "/Search/Index"
    return t


def can_fetch_pdf(tra_cuu):
    t = tra_cuu or {}
    mst = t.get("ncc_mst") or ("0101243150" if str(t.get("ncc", "")).startswith("MISA") else "")
    return bool(t.get("code") or t.get("guid")) and mst in PDF_FETCHERS, mst


def fetch_original_pdf(out_root, mst, kind, key):
    """Tải hoá đơn gốc (PDF + XML) từ trang tra cứu của nhà cung cấp rồi gắn vào hoá đơn. Trả về đường dẫn PDF."""
    return fetch_original(out_root, mst, kind, key)["pdf"]


def needs_original(base, e):
    """Hoá đơn còn thiếu file gốc tải được tự động: thiếu PDF, hoặc thiếu XML gốc (chưa từng thử không được)."""
    if not can_fetch_pdf(e.get("tra_cuu"))[0]:
        return False
    has_pdf = e.get("pdf") and os.path.exists(os.path.join(base, e["pdf"]))
    has_xml = e.get("xml_goc") and os.path.exists(os.path.join(base, e["xml_goc"]))
    return not has_pdf or (not has_xml and not e.get("xml_goc_loi"))


def fetch_original(out_root, mst, kind, key):
    """Tải PDF và XML gốc (bản người bán phát hành, đúng như nút "Tải hoá đơn dạng PDF / XML" trên trang tra cứu).
    Chỉ tải phần còn thiếu. Trả {'pdf': đường dẫn, 'xml': đường dẫn hoặc '', 'xml_err': lý do không có XML}."""
    base = company_dir(out_root, mst)
    e = _load_json(index_file(out_root, mst), {}).get(kind, {}).get(key)
    if e is None:
        raise ValueError("Không tìm thấy hoá đơn")
    ok, prov = can_fetch_pdf(e.get("tra_cuu"))
    if not ok:
        raise ValueError("Chưa hỗ trợ tự tải hoá đơn gốc của nhà cung cấp này – bấm 'Tra cứu' để xem trên trang của nhà cung cấp")
    has_pdf = bool(e.get("pdf") and os.path.exists(os.path.join(base, e["pdf"])))
    has_xml = bool(e.get("xml_goc") and os.path.exists(os.path.join(base, e["xml_goc"])))
    got = ORIGINAL_FETCHERS[prov](fix_lookup(e["tra_cuu"], (e.get("inv") or {}).get("nbmst")),
                                  want_pdf=not has_pdf, want_xml=not has_xml)
    if got.get("pdf") and got.get("pdf_check"):  # PDF tải theo mã → kiểm tra lại đúng hoá đơn rồi mới gắn
        try:
            ok_pdf = match_pdf_text(_pdf_text(got["pdf"]), {kind: {key: e}}) == [(kind, key)]
        except ValueError:
            ok_pdf = False
        if not ok_pdf:
            got["pdf"], got["pdf_err"] = None, "PDF tải về không khớp ký hiệu / số / MST của hoá đơn này – không gắn"
    pdf = attach_pdf(out_root, mst, kind, key, got["pdf"]) if got.get("pdf") else (e.get("pdf") or "")
    xml = ""
    if got.get("xml"):
        try:  # chỉ gắn XML đúng hoá đơn này (MST người bán, ký hiệu, số) – sai thì bỏ, không gắn nhầm
            if match_xml(got["xml"], {kind: {key: e}}) != [(kind, key)]:
                raise ValueError("XML tải về không khớp ký hiệu / số / MST của hoá đơn này – không gắn")
            xml = attach_original_xml(out_root, mst, kind, key, got["xml"])
        except ValueError as err:
            got["xml_err"] = str(err)
    elif has_xml:
        xml = e.get("xml_goc")
    if not xml and not has_xml:
        _set_entry(out_root, mst, kind, key, xml_goc_loi=got.get("xml_err") or "không tải được")
    if got.get("pdf_err") and not pdf:
        raise PortalError(got["pdf_err"] + (" (đã gắn XML gốc)" if xml else ""))
    return {"pdf": pdf, "xml": xml or "", "xml_err": "" if xml else (got.get("xml_err") or "")}


def parse_invoice_xml(xml_path):
    """Đọc XML hoá đơn → {'items': [...], 'rates': [...], 'httt', 'nb_dchi', 'nm_dchi', 'nky'}."""
    import xml.etree.ElementTree as ET
    info = {"items": [], "rates": [], "httt": "", "nb_dchi": "", "nm_dchi": "", "nky": None, "msttcgp": "",
            "ttkhac": {}, "dlhdon_id": "", "nb": {}, "nm": {}, "ttchung": {}, "tong": {}, "mccqt": ""}
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
            if nature == "4":  # dòng ghi chú/diễn giải: giữ tên (Nibot liệt kê với số tiền 0), không tính tiền
                info["items"].append({"name": text(el, "THHDVu"), "unit": "", "qty": 0.0, "price": 0.0, "amount": 0.0,
                                      "rate": norm_rate(text(el, "TSuat")), "tax": 0.0, "discount": 0.0, "nature": "4",
                                      "code": text(el, "MHHDVu")})
                continue
            amount = _float(text(el, "ThTien"))
            if nature == "3":  # chiết khấu thương mại giảm trừ
                amount = -abs(amount)
            rate = norm_rate(text(el, "TSuat"))
            tax_text = text(el, "TThue")
            if not tax_text:  # nhiều nhà cung cấp ghi tiền thuế từng dòng trong TTKhac của dòng hàng
                for tt in el.iter():
                    if _local(tt.tag) == "TTin" and _plain(text(tt, "TTruong")) in ("tthue", "tienthuegtgt", "thuegtgt"):
                        tax_text = text(tt, "DLieu")
                        break
            if tax_text:
                tax = _float(tax_text)
            else:
                tax = round(amount * float(rate) / 100, 2) if rate in VAT_RATES else 0.0
            info["items"].append({
                "name": text(el, "THHDVu"), "unit": text(el, "DVTinh"), "qty": _float(text(el, "SLuong")),
                "price": _float(text(el, "DGia")), "amount": amount, "rate": rate, "tax": tax,
                "discount": _float(text(el, "STCKhau")), "nature": nature, "code": text(el, "MHHDVu")})
        elif tag == "LTSuat":
            info["rates"].append({"rate": norm_rate(text(el, "TSuat")), "base": _float(text(el, "ThTien")),
                                  "tax": _float(text(el, "TThue"))})
        elif tag == "MSTTCGP" and not info["msttcgp"]:
            info["msttcgp"] = (el.text or "").strip()
        elif tag == "MCCQT" and not info["mccqt"]:
            info["mccqt"] = (el.text or "").strip()
        elif tag == "TTin":
            k, v = text(el, "TTruong"), text(el, "DLieu")
            if k and v:
                info["ttkhac"].setdefault(k, v)
        elif tag == "DLHDon" and not info["dlhdon_id"]:
            info["dlhdon_id"] = el.get("Id", "")
        if tag in ("NBan", "NMua"):
            # Chỉ lấy thẻ có thông tin (Tên / MST): phần chữ ký số (DSCKS) cũng có thẻ <NBan> chỉ chứa chữ ký,
            # không được ghi đè thông tin người bán đã đọc.
            d = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
            if d.get("MST") or d.get("Ten") or not info["nb" if tag == "NBan" else "nm"]:
                info["nb" if tag == "NBan" else "nm"] = d
        elif tag == "TTChung":
            info["ttchung"] = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
        elif tag == "TToan":
            info["tong"] = {_local(c.tag): (c.text or "").strip() for c in el if len(c) == 0}
    return info


def _detail_rate(it):
    """Thuế suất một dòng hàng trong dữ liệu chi tiết của cổng: ltsuat ('8%', 'KCT') hoặc tsuat (0.08 / 8)."""
    lt = _dget(it, "ltsuat")
    if lt not in ("", None):
        return norm_rate(lt)
    ts = _dget(it, "tsuat")
    if isinstance(ts, (int, float)) and not isinstance(ts, bool):
        return "%g" % (ts * 100 if 0 < ts < 1 else ts)
    return norm_rate(ts)


def _round_half_up(v):
    return float(int(v + 0.5)) if v >= 0 else -float(int(-v + 0.5))


def detail_lines(inv):
    """Dòng hàng từ dữ liệu "Xem hoá đơn" (JSON) của cổng – cho HĐ không có XML (phí ngân hàng, bảo hiểm…),
    giống Nibot: tên hàng, ĐVT, số lượng, đơn giá, thành tiền, thuế suất (KCT / 8 / 10)."""
    d = inv.get("_detailinfo") or {}
    out = []
    for it in d.get("hdhhdvu") or []:
        nature = str(_dget(it, "tchat") or "")
        amount = _float(_dget(it, "thtien"))
        if nature == "3":
            amount = -abs(amount)
        rate = _detail_rate(it)
        tax = _dget(it, "tthue")
        tax = _float(tax) if tax not in ("", None) else (
            _round_half_up(amount * float(rate) / 100) if rate in VAT_RATES else 0.0)
        if nature == "4":
            amount = tax = 0.0
        out.append({"name": str(_dget(it, "ten", "thhdvu") or ""), "unit": str(_dget(it, "dvtinh") or ""),
                    "qty": _float(_dget(it, "sluong")), "price": _float(_dget(it, "dgia")), "amount": amount,
                    "rate": rate, "tax": tax, "discount": _float(_dget(it, "stckhau")), "nature": nature})
    real = [x for x in out if x["nature"] != "4"]
    if len(real) == 1 and inv.get("tgtthue") is not None and real[0]["rate"] in VAT_RATES:
        real[0]["tax"] = _float(inv.get("tgtthue"))  # một dòng: tiền thuế đúng bằng tổng thuế của hoá đơn
    return out


def invoice_lines(inv):
    """Danh sách dòng hàng của một hoá đơn: XML; không có XML thì dữ liệu chi tiết của cổng; không có nữa thì
    dựng từ tổng tiền. Dòng ghi chú (nature '4') có tiền = 0."""
    info = inv.get("_xmlinfo") or {}
    if info.get("items"):
        return info["items"]
    lines = detail_lines(inv)
    if lines:
        return lines
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
        if it.get("nature") == "4":
            continue
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


def _sign_dt(inv):
    """Ngày ký của người bán (XML), như cột "Ngày ký" của Nibot."""
    return (inv.get("_xmlinfo") or {}).get("nky") or _to_dt(inv.get("nky"))


def _nb_name(name):
    """Tên hàng như Nibot: bỏ khoảng trắng đầu/cuối, tối đa 250 ký tự."""
    return str(name or "").strip()[:250].rstrip()


def write_nibot_workbook(path, kind, invoices, mst, company_name, start, end, changes=None, goods=None,
                         partners=None, units=None):
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
    month_end = lambda y, m: date(y, m, calendar.monthrange(y, m)[1])
    if start.day == 1 and end == month_end(start.year, start.month):
        period = "Tháng %d năm %d" % (start.month, start.year)
    elif start.day == 1 and start.month % 3 == 1 and end == month_end(start.year, start.month + 2):
        period = "Quý %d năm %d" % ((start.month + 2) // 3, start.year)  # như Nibot: "Quý 3 năm 2026"
    else:
        period = "Từ ngày %s đến ngày %s" % (start.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y"))
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
                                       "Thuế suất", "Tiền thuế", "Mã TK", "Mã hàng"])
        for inv in invoices:
            for it in invoice_lines(inv):
                if it.get("nature") == "4":
                    continue
                gm = (goods or {}).get((_goods_key(it["name"]), "R")) or {}
                wi.append(_inv_head(inv) + [it["name"], it["unit"], it["qty"], it["price"], it["amount"],
                                            it["rate"], it["tax"], gm.get("ma_tk", ""), gm.get("ma_hang", "")])
        for row in wi.iter_rows(min_row=2):
            for idx in (10, 11, 13):
                row[idx].number_format = money
        widths(wi, [11, 11, 9, 14, 34, 14, 34, 40, 8, 9, 12, 14, 8, 12])
        wb.save(path)
        return path

    good = [i for i in invoices if not _is_bad(i)]
    bad = [i for i in invoices if _is_bad(i)]

    # Ghi chú + TK có như Nibot: nhiều HĐ cùng ngày, cùng người bán, tổng >= 5 triệu → "cùng ngày…" (TK 331, kể cả
    # khi từng HĐ đã > 5 triệu); còn lại HĐ > 5 triệu → "HĐ có giá trị > 5,000,000 đồng" (TK 331); khác → 1111.
    day_key = lambda i: (invoice_date(i), str(i.get("nbmst") or ""))
    day_sum, day_cnt = collections.defaultdict(float), collections.Counter()
    for i in good:
        day_sum[day_key(i)] += _float(i.get("tgtttbso"))
        day_cnt[day_key(i)] += 1

    def cash_note(inv):
        k = day_key(inv)
        if day_cnt[k] >= 2 and day_sum[k] >= 5000000:
            return "HĐ cùng ngày, cùng ĐTPN và có tổng giá trị >= 5,000,000 đồng"
        if _float(inv.get("tgtttbso")) > 5000000:
            return "HĐ có giá trị > 5,000,000 đồng"
        return ""

    def bk_note(inv):  # cột Ghi chú của bảng kê: như Nibot (không ghi "HĐ có mã từ máy tính tiền")
        tthai = _num(inv.get("tthai"))
        return " - ".join(x for x in (cash_note(inv), TTHAI_NIBOT.get(tthai, "") if tthai != 1 else "") if x)

    # 2. Smart_KTSC_OK / CAN_XEM_XET (mẫu import phần mềm kế toán Smart Pro) ------
    def smart_rows(inv, bad_inv):
        info = inv.get("_xmlinfo") or {}
        d = invoice_date(inv)
        ngay = datetime(d.year, d.month, d.day) if d else None
        cash = cash_note(inv)
        big = bool(cash)
        sr = "%s%s" % (inv.get("khmshdon") or "", inv.get("khhdon") or "")
        so = str(inv.get("shdon") or "")
        ghichu = [cash] if cash else []
        tthai = _num(inv.get("tthai"))
        if tthai != 1 and TTHAI_NIBOT.get(tthai):  # như Nibot: không ghi "HĐ có mã từ máy tính tiền"
            ghichu.append(TTHAI_NIBOT[tthai])
        tkco = "331" if big else "1111"
        lctg = dien_giai = ""
        pt = (partners or {}).get((str(inv.get("nbmst") or "").strip(), "V")) or {}  # ĐTPN: mã NCC, TK TM/CK, TK CP
        if pt.get("tk_tt"):
            tkco = pt["tk_tt"]  # như file Nibot: để trống loại chứng từ và diễn giải
        nbmst, nbten = str(inv.get("nbmst") or ""), inv.get("nbten") or ""
        ma_dt = pt.get("ma_kh") or nbmst
        dchi = inv.get("nbdchi") or info.get("nb_dchi", "")
        guid = str(uuid.uuid5(uuid.NAMESPACE_URL, invoice_key(inv)))
        lines = invoice_lines(inv)
        if bad_inv:
            label = TTHAI_NIBOT.get(_num(inv.get("tthai")), "")
            lines = [{"name": label, "unit": "", "qty": 0.0, "price": 0.0, "amount": 0.0,
                      "rate": (lines[0]["rate"] if lines else ""), "tax": 0.0, "discount": 0.0}]
            ghichu, dien_giai, lctg = [label], "", ""
        first_rate = next((x["rate"] for x in lines if x.get("rate")), "")
        out = []
        for it in lines:
            it = dict(it, rate=it.get("rate") or first_rate)  # dòng ghi chú: thuế suất theo dòng hàng (như Nibot)
            gm = (goods or {}).get((_goods_key(it["name"]), "V")) or {}  # danh mục Hàng hoá: Mã TK → TKNO, Mã hàng → MADMNO
            unit, qty, price = unit_convert(it, units, gm.get("ma_hang"))     # Quy đổi ĐVT
            out.append(["; ".join(ghichu), lctg, sr, so, info.get("nky") or _to_dt(inv.get("nky")), ngay,
                        _num(so), ngay, dien_giai, inv.get("thtttoan") or info.get("httt", ""),
                        gm.get("ma_tk") or pt.get("tk_cpdt", ""), "", tkco, ma_dt, gm.get("ma_hang", ""), "",
                        _nb_name(it["name"]), _nb_name(it["name"]), 0, "", unit, qty, 0, 0, 0, price,
                        it["amount"], 0, it.get("discount", 0.0), "V", "1331", it["rate"], 0, it["tax"], 0,
                        it["amount"] + it["tax"], ma_dt, nbten, nbten, dchi, nbmst, dchi, "", "TIENHANG", "", guid])
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
    def bang_ke(title, heading, groups, with_tax, sec_of=None):
        wsb = wb.create_sheet(title)
        last = "M"
        wsb["A4"] = heading
        wsb["A4"].font = Font(bold=True, size=13)
        wsb["A4"].alignment = Alignment(horizontal="center")
        wsb.merge_cells("A4:%s4" % last)
        wsb["A5"] = "Bangkemuavao.xls"  # như Nibot
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
        header_row(wsb, 13, ["XML", "PDF", "Ngày ký"], 16)  # cột phụ như Nibot (P, Q, R)
        header_row(wsb, 16, ["[X]", "[P]", "[Q]"], 16)
        heads = ("1. HH, DV dùng riêng cho SXKD chịu thuế GTGT và sử dụng cho các hoạt động cung cấp HH, DV "
                 "không kê khai, nộp thuế GTGT đủ điều kiện khấu trừ thuế: ",
                 "2. HH, DV dùng chung cho SXKD chịu thuế và không chịu thuế đủ điều kiện khấu trừ thuế:",
                 "3. HH, DV dùng cho dự án đầu tư đủ điều kiện được khấu trừ thuế (*):")
        # mục theo "Loại HHDV SXKD" của ĐTPN (1 / 2 / 3; để trống = mục 1)
        sections = [[x for x in groups if (sec_of(x[0]) if sec_of else 1) == k] for k in (1, 2, 3)]
        r, totals = 17, []
        for k, rows_ in enumerate(sections):
            wsb.cell(row=r, column=2, value=heads[k]).font = bold
            r += 1
            first = r
            for n, (inv, g) in enumerate(rows_, 1):
                d = invoice_date(inv)
                vals = [n, _num(inv.get("khmshdon")), inv.get("khhdon") or "", _num(inv.get("shdon")),
                        datetime(d.year, d.month, d.day) if d else None, inv.get("nbten") or "",
                        str(inv.get("nbmst") or ""), g["names"][0] if g["names"] else "", g["base"],
                        g["rate"], g["tax"] if with_tax else None, bk_note(inv), None, None, None, None, _sign_dt(inv)]
                for i, v in enumerate(vals, 2):
                    if v is None and i > 13:
                        continue
                    c = wsb.cell(row=r, column=i, value=v)
                    if i == 18:
                        c.number_format = "dd/mm/yyyy hh:mm:ss"
                        continue
                    c.border = box
                    if i == 6:
                        c.number_format = dfmt
                    elif i in (10, 12):
                        c.number_format = money
                r += 1
            if not rows_ and k:
                r += 1  # mục 2, 3 trống: để một dòng trống như mẫu
            wsb.cell(row=r, column=2, value="Tổng").font = bold
            if rows_:
                for col in ("J", "L") if with_tax else ("J",):
                    c = wsb["%s%d" % (col, r)]
                    c.value, c.font, c.number_format = "=SUM(%s%d:%s%d)" % (col, first, col, r - 1), bold, money
                totals.append(r)
            r += 1
        r += 1
        wsb.cell(row=r, column=2, value="Tổng giá trị HHDV mua vào phục vụ SXKD được khấu trừ thuế GTGT (**):")
        c = wsb.cell(row=r, column=10, value="=" + "+".join("J%d" % t for t in totals) if totals else 0)
        c.number_format, c.font = money, bold
        if with_tax:
            wsb.cell(row=r + 1, column=2, value="Tổng số thuế GTGT của HHDV mua vào đủ điều kiện được khấu trừ (***):")
            c = wsb.cell(row=r + 1, column=12, value="=" + "+".join("L%d" % t for t in totals) if totals else 0)
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
    def sec_of(inv):  # ĐTPN "Loại HHDV SXKD": 1 dùng riêng (mặc định), 2 dùng chung, 3 dự án đầu tư
        sx = ((partners or {}).get((str(inv.get("nbmst") or "").strip(), "V")) or {}).get("sxkd") or "1"
        return int(sx) if sx in ("1", "2", "3") else 1
    bang_ke("BangKe_MuaVao", "BẢNG KÊ HOÁ ĐƠN, CHỨNG TỪ HÀNG HOÁ, DỊCH VỤ MUA VÀO", vat_groups, True, sec_of)
    if kct_groups:  # bố cục như Nibot: không chia mục 1/2/3, tách tổng không chịu thuế / HĐ bán hàng (mẫu 2)
        wsk = wb.create_sheet("BangKe_MuaVao_KCT_HDBH")
        wsk["A4"] = "BẢNG KÊ HOÁ ĐƠN, CHỨNG TỪ HÀNG HOÁ, DỊCH VỤ MUA VÀO KHÔNG CHỊU THUẾ, HÓA ĐƠN BÁN HÀNG"
        wsk["A4"].font = Font(bold=True, size=13)
        wsk["A4"].alignment = Alignment(horizontal="center")
        wsk.merge_cells("A4:K4")
        wsk["A5"] = "Bangkemuavao.xls"
        wsk["B7"] = "Kỳ tính thuế: %s" % period
        wsk["B7"].alignment = Alignment(horizontal="center")
        wsk.merge_cells("B7:K7")
        wsk["B9"] = "Người nộp thuế: %s" % (company_name or "")
        wsk["B10"] = "Mã số thuế: %s" % mst
        wsk["B12"] = "Đơn vị tiền: đồng Việt Nam"
        header_row(wsk, 13, ["STT", "", "", "", "", "Tên người bán", "Mã số thuế người bán", "Mặt hàng",
                             "Doanh số hàng hoá, dịch vụ mua vào", "Ghi chú"], 2)
        header_row(wsk, 15, ["KH mẫu HĐ", "Ký hiệu hoá đơn", "Số hoá đơn", "Ngày, tháng, năm lập hóa đơn"], 3)
        for col in "BGHIJK":
            wsk.merge_cells("%s13:%s15" % (col, col))
        wsk.merge_cells("C13:F14")
        header_row(wsk, 16, ["[1]", "[2]", "[3]", "[2]", "[3]", "[4]", "[5]", "[6]", "[6]", "[7]"], 2)
        header_row(wsk, 13, ["XML", "PDF", "Ngày ký"], 16)
        header_row(wsk, 16, ["[X]", "[P]", "[Q]"], 16)
        r = 17
        for n, (inv, g) in enumerate(kct_groups, 1):
            d = invoice_date(inv)
            vals = [n, _num(inv.get("khmshdon")), inv.get("khhdon") or "", _num(inv.get("shdon")),
                    datetime(d.year, d.month, d.day) if d else None, inv.get("nbten") or "",
                    str(inv.get("nbmst") or ""), None, g["base"], bk_note(inv) or None]
            if _sign_dt(inv):
                wsk.cell(row=r, column=18, value=_sign_dt(inv)).number_format = "dd/mm/yyyy hh:mm:ss"
            for i, v in enumerate(vals, 2):
                c = wsk.cell(row=r, column=i, value=v)
                c.border = box
                if i == 6:
                    c.number_format = dfmt
                elif i == 10:
                    c.number_format = money
            r += 1
        last = r - 1
        wsk.cell(row=r, column=2, value="Tổng").font = bold
        for row, label, formula in ((r, None, "=SUM(J17:J%d)" % last),
                                    (r + 2, "Tổng giá trị HHDV mua vào không chịu thuế GTGT (**):",
                                     '=SUMIF(C17:C%d,"<>2",J17:J%d)' % (last, last)),
                                    (r + 3, "Tổng giá trị Hóa đơn Bán hàng (ký hiệu mẫu số 2) (**):",
                                     '=SUMIF(C17:C%d,"=2",J17:J%d)' % (last, last))):
            if label:
                wsk.cell(row=row, column=2, value=label)
            c = wsk.cell(row=row, column=10, value=formula)
            c.font, c.number_format = bold, money
        wsk.cell(row=r + 4, column=2, value="*Ghi chú: Số liệu trên bảng kê này dùng để tập hợp thêm vào giá trị hàng "
                                            "hoá, dịch vụ mua vào (chỉ tiêu 23) trên tờ khai 01GTKT kết hợp với bảng kê "
                                            "mua vào 01-2/GTGT").font = Font(italic=True)
        r += 6
        for i, t in enumerate([sign_date, "NGƯỜI NỘP THUẾ hoặc", "ĐẠI DIỆN HỢP PHÁP CỦA NGƯỜI NỘP THUẾ",
                               " Ký tên, đóng dấu (ghi rõ họ tên và chức vụ)"]):
            c = wsk.cell(row=r + i, column=9, value=t)
            c.alignment = Alignment(horizontal="center")
            c.font = Font(bold=0 < i < 3, italic=i in (0, 3))
            wsk.merge_cells("I%d:K%d" % (r + i, r + i))
        wsk.freeze_panes = "B17"
        widths(wsk, [2, 6, 6, 10, 10, 11, 36, 14, 30, 16, 40])

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
    ht_lines = []  # như Nibot: liệt kê từng dòng hàng của mọi hoá đơn (kể cả KCT, dòng ghi chú tiền 0)
    for inv in good:
        lines = invoice_lines(inv)
        first_rate = next((x["rate"] for x in lines if x.get("rate")), "")
        ht_lines += [(inv, dict(it, rate=it.get("rate") or first_rate)) for it in lines]
    for inv, it in ht_lines:
        d = invoice_date(inv)
        vals = [r - 16, str(inv.get("khmshdon") or ""), inv.get("khhdon") or "", str(inv.get("shdon") or ""),
                datetime(d.year, d.month, d.day) if d else None, inv.get("nbten") or "", str(inv.get("nbmst") or ""),
                _nb_name(it["name"]), it["unit"], it["qty"], it["price"], it["amount"], it["rate"], it["tax"], ""]
        for i, v in enumerate(vals, 2):
            c = wsh.cell(row=r, column=i, value=v)
            c.border = box
            if i == 6:
                c.number_format = dfmt
            elif i in (12, 13, 15):
                c.number_format = money
        r += 1
    wsh.cell(row=r, column=5, value="TỔNG CỘNG").font = bold
    for col in "KLN":
        wsh["%s%d" % (col, r)] = "\\"
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


_FERNET = None  # bản web trên máy chủ: mã hoá mật khẩu cổng thuế bằng khoá bí mật của máy chủ


def set_secret_key(path=None, key=None):
    """Bật mã hoá Fernet (AES) cho mật khẩu cổng thuế. Khoá lấy từ biến môi trường hoặc file (tự tạo, quyền 600)."""
    global _FERNET
    try:
        from cryptography.fernet import Fernet
    except ImportError:
        raise SystemExit("Bản web cần thư viện cryptography: pip install cryptography")
    if not key:
        if path and os.path.exists(path):
            with open(path, "rb") as f:
                key = f.read().strip()
        else:
            key = Fernet.generate_key()
            os.makedirs(os.path.dirname(path), exist_ok=True)
            fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
            with os.fdopen(fd, "wb") as f:
                f.write(key)
    _FERNET = Fernet(key if isinstance(key, bytes) else key.encode())


def protect(text):
    import base64
    raw = text.encode("utf-8")
    if _FERNET is not None:
        return "fernet:" + _FERNET.encrypt(raw).decode()
    if os.name == "nt":
        return "dpapi:" + base64.b64encode(_dpapi(raw, True)).decode()
    return "b64:" + base64.b64encode(raw).decode()


def unprotect(value):
    import base64
    if not value:
        return ""
    kind, _, data = value.partition(":")
    if kind == "fernet":
        if _FERNET is None:
            raise PortalError("Mật khẩu được mã hoá bằng khoá máy chủ – chạy bản web để dùng")
        try:
            return _FERNET.decrypt(data.encode()).decode("utf-8")
        except Exception:
            raise PortalError("Không giải mã được mật khẩu (sai khoá bí mật máy chủ?) – nhập lại mật khẩu")
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

    def reencrypt(self):
        """Chuyển mật khẩu đang lưu dạng cũ (base64 / DPAPI) sang cách mã hoá hiện tại (khoá máy chủ)."""
        n = 0
        with self.lock:
            for c in self.items:
                pw = c.get("pw") or ""
                if pw and not pw.startswith("fernet:") and _FERNET is not None:
                    try:
                        c["pw"] = protect(unprotect(pw))
                        n += 1
                    except Exception:
                        c["loi"] = "Không đọc được mật khẩu cũ – nhập lại mật khẩu"
            if n:
                self.save()
        return n


# ---------------------------------------------------------------------------
# Người dùng phần mềm (bản web): quản trị (admin) xem tất cả; nhân viên chỉ thấy các DN được giao
# ---------------------------------------------------------------------------

ROLES = {"admin": "Quản trị", "staff": "Nhân viên"}
DEFAULT_VP = "goc"  # văn phòng đầu tiên: dùng nguyên thư mục dữ liệu cũ (không phải chuyển file)
# Quyền theo từng mục cho nhân viên (quản trị văn phòng có tất cả). Nhân viên tạo trước khi có phân quyền: có hết.
PERMS = {"dongbo": "Đồng bộ / tải hoá đơn", "hdgoc": "Tải và gắn hoá đơn gốc",
         "sua": "Ghi chú, duyệt nội bộ, đánh dấu HĐ dịch vụ", "ketxuat": "Kết xuất Excel / ZIP",
         "saoke": "Sao kê ngân hàng", "dn": "Thêm / sửa doanh nghiệp, mật khẩu cổng thuế"}
STAFF_DEFAULT_PERMS = [k for k in PERMS if k != "dn"]


EMAIL_RE = re.compile(r"[^@\s]+@[^@\s]+\.[A-Za-z]{2,}")


def user_vp(user):
    return (user or {}).get("vp") or DEFAULT_VP


class TenantStore:
    """Danh sách văn phòng (công ty dịch vụ kế toán) dùng chung máy chủ. Mỗi văn phòng có doanh nghiệp, người dùng,
    cài đặt, thông báo và thư mục dữ liệu riêng."""

    def __init__(self, path):
        self.path = path
        self.lock = threading.RLock()
        self.items = _load_json(path, [])

    def _default(self):
        return {"id": DEFAULT_VP, "ten": "Văn phòng chính", "active": True, "max_dn": 0, "max_jobs": 0,
                "tao": "", "ghichu": "", "han_muc_hd": 0, "tai_goc": True, "han_muc_goc": 0}

    def list(self):
        with self.lock:
            ids = {t["id"] for t in self.items}
            out = ([self._default()] if DEFAULT_VP not in ids else []) + [dict(t) for t in self.items]
            for t in out:  # văn phòng tạo trước khi có gói theo số hoá đơn: không giới hạn, có tải HĐ gốc
                t.setdefault("han_muc_hd", 0)
                t.setdefault("tai_goc", True)
                t.setdefault("han_muc_goc", 0)
            return out

    def get(self, vid):
        return next((t for t in self.list() if t["id"] == vid), None)

    def upsert(self, vid, create=False, **fields):
        vid = (vid or "").strip().lower()
        if not re.fullmatch(r"[a-z0-9][a-z0-9-]{1,29}", vid):
            raise ValueError("Mã văn phòng 2–30 ký tự: chữ thường không dấu, số, dấu - (vd: ketoan-abc)")
        with self.lock:
            t = next((x for x in self.items if x["id"] == vid), None)
            if t and create or (create and vid == DEFAULT_VP):
                raise ValueError("Mã văn phòng %s đã có" % vid)
            if not t:
                if not create and vid != DEFAULT_VP:
                    raise ValueError("Không tìm thấy văn phòng %s" % vid)
                t = self._default() if vid == DEFAULT_VP else {
                    "id": vid, "ten": "", "active": True, "max_dn": 0, "max_jobs": 2, "ghichu": "",
                    "tao": date.today().strftime("%d/%m/%y"), "han_muc_hd": 0, "tai_goc": True, "han_muc_goc": 0}
                self.items.append(t)
            if fields.get("ten") is not None:
                t["ten"] = str(fields["ten"]).strip()[:150]
            if fields.get("ghichu") is not None:
                t["ghichu"] = str(fields["ghichu"]).strip()[:300]
            for k in ("max_dn", "max_jobs", "han_muc_hd", "han_muc_goc", "max_may"):
                if fields.get(k) is not None:
                    t[k] = max(0, int(fields[k] or 0))
            if fields.get("het_han") is not None:  # ngày hết hạn gói (yyyy-mm-dd, trống = không hạn)
                h = str(fields["het_han"]).strip()
                if h and not re.fullmatch(r"\d{4}-\d{2}-\d{2}", h):
                    raise ValueError("Ngày hết hạn không hợp lệ")
                t["het_han"] = h
            if fields.get("tai_goc") is not None:
                t["tai_goc"] = bool(fields["tai_goc"])
            if fields.get("active") is not None:
                if vid == DEFAULT_VP and not fields["active"]:
                    raise ValueError("Không khoá văn phòng chính (văn phòng của chủ hệ thống)")
                t["active"] = bool(fields["active"])
            if not t["ten"]:
                raise ValueError("Nhập tên văn phòng")
            _save_json(self.path, self.items)
            return dict(t)


class Tenant:
    """Dữ liệu đang dùng của một văn phòng: danh sách doanh nghiệp, phiên đăng nhập cổng thuế đang giữ."""

    def __init__(self, vid, root):
        self.id, self.root = vid, root
        self.cfg = os.path.join(root, "_cau-hinh")
        self.store = Store(os.path.join(self.cfg, "doanh-nghiep.json"))
        self.clients = {}


_QUOTA_LOCK = threading.Lock()


class Quota:
    """Hạn mức theo gói của văn phòng: tổng số hoá đơn tải về (mỗi hoá đơn mới tính một lần, đồng bộ lại không tính)
    và số hoá đơn gốc tự tải từ trang tra cứu (gói có thể không gồm tải HĐ gốc). 0 = không giới hạn.
    Số đã dùng lưu ở <văn phòng>/_cau-hinh/su-dung.json."""

    def __init__(self, tenants, tenant):
        self.tenants, self.vid = tenants, tenant.id
        self.path = os.path.join(tenant.cfg, "su-dung.json")

    def info(self):
        t = self.tenants.get(self.vid) or {}
        used = _load_json(self.path, {})
        return {"han_muc_hd": t.get("han_muc_hd", 0), "da_dung_hd": used.get("hd", 0),
                "tai_goc": t.get("tai_goc", True), "han_muc_goc": t.get("han_muc_goc", 0),
                "da_dung_goc": used.get("goc", 0)}

    def _take(self, kind, n, limit):
        with _QUOTA_LOCK:
            used = _load_json(self.path, {})
            cur = used.get(kind, 0)
            n = n if not limit else max(0, min(n, limit - cur))
            if n:
                used[kind] = cur + n
                used.setdefault("lich_su", []).append([datetime.now().strftime("%Y-%m-%d %H:%M"), kind, n])
                used["lich_su"] = used["lich_su"][-500:]
                _save_json(self.path, used)
            return n

    def take_hd(self, n):
        """Xin n hoá đơn mới; trả về số được phép tải (ít hơn n khi sắp hết hạn mức)."""
        return self._take("hd", n, self.info()["han_muc_hd"])

    def goc_error(self):
        i = self.info()
        if not i["tai_goc"]:
            return "Gói của văn phòng không gồm tự tải hoá đơn gốc"
        if i["han_muc_goc"] and i["da_dung_goc"] >= i["han_muc_goc"]:
            return "Đã dùng hết hạn mức %d hoá đơn gốc của gói" % i["han_muc_goc"]
        return ""

    def take_goc(self):
        i = self.info()
        if not i["tai_goc"]:
            return False
        return self._take("goc", 1, i["han_muc_goc"]) == 1

    def refund_goc(self):
        with _QUOTA_LOCK:
            used = _load_json(self.path, {})
            used["goc"] = max(0, used.get("goc", 0) - 1)
            _save_json(self.path, used)

    def reset(self, kind):
        with _QUOTA_LOCK:
            used = _load_json(self.path, {})
            used.setdefault("lich_su", []).append([datetime.now().strftime("%Y-%m-%d %H:%M"), "dat-lai-" + kind,
                                                   used.get(kind, 0)])
            used[kind] = 0
            _save_json(self.path, used)


# ---------------------------------------------------------------------------
# Bản cài trên máy khách (exe) – cấp phép từ máy chủ. Máy con tự tải hoá đơn về ổ cứng của nó; máy chủ chỉ giữ
# tài khoản, gói / hạn mức, danh sách máy đã kích hoạt và MÃ BĂM của hoá đơn đã tính (không lưu nội dung hoá đơn,
# không nhận mật khẩu cổng thuế).
# ---------------------------------------------------------------------------
LIC_TOKEN_DAYS = 30
DEFAULT_MAX_MAY = 2


def _b64u(b):
    import base64
    return base64.urlsafe_b64encode(b).rstrip(b"=").decode()


def _b64u_dec(s):
    import base64
    return base64.urlsafe_b64decode(s + "=" * (-len(s) % 4))


class LicenseBlocked(PermissionError):
    """Văn phòng bị khoá / hết hạn gói / phiên bản quá cũ: chặn tải nhưng KHÔNG bắt máy con đăng nhập lại."""


def version_tuple(v):
    return tuple(int(x) for x in re.findall(r"\d+", str(v or "0"))[:4]) or (0,)


EXE_NAME = "TaiHoaDon.exe"


def exe_path(app):
    """File exe phát cho khách: chủ hệ thống tải lên trong Quản trị (hoặc chép thẳng vào thư mục này)."""
    return os.path.join(app.cfg, "ban-cai", EXE_NAME)


def exe_info(app):
    p = exe_path(app)
    if not os.path.isfile(p):
        return None
    st = os.stat(p)
    return {"size": st.st_size, "time": datetime.fromtimestamp(st.st_mtime).strftime("%d/%m/%Y %H:%M"),
            "version": _load_json(p + ".json", {}).get("version", "")}


DOWNLOAD_PAGE = """<!doctype html><html lang=vi><head><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1">
<title>Tải phần mềm Tải hoá đơn</title><style>body{font-family:system-ui,Segoe UI,Arial;max-width:640px;margin:40px auto;padding:0 16px;
color:#1d2433}a.b{display:inline-block;background:#2f3fa0;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:600}
li{margin:6px 0}.m{color:#667}</style></head><body><h2>Phần mềm Tải hoá đơn – bản cài trên máy (Windows)</h2>%s
<ol><li>Tải file <b>TaiHoaDon.exe</b>, đặt vào một thư mục riêng (vd <code>D:\\TaiHoaDon</code>) – hoá đơn sẽ lưu ở thư mục
<code>HoaDon</code> cạnh file.</li><li>Bấm đúp để chạy. Windows có thể cảnh báo "Windows protected your PC" → <i>More info</i> → <i>Run anyway</i>.</li>
<li>Đăng nhập bằng <b>tài khoản nhà cung cấp đã cấp</b> (máy chủ: <code>%s</code>).</li></ol>
<p class=m>Hoá đơn và mật khẩu cổng thuế chỉ lưu trên máy của bạn.</p></body></html>"""


class Licenses:
    """Phía máy chủ: đăng nhập máy con, kiểm tra phiên, trừ hạn mức theo mã băm hoá đơn."""

    def __init__(self, app):
        self.app = app
        kp = os.path.join(app.cfg, "ban-quyen.key")
        if not os.path.exists(kp):
            os.makedirs(app.cfg, exist_ok=True)
            with open(kp, "wb") as f:
                f.write(secrets.token_bytes(32))
        with open(kp, "rb") as f:
            self.key = f.read()
        self.lock = threading.RLock()
        self._counted = {}  # văn phòng → tập mã băm hoá đơn đã tính

    # ---- mã phiên (token) ký HMAC ----
    def sign(self, payload):
        import hmac
        body = _b64u(json.dumps(payload, separators=(",", ":")).encode())
        return body + "." + _b64u(hmac.new(self.key, body.encode(), "sha256").digest())

    def unsign(self, token):
        import hmac
        try:
            body, sig = str(token or "").split(".", 1)
            good = _b64u(hmac.new(self.key, body.encode(), "sha256").digest())
            if not hmac.compare_digest(sig, good):
                return None
            return json.loads(_b64u_dec(body))
        except (ValueError, TypeError):
            return None

    # ---- máy đã kích hoạt (theo văn phòng) ----
    def _dev_path(self, vid):
        return os.path.join(self.app.t(vid).cfg, "may-con.json")

    def devices(self, vid):
        return _load_json(self._dev_path(vid), {})

    def remove_device(self, vid, device_id):
        with self.lock:
            d = self.devices(vid)
            if d.pop(device_id, None) is None:
                raise ValueError("Không tìm thấy máy")
            _save_json(self._dev_path(vid), d)

    def _register(self, vid, device_id, name, user, version, ip):
        with self.lock:
            d = self.devices(vid)
            limit = self.app.vp_info(vid).get("max_may", DEFAULT_MAX_MAY)
            if device_id not in d and limit and len(d) >= limit:
                raise PermissionError("Văn phòng đã kích hoạt đủ %d máy. Nhờ quản trị gỡ máy cũ (Quản trị › Máy cài phần "
                                      "mềm) hoặc liên hệ nhà cung cấp để tăng số máy." % limit)
            now = datetime.now().strftime("%d/%m/%Y %H:%M")
            cur = d.get(device_id) or {"dang_ky": now}
            cur.update(ten=str(name or "")[:80], user=user, lan_cuoi=now, phien_ban=str(version or "")[:20], ip=ip)
            d[device_id] = cur
            _save_json(self._dev_path(vid), d)

    # ---- kiểm tra ----
    def _check_vp(self, vid, version):
        info = self.app.vp_info(vid)
        if not info.get("active", False):
            raise LicenseBlocked("Văn phòng của tài khoản này đang bị khoá – liên hệ nhà cung cấp phần mềm")
        if info.get("het_han") and info["het_han"] < date.today().isoformat():
            raise LicenseBlocked("Gói đã hết hạn ngày %s – liên hệ nhà cung cấp để gia hạn"
                                  % datetime.strptime(info["het_han"], "%Y-%m-%d").strftime("%d/%m/%Y"))
        need = self.app.system().get("phien_ban_toi_thieu") or ""
        if need and version_tuple(version) < version_tuple(need):
            raise LicenseBlocked("Phiên bản %s đã cũ – cài bản %s trở lên để tiếp tục tải hoá đơn (%s)"
                                  % (version or "?", need, self.latest()["url"]))

    def latest(self):
        s = self.app.system()
        info = exe_info(self.app) or {}
        return {"version": s.get("phien_ban_moi") or info.get("version") or __version__,
                "url": s.get("link_tai") or "/tai-ve/" + EXE_NAME}  # đường dẫn tương đối: máy con ghép với địa chỉ máy chủ

    def info(self, vid, u):
        q = self.app.quota(vid).info()
        vp = self.app.vp_info(vid)
        return {"user": u["username"], "ten": u.get("ten", ""), "vp": vid, "vp_ten": vp.get("ten", ""),
                "het_han": vp.get("het_han", ""), "goi": q, "latest": self.latest(),
                "thong_bao": self.app.system().get("thong_bao", "")}

    def login(self, username, password, device_id, device_name, version, ip):
        if not re.fullmatch(r"[A-Za-z0-9-]{8,64}", str(device_id or "")):
            raise ValueError("Mã máy không hợp lệ")
        u = self.app.users.authenticate(username, password)
        if not u:
            raise LookupError("Sai tên đăng nhập hoặc mật khẩu")
        vid = user_vp(u)
        self._check_vp(vid, version)
        self._register(vid, device_id, device_name, u["username"], version, ip)
        exp = int(time.time()) + LIC_TOKEN_DAYS * 86400
        token = self.sign({"u": u["username"], "d": device_id, "v": vid, "exp": exp})
        return {"token": token, "info": self.info(vid, u)}

    def session(self, token, device_id, version, ip=""):
        """Kiểm tra mã phiên của máy con → (user, văn phòng). Gia hạn lần dùng cuối của máy."""
        p = self.unsign(token)
        if not p or p.get("d") != device_id:
            raise PermissionError("Phiên bản quyền không hợp lệ – đăng nhập lại")
        if p.get("exp", 0) < time.time():
            raise PermissionError("Phiên bản quyền đã hết hạn – đăng nhập lại")
        u = self.app.users.get(p.get("u"))
        if not u or not u.get("active", True) or user_vp(u) != p.get("v"):
            raise PermissionError("Tài khoản đã bị khoá hoặc xoá – liên hệ quản trị")
        vid = p["v"]
        if device_id not in self.devices(vid):
            raise PermissionError("Máy này đã bị gỡ khỏi danh sách máy được dùng – đăng nhập lại để kích hoạt")
        self._check_vp(vid, version)
        with self.lock:
            d = self.devices(vid)
            if device_id in d:
                d[device_id].update(lan_cuoi=datetime.now().strftime("%d/%m/%Y %H:%M"), phien_ban=str(version or "")[:20],
                                    ip=ip or d[device_id].get("ip", ""))
                _save_json(self._dev_path(vid), d)
        return u, vid

    # ---- hạn mức theo mã băm hoá đơn: mỗi hoá đơn chỉ tính 1 lần cho văn phòng, dù tải trên máy nào ----
    def _counted_path(self, vid):
        return os.path.join(self.app.t(vid).cfg, "hd-da-tinh.txt")

    def _counted_set(self, vid):
        if vid not in self._counted:
            try:
                with open(self._counted_path(vid), encoding="ascii") as f:
                    self._counted[vid] = {x.strip() for x in f if x.strip()}
            except OSError:
                self._counted[vid] = set()
        return self._counted[vid]

    def take(self, vid, hashes):
        """Xin tải các hoá đơn (mã băm). Trả về danh sách mã được phép: hoá đơn đã tính trước đây luôn được phép
        (không trừ lại); hoá đơn mới trừ hạn mức, hết hạn mức thì không được phép."""
        hashes = [h for h in dict.fromkeys(hashes or []) if re.fullmatch(r"[0-9a-f]{64}", str(h))][:20000]
        with self.lock:
            seen = self._counted_set(vid)
            fresh = [h for h in hashes if h not in seen]
            got = self.app.quota(vid).take_hd(len(fresh)) if fresh else 0
            ok_new = fresh[:got]
            if ok_new:
                with open(self._counted_path(vid), "a", encoding="ascii") as f:
                    f.write("".join(h + "\n" for h in ok_new))
                seen.update(ok_new)
            allowed = [h for h in hashes if h in seen]
        return {"allowed": allowed, "moi": len(ok_new), "tu_choi": len(fresh) - len(ok_new),
                "goi": self.app.quota(vid).info()}


class LicenseError(Exception):
    def __init__(self, msg, relogin=False, offline=False):
        super().__init__(msg)
        self.relogin, self.offline = relogin, offline


DEFAULT_LICENSE_SERVER = os.environ.get("TAIHOADON_LICENSE_SERVER") or "https://taihoadon.tinhoc119.com"


class LicenseClient:
    """Bản cài trên máy khách: đăng nhập tài khoản do máy chủ cấp, xin tải hoá đơn mới (trừ hạn mức theo mã băm).
    Hoá đơn và mật khẩu cổng thuế ở lại trên máy này – chỉ gửi mã băm (SHA-256) của khoá hoá đơn lên máy chủ.
    Lưu ở _cau-hinh/ban-quyen.json (mã phiên được mã hoá bằng Windows DPAPI)."""

    def __init__(self, cfg_dir, server=None):
        self.path = os.path.join(cfg_dir, "ban-quyen.json")
        self.lock = threading.Lock()
        self.d = _load_json(self.path, {})
        if not self.d.get("device_id"):
            self.d["device_id"] = str(uuid.uuid4())
        self.d.setdefault("server", server or DEFAULT_LICENSE_SERVER)
        self.last_error = ""
        self._save()

    def _save(self):
        os.makedirs(os.path.dirname(self.path), exist_ok=True)
        _save_json(self.path, self.d)

    @property
    def logged(self):
        return bool(self.d.get("token"))

    def _token(self):
        try:
            return unprotect(self.d["token"]) if self.d.get("token") else ""
        except Exception:
            return ""

    def _call(self, path, body, timeout=40):
        import platform
        body = dict(body, device_id=self.d["device_id"], version=__version__)
        req = urllib.request.Request(self.d["server"].rstrip("/") + path, data=json.dumps(body).encode(),
                                     headers={"Content-Type": "application/json", "User-Agent": "TaiHoaDon/" + __version__
                                              + " (" + platform.system() + ")"})
        try:
            with urllib.request.urlopen(req, timeout=timeout) as r:
                return json.loads(r.read().decode("utf-8") or "{}")
        except urllib.error.HTTPError as e:
            try:
                d = json.loads(e.read().decode("utf-8") or "{}")
            except ValueError:
                d = {}
            raise LicenseError(d.get("error") or "Máy chủ bản quyền báo lỗi HTTP %d" % e.code, relogin=bool(d.get("lic")))
        except (urllib.error.URLError, TimeoutError, ConnectionError, OSError) as e:
            raise LicenseError("Không kết nối được máy chủ bản quyền %s (%s) – cần mạng Internet để tải hoá đơn mới; "
                               "xem / kết xuất dữ liệu đã tải vẫn dùng bình thường." % (self.d["server"], getattr(e, "reason", e)),
                               offline=True)

    def _authed(self, path, body=None):
        if not self.logged:
            raise LicenseError("Chưa đăng nhập bản quyền – bấm \"Bản quyền\" ở đầu trang để đăng nhập tài khoản nhà cung cấp "
                               "đã cấp.", relogin=True)
        try:
            r = self._call(path, dict(body or {}, token=self._token()))
            self.last_error = ""
            return r
        except LicenseError as e:
            self.last_error = str(e)
            if e.relogin:
                with self.lock:
                    self.d.pop("token", None)
                    self._save()
            raise

    def login(self, server, username, password):
        import platform
        if server:
            server = server.strip().rstrip("/")
            if not re.match(r"https?://[^\s/]+", server):
                raise LicenseError("Địa chỉ máy chủ không hợp lệ (vd: https://taihoadon.tinhoc119.com)")
            self.d["server"] = server
        r = self._call("/api/lic/login", {"username": username, "password": password,
                                          "device_name": platform.node() or "Máy tính"})
        with self.lock:
            self.d.update(token=protect(r["token"]), username=r["info"]["user"], info=r["info"],
                          checked=datetime.now().strftime("%d/%m/%Y %H:%M"))
            self._save()
        self.last_error = ""
        return r["info"]

    def logout(self):
        try:
            if self.logged:
                self._call("/api/lic/logout", {"token": self._token()}, timeout=15)
        except LicenseError:
            pass
        with self.lock:
            for k in ("token", "info"):
                self.d.pop(k, None)
            self._save()

    def status(self):
        r = self._authed("/api/lic/status")
        with self.lock:
            self.d.update(info=r["info"], checked=datetime.now().strftime("%d/%m/%Y %H:%M"))
            self._save()
        return r["info"]

    def take(self, hashes):
        r = self._authed("/api/lic/take", {"keys": list(hashes)})
        with self.lock:
            if self.d.get("info") and r.get("goi"):
                self.d["info"]["goi"] = r["goi"]
                self._save()
        return r

    def goc(self, refund=False):
        return self._authed("/api/lic/goc", {"refund": bool(refund)})

    def info(self):
        return self.d.get("info") or {}

    def state(self):
        i = self.info()
        latest = (i.get("latest") or {})
        if latest.get("url", "").startswith("/"):
            latest = dict(latest, url=self.d.get("server", "").rstrip("/") + latest["url"])
        return {"logged": self.logged, "server": self.d.get("server"), "username": self.d.get("username", ""),
                "device_id": self.d["device_id"], "info": i, "checked": self.d.get("checked", ""),
                "error": self.last_error,
                "update": latest if latest.get("version") and version_tuple(latest["version"]) > version_tuple(__version__)
                else None}


def self_update(url, server="", exe=None, download=None, launch=None):
    """Bản .exe: tải bản mới về cạnh TaiHoaDon.exe, kiểm tra đúng file chương trình Windows, rồi chạy một tệp .bat nhỏ
    chờ phần mềm tắt → thay file → mở lại. Trả về {'ok', 'size', 'bat'}; phần mềm tự tắt ngay sau đó."""
    exe = exe or (sys.executable if getattr(sys, "frozen", False) else None)
    if not exe:
        raise ValueError("Chỉ tự cập nhật được ở bản cài TaiHoaDon.exe")
    url = str(url or "").strip()
    srv = str(server or "").rstrip("/")
    if not (url.startswith("https://") or (srv and url.startswith(srv + "/"))):
        raise ValueError("Đường dẫn bản mới không hợp lệ: %s" % url)
    with _BUSY_LOCK:
        if _BUSY:
            raise ValueError("Đang đồng bộ hoá đơn – chờ xong rồi hãy cập nhật")
    if download is None:
        def download(u):
            req = urllib.request.Request(u, headers={"User-Agent": "TaiHoaDon/" + __version__})
            with urllib.request.urlopen(req, timeout=600) as r:
                return r.read()
    data = download(url)
    if not data or data[:2] != b"MZ" or len(data) < 1000000:
        raise ValueError("File tải về không phải bản cài TaiHoaDon.exe (có thể đường dẫn sai) – không cập nhật")
    folder = os.path.dirname(os.path.abspath(exe))
    new = os.path.join(folder, "TaiHoaDon.moi.exe")
    with open(new, "wb") as f:
        f.write(data)
    bat = os.path.join(folder, "cap-nhat-taihoadon.bat")
    args = " ".join('"%s"' % a for a in sys.argv[1:] if '"' not in a)
    with open(bat, "w", encoding="ascii", errors="replace") as f:  # cmd đọc .bat theo bảng mã ANSI → chỉ dùng ký tự ASCII
        f.write("@echo off\r\nset n=0\r\n:cho\r\ntimeout /t 2 /nobreak >nul\r\n"
                "move /y \"%s\" \"%s\" >nul 2>&1\r\nif not errorlevel 1 goto chay\r\nset /a n+=1\r\n"
                "if %%n%% lss 30 goto cho\r\n:chay\r\nstart \"\" \"%s\" %s\r\ndel \"%%~f0\"\r\n"
                % (new, os.path.abspath(exe), os.path.abspath(exe), args))
    if launch is None:
        def launch(b):
            import subprocess
            subprocess.Popen(["cmd", "/c", b], cwd=folder, close_fds=True,
                             creationflags=0x08000000 | 0x00000008)  # CREATE_NO_WINDOW | DETACHED_PROCESS
            threading.Timer(1.5, lambda: os._exit(0)).start()  # trả lời trình duyệt xong mới tắt
    launch(bat)
    return {"ok": True, "size": len(data), "bat": bat}


def invoice_hash(mst, kind, key):
    """Mã băm gửi máy chủ để tính hạn mức – không đọc ngược ra được hoá đơn nào."""
    import hashlib
    return hashlib.sha256(("%s|%s|%s" % (str(mst).strip(), kind, key)).encode("utf-8")).hexdigest()


class RemoteQuota:
    """Hạn mức của bản cài máy: hỏi máy chủ cấp phép (cùng giao diện với Quota của bản web)."""

    def __init__(self, lic):
        self.lic = lic

    def take_keys(self, mst, kind, keys):
        """Xin tải các hoá đơn mới (khoá) → tập khoá được phép. Hoá đơn đã tính trước đây (kể cả trên máy khác,
        hoặc tải lại sau khi xoá dữ liệu) không bị trừ lần nữa."""
        hm = {invoice_hash(mst, kind, k): k for k in keys}
        r = self.lic.take(list(hm))
        return {hm[h] for h in r.get("allowed", []) if h in hm}

    def info(self):
        return self.lic.info().get("goi") or {}

    def goc_error(self):
        i = self.info()
        if i and not i.get("tai_goc", True):
            return "Gói của văn phòng không gồm tự tải hoá đơn gốc"
        if i.get("han_muc_goc") and i.get("da_dung_goc", 0) >= i["han_muc_goc"]:
            return "Đã dùng hết hạn mức %d hoá đơn gốc của gói" % i["han_muc_goc"]
        return ""

    def take_goc(self):
        try:
            return bool(self.lic.goc().get("ok"))
        except LicenseError:
            return False

    def refund_goc(self):
        try:
            self.lic.goc(refund=True)
        except LicenseError:
            pass


def _quiet(fn):
    try:
        fn()
    except Exception:
        pass


def default_backup_dir():
    home = os.path.expanduser("~")
    docs = os.path.join(home, "Documents")
    return os.path.join(docs if os.path.isdir(docs) else home, "TaiHoaDon-SaoLuu")


def backup_data(out_root, dest_dir):
    """Sao lưu toàn bộ dữ liệu (hoá đơn, cấu hình, danh mục) thành 1 file ZIP trong thư mục đích – nên chọn thư mục
    Google Drive / OneDrive trên máy để có bản sao ngoài máy. Bỏ qua file kết xuất tạm và chẩn đoán."""
    dest_dir = os.path.abspath(os.path.expanduser(dest_dir or ""))
    root = os.path.abspath(out_root)
    if not dest_dir or dest_dir == root or dest_dir.startswith(root + os.sep):
        raise ValueError("Chọn thư mục sao lưu nằm ngoài thư mục dữ liệu của phần mềm")
    os.makedirs(dest_dir, exist_ok=True)
    path = os.path.join(dest_dir, "TaiHoaDon_SaoLuu_%s.zip" % datetime.now().strftime("%Y%m%d_%H%M"))
    skip = {"_ket-xuat", "chan-doan", "__pycache__"}
    n = 0
    with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
        for d, dirs, files in os.walk(root):
            dirs[:] = [x for x in dirs if x not in skip]
            for f in files:
                full = os.path.join(d, f)
                if os.path.abspath(full) == path or f.endswith(".lock"):
                    continue
                z.write(full, os.path.relpath(full, root))
                n += 1
    return {"path": path, "files": n, "size": os.path.getsize(path)}


class UserStore:
    ITER = 200000

    def __init__(self, path):
        self.path = path
        self.lock = threading.RLock()
        self.items = _load_json(path, [])

    def save(self):
        with self.lock:
            _save_json(self.path, self.items)
            try:
                os.chmod(self.path, 0o600)
            except OSError:
                pass

    @classmethod
    def hash(cls, password, salt=None, iterations=None):
        import hashlib
        salt = salt or secrets.token_hex(16)
        iterations = iterations or cls.ITER
        dk = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt.encode(), iterations)
        return "pbkdf2_sha256$%d$%s$%s" % (iterations, salt, dk.hex())

    @classmethod
    def check(cls, password, stored):
        try:
            _, it, salt, _h = stored.split("$")
            return secrets.compare_digest(cls.hash(password, salt, int(it)), stored)
        except (ValueError, AttributeError):
            cls.hash(password or "", "x" * 32)  # tốn thời gian như nhau để không lộ tên đăng nhập có tồn tại
            return False

    def get(self, username):
        username = (username or "").strip().lower()
        with self.lock:
            return next((u for u in self.items if u["username"] == username), None)

    def count(self):
        with self.lock:
            return len(self.items)

    def authenticate(self, username, password):
        u = self.get(username)
        ok = self.check(password or "", u["pw"] if u else "")
        return u if ok and u.get("active", True) else None

    def _active_admins(self, vp=DEFAULT_VP):
        return [u for u in self.items if u["role"] == "admin" and u.get("active", True) and user_vp(u) == vp]

    def upsert(self, username, ten=None, role=None, password=None, msts=None, active=None, create=False,
               vp=None, quyen=None, owner=None, email=None, invite=False):
        """invite=True: tạo người dùng chưa có mật khẩu (sẽ nhận email để tự đặt)."""
        username = (username or "").strip().lower()
        if email is not None:
            email = str(email).strip()
            if email and not EMAIL_RE.fullmatch(email):
                raise ValueError("Email không hợp lệ")
        if not re.fullmatch(r"[a-z0-9][a-z0-9._@-]{2,39}", username):
            raise ValueError("Tên đăng nhập 3–40 ký tự: chữ thường không dấu, số, . _ - @ (bắt đầu bằng chữ/số)")
        if password is not None and password != "" and len(password) < 8:
            raise ValueError("Mật khẩu phần mềm tối thiểu 8 ký tự")
        if role is not None and role not in ROLES:
            raise ValueError("Vai trò không hợp lệ")
        with self.lock:
            u = self.get(username)
            if u and create:
                raise ValueError("Tên đăng nhập %s đã tồn tại" % username)
            is_new = u is None
            if is_new:
                if not password and not (invite and email):
                    raise ValueError("Nhập mật khẩu cho người dùng mới (hoặc nhập email để gửi lời mời tự đặt mật khẩu)")
                u = {"username": username, "ten": "", "role": "staff", "pw": "", "msts": [], "active": True,
                     "tao": date.today().strftime("%d/%m/%y"), "vp": vp or DEFAULT_VP,
                     "quyen": list(STAFF_DEFAULT_PERMS)}
                self.items.append(u)
            old = dict(u)
            if email is not None:
                u["email"] = email
            if is_new and not password:
                u["pw"] = "!" + secrets.token_hex(16)  # chưa đặt mật khẩu: không đăng nhập được tới khi bấm link trong email
            if quyen is not None:
                u["quyen"] = [k for k in PERMS if k in quyen]
            if owner is not None:
                u["owner"] = bool(owner)
            if ten is not None:
                u["ten"] = str(ten).strip()[:100]
            if role is not None:
                u["role"] = role
            if active is not None:
                u["active"] = bool(active)
            if msts is not None:
                u["msts"] = sorted({str(m).strip() for m in msts if str(m).strip()})
            if password:
                u["pw"] = self.hash(password)
                u["doi_mk"] = int(time.time())
            if not self._active_admins(user_vp(u)):
                if is_new:
                    self.items.remove(u)
                else:
                    u.clear()
                    u.update(old)
                raise ValueError("Phải còn ít nhất một quản trị đang hoạt động")
            self.save()
            return u

    def delete(self, username):
        with self.lock:
            u = self.get(username)
            if not u:
                return
            rest = [x for x in self.items if x is not u]
            if not any(x["role"] == "admin" and x.get("active", True) and user_vp(x) == user_vp(u) for x in rest):
                raise ValueError("Không xoá được quản trị cuối cùng")
            self.items = rest
            self.save()

    def remove_mst(self, mst, vp=DEFAULT_VP):
        with self.lock:
            for u in self.items:
                if mst in u.get("msts", []) and user_vp(u) == vp:
                    u["msts"].remove(mst)
            self.save()

    def public(self, vp=None):
        with self.lock:
            return [dict({k: v for k, v in u.items() if k != "pw"}, vp=user_vp(u), quyen=self.perms(u),
                         pw_set=not str(u.get("pw", "")).startswith("!"))
                    for u in self.items if vp is None or user_vp(u) == vp]

    def add_mst(self, username, mst):
        with self.lock:
            u = self.get(username)
            if u and u["role"] != "admin" and mst not in u.get("msts", []):
                u.setdefault("msts", []).append(mst)
                self.save()

    @staticmethod
    def can(user, mst):
        return bool(user) and (user["role"] == "admin" or mst in (user.get("msts") or []))

    @staticmethod
    def perms(user):
        """Các quyền thực có: quản trị có hết; nhân viên cũ (chưa phân quyền) có hết như trước."""
        if not user:
            return []
        if user.get("role") == "admin" or user.get("quyen") is None:
            return list(PERMS)
        return [k for k in PERMS if k in user["quyen"]]


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
        self.unattended = False  # đồng bộ tự động theo lịch: không hỏi captcha
        self.owner = ""

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
                    "rows": self.rows[-1500:], "phase": self.phase, "gaps": list(getattr(self, "gaps", [])),
                    "mtt_skipped": bool(getattr(self, "mtt_skipped", False)),
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
        text = None if auto_failed and not job.unattended else solver.solve(cap["content"])
        auto = text is not None
        if not auto and job.unattended:  # đồng bộ tự động: không có người gõ captcha → thử captcha khác
            job.say("%s: không tự giải được captcha, thử captcha khác" % company["mst"])
            time.sleep(1)
            continue
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
    gaps = list(dict.fromkeys(getattr(client, "gaps", []) or []))
    if gaps:
        job.say("%s: THIẾU DỮ LIỆU – trang thuế lỗi, chưa tra được: %s. Các hoá đơn khác vẫn được đồng bộ; "
                "đồng bộ lại sau để lấy đủ." % (mst, "; ".join(gaps)))
        job.gaps = getattr(job, "gaps", []) + ["%s %s: %s" % (mst, "mua vào" if kind == "purchase" else "bán ra", g)
                                                for g in gaps]
    if getattr(client, "mtt_skipped", False):
        job.mtt_skipped = True
    quota = getattr(job, "quota", None)
    if quota:  # gói theo số hoá đơn: chỉ hoá đơn mới (chưa có trong kho) tính vào hạn mức
        fresh = sorted((i for i in invoices if invoice_key(i) not in known),
                       key=lambda i: (invoice_date(i) or date.max, _num(i.get("shdon"))))
        lic_err = None
        if hasattr(quota, "take_keys"):  # bản cài máy: máy chủ cấp phép quyết định (theo mã băm hoá đơn)
            try:
                ok = quota.take_keys(mst, kind, [invoice_key(i) for i in fresh]) if fresh else set()
            except LicenseError as e:
                ok, lic_err = set(), e
            fresh = [i for i in fresh if invoice_key(i) in ok] + [i for i in fresh if invoice_key(i) not in ok]
            got = len(ok)
        else:
            got = quota.take_hd(len(fresh)) if fresh else 0
        if got < len(fresh):
            drop = {invoice_key(i) for i in fresh[got:]}
            invoices = [i for i in invoices if invoice_key(i) not in drop]
            if lic_err:
                job.say("%s: %s Chỉ cập nhật trạng thái hoá đơn đã có, chưa tải %d hoá đơn mới." % (mst, lic_err, len(drop)))
            else:
                job.say("%s: HẾT HẠN MỨC hoá đơn của gói – bỏ qua %d hoá đơn mới (đã tải %d). Liên hệ nhà cung cấp "
                        "để nâng gói rồi đồng bộ lại." % (mst, len(drop), got))
            job.quota_out = True
    loai = "Mua vào" if kind == "purchase" else "Bán ra"
    with job.lock:
        job.found[kind] = len(invoices)
    job.phase = "đang đồng bộ hoá đơn"
    new, changes, todo, status, detail_todo = 0, [], [], {}, []
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
                if old.get("detail") and os.path.exists(os.path.join(base, old["detail"])):
                    inv["_detail"] = old["detail"]
                else:
                    detail_todo.append(inv)  # bản cũ chưa lấy dữ liệu chi tiết → lấy để có hàng hoá và PDF của thuế
            elif os.path.exists(existing) and status[key] != "Đổi trạng thái" and not (
                    os.path.exists(existing[:-4] + ".html") and not os.path.isdir(existing[:-4] + "_goc")
                    and html_missing_assets(existing[:-4] + ".html")):
                inv["_xml"] = os.path.relpath(existing, folder)
                inv["_xmlinfo"] = parse_invoice_xml(existing)
            else:
                todo.append(inv)
    errors = {}
    with job.lock:
        job.total += len(todo)

    def fetch_detail(inv):
        """HĐ không có XML gốc: lấy dữ liệu 'Xem hoá đơn' của cổng (hàng hoá, thuế suất) → PDF của thuế."""
        try:
            detail = client.get_detail(inv)
            rel = os.path.join(os.path.relpath(xml_dir, base), invoice_basename(inv) + ".json")
            os.makedirs(xml_dir, exist_ok=True)
            _save_json(os.path.join(base, rel), detail)
            inv["_detail"] = rel
        except PortalError as e:
            job.say("%s: không lấy được chi tiết HĐ %s/%s: %s" % (mst, inv.get("khhdon"), inv.get("shdon"), e))

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
                fetch_detail(inv)
            else:
                inv["_xml"] = "Lỗi: %s" % e
                errors[invoice_key(inv)] = str(e)
        with job.lock:
            job.done += 1

    if todo or detail_todo:
        # Tải XML song song (vừa phải để cổng không chặn).
        from concurrent.futures import ThreadPoolExecutor
        with ThreadPoolExecutor(max_workers=XML_WORKERS) as pool:
            list(pool.map(fetch, todo))
            list(pool.map(lambda i: None if job.cancel else fetch_detail(i), detail_todo))
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
        rows.append({"mst": mst, "tthai": TTHAI_NIBOT.get(_num(inv.get("tthai")), ""), "loai": loai, "ngay": d.strftime("%d/%m/%Y") if d else "", "mau": str(inv.get("khmshdon") or ""),
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
        if inv.get("_detail"):
            entry["detail"] = inv["_detail"]
            if not entry.get("mat_hang"):
                d = _load_json(os.path.join(base, inv["_detail"]), {})
                entry["mat_hang"] = "; ".join(str(it.get("ten") or "") for it in (d.get("hdhhdvu") or [])
                                              if it.get("ten"))[:500]
        if inv.get("_xmlinfo") is not None:
            entry["xml"] = os.path.relpath(os.path.join(folder, inv["_xml"]), base)
            names = [it["name"] for it in inv["_xmlinfo"]["items"] if it["name"] and it.get("nature") != "4"]
            entry["mat_hang"] = "; ".join(names)[:500]
            entry["tra_cuu"] = lookup_info(inv["_xmlinfo"], inv.get("nbmst"))
        known[key] = entry
    save_index_kind(out_root, mst, kind, known)
    if getattr(job, "want_pdf", False):
        by_key = {invoice_key(i): r for i, r in zip(invoices, rows)}
        base_dir = company_dir(out_root, mst)
        cur = _load_json(index_file(out_root, mst), {}).get(kind, {})
        todo = [k for k in by_key if needs_original(base_dir, cur.get(k) or known[k])]
        if todo:
            job.say("%s: tải hoá đơn gốc (PDF + XML) %d hoá đơn" % (mst, len(todo)))
            job.phase = "đang tải hoá đơn gốc"
            with job.lock:
                job.total += len(todo)
        fails = {}  # nhà cung cấp → số lần lỗi liên tiếp
        flock = threading.Lock()

        def one(k):
            if job.cancel:
                return
            prov = can_fetch_pdf(known[k].get("tra_cuu"))[1]
            label = k.split("|", 2)[-1].replace("|", "/")
            with flock:
                skip = fails.get(prov, 0) >= 3  # nhà cung cấp đang chặn / lỗi → không thử tiếp trong lượt này
            if skip:
                note = " (HĐ gốc: bỏ qua)"
            elif quota and not quota.take_goc():
                note = " (HĐ gốc: hết hạn mức)"
                with flock:
                    first = not getattr(job, "goc_out", False)
                    job.goc_out = True
                if first:
                    job.say("%s: %s – không tự tải thêm hoá đơn gốc" % (mst, quota.goc_error() or "Hết hạn mức HĐ gốc"))
            else:
                try:
                    got = fetch_original(out_root, mst, kind, k)
                    note = " + PDF gốc" + (" + XML gốc" if got["xml"] else "")
                    if got["xml_err"]:
                        job.say("%s: chưa lấy được XML gốc %s: %s" % (mst, label, got["xml_err"]))
                    with flock:
                        fails[prov] = 0
                except Exception as e:  # lỗi một hoá đơn không được làm dừng cả lượt đồng bộ
                    if quota:
                        quota.refund_goc()  # tải lỗi không tính vào hạn mức
                    with flock:
                        fails[prov] = n = fails.get(prov, 0) + 1
                    note = " (HĐ gốc: lỗi)"
                    job.say("%s: không tải được hoá đơn gốc %s: %s" % (mst, label, e))
                    if n == 3:
                        job.say("%s: %s lỗi 3 lần liên tiếp – bỏ qua hoá đơn gốc của nhà cung cấp này trong lượt này "
                                "(tải sau bằng nút Tải HĐ gốc hàng loạt)" % (mst, (known[k].get("tra_cuu") or {}).get("ncc") or prov))
            with job.lock:
                by_key[k]["ketqua"] += note
                job.done += 1

        # Tải song song vài hoá đơn một lúc (mỗi hoá đơn ở trang của nhà cung cấp riêng, không phải cổng thuế).
        if todo:
            from concurrent.futures import ThreadPoolExecutor
            with ThreadPoolExecutor(max_workers=ORIGINAL_WORKERS) as pool:
                list(pool.map(one, todo))
        if job.cancel:
            raise Cancelled()
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


_BUSY_LOCK = threading.Lock()
_BUSY = {}  # MST → Job đang đồng bộ (hai người không đồng bộ cùng một DN một lúc)


def claim_mst(mst, job):
    with _BUSY_LOCK:
        if _BUSY.get(mst) not in (None, job):
            return False
        _BUSY[mst] = job
        return True


def release_mst(mst, job):
    with _BUSY_LOCK:
        if _BUSY.get(mst) is job:
            del _BUSY[mst]


def busy_msts(root=None):
    """MST đang đồng bộ (khoá theo thư mục dữ liệu + MST: văn phòng khác cùng MST không chặn nhau)."""
    with _BUSY_LOCK:
        return {k[1] if isinstance(k, tuple) else k for k in _BUSY
                if root is None or (isinstance(k, tuple) and k[0] == os.path.abspath(root))}


def run_batch(job, store, solver, msts, kinds, start, end, include_mtt, want_xml, out_root,
              test_only=False, client_factory=None, clients=None):
    """clients: dict MST → HoaDonClient để giữ phiên đăng nhập giữa các lần đồng bộ (không phải nhập lại captcha)."""
    client_factory = client_factory or HoaDonClient
    clients = {} if clients is None else clients
    try:
        for i, mst in enumerate(msts):
            if job.cancel:
                break
            job.dn = [i + 1, len(msts)]
            company = store.get(mst)
            if not company:
                continue
            if not claim_mst((os.path.abspath(out_root), mst), job):
                job.say("%s: LỖI doanh nghiệp đang được người khác đồng bộ – bỏ qua, thử lại sau" % mst)
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
                    gaps_before = len(getattr(job, "gaps", []))
                    reused = "dùng lại" in (job.auth or "")
                    try:
                        total, new, known = sync_kind(job, client, company, kind, start, end, include_mtt,
                                                      want_xml, out_root)
                        retry = reused and len(getattr(job, "gaps", [])) > gaps_before
                    except PortalError as e:
                        if "hết hạn" not in str(e) and not (reused and _is_busy(e)):
                            raise
                        retry = True
                    if retry:  # phiên cũ hết hạn / cổng trả lỗi khi dùng phiên cũ → đăng nhập mới rồi làm lại một lần
                        job.say("%s: trang thuế báo lỗi khi dùng phiên đăng nhập cũ – đăng nhập lại rồi thử lại" % mst)
                        if hasattr(job, "gaps"):
                            del job.gaps[gaps_before:]
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
            finally:
                release_mst((os.path.abspath(out_root), mst), job)
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


def _set_entry(out_root, mst, kind, key, **fields):
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        e = cur.get(kind, {}).get(key)
        if e is not None:
            e.update(fields)
            _save_json(path, cur)


def attach_original_xml(out_root, mst, kind, key, data):
    """Lưu XML gốc tải từ trang tra cứu của người bán: <tên XML của thuế>_goc-ncc.xml (không ghi đè XML của thuế)."""
    if not _is_xml(data):
        raise ValueError("File không phải XML hoá đơn")
    base = company_dir(out_root, mst)
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        e = cur.get(kind, {}).get(key)
        if e is None:
            raise ValueError("Không tìm thấy hoá đơn")
        if e.get("xml"):
            rel = os.path.splitext(e["xml"])[0] + "_goc-ncc.xml"
        else:
            rel = os.path.join("xml-goc", invoice_basename(e.get("inv") or {}) + ".xml")
        os.makedirs(os.path.dirname(os.path.join(base, rel)), exist_ok=True)
        with open(os.path.join(base, rel), "wb") as fh:
            fh.write(data)
        e["xml_goc"] = rel
        e.pop("xml_goc_loi", None)
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
    labeled = (
        # "Số: 123", "Số (No.): 123" – số không được dính chữ phía sau (vd "Số: 1C25TLV" không phải số 1)
        r"Số\s*(?:\(No\.?\)\s*:?|:)\s*0*(\d{1,10})(?![\d.,]\d)(?![A-Za-z])",
        r"(?<![\d.,])(\d{1,10})\s*\(No\)",
        # số đứng ngay sau ký hiệu: "1C25TQP 106", "Số: 1C25TLV 00004279", "Ký hiệu: 1C26TAA Số: 1614"
        r"(?<![A-Z0-9])[12]?[CK]\d{2}[A-Z]{2,3}\s+(?:Số\s*(?:\([^)]{0,8}\))?\s*:?\s*)?0*(\d{1,10})(?![\d.,]\d)(?![A-Za-z])",
        # chữ PDF bị đảo thứ tự: "00000165:Số"
        r"(?<![\d.,])0*(\d{1,10})\s*:\s*Số(?!\s*(?:tài|TK|điện))",
    )
    for pat in labeled:
        for m in re.finditer(pat, flat):
            score[int(m.group(1))] = 4  # đúng vị trí số hoá đơn – hơn số nhắc tới trong nội dung (vd HĐ bị điều chỉnh)
    msts = set(re.findall(r"(?<!\d)(\d{10}(?:-\d{3})?)(?!\d)", flat))
    msts |= {re.sub(r"\s", "", m) for m in re.findall(r"(?<!\d)((?:\d ){9}\d)(?!\d)", flat)}
    msts |= {m.split("-")[0] for m in msts}
    # Ký hiệu 1C25TAA; PDF đảo thứ tự chữ có thể in thành C25TAA1
    khs = set(re.findall(r"(?<![A-Z0-9])[12]?([CK]\d{2}[A-Z]{2,3})[12]?(?![A-Z0-9])", flat))
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


def match_xml(data, entries_by_kind):
    """Hoá đơn trong kho khớp với file XML gốc: cùng MST người bán, ký hiệu mẫu, ký hiệu và số hoá đơn."""
    import xml.etree.ElementTree as ET
    try:
        root = ET.fromstring(data)
    except ET.ParseError:
        raise ValueError("file XML hỏng")
    found = {}
    for el in root.iter():
        tag = _local(el.tag)
        if tag in ("KHMSHDon", "KHHDon", "SHDon") and tag not in found:
            found[tag] = (el.text or "").strip()
        if tag == "NBan":
            for c in el:
                if _local(c.tag) == "MST" and "nb" not in found:
                    found["nb"] = (c.text or "").strip()
    if not found.get("KHHDon") or not found.get("SHDon"):
        raise ValueError("không đọc được ký hiệu / số hoá đơn trong XML")
    hits = []
    for kind, entries in entries_by_kind.items():
        for key, e in entries.items():
            inv = e.get("inv") or {}
            if (str(inv.get("khhdon") or "") == found["KHHDon"] and _num(inv.get("shdon")) == _num(found["SHDon"])
                    and str(inv.get("khmshdon") or "") == found.get("KHMSHDon", str(inv.get("khmshdon") or ""))
                    and str(inv.get("nbmst") or "") == found.get("nb", str(inv.get("nbmst") or ""))):
                hits.append((kind, key))
    return hits


def _unzip_originals(files):
    """File .zip (nhà cung cấp hay nén PDF + XML) → các file PDF / XML bên trong; ZIP khác bỏ qua im lặng."""
    out = []
    for name, data in files:
        if not (data or b"")[:2] == b"PK":
            out.append((name, data))
            continue
        try:
            with zipfile.ZipFile(io.BytesIO(data)) as z:
                # lọc PDF / XML trước rồi mới giới hạn số file (ZIP có thể kèm cả trang web đã lưu: js, css, ảnh…)
                picks = [n for n in z.namelist() if n.lower().endswith((".pdf", ".xml"))
                         and z.getinfo(n).file_size < 30 << 20][:50]
                for n in picks:
                    out.append(("%s/%s" % (name, n), z.read(n)))
        except (zipfile.BadZipFile, OSError):
            continue
    return out


def import_pdfs(out_root, mst, files):
    """Gắn hàng loạt PDF / XML gốc có sẵn trên máy vào đúng hoá đơn (đọc ký hiệu, số, MST trong file)."""
    idx = _load_json(index_file(out_root, mst), {})
    results = []
    files = _unzip_originals(files)
    for name, data in files:
        try:
            if _is_xml(data or b""):
                hits = match_xml(data, {k: idx.get(k, {}) for k in ("purchase", "sold")})
                if len(hits) != 1:
                    raise ValueError("XML không khớp hoá đơn nào trong kho (đã đồng bộ kỳ này chưa?)" if not hits
                                     else "XML khớp %d hoá đơn, không chắc gắn vào đâu" % len(hits))
                kind, key = hits[0]
                attach_original_xml(out_root, mst, kind, key, data)
                nb, _, kh, so = key.split("|")
                results.append({"file": name, "ok": True, "hd": "%s %s – %s (XML gốc)" % (
                    "Mua vào" if kind == "purchase" else "Bán ra", kh, so)})
                continue
            if not data.startswith(b"%PDF"):
                raise ValueError("không phải file PDF / XML")
            text = _pdf_text(data)
            if re.search(r"chưa\s*cấp\s*số", text, re.I):
                raise ValueError("PDF là bản nháp, hoá đơn chưa được cấp số – lấy bản PDF sau khi người bán đã phát hành")
            hits = match_pdf_text(text, {k: idx.get(k, {}) for k in ("purchase", "sold")})
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


# ---------------------------------------------------------------------------
# PDF bản thể hiện hoá đơn theo dữ liệu của cổng thuế ("PDF của thuế"): dựng từ XML, hoặc từ dữ liệu chi tiết
# trên cổng với hoá đơn không có XML gốc (HĐ không mã, máy tính tiền). Cổng không có API trả PDF.
# ---------------------------------------------------------------------------

INVOICE_TITLES = {"1": "HÓA ĐƠN GIÁ TRỊ GIA TĂNG", "2": "HÓA ĐƠN BÁN HÀNG", "3": "HÓA ĐƠN BÁN TÀI SẢN CÔNG",
                  "4": "HÓA ĐƠN BÁN HÀNG DỰ TRỮ QUỐC GIA", "5": "HÓA ĐƠN KHÁC",
                  "6": "PHIẾU XUẤT KHO KIÊM VẬN CHUYỂN NỘI BỘ"}


def _dget(d, *keys):
    for k in keys:
        v = (d or {}).get(k)
        if v not in (None, "", []):
            return v
    return ""


def _vn_money(v):
    if v in (None, ""):
        return ""
    try:
        f = float(str(v).replace(",", ""))
    except ValueError:
        return str(v)
    s = "{:,.2f}".format(f).rstrip("0").rstrip(".")
    return s.replace(",", "\x00").replace(".", ",").replace("\x00", ".")


def _vn_rate(v):
    v = str(v if v is not None else "").strip()
    try:
        f = float(v)
        return "%g%%" % (f * 100 if f < 1 else f)
    except ValueError:
        return v


def invoice_view_from_xml(xml_bytes):
    x = read_invoice_xml(xml_bytes)
    tc, nb, nm, tong = x["ttchung"], x["nb"], x["nm"], x["tong"]
    return {
        "mau": tc.get("KHMSHDon", ""), "kh": tc.get("KHHDon", ""), "so": tc.get("SHDon", ""), "title": tc.get("THDon", ""),
        "ngay": tc.get("NLap", ""), "mccqt": x["mccqt"], "httt": tc.get("HTTToan", ""), "dvtte": tc.get("DVTTe", "VND"),
        "nb": {"ten": nb.get("Ten"), "mst": nb.get("MST"), "dchi": nb.get("DChi"), "sdt": nb.get("SDThoai"),
               "stk": nb.get("STKNHang"), "nh": nb.get("TNHang")},
        "nm": {"ten": nm.get("Ten"), "hoten": nm.get("HVTNMHang"), "mst": nm.get("MST") or nm.get("CCCDan"),
               "dchi": nm.get("DChi"), "stk": nm.get("STKNHang")},
        "items": [{"stt": it["STT"], "ten": it["THHDVu"], "dvt": it["DVTinh"], "sl": it["SLuong"], "dgia": it["DGia"],
                   "ck": it["STCKhau"], "tsuat": it["TSuat"], "thtien": it["ThTien"], "tchat": it["TChat"]}
                  for it in x["items"]],
        "rates": [{"tsuat": r.get("TSuat"), "thtien": r.get("ThTien"), "tthue": r.get("TThue")} for r in x["rates"]],
        "tong": {"cthue": tong.get("TgTCThue"), "thue": tong.get("TgTThue"), "ck": tong.get("TTCKTMai"),
                 "phi": tong.get("TgTPhi"), "tt": tong.get("TgTTTBSo"), "chu": tong.get("TgTTTBChu")},
        "signed": bool(x["so_chu_ky"]), "nky": "", "nguon": "XML hoá đơn",
    }


def invoice_view_from_detail(d):
    """Dữ liệu 'Xem hoá đơn' của cổng (JSON) – dùng cho HĐ không có XML gốc."""
    items = []
    for i, it in enumerate(d.get("hdhhdvu") or [], 1):
        items.append({"stt": _dget(it, "stt") or i, "ten": _dget(it, "ten", "thhdvu"), "dvt": _dget(it, "dvtinh"),
                      "sl": _dget(it, "sluong"), "dgia": _dget(it, "dgia"), "ck": _dget(it, "stckhau"),
                      "tsuat": _dget(it, "tsuat", "ltsuat"), "thtien": _dget(it, "thtien"), "tchat": _dget(it, "tchat")})
    rates = [{"tsuat": _dget(r, "tsuat"), "thtien": _dget(r, "thtien"), "tthue": _dget(r, "tthue")}
             for r in (d.get("thttltsuat") or [])]
    return {
        "mau": str(_dget(d, "khmshdon")), "kh": _dget(d, "khhdon"), "so": str(_dget(d, "shdon")), "title": _dget(d, "thdon"),
        "ngay": _dget(d, "tdlap", "nlap"), "mccqt": _dget(d, "mhdon"), "httt": _dget(d, "thtttoan", "htttoan"),
        "dvtte": _dget(d, "dvtte") or "VND",
        "nb": {"ten": _dget(d, "nbten"), "mst": _dget(d, "nbmst"), "dchi": _dget(d, "nbdchi"), "sdt": _dget(d, "nbsdthoai"),
               "stk": _dget(d, "nbstkhoan"), "nh": _dget(d, "nbtnhang")},
        "nm": {"ten": _dget(d, "nmten"), "hoten": _dget(d, "nmtnmua"), "mst": _dget(d, "nmmst", "nmcccd"),
               "dchi": _dget(d, "nmdchi"), "stk": _dget(d, "nmstkhoan")},
        "items": items, "rates": rates,
        "tong": {"cthue": _dget(d, "tgtcthue"), "thue": _dget(d, "tgtthue"), "ck": _dget(d, "ttcktmai"),
                 "phi": _dget(d, "tgtphi"), "tt": _dget(d, "tgtttbso"), "chu": _dget(d, "tgtttbchu")},
        "signed": bool(_dget(d, "nky")), "nky": _dget(d, "nky"), "nguon": "dữ liệu chi tiết trên cổng thuế",
    }


def _fill_view(v, extra):
    """Thông tin còn trống (ký hiệu, số, ngày, mã CQT, tổng tiền…) lấy bù từ dữ liệu danh sách hoá đơn của cổng."""
    for k, val in extra.items():
        if isinstance(val, dict):
            for kk, vv in val.items():
                if v.setdefault(k, {}).get(kk) in (None, "") and vv not in (None, ""):
                    v[k][kk] = vv
        elif k not in ("items", "rates", "nguon", "signed") and v.get(k) in (None, "") and val not in (None, ""):
            v[k] = val
    return v


def invoice_view_from_summary(inv):
    v = invoice_view_from_detail(inv)
    v["nguon"] = "dữ liệu tổng hợp (chưa có chi tiết hàng hoá – đồng bộ lại để lấy)"
    return v


_FONT = {}


def resource_dir():
    """Thư mục chứa fonts/ kèm phần mềm (bản .exe: thư mục tạm PyInstaller giải nén)."""
    return getattr(sys, "_MEIPASS", None) or os.path.dirname(os.path.abspath(__file__))


def app_dir():
    """Thư mục đặt phần mềm (bản .exe: thư mục chứa TaiHoaDon.exe)."""
    return os.path.dirname(os.path.abspath(sys.executable if getattr(sys, "frozen", False) else __file__))


def _vn_fonts():
    """Font có dấu tiếng Việt cho reportlab: thư mục fonts/ kèm phần mềm, font hệ thống (Linux), Arial (Windows)."""
    if _FONT:
        return _FONT["n"], _FONT["b"]
    try:
        from reportlab.pdfbase import pdfmetrics
        from reportlab.pdfbase.ttfonts import TTFont
        from reportlab.lib.fonts import addMapping
    except ImportError:
        raise ValueError("Tạo PDF cần thư viện reportlab: pip install reportlab (chay.bat / bản web tự cài)")
    here = resource_dir()
    win = os.path.join(os.environ.get("WINDIR", r"C:\Windows"), "Fonts")
    for reg, bold in ((os.path.join(here, "fonts", "DejaVuSans.ttf"), os.path.join(here, "fonts", "DejaVuSans-Bold.ttf")),
                      ("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"),
                      ("/usr/share/fonts/dejavu/DejaVuSans.ttf", "/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf"),
                      (os.path.join(win, "arial.ttf"), os.path.join(win, "arialbd.ttf")),
                      ("/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf",
                       "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf")):
        if os.path.exists(reg) and os.path.exists(bold):
            pdfmetrics.registerFont(TTFont("VN", reg))
            pdfmetrics.registerFont(TTFont("VN-B", bold))
            addMapping("VN", 0, 0, "VN")
            addMapping("VN", 1, 0, "VN-B")
            addMapping("VN", 0, 1, "VN")
            addMapping("VN", 1, 1, "VN-B")
            _FONT.update(n="VN", b="VN-B")
            return "VN", "VN-B"
    raise ValueError("Không tìm thấy font tiếng Việt – chép thư mục fonts/ (DejaVuSans.ttf) cạnh taihoadon.py")


def build_tax_pdf(v, tthai="", kq=""):
    """Bản thể hiện hoá đơn (A4) theo dữ liệu của cổng thuế → bytes PDF."""
    fn, fb = _vn_fonts()
    from reportlab.lib import colors
    from reportlab.lib.enums import TA_CENTER, TA_RIGHT
    from reportlab.lib.pagesizes import A4
    from reportlab.lib.styles import ParagraphStyle
    from reportlab.lib.units import mm
    from reportlab.platypus import Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle
    import html as H
    esc = lambda s: H.escape(str(s if s is not None else ""))
    st = ParagraphStyle("n", fontName=fn, fontSize=8.5, leading=11.5)
    small = ParagraphStyle("s", parent=st, fontSize=7.5, leading=9.5, textColor=colors.HexColor("#555555"))
    cell = ParagraphStyle("c", parent=st, fontSize=8, leading=10)
    cell_r = ParagraphStyle("cr", parent=cell, alignment=TA_RIGHT)
    cell_c = ParagraphStyle("cc", parent=cell, alignment=TA_CENTER)
    title = ParagraphStyle("t", parent=st, fontName=fb, fontSize=15, leading=19, alignment=TA_CENTER,
                           textColor=colors.HexColor("#b4161b"))
    center = ParagraphStyle("ce", parent=st, alignment=TA_CENTER)
    buf = io.BytesIO()
    doc = SimpleDocTemplate(buf, pagesize=A4, leftMargin=12 * mm, rightMargin=12 * mm, topMargin=10 * mm,
                            bottomMargin=12 * mm, title="%s %s" % (v.get("kh"), v.get("so")), author=str(v["nb"].get("ten") or ""))
    W = A4[0] - 24 * mm
    d = _to_dt(v.get("ngay"))
    ngay = ("Ngày %02d tháng %02d năm %d" % (d.day, d.month, d.year)) if d else esc(v.get("ngay"))
    line = lambda k, val, bold=False: Paragraph("%s: %s" % (k, ("<b>%s</b>" if bold else "%s") % esc(val)), st) if val else None
    nb, nm, tong = v["nb"], v["nm"], v["tong"]
    story = []
    head = Table([[Paragraph("<b>%s</b>" % esc(nb.get("ten")), ParagraphStyle("h", parent=st, fontSize=10, leading=13)),
                   Paragraph("Mẫu số: <b>%s</b><br/>Ký hiệu: <b>%s</b><br/>Số: <b><font color='#b4161b'>%s</font></b>" % (
                       esc(v.get("mau")), esc(v.get("kh")), esc(v.get("so"))), st)]],
                 colWidths=[W - 55 * mm, 55 * mm])
    head.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("BOX", (1, 0), (1, 0), 0.6, colors.HexColor("#999999")),
                              ("LEFTPADDING", (0, 0), (0, 0), 0)]))
    story += [head, Spacer(1, 4 * mm),
              Paragraph(esc((v.get("title") or INVOICE_TITLES.get(str(v.get("mau")), "HÓA ĐƠN")).upper()), title),
              Paragraph("(Bản thể hiện của hóa đơn điện tử)", center), Paragraph(ngay, center),
              Paragraph("Mã của cơ quan thuế: <b>%s</b>" % esc(v.get("mccqt") or "(hóa đơn không có mã)"), center),
              Spacer(1, 3 * mm)]

    def block(rows):
        t = Table([[r] for r in rows if r is not None], colWidths=[W])
        t.setStyle(TableStyle([("LINEBELOW", (0, -1), (-1, -1), 0.5, colors.HexColor("#bbbbbb")),
                               ("LEFTPADDING", (0, 0), (-1, -1), 0), ("TOPPADDING", (0, 0), (-1, -1), 1),
                               ("BOTTOMPADDING", (0, 0), (-1, -1), 1)]))
        return t
    story.append(block([line("Đơn vị bán hàng", nb.get("ten"), True), line("Mã số thuế", nb.get("mst"), True),
                        line("Địa chỉ", nb.get("dchi")), line("Điện thoại", nb.get("sdt")),
                        line("Số tài khoản", " – ".join(x for x in (nb.get("stk"), nb.get("nh")) if x))]))
    story.append(Spacer(1, 2 * mm))
    story.append(block([line("Họ tên người mua hàng", nm.get("hoten")), line("Tên đơn vị", nm.get("ten"), True),
                        line("Mã số thuế", nm.get("mst"), True), line("Địa chỉ", nm.get("dchi")),
                        line("Số tài khoản", nm.get("stk")),
                        Paragraph("Hình thức thanh toán: %s &nbsp;&nbsp;&nbsp; Đơn vị tiền tệ: %s" % (
                            esc(v.get("httt")), esc(v.get("dvtte") or "VND")), st)]))
    story.append(Spacer(1, 3 * mm))
    hdr = ["STT", "Tên hàng hóa, dịch vụ", "Đơn vị tính", "Số lượng", "Đơn giá", "Thuế suất", "Thành tiền"]
    data = [[Paragraph("<b>%s</b>" % h, cell_c) for h in hdr]]
    for it in v["items"]:
        note = str(it.get("tchat") or "")
        ten = esc(it.get("ten")) + (" <font color='#777777'>(%s)</font>" % {"2": "khuyến mại", "3": "chiết khấu",
                                                                             "4": "ghi chú"}.get(note, "") if note in ("2", "3", "4") else "")
        data.append([Paragraph(esc(it.get("stt")), cell_c), Paragraph(ten, cell), Paragraph(esc(it.get("dvt")), cell_c),
                     Paragraph(_vn_money(it.get("sl")), cell_r), Paragraph(_vn_money(it.get("dgia")), cell_r),
                     Paragraph(esc(_vn_rate(it.get("tsuat"))), cell_c), Paragraph(_vn_money(it.get("thtien")), cell_r)])
    if not v["items"]:
        data.append(["", Paragraph("<i>(Không có dữ liệu chi tiết hàng hóa, dịch vụ)</i>", cell), "", "", "", "", ""])
    items = Table(data, colWidths=[12 * mm, W - 114 * mm, 18 * mm, 18 * mm, 22 * mm, 16 * mm, 28 * mm], repeatRows=1)
    items.setStyle(TableStyle([("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#999999")),
                               ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#eef1f6")), ("VALIGN", (0, 0), (-1, -1), "MIDDLE")]))
    story += [items, Spacer(1, 2 * mm)]
    rate_rows = [[Paragraph("<b>Thuế suất</b>", cell_c), Paragraph("<b>Thành tiền chưa thuế</b>", cell_c),
                  Paragraph("<b>Tiền thuế</b>", cell_c)]]
    rate_rows += [[Paragraph(esc(_vn_rate(r.get("tsuat"))), cell_c), Paragraph(_vn_money(r.get("thtien")), cell_r),
                   Paragraph(_vn_money(r.get("tthue")), cell_r)] for r in v["rates"]]
    tot = [("Tổng tiền chưa thuế", tong.get("cthue")), ("Tổng tiền thuế", tong.get("thue")),
           ("Tổng tiền chiết khấu thương mại", tong.get("ck") or 0), ("Tổng tiền phí", tong.get("phi") or 0),
           ("Tổng tiền thanh toán", tong.get("tt"))]
    tot_rows = [[Paragraph(("<b>%s</b>" if k.endswith("thanh toán") else "%s") % k, cell),
                 Paragraph(("<b>%s</b>" if k.endswith("thanh toán") else "%s") % _vn_money(val), cell_r)] for k, val in tot]
    rt = Table(rate_rows, colWidths=[18 * mm, 32 * mm, 28 * mm])
    rt.setStyle(TableStyle([("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#999999")),
                            ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#eef1f6"))]))
    tt = Table(tot_rows, colWidths=[52 * mm, 34 * mm])
    tt.setStyle(TableStyle([("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#999999"))]))
    both = Table([[rt, tt]], colWidths=[W - 88 * mm, 88 * mm])
    both.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 0)]))
    story += [both, Spacer(1, 2 * mm)]
    if tong.get("chu"):
        story.append(Paragraph("Số tiền viết bằng chữ: <b>%s</b>" % esc(tong.get("chu")), st))
    story.append(Spacer(1, 5 * mm))
    sign = "<b>NGƯỜI BÁN HÀNG</b>"
    if v.get("signed") or v.get("mccqt"):
        sign += "<br/><font color='#1e8449'>Đã ký số</font><br/>Ký bởi: %s" % esc(nb.get("ten"))
        k = _to_dt(v.get("nky"))
        if k:
            sign += "<br/>Ký ngày: %s" % k.strftime("%d/%m/%Y")
    sig = Table([[Paragraph("<b>NGƯỜI MUA HÀNG</b>", center), Paragraph(sign, center)]], colWidths=[W / 2, W / 2])
    sig.setStyle(TableStyle([("VALIGN", (0, 0), (-1, -1), "TOP"),
                             ("BOX", (1, 0), (1, 0), 0.6, colors.HexColor("#1e8449") if (v.get("signed") or v.get("mccqt"))
                              else colors.white)]))
    story += [sig, Spacer(1, 6 * mm)]
    meta = "Trạng thái: %s%s" % (esc(tthai or ""), (" · Kết quả kiểm tra: %s" % esc(kq)) if kq else "")
    story.append(Paragraph("%s<br/>Bản thể hiện dựng từ %s trên cổng hoadondientu.gdt.gov.vn (Tổng cục Thuế); "
                           "tra cứu hóa đơn tại https://hoadondientu.gdt.gov.vn" % (meta, esc(v.get("nguon"))), small))
    cancelled = bool(re.search(r"hủy|huỷ", tthai or "", re.I))

    def on_page(canvas, _doc):
        if cancelled:
            canvas.saveState()
            canvas.setFont(fb, 46)
            canvas.setFillColor(colors.Color(0.8, 0.1, 0.1, alpha=0.18))
            canvas.translate(A4[0] / 2, A4[1] / 2)
            canvas.rotate(35)
            canvas.drawCentredString(0, 0, "HÓA ĐƠN ĐÃ BỊ HỦY")
            canvas.restoreState()
    doc.build(story, onFirstPage=on_page, onLaterPages=on_page)
    return buf.getvalue()


def find_browser():
    """Chrome / Edge / Chromium để in bản HTML của cổng thuế ra PDF (chạy ngầm). Không có → None."""
    import glob
    import shutil
    here = app_dir()
    cands = [os.environ.get("TAIHOADON_CHROME", "")]
    for env in ("PROGRAMFILES(X86)", "PROGRAMFILES", "LOCALAPPDATA"):
        root = os.environ.get(env)
        if root:
            cands += [os.path.join(root, "Microsoft", "Edge", "Application", "msedge.exe"),
                      os.path.join(root, "Google", "Chrome", "Application", "chrome.exe")]
    cands += [shutil.which(n) or "" for n in ("chromium", "chromium-browser", "google-chrome", "google-chrome-stable",
                                              "microsoft-edge", "msedge", "chrome")]
    for root in (os.environ.get("PLAYWRIGHT_BROWSERS_PATH", ""), os.path.join(here, "browsers"), "/opt/pw-browsers",
                 os.path.expanduser("~/.cache/ms-playwright")):
        if root:
            cands += sorted(glob.glob(os.path.join(root, "chromium-*", "chrome-linux*", "chrome")), reverse=True)
            cands += sorted(glob.glob(os.path.join(root, "chromium-*", "chrome-win*", "chrome.exe")), reverse=True)
    return next((c for c in cands if c and os.path.isfile(c)), None)


_BROWSER_FAIL = {"t": 0.0}


def html_to_pdf(html_path, timeout=90):
    """In file HTML ra PDF bằng trình duyệt chạy ngầm (chạy cả JavaScript của trang). Lỗi / không có trình duyệt → None."""
    import subprocess
    import tempfile
    exe = find_browser()
    if not exe or time.time() - _BROWSER_FAIL["t"] < 600:  # trình duyệt vừa lỗi (thiếu thư viện…) → 10 phút sau mới thử lại
        return None
    with tempfile.TemporaryDirectory() as td:
        out = os.path.join(td, "out.pdf")
        cmd = [exe, "--headless=new", "--disable-gpu", "--no-first-run", "--no-default-browser-check",
               "--user-data-dir=" + os.path.join(td, "profile"), "--no-pdf-header-footer", "--print-to-pdf-no-header",
               "--run-all-compositor-stages-before-draw", "--virtual-time-budget=8000", "--print-to-pdf=" + out,
               "file:///" + os.path.abspath(html_path).replace("\\", "/").lstrip("/")]
        if os.name != "nt":
            cmd.insert(1, "--no-sandbox")  # máy chủ / container thường không có sandbox của Chrome
        try:
            subprocess.run(cmd, timeout=timeout, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
                           env=dict(os.environ, HOME=os.environ.get("HOME") or td),
                           creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0))
        except (OSError, subprocess.SubprocessError):
            return None
        if os.path.exists(out) and os.path.getsize(out) > 800:
            with open(out, "rb") as f:
                return f.read()
    _BROWSER_FAIL["t"] = time.time()
    return None


def portal_html(base, e):
    """Bản HTML của cổng thuế đã tải (trong gói _goc nếu có, không thì file .html cạnh XML)."""
    xml = e.get("xml") or ""
    if not xml:
        return ""
    stem = os.path.splitext(xml)[0]
    goc = os.path.join(base, stem + "_goc")
    if os.path.isdir(goc):
        htmls = sorted(f for f in os.listdir(goc) if f.lower().endswith((".html", ".htm")))
        if htmls:
            return os.path.join(goc, htmls[0])
    for ext in (".html", ".htm"):
        if os.path.exists(os.path.join(base, stem + ext)):
            return os.path.join(base, stem + ext)
    return ""


def html_missing_assets(html_path):
    """Các file script / css mà bản HTML cần nhưng chưa có cạnh nó (bản cũ chỉ lưu .html)."""
    try:
        with open(html_path, encoding="utf-8", errors="ignore") as f:
            text = f.read(400000)
    except OSError:
        return []
    refs = re.findall(r"""<(?:script|link)[^>]+(?:src|href)\s*=\s*["']([^"':?#]+)["']""", text, re.I)
    folder = os.path.dirname(html_path)
    return [r for r in refs if not os.path.exists(os.path.join(folder, os.path.basename(r)))]


def _pdf_has_invoice(data, inv):
    try:
        import pypdf
        text = " ".join((p.extract_text() or "") for p in pypdf.PdfReader(io.BytesIO(data)).pages[:2])
    except Exception:
        return False
    so = str(_num(inv.get("shdon")) or "")
    return bool(so) and re.search(r"(?<!\d)0*%s(?!\d)" % re.escape(so), text) is not None


def tax_pdf(out_root, mst, kind, key, client=None):
    """Tạo (hoặc dùng lại) PDF bản thể hiện theo dữ liệu cổng thuế của một hoá đơn trong kho → đường dẫn tương đối."""
    base = company_dir(out_root, mst)
    idx = _load_json(index_file(out_root, mst), {})
    e = idx.get(kind, {}).get(key)
    if e is None:
        raise ValueError("Không tìm thấy hoá đơn")
    inv = dict(e.get("inv") or {})
    inv.setdefault("tdlap", e.get("tdlap"))
    src = ""
    if e.get("xml") and os.path.exists(os.path.join(base, e["xml"])):
        src = e["xml"]
    elif e.get("detail") and os.path.exists(os.path.join(base, e["detail"])):
        src = e["detail"]
    elif client is not None and client.token_valid():
        try:
            detail = client.get_detail(inv)
            src = save_detail_json(base, e, inv, detail)
            update_invoice_fields(out_root, mst, kind, key, detail=src)
        except PortalError:
            src = ""
    rel = (os.path.splitext(src)[0] if src else os.path.join("_cqt", invoice_basename(inv))) + "_CQT.pdf"
    path = os.path.join(base, rel)
    html = portal_html(base, e)
    want_html = bool(html) and find_browser() is not None
    if os.path.exists(path) and (not src or os.path.getmtime(path) >= os.path.getmtime(os.path.join(base, src))) \
            and (e.get("cqt_src") == "html" or not want_html):
        return rel
    if want_html:  # bản HTML chuẩn của cổng thuế → PDF (giữ nguyên giao diện của thuế)
        data = html_to_pdf(html)
        if data and _pdf_has_invoice(data, inv):
            os.makedirs(os.path.dirname(path), exist_ok=True)
            with open(path, "wb") as f:
                f.write(data)
            update_invoice_fields(out_root, mst, kind, key, cqt_src="html")
            return rel
    if src.endswith(".xml"):
        with open(os.path.join(base, src), "rb") as f:
            v = invoice_view_from_xml(f.read())
    elif src:
        v = invoice_view_from_detail(_load_json(os.path.join(base, src), {}))
    else:
        v = invoice_view_from_summary(inv)
    _fill_view(v, invoice_view_from_summary(inv))
    tthai = TTHAI_NIBOT.get(_num(e.get("tthai")), "")
    kq = TTXLY_NIBOT.get(_num(inv.get("ttxly")), "") if inv else ""
    data = build_tax_pdf(v, tthai, kq)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "wb") as f:
        f.write(data)
    update_invoice_fields(out_root, mst, kind, key, cqt_src="dung")
    return rel


def save_detail_json(base, e, inv, detail):
    """Lưu dữ liệu chi tiết của cổng cạnh thư mục XML của kỳ (hoặc _chi-tiet/) → đường dẫn tương đối với thư mục DN."""
    folder = os.path.dirname(e["xml"]) if e.get("xml") else "_chi-tiet"
    rel = os.path.join(folder, invoice_basename(inv) + ".json")
    os.makedirs(os.path.join(base, folder), exist_ok=True)
    _save_json(os.path.join(base, rel), detail)
    return rel


def update_invoice_fields(out_root, mst, kind, key, **fields):
    path = index_file(out_root, mst)
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        e = cur.get(kind, {}).get(key)
        if e is not None:
            e.update(fields)
            _save_json(path, cur)


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


def update_invoices(out_root, mst, kind, keys, **fields):
    """Sửa duyệt / HĐ dịch vụ cho nhiều hoá đơn một lần."""
    path = index_file(out_root, mst)
    n = 0
    with _INDEX_LOCK:
        cur = _load_json(path, {})
        entries = cur.get(kind, {})
        for key in keys:
            e = entries.get(key)
            if e is None:
                continue
            for f in ("duyet", "dv"):
                if fields.get(f) is not None:
                    e[f] = bool(fields[f]) if f == "dv" else fields[f]
            n += 1
        _save_json(path, cur)
    return n


def _sibling(base, rel, ext):
    if not rel:
        return ""
    cand = os.path.splitext(rel)[0] + ext
    return cand if os.path.exists(os.path.join(base, cand)) else ""


def _cqt_rel(base, e):
    src = e.get("xml") or e.get("detail") or ""
    rel = (os.path.splitext(src)[0] + "_CQT.pdf") if src else ""
    return rel if rel and os.path.exists(os.path.join(base, rel)) else ""


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
        tc = e.get("tra_cuu") or {}
        if xml and tc.get("ncc", "").startswith("MST ") and tc.get("ncc_mst") in PROVIDERS:
            # lưu từ bản chưa biết nhà cung cấp này → đọc lại XML một lần, lưu lại link + mã tra cứu
            e["tra_cuu"] = lookup_info(parse_invoice_xml(os.path.join(base, xml)), (e.get("inv") or {}).get("nbmst"))
            _set_entry(out_root, mst, kind, key, tra_cuu=e["tra_cuu"])
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
            "xml_goc": e.get("xml_goc") if e.get("xml_goc") and os.path.exists(os.path.join(base, e["xml_goc"])) else "",
            "need_goc": needs_original(base, e), "xml_goc_loi": e.get("xml_goc_loi", ""), "xml_url": browser_xml_url(e.get("tra_cuu")),
            "tra_cuu": fix_lookup(e.get("tra_cuu"), inv.get("nbmst")), "pdf_url": browser_pdf_url(e.get("tra_cuu")),
            "can_fetch": can_fetch_pdf(e.get("tra_cuu"))[0], "cqt": _cqt_rel(base, e)})
    rows.sort(key=lambda r: r.pop("_sort"))
    return rows


# ---------------------------------------------------------------------------
# Hàng hoá (như Nibot): danh mục tên hàng lấy từ hoá đơn mua vào (V) / bán ra (R), gán Mã TK + Mã hàng
# để kết xuất Smart KTSC (TKNO / MADMNO). Lưu theo từng doanh nghiệp: <thư mục DN>/hang-hoa.json
# ---------------------------------------------------------------------------
GOODS_LOAI = ("", "V", "R", "VR")
GOODS_LOCK = threading.RLock()


def goods_file(out_root, mst):
    return os.path.join(company_dir(out_root, mst), "hang-hoa.json")


def _goods_key(name):
    return re.sub(r"\s+", " ", str(name or "")).strip().casefold()


def goods_load(out_root, mst):
    return _load_json(goods_file(out_root, mst), {"items": []}).get("items", [])


def goods_save(out_root, mst, items):
    os.makedirs(company_dir(out_root, mst), exist_ok=True)
    _save_json(goods_file(out_root, mst), {"items": items})


def _goods_row(ten, loai="", ma_tk="", ma_hang="", ghi_chu="", dvt=None, gid=None):
    return {"id": gid or str(uuid.uuid4()), "ma_tk": str(ma_tk or "").strip(), "ma_hang": str(ma_hang or "").strip(),
            "ten": re.sub(r"\s+", " ", str(ten or "")).strip(), "loai": loai, "ghi_chu": str(ghi_chu or "").strip(),
            "dvt": list(dvt or [])}


def _goods_check(row):
    if not row["ten"]:
        raise ValueError("Tên hàng bị trống")
    if len(row["ten"]) > 5000:
        raise ValueError("Tên hàng quá dài: %s…" % row["ten"][:60])
    if len(row["ma_hang"]) > 100:
        raise ValueError("Mã hàng quá 100 ký tự: %s" % row["ma_hang"])
    if row["loai"] not in GOODS_LOAI:
        raise ValueError("Loại HĐ của '%s' không hợp lệ – chỉ được để trống, V, R hoặc VR" % row["ten"][:60])


def goods_sync(out_root, mst, start, end, kinds=("purchase", "sold"), skip_zero=False, use_xml_code=False):
    """Đồng bộ hàng hoá: tên hàng trong hoá đơn chưa có trong danh mục thì thêm vào (giữ nguyên mã đã gán).
    skip_zero: bỏ dòng chiết khấu hoặc (SL = 0 và thành tiền = 0). use_xml_code: gắn luôn mã hàng có trong XML."""
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        idx = {(_goods_key(x["ten"]), x["loai"]): x for x in items}
        base = company_dir(out_root, mst)
        added = 0
        for kind in kinds:
            loai = "V" if kind == "purchase" else "R"
            entries = _load_json(index_file(out_root, mst), {}).get(kind, {})
            for r in query_invoices(out_root, mst, {"kind": kind, "from": start, "to": end}):
                e = entries.get(r["key"]) or {}
                inv = dict(e.get("inv") or {})
                if r["xml"]:
                    inv["_xmlinfo"] = parse_invoice_xml(os.path.join(base, r["xml"]))
                elif e.get("detail"):
                    inv["_detailinfo"] = _load_json(os.path.join(base, e["detail"]), {})
                else:
                    continue
                for it in invoice_lines(inv):
                    name = re.sub(r"\s+", " ", it.get("name") or "").strip()
                    if not name:
                        continue
                    if skip_zero and (it.get("nature") == "3" or (not it.get("qty") and not it.get("amount"))):
                        continue
                    k = (_goods_key(name), loai)
                    row = idx.get(k)
                    if row is None:
                        row = _goods_row(name, loai)
                        items.append(row)
                        idx[k] = row
                        added += 1
                    unit = (it.get("unit") or "").strip()
                    if unit and unit not in row["dvt"]:
                        row["dvt"].append(unit)
                    if use_xml_code and not row["ma_hang"] and it.get("code"):
                        row["ma_hang"] = str(it["code"]).strip()[:100]
        goods_save(out_root, mst, items)
        return {"added": added, "total": len(items)}


def goods_update(out_root, mst, rows):
    """Lưu các dòng sửa / thêm mới: [{id?, ma_tk, ma_hang, ten, loai, ghi_chu}]."""
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        by_id = {x["id"]: x for x in items}
        n = 0
        for src in rows or []:
            cur = by_id.get(src.get("id") or "")
            row = _goods_row(src.get("ten", cur["ten"] if cur else ""), str(src.get("loai", cur["loai"] if cur else "")
                                                                            ).strip().upper(),
                             src.get("ma_tk", cur["ma_tk"] if cur else ""), src.get("ma_hang", cur["ma_hang"] if cur else ""),
                             src.get("ghi_chu", cur["ghi_chu"] if cur else ""), cur["dvt"] if cur else [],
                             cur["id"] if cur else None)
            _goods_check(row)
            if cur:
                cur.update(row)
            else:
                dup = [x for x in items if _goods_key(x["ten"]) == _goods_key(row["ten"]) and x["loai"] == row["loai"]]
                if dup:
                    raise ValueError("Đã có hàng hoá '%s' (loại %s)" % (row["ten"][:60], row["loai"] or "trống"))
                items.append(row)
                by_id[row["id"]] = row
            n += 1
        goods_save(out_root, mst, items)
        return n


def goods_assign(out_root, mst, ids, col, old, new):
    """Gán hàng loạt Mã TK / Mã hàng cho các dòng đã chọn; old khác trống thì chỉ thay dòng đang có giá trị đó."""
    field = {"MaTk": "ma_tk", "MaHang": "ma_hang", "ma_tk": "ma_tk", "ma_hang": "ma_hang"}.get(col)
    if not field:
        raise ValueError("Chưa chọn cột cần gán")
    new, old = str(new or "").strip(), str(old or "").strip()
    if field == "ma_hang" and len(new) > 100:
        raise ValueError("Mã hàng quá 100 ký tự")
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        want, n = set(ids or []), 0
        for x in items:
            if x["id"] in want and (not old or x[field] == old or not x[field]):
                x[field] = new
                n += 1
        goods_save(out_root, mst, items)
        return n


def _goods_ascii(text):
    t = unicodedata.normalize("NFD", str(text or "")).replace("đ", "d").replace("Đ", "D")
    return "".join(c for c in t if unicodedata.category(c) != "Mn")


def goods_autocode(out_root, mst, ids, kind, prefix="HH", digits=6):
    """Tạo mã hàng tự động cho các mặt hàng chưa có mã (đã chọn, hoặc tất cả):
    1 = <tiền tố><số thứ tự> (HH000001…); 2 = tên không dấu, không khoảng cách; 3 = chữ cái đầu của các từ."""
    kind = int(kind)
    if kind not in (1, 2, 3):
        raise ValueError("Chưa chọn kiểu tạo mã hàng")
    digits = max(1, min(int(digits or 6), 15))
    prefix = str(prefix or "").strip()
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        used = {x["ma_hang"].upper() for x in items if x["ma_hang"]}
        want = set(ids or [])
        targets = [x for x in items if not x["ma_hang"] and (not want or x["id"] in want)]
        nums = [int(m.group(1)) for c in used for m in [re.fullmatch(re.escape(prefix.upper()) + r"(\d+)", c)] if m]
        seq = max(nums) if nums else 0
        for x in targets:
            if kind == 1:
                seq += 1
                code = prefix + str(seq).zfill(digits)
            else:
                words = re.findall(r"[A-Za-z0-9]+", _goods_ascii(x["ten"]))
                code = ("".join(words) if kind == 2 else "".join(w[0] for w in words)).upper()[:90] or "HH"
                base_code, i = code, 1
                while code.upper() in used:
                    i += 1
                    code = "%s%d" % (base_code, i)
            used.add(code.upper())
            x["ma_hang"] = code
        goods_save(out_root, mst, items)
        return len(targets)


def goods_delete(out_root, mst, mode, ids=None):
    """mode 0: xoá tất cả; 1: xoá hàng hoá chưa gán mã; 2: xoá các dòng đã chọn."""
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        want = set(ids or [])
        keep = [] if int(mode) == 0 else [x for x in items if (x["ma_hang"] or x["ma_tk"])] if int(mode) == 1 else \
            [x for x in items if x["id"] not in want]
        goods_save(out_root, mst, keep)
        return len(items) - len(keep)


GOODS_HEAD = ["Mã TK", "Mã hàng", "Tên hàng", "ĐVT", "Loại HĐ", "Ghi chú", "GuidHang"]


def goods_export(out_root, mst, items=None):
    """Xuất Excel đúng mẫu Nibot (Mã TK, Mã hàng, Tên hàng, ĐVT, Loại HĐ, Ghi chú, GuidHang) – sửa rồi Import lại."""
    from openpyxl import Workbook
    from openpyxl.styles import Font
    items = goods_load(out_root, mst) if items is None else items
    wb = Workbook()
    ws = wb.active
    ws.title = "Sheet"
    ws.append(GOODS_HEAD)
    for c in ws[1]:
        c.font = Font(bold=True)
    for x in sorted(items, key=lambda x: x["id"]):
        ws.append([x["ma_tk"], x["ma_hang"], x["ten"], ", ".join(x.get("dvt") or []), x["loai"], x["ghi_chu"], x["id"]])
    for col, w in zip("ABCDEFG", (10, 16, 60, 10, 8, 20, 38)):
        ws.column_dimensions[col].width = w
    out_dir = os.path.join(company_dir(out_root, mst), "_ket-xuat")
    os.makedirs(out_dir, exist_ok=True)
    path = os.path.join(out_dir, "HangHoa_%s_%s.xlsx" % (mst, datetime.now().strftime("%y%m%d%H%M%S")))
    wb.save(path)
    return path


def goods_import(out_root, mst, data, drop_dup=False):
    """Import Excel mẫu Nibot. Khớp theo GuidHang, không có thì theo Tên hàng + Loại HĐ; chưa có thì thêm mới.
    Trả {'added','updated'} hoặc ValueError kèm danh sách dòng lỗi / tên trùng."""
    from openpyxl import load_workbook
    try:
        wb = load_workbook(io.BytesIO(data), read_only=True, data_only=True)
    except Exception:
        raise ValueError("File Excel không đúng định dạng XLSX (Office 2007 trở lên)")
    rows = list(wb.worksheets[0].iter_rows(values_only=True))
    if not rows:
        raise ValueError("Không đọc được dữ liệu trong file Excel")
    head = [str(v or "").strip().lower() for v in rows[0]]
    col = {}
    for name in ("Mã TK", "Mã hàng", "Tên hàng", "Loại HĐ", "Ghi chú", "GuidHang"):
        if name.lower() not in head:
            raise ValueError("Không tìm thấy cột %s – dùng file Export Excel của trang Hàng hoá làm mẫu" % name)
        col[name] = head.index(name.lower())
    get = lambda r, k: ("" if col[k] >= len(r) or r[col[k]] is None else str(r[col[k]]).strip())
    errs, recs = [], []
    for i, r in enumerate(rows[1:], 2):
        if not any(v not in (None, "") for v in r):
            continue
        row = _goods_row(get(r, "Tên hàng"), get(r, "Loại HĐ").upper(), get(r, "Mã TK"), get(r, "Mã hàng"),
                         get(r, "Ghi chú"), gid=get(r, "GuidHang") or None)
        try:
            _goods_check(row)
        except ValueError as e:
            errs.append("Dòng %d: %s" % (i, e))
            continue
        recs.append(row)
    if errs:
        raise ValueError("\n".join(errs[:30]))
    keys = collections.Counter((_goods_key(x["ten"]), x["loai"]) for x in recs)
    dups = sorted({x["ten"] for x in recs if keys[(_goods_key(x["ten"]), x["loai"])] > 1})
    if dups and not drop_dup:
        raise ValueError("Có %d hàng hoá bị trùng tên:\n%s" % (len(dups), "\n".join(dups[:30])))
    with GOODS_LOCK:
        items = goods_load(out_root, mst)
        by_id = {x["id"]: x for x in items}
        by_key = {(_goods_key(x["ten"]), x["loai"]): x for x in items}
        added = updated = 0
        seen = set()
        for row in recs:
            k = (_goods_key(row["ten"]), row["loai"])
            if k in seen:
                continue  # trùng tên trong file: giữ dòng đầu
            seen.add(k)
            cur = by_id.get(row["id"]) or by_key.get(k)
            if cur:
                row["id"], row["dvt"] = cur["id"], cur.get("dvt") or []
                cur.update(row)
                updated += 1
            else:
                items.append(row)
                added += 1
            by_key[k] = cur or row
        goods_save(out_root, mst, items)
    return {"added": added, "updated": updated}


def goods_index(out_root, mst):
    """Tra mã theo tên hàng cho kết xuất: {(tên chuẩn hoá, 'V'/'R'): {ma_tk, ma_hang}} – dòng loại trống/VR dùng cho cả hai."""
    out = {}
    for x in goods_load(out_root, mst):
        if not (x["ma_tk"] or x["ma_hang"]):
            continue
        for loai in (("V", "R") if x["loai"] in ("", "VR") else (x["loai"],)):
            k = (_goods_key(x["ten"]), loai)
            if k not in out or x["loai"] == loai:  # dòng đúng loại ưu tiên hơn dòng dùng chung
                out[k] = {"ma_tk": x["ma_tk"], "ma_hang": x["ma_hang"]}
    return out


# ---------------------------------------------------------------------------
# Đối tượng pháp nhân – ĐTPN (như Nibot): người bán (V) / người mua (R) lấy từ hoá đơn, gán Mã KH/NCC (Smart Pro),
# TK thanh toán (TM/CK), TK chi phí / doanh thu, duyệt nội bộ, HĐ dịch vụ, loại HHDV SXKD (mục 1/2/3 của bảng kê).
# Lưu ở <thư mục DN>/doi-tuong.json
# ---------------------------------------------------------------------------
PARTNER_FIELDS = ("ma_kh", "tk_tt", "tk_cpdt")
PARTNER_HEAD = ["Loại HĐ", "Tên đối tượng PN", "MST doanh nghiệp", "Mã Khách Hàng/ NCC Smart Pro", "Duyệt nội bộ",
                "Hóa Đơn DV", "Mã Tài khoản TK/CM", "Mã Tài khoản DT/CP", "Loại HHDV SXKD"]


def partner_file(out_root, mst):
    return os.path.join(company_dir(out_root, mst), "doi-tuong.json")


def partner_load(out_root, mst):
    return _load_json(partner_file(out_root, mst), {"items": []}).get("items", [])


def partner_save(out_root, mst, items):
    os.makedirs(company_dir(out_root, mst), exist_ok=True)
    _save_json(partner_file(out_root, mst), {"items": items})


def _partner_row(mst_, ten="", loai="V", ma_kh="", duyet="", hd_dv=False, tk_tt="", tk_cpdt="", sxkd="", pid=None):
    return {"id": pid or str(uuid.uuid4()), "loai": loai, "ten": str(ten or "").strip(),
            "mst": re.sub(r"\s+", "", str(mst_ or "")) if re.fullmatch(r"[\d\s-]+", str(mst_ or "")) else str(mst_ or "").strip(),
            "ma_kh": str(ma_kh or "").strip(), "duyet": duyet or "", "hd_dv": bool(hd_dv),
            "tk_tt": str(tk_tt or "").strip(), "tk_cpdt": str(tk_cpdt or "").strip(), "sxkd": str(sxkd or "").strip()}


def _partner_check(r):
    if not r["mst"]:
        raise ValueError("Thiếu MST (hoặc tên khách lẻ) của đối tượng")
    if r["loai"] not in ("V", "R"):
        raise ValueError("Loại HĐ của %s phải là V (mua vào) hoặc R (bán ra)" % r["mst"])
    if r["sxkd"] not in ("", "1", "2", "3"):
        raise ValueError("Loại HHDV SXKD của %s: để trống hoặc 1: HHDV dùng riêng cho SXKD; 2: dùng chung; "
                         "3: dùng cho dự án" % r["mst"])
    if r["duyet"] and r["duyet"] not in DUYET_OPTIONS:
        raise ValueError("Duyệt nội bộ của %s phải là: %s" % (r["mst"], ", ".join(DUYET_OPTIONS)))


def _partner_of(inv, kind):
    """(mã đối tượng, tên) của hoá đơn: mua vào = người bán; bán ra = người mua (không MST thì tên khách lẻ)."""
    if kind == "purchase":
        return str(inv.get("nbmst") or "").strip(), inv.get("nbten") or ""
    m = str(inv.get("nmmst") or inv.get("nmcccd") or "").strip()
    return (m or (inv.get("nmten") or inv.get("nmtnmua") or "").strip()), inv.get("nmten") or inv.get("nmtnmua") or ""


def partner_sync(out_root, mst, start, end, kinds=("purchase", "sold")):
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        have = {(x["mst"], x["loai"]) for x in items}
        added = 0
        for kind in kinds:
            loai = "V" if kind == "purchase" else "R"
            entries = _load_json(index_file(out_root, mst), {}).get(kind, {})
            for r in query_invoices(out_root, mst, {"kind": kind, "from": start, "to": end}):
                code, ten = _partner_of((entries.get(r["key"]) or {}).get("inv") or {}, kind)
                if code and (code, loai) not in have:
                    items.append(_partner_row(code, ten, loai))
                    have.add((code, loai))
                    added += 1
        partner_save(out_root, mst, items)
        return {"added": added, "total": len(items)}


def partner_update(out_root, mst, rows):
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        by_id = {x["id"]: x for x in items}
        n = 0
        for src in rows or []:
            cur = by_id.get(src.get("id") or "") or {}
            v = lambda k, d="": src.get(k, cur.get(k, d))
            row = _partner_row(v("mst"), v("ten"), str(v("loai", "V")).upper(), v("ma_kh"), v("duyet"), v("hd_dv", False),
                               v("tk_tt"), v("tk_cpdt"), v("sxkd"), cur.get("id"))
            _partner_check(row)
            if cur:
                cur.update(row)
            else:
                if any(x["mst"] == row["mst"] and x["loai"] == row["loai"] for x in items):
                    raise ValueError("Đã có đối tượng %s (loại %s)" % (row["mst"], row["loai"]))
                items.append(row)
            n += 1
        partner_save(out_root, mst, items)
        return n


def partner_assign(out_root, mst, ids, col, old, new):
    field = {"MaKh": "ma_kh", "MaTaiKhoan": "tk_tt", "MaTkCpdt": "tk_cpdt"}.get(col, col)
    if field not in PARTNER_FIELDS:
        raise ValueError("Chưa chọn cột cần gán")
    new, old = str(new or "").strip(), str(old or "").strip()
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        want, n = set(ids or []), 0
        for x in items:
            if x["id"] in want and (not old or x[field] == old or not x[field]):
                x[field] = new
                n += 1
        partner_save(out_root, mst, items)
        return n


def partner_autocode(out_root, mst, ids, kind, prefix="", suffix=""):
    """Tạo Mã ĐTPN cho đối tượng chưa có mã: 1 = MST (hoặc tên khách lẻ); 2 = 5 ký tự cuối của MST (kiểu Smart Pro);
    3 = tên không dấu, không khoảng cách. Có thể thêm tiền tố / hậu tố. Trùng mã thì thêm _2, _3…"""
    kind = int(kind)
    if kind not in (1, 2, 3):
        raise ValueError("Chưa chọn kiểu tạo mã")
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        used = {x["ma_kh"].upper() for x in items if x["ma_kh"]}
        want = set(ids or [])
        targets = [x for x in items if not x["ma_kh"] and (not want or x["id"] in want)]
        for x in targets:
            if kind == 1:
                core = x["mst"]
            elif kind == 2:
                digits = re.sub(r"\D", "", x["mst"].split("-")[0])
                core = digits[-5:] if digits else re.sub(r"[^A-Za-z0-9]", "", _goods_ascii(x["mst"])).upper()[:40]
            else:
                core = re.sub(r"[^A-Za-z0-9]", "", _goods_ascii(x["ten"] or x["mst"])).upper()[:40]
            code = base = "%s%s%s" % (prefix or "", core, suffix or "")
            i = 1
            while code.upper() in used:
                i += 1
                code = "%s_%d" % (base, i)
            used.add(code.upper())
            x["ma_kh"] = code
        partner_save(out_root, mst, items)
        return len(targets)


def partner_delete(out_root, mst, mode, ids=None):
    """mode 0: xoá tất cả; 1: chưa có mã ĐTPN; 2: các dòng đã chọn."""
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        want = set(ids or [])
        mode = int(mode)
        keep = [] if mode == 0 else [x for x in items if x["ma_kh"]] if mode == 1 else [x for x in items if x["id"] not in want]
        partner_save(out_root, mst, keep)
        return len(items) - len(keep)


def partner_export(out_root, mst):
    from openpyxl import Workbook
    from openpyxl.styles import Font
    wb = Workbook()
    ws = wb.active
    ws.title = "DTPN"
    ws.append(PARTNER_HEAD)
    for c in ws[1]:
        c.font = Font(bold=True)
    for x in partner_load(out_root, mst):
        ws.append([x["loai"], x["ten"], x["mst"], x["ma_kh"], x["duyet"], 1 if x["hd_dv"] else "", x["tk_tt"], x["tk_cpdt"],
                   x["sxkd"]])
    for col, w in zip("ABCDEFGHI", (8, 50, 16, 22, 12, 10, 14, 14, 12)):
        ws.column_dimensions[col].width = w
    out_dir = os.path.join(company_dir(out_root, mst), "_ket-xuat")
    os.makedirs(out_dir, exist_ok=True)
    path = os.path.join(out_dir, "DTPN_%s_%s.xlsx" % (mst, datetime.now().strftime("%y%m%d%H%M%S")))
    wb.save(path)
    return path


def _xlsx_rows(data, need):
    """Đọc sheet đầu của file .xlsx, tìm cột theo tiêu đề (không phân biệt hoa thường) → (danh sách dòng, hàm lấy ô)."""
    from openpyxl import load_workbook
    try:
        wb = load_workbook(io.BytesIO(data), read_only=True, data_only=True)
    except Exception:
        raise ValueError("File Excel không đúng định dạng XLSX (Office 2007 trở lên)")
    rows = list(wb.worksheets[0].iter_rows(values_only=True))
    if not rows:
        raise ValueError("Không đọc được dữ liệu trong file Excel")
    head = [re.sub(r"\s+", " ", str(v or "")).strip().lower() for v in rows[0]]
    col = {}
    for name in need:
        key = re.sub(r"\s+", " ", name).strip().lower()
        if key not in head:
            raise ValueError("Không tìm thấy cột \"%s\" – dùng file Export Excel làm mẫu" % name)
        col[name] = head.index(key)

    def get(r, k):
        i = col[k]
        v = r[i] if i < len(r) else None
        if isinstance(v, float) and v.is_integer():
            v = int(v)
        return "" if v is None else str(v).strip()
    return rows[1:], get


def partner_import(out_root, mst, data):
    rows, get = _xlsx_rows(data, PARTNER_HEAD)
    recs, errs, seen = [], [], set()
    for i, r in enumerate(rows, 2):
        if not any(v not in (None, "") for v in r):
            continue
        dv = get(r, "Hóa Đơn DV").lower()
        row = _partner_row(get(r, "MST doanh nghiệp"), get(r, "Tên đối tượng PN"), (get(r, "Loại HĐ") or "V").upper(),
                           get(r, "Mã Khách Hàng/ NCC Smart Pro"), get(r, "Duyệt nội bộ"), dv in ("1", "true", "x", "có"),
                           get(r, "Mã Tài khoản TK/CM"), get(r, "Mã Tài khoản DT/CP"), get(r, "Loại HHDV SXKD"))
        try:
            _partner_check(row)
            if (row["mst"], row["loai"]) in seen:
                raise ValueError("MST %s bị trùng" % row["mst"])
        except ValueError as e:
            errs.append("Dòng %d: %s" % (i, e))
            continue
        seen.add((row["mst"], row["loai"]))
        recs.append(row)
    if errs:
        raise ValueError("\n".join(errs[:30]))
    with GOODS_LOCK:
        items = partner_load(out_root, mst)
        idx = {(x["mst"], x["loai"]): x for x in items}
        added = updated = 0
        for row in recs:
            cur = idx.get((row["mst"], row["loai"]))
            if cur:
                row["id"] = cur["id"]
                row["ten"] = row["ten"] or cur["ten"]
                cur.update(row)
                updated += 1
            else:
                items.append(row)
                added += 1
        partner_save(out_root, mst, items)
    return {"added": added, "updated": updated}


def partner_index(out_root, mst):
    return {(x["mst"], x["loai"]): x for x in partner_load(out_root, mst)}


def partner_remark(out_root, mst, year, what):
    """Đánh dấu lại hoá đơn hàng loạt theo ĐTPN: what='dv' → HĐ dịch vụ; 'duyet' → duyệt nội bộ (chỉ ĐTPN có đặt)."""
    idx = partner_index(out_root, mst)
    n = 0
    for kind in ("purchase", "sold"):
        loai = "V" if kind == "purchase" else "R"
        f = {"kind": kind, "from": "%d-01-01" % int(year), "to": "%d-12-31" % int(year)} if year else {"kind": kind}
        entries = _load_json(index_file(out_root, mst), {}).get(kind, {})
        groups = collections.defaultdict(list)
        for r in query_invoices(out_root, mst, f):
            p = idx.get((_partner_of((entries.get(r["key"]) or {}).get("inv") or {}, kind)[0], loai))
            if not p:
                continue
            if what == "dv":
                groups[("dv", p["hd_dv"])].append(r["key"])
            elif p["duyet"]:
                groups[("duyet", p["duyet"])].append(r["key"])
        for (f_, v), keys in groups.items():
            n += update_invoices(out_root, mst, kind, keys, **{f_: v})
    return n


# ---------------------------------------------------------------------------
# Quy đổi ĐVT (như Nibot): mặt hàng + ĐVT gốc trên hoá đơn → ĐVT đích, số lượng quy đổi (1 ĐVT gốc = SL ĐVT đích).
# Lưu ở <thư mục DN>/quy-doi.json. Kết xuất KTSC: DONVI = ĐVT đích, LUONG = SL × hệ số, đơn giá = đơn giá / hệ số.
# ---------------------------------------------------------------------------
UNIT_HEAD = ["Mã hàng", "Tên hàng", "ĐVT Gốc (hóa đơn)", "ĐVT Đích (Quy Đổi)", "Số lượng quy đổi"]


def unit_file(out_root, mst):
    return os.path.join(company_dir(out_root, mst), "quy-doi.json")


def unit_load(out_root, mst):
    return _load_json(unit_file(out_root, mst), {"items": []}).get("items", [])


def unit_save(out_root, mst, items):
    os.makedirs(company_dir(out_root, mst), exist_ok=True)
    _save_json(unit_file(out_root, mst), {"items": items})


def _unit_row(ma_hang, ten, dvt, dvt_qd, sl, uid=None):
    try:
        sl = float(str(sl).replace(",", ".")) if sl not in (None, "") else 0.0
    except ValueError:
        raise ValueError("Số lượng quy đổi phải là số")
    r = {"id": uid or str(uuid.uuid4()), "ma_hang": str(ma_hang or "").strip(),
         "ten": re.sub(r"\s+", " ", str(ten or "")).strip(), "dvt": str(dvt or "").strip(),
         "dvt_qd": str(dvt_qd or "").strip(), "sl": sl}
    if not (r["ma_hang"] or r["ten"]):
        raise ValueError("Chưa chọn hàng hoá")
    if not r["dvt"]:
        raise ValueError("Đơn vị tính (Hóa đơn) không được để trống")
    if not r["dvt_qd"]:
        raise ValueError("Đơn vị tính (Quy đổi) không được để trống")
    if r["sl"] <= 0:
        raise ValueError("Số lượng (Quy đổi) phải là số lớn hơn 0")
    return r


def _unit_key(r):
    return (r["ma_hang"].upper() or _goods_key(r["ten"]), r["dvt"].casefold())


def unit_update(out_root, mst, rows, delete_ids=None):
    with GOODS_LOCK:
        items = unit_load(out_root, mst)
        drop = set(delete_ids or [])
        items = [x for x in items if x["id"] not in drop]
        by_id = {x["id"]: x for x in items}
        n = len(drop)
        for src in rows or []:
            cur = by_id.get(src.get("id") or "") or {}
            v = lambda k: src.get(k, cur.get(k, ""))
            row = _unit_row(v("ma_hang"), v("ten"), v("dvt"), v("dvt_qd"), v("sl"), cur.get("id"))
            if any(_unit_key(x) == _unit_key(row) and x["id"] != row["id"] for x in items):
                raise ValueError("%s đã có ĐVT [%s] được quy đổi" % (row["ma_hang"] or row["ten"], row["dvt"]))
            if cur:
                cur.update(row)
            else:
                items.append(row)
            n += 1
        unit_save(out_root, mst, items)
        return n


def unit_delete(out_root, mst, ids=None):
    """Xoá các dòng đã chọn; ids rỗng = xoá tất cả."""
    with GOODS_LOCK:
        items = unit_load(out_root, mst)
        keep = [x for x in items if ids and x["id"] not in set(ids)]
        unit_save(out_root, mst, keep)
        return len(items) - len(keep)


def unit_export(out_root, mst):
    from openpyxl import Workbook
    from openpyxl.styles import Font
    wb = Workbook()
    ws = wb.active
    ws.title = "Sheet"
    ws.append(UNIT_HEAD)
    for c in ws[1]:
        c.font = Font(bold=True)
    for x in unit_load(out_root, mst):
        ws.append([x["ma_hang"], x["ten"], x["dvt"], x["dvt_qd"], x["sl"]])
    ws.freeze_panes = "A2"
    for col, w in zip("ABCDE", (16, 50, 16, 16, 14)):
        ws.column_dimensions[col].width = w
    out_dir = os.path.join(company_dir(out_root, mst), "_ket-xuat")
    os.makedirs(out_dir, exist_ok=True)
    path = os.path.join(out_dir, "Quy Đổi ĐVT - %s.xlsx" % mst)
    wb.save(path)
    return path


def unit_import(out_root, mst, data):
    rows, get = _xlsx_rows(data, UNIT_HEAD)
    recs, errs, seen = [], [], set()
    for i, r in enumerate(rows, 2):
        if not any(v not in (None, "") for v in r):
            continue
        try:
            row = _unit_row(get(r, "Mã hàng"), get(r, "Tên hàng"), get(r, "ĐVT Gốc (hóa đơn)"),
                            get(r, "ĐVT Đích (Quy Đổi)"), get(r, "Số lượng quy đổi"))
            if _unit_key(row) in seen:
                raise ValueError("Dữ liệu bị trùng với dòng trước đó")
        except ValueError as e:
            errs.append("Dòng %d: %s" % (i, e))
            continue
        seen.add(_unit_key(row))
        recs.append(row)
    if errs:
        raise ValueError("\n".join(errs[:30]))
    with GOODS_LOCK:
        items = unit_load(out_root, mst)
        idx = {_unit_key(x): x for x in items}
        added = updated = 0
        for row in recs:
            cur = idx.get(_unit_key(row))
            if cur:
                row["id"] = cur["id"]
                cur.update(row)
                updated += 1
            else:
                items.append(row)
                added += 1
        unit_save(out_root, mst, items)
    return {"added": added, "updated": updated}


def unit_index(out_root, mst):
    return {_unit_key(x): x for x in unit_load(out_root, mst)}


def unit_convert(it, units, ma_hang=""):
    """Dòng hàng sau quy đổi ĐVT: (đơn vị, số lượng, đơn giá). Khớp theo mã hàng (danh mục Hàng hoá) rồi tên hàng."""
    unit = (it.get("unit") or "").strip()
    if not units or not unit:
        return unit, it.get("qty") or 0, it.get("price") or 0
    rule = None
    for key in ((str(ma_hang or "").upper(), unit.casefold()), (_goods_key(it.get("name")), unit.casefold())):
        if key[0] and key in units:
            rule = units[key]
            break
    if not rule:
        return unit, it.get("qty") or 0, it.get("price") or 0
    k = rule["sl"]
    return rule["dvt_qd"], round((it.get("qty") or 0) * k, 6), round((it.get("price") or 0) / k, 6)


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
            elif e.get("detail"):  # không có XML: dòng hàng lấy từ dữ liệu chi tiết của cổng (như Nibot)
                inv["_detailinfo"] = _load_json(os.path.join(base, e["detail"]), {})
            invoices.append(inv)
        dates = [invoice_date(i) for i in invoices if invoice_date(i)]
        start = parse_date(f["from"]) if f.get("from") else min(dates)
        end = parse_date(f["to"]) if f.get("to") else max(dates)
        path = os.path.join(out_dir, "%s_%s_%s.xlsx" % (label, mst, stamp))
        return write_nibot_workbook(path, kind, invoices, mst, company_name, start, end,
                                    goods=goods_index(out_root, mst), partners=partner_index(out_root, mst),
                                    units=unit_index(out_root, mst))
    if fmt == "all":  # tải hết: XML gốc + PDF (gốc của người bán, không có thì PDF thuế) + bảng kê Excel
        path = os.path.join(out_dir, "%s_TAT_CA_%s_%s.zip" % (label, mst, stamp))
        xlsx = export_invoices(out_root, mst, company_name, f, "xlsx")
        with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
            z.write(xlsx, os.path.basename(xlsx))
            for r in rows:
                if r["xml"]:
                    z.write(os.path.join(base, r["xml"]), "XML/" + os.path.basename(r["xml"]))
                if r["xml_goc"]:
                    z.write(os.path.join(base, r["xml_goc"]), "XML_goc_nguoi_ban/" + os.path.basename(r["xml_goc"]))
                if r["pdf"]:
                    z.write(os.path.join(base, r["pdf"]), "PDF/" + os.path.basename(r["pdf"]))
                else:
                    try:
                        cq = tax_pdf(out_root, mst, kind, r["key"])
                        z.write(os.path.join(base, cq), "PDF/" + os.path.basename(cq))
                    except (ValueError, OSError):
                        pass
        return path
    if fmt == "cqt":
        files = [tax_pdf(out_root, mst, kind, r["key"]) for r in rows]
        path = os.path.join(out_dir, "%s_PDF_THUE_%s_%s.zip" % (label, mst, stamp))
        with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
            for rel in files:
                z.write(os.path.join(base, rel), os.path.basename(rel))
        return path
    if fmt == "pdf":  # hoá đơn gốc của người bán: PDF gốc + XML gốc (nếu có)
        files = [(r["pdf"], "") for r in rows if r["pdf"]] + [(r["xml_goc"], "XML_goc/") for r in rows if r["xml_goc"]]
        if not files:
            raise ValueError("Các hoá đơn đang lọc chưa có hoá đơn gốc (PDF/XML của người bán)")
        path = os.path.join(out_dir, "%s_HD_GOC_%s_%s.zip" % (label, mst, stamp))
        with zipfile.ZipFile(path, "w", zipfile.ZIP_DEFLATED) as z:
            for rel, folder in files:
                z.write(os.path.join(base, rel), folder + os.path.basename(rel))
        return path
    if fmt not in ("xml", "html"):
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
# Sao kê ngân hàng → file KTSC nhập phần mềm kế toán (Smart Pro), theo mẫu Nibot
# ---------------------------------------------------------------------------

BANK_NAMES = (("VIETINBANK", "VIETIN"), ("CONG THUONG", "VIETIN"), ("VIETCOMBANK", "VCB"), ("NGOAI THUONG", "VCB"),
              ("TECHCOMBANK", "TCB"), ("KY THUONG", "TCB"), ("BIDV", "BIDV"), ("DAU TU VA PHAT TRIEN", "BIDV"),
              ("AGRIBANK", "AGRIBANK"), ("ACB", "ACB"), ("A CHAU", "ACB"), ("MBBANK", "MB"), ("QUAN DOI", "MB"),
              ("SACOMBANK", "SACOMBANK"), ("SAI GON THUONG TIN", "SACOMBANK"), ("VPBANK", "VPBANK"),
              ("TPBANK", "TPBANK"), ("TIEN PHONG", "TPBANK"), ("SHB", "SHB"), ("HDBANK", "HDBANK"), ("OCB", "OCB"),
              ("VIB", "VIB"), ("EXIMBANK", "EXIMBANK"), ("SEABANK", "SEABANK"), ("MSB", "MSB"), ("HANG HAI", "MSB"),
              ("SHINHAN", "SHINHAN"), ("WOORI", "WOORI"))
KTSC_BANK_COLS = ("LCTG", "NGAYCT", "SOCT", "DIENGIAI", "TKNO", "MADTPNNO", "TKCO", "MADTPNCO", "TTVND", "TTVND_TT",
                  "TENKH", "ID_NGHIEPVU", "GHICHU", "GUID")


def _bank_plain(v):
    return re.sub(r"[^a-z0-9/]+", "", _plain(v))


def read_table_file(name, data):
    """Đọc file bảng tính sao kê → [(tên sheet, [hàng [ô...]])]. Hỗ trợ .xlsx, .xls (Excel 97-2003, cần xlrd),
    .xls/.html dạng bảng HTML (nhiều ngân hàng xuất kiểu này) và .csv."""
    head = data[:8]
    if head[:2] == b"PK":
        try:
            import openpyxl
        except ImportError:
            raise ValueError("Chưa cài openpyxl (pip install openpyxl)")
        try:
            wb = openpyxl.load_workbook(io.BytesIO(data), read_only=True, data_only=True)
        except Exception as e:
            raise ValueError("Không đọc được file Excel %s: %s" % (name, e))
        return [(ws.title, [list(r) for r in ws.iter_rows(values_only=True)]) for ws in wb.worksheets]
    if head == b"\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1":
        try:
            import xlrd
        except ImportError:
            raise ValueError("Đọc file .xls (Excel 97-2003) cần thư viện xlrd: pip install xlrd "
                             "(bản web: khởi động lại dịch vụ để tự cài) – hoặc mở file bằng Excel, lưu lại dạng .xlsx")
        try:
            book = xlrd.open_workbook(file_contents=data)
        except Exception as e:
            raise ValueError("Không đọc được file .xls %s: %s" % (name, e))
        sheets = []
        for sh in book.sheets():
            rows = []
            for r in range(sh.nrows):
                row = []
                for c in range(sh.ncols):
                    cell = sh.cell(r, c)
                    if cell.ctype == xlrd.XL_CELL_DATE:
                        try:
                            row.append(xlrd.xldate_as_datetime(cell.value, book.datemode))
                            continue
                        except Exception:
                            pass
                    row.append(cell.value)
                rows.append(row)
            sheets.append((sh.name, rows))
        return sheets
    text = None
    for enc in ("utf-8-sig", "utf-16", "cp1258", "cp1252"):
        try:
            text = data.decode(enc)
            if enc != "utf-16" or "\x00" not in text:
                break
        except UnicodeDecodeError:
            continue
    if text is None:
        raise ValueError("Không nhận ra định dạng file %s" % name)
    if re.search(r"<\s*(table|tr)\b", text[:200000], re.I):
        from html.parser import HTMLParser

        class P(HTMLParser):
            def __init__(self):
                super().__init__()
                self.rows, self.row, self.cell = [], None, None

            def handle_starttag(self, tag, attrs):
                if tag == "tr":
                    self.row = []
                elif tag in ("td", "th") and self.row is not None:
                    self.cell = []
                elif tag == "br" and self.cell is not None:
                    self.cell.append(" ")

            def handle_endtag(self, tag):
                if tag in ("td", "th") and self.cell is not None and self.row is not None:
                    self.row.append(re.sub(r"\s+", " ", "".join(self.cell)).strip())
                    self.cell = None
                elif tag == "tr" and self.row is not None:
                    self.rows.append(self.row)
                    self.row = None

            def handle_data(self, d):
                if self.cell is not None:
                    self.cell.append(d)
        p = P()
        p.feed(text)
        return [(os.path.splitext(name)[0], p.rows)]
    sample = text[:5000]
    delim = max((";", ",", "\t", "|"), key=sample.count)
    return [(os.path.splitext(name)[0], [r for r in csv.reader(io.StringIO(text), delimiter=delim)])]


def _bank_amount(v):
    if v is None or v == "":
        return 0.0
    if isinstance(v, (int, float)):
        return float(v)
    s = re.sub(r"[^\d,.\-()]", "", str(v))
    neg = s.startswith("-") or (s.startswith("(") and s.endswith(")"))
    s = s.strip("-()")
    if not s:
        return 0.0
    if "," in s and "." in s:  # 1,234,567.89 hoặc 1.234.567,89
        dec = "." if s.rfind(".") > s.rfind(",") else ","
        s = s.replace("." if dec == "," else ",", "").replace(dec, ".")
    elif s.count(",") > 1 or s.count(".") > 1 or re.fullmatch(r"\d{1,3}([.,]\d{3})+", s):
        s = s.replace(",", "").replace(".", "")  # dấu ngăn cách hàng nghìn
    else:
        s = s.replace(",", ".")
    try:
        f = float(s)
    except ValueError:
        return 0.0
    return -f if neg else f


def _bank_date(v):
    if isinstance(v, datetime):
        return v
    if isinstance(v, date):
        return datetime(v.year, v.month, v.day)
    s = str(v or "").strip()
    m = re.match(r"(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})(?:\D+(\d{1,2}):(\d{2})(?::(\d{2}))?)?", s)
    if m:
        d, mo, y = int(m.group(1)), int(m.group(2)), int(m.group(3))
        hh, mm, ss = (int(m.group(i) or 0) for i in (4, 5, 6))
    else:
        m = re.match(r"(\d{4})[/.-](\d{1,2})[/.-](\d{1,2})(?:\D+(\d{1,2}):(\d{2})(?::(\d{2}))?)?", s)
        if not m:
            return None
        y, mo, d = int(m.group(1)), int(m.group(2)), int(m.group(3))
        hh, mm, ss = (int(m.group(i) or 0) for i in (4, 5, 6))
    try:
        return datetime(y, mo, d, hh, mm, ss)
    except ValueError:
        return None


def _bank_col(text):
    """Tên cột sao kê → loại cột (date, desc, out, in, amount, ref, name, None)."""
    p = _bank_plain(text)
    toks = set(re.split(r"[^a-z0-9]+", _plain(text)))
    if not p or "stt" in toks or p.startswith("stt") or "sodu" in p or "balance" in p or "luyke" in p:
        return None
    if "ten" in p and ("doiung" in p or "corresponsive" in p or "thuhuong" in p or "nguoichuyen" in p
                       or "nguoinhan" in p or "khachhang" in p or "counterpart" in p or "beneficiary" in p):
        return "name"
    if "sotaikhoan" in p or "accountno" in p or "taikhoandoiung" in p or "virtualaccount" in p:
        return None
    if any(k in p for k in ("sogiaodich", "transactionnumber", "sothamchieu", "reference", "refno", "magiaodich",
                            "sobut", "soct", "transactionid", "transno")):
        return "ref"
    if any(k in p for k in ("mota", "noidung", "diengiai", "description", "details", "remark", "narrative")):
        return "desc"
    if "ngay" in p or "date" in p:
        return "date"
    if any(k in p for k in ("ghino", "sotienno", "phatsinhno", "debit", "withdraw", "sotienchi", "tienra", "rut")) \
            or p.startswith("no/") or p == "no" or p.startswith("chi"):
        return "out"
    if any(k in p for k in ("ghico", "sotienco", "phatsinhco", "credit", "deposit", "sotienthu", "tienvao")) \
            or p.startswith("co/") or p == "co" or p.startswith("thu"):
        return "in"
    if "sotien" in p or "amount" in p:
        return "amount"
    return None


def parse_bank_statement(name, data):
    """Đọc sao kê ngân hàng → thông tin tài khoản + danh sách giao dịch (theo thứ tự trong file)."""
    if data[:5] == b"%PDF-":
        return _finish_statement(parse_bank_pdf(name, data))
    best = None
    for sheet, rows in read_table_file(name, data):
        for i, row in enumerate(rows[:80]):
            cols = {}
            for j, v in enumerate(row):
                kind = _bank_col(v) if isinstance(v, str) else None
                if kind and kind not in cols:
                    cols[kind] = j
            if "date" in cols and ("desc" in cols or "ref" in cols) and (
                    ("in" in cols and "out" in cols) or "amount" in cols):
                best = (sheet, rows, i, cols)
                break
        if best:
            break
    if not best:
        raise ValueError("%s: không tìm thấy bảng giao dịch (cần các cột Ngày, Mô tả/Nội dung, Nợ/Có hoặc Số tiền)" % name)
    sheet, rows, hdr, cols = best
    top = " ".join(_plain(c) if isinstance(c, str) else str(c or "") for r in rows[:hdr] for c in r)
    top += " " + _plain(name)
    if data[:2] != b"PK" and data[:4] != b"\xd0\xcf\x11\xe0":  # file dạng chữ (html/csv): tên ngân hàng có thể nằm ngoài bảng
        top += " " + _plain(re.sub(r"<[^>]+>", " ", data[:20000].decode("utf-8", "ignore")))
    info = {"file": name, "bank": "", "account": "", "company": "", "from": "", "to": "",
            "opening": None, "closing": None, "total_in": None, "total_out": None, "rows": [], "warnings": []}
    for key, code in BANK_NAMES:
        if _plain(key).replace(" ", "") in top.replace(" ", ""):
            info["bank"] = code
            break

    def label_value(*labels):
        for r in rows[:hdr]:
            for j, c in enumerate(r):
                if isinstance(c, str) and any(lb in _bank_plain(c) for lb in labels):
                    rest = [x for x in r[j + 1:] if x not in (None, "")]
                    if rest:
                        return rest[0]
                    m = re.search(r":\s*(.+)$", c)
                    if m:
                        return m.group(1).strip()
        return None
    acc = label_value("sotaikhoan", "accountno", "taikhoanso")
    info["account"] = re.sub(r"[^\dA-Za-z]", "", str(acc or ""))[:30]
    info["company"] = str(label_value("congty/companyname", "tenkhachhang", "tentaikhoan", "chutaikhoan",
                                      "companyname", "accountname") or "").strip()
    for key, labels in (("opening", ("sodudauky", "openingbalance")), ("closing", ("soducuoiky", "closingbalance")),
                        ("total_in", ("tonggiatrighico", "tongghico", "totalcredit", "tongsotienghico")),
                        ("total_out", ("tonggiatrighino", "tongghino", "totaldebit", "tongsotienghino"))):
        v = label_value(*labels)
        if v not in (None, ""):
            info[key] = _bank_amount(v)
    # Hàng "Tổng giá trị ghi có ... Tổng giá trị ghi nợ" nằm cùng một hàng: tìm riêng từng nhãn
    for r in rows[:hdr]:
        for j, c in enumerate(r):
            if not isinstance(c, str):
                continue
            p = _bank_plain(c)
            nxt = next((x for x in r[j + 1:] if x not in (None, "")), None)
            if nxt is None:
                continue
            if "ghico" in p and "tong" in p and "so" not in p.replace("tongso", "") and "giaodich" not in p:
                info["total_in"] = _bank_amount(nxt)
            elif "ghino" in p and "tong" in p and "giaodich" not in p:
                info["total_out"] = _bank_amount(nxt)
    ncol = max(cols.values()) + 1
    for r in rows[hdr + 1:]:
        r = list(r) + [None] * (ncol - len(r))
        d = _bank_date(r[cols["date"]])
        if not d:
            continue
        if "amount" in cols and not ("in" in cols and "out" in cols):
            a = _bank_amount(r[cols["amount"]])
            tin, tout = (a, 0.0) if a > 0 else (0.0, -a)
        else:
            tin, tout = abs(_bank_amount(r[cols["in"]])), abs(_bank_amount(r[cols["out"]]))
        if not tin and not tout:
            continue
        val = lambda k: re.sub(r"[\r\n\t]+", " ", str(r[cols[k]] if k in cols and r[cols[k]] is not None else "")).strip()
        ref = val("ref")
        if ref.endswith(".0") and ref[:-2].isdigit():
            ref = ref[:-2]
        info["rows"].append({"date": d.strftime("%Y-%m-%d"), "time": d.strftime("%H:%M:%S"), "ref": ref,
                             "desc": val("desc"), "name": val("name"), "in": tin, "out": tout})
    return _finish_statement(info)


def _finish_statement(info):
    """Kỳ sao kê, tổng thu/chi và đối chiếu với tổng / số dư in trên sao kê."""
    name = info["file"]
    if not info["rows"]:
        raise ValueError("%s: không có giao dịch nào" % name)
    ds = sorted(x["date"] for x in info["rows"])
    info["from"], info["to"] = ds[0], ds[-1]
    s_in, s_out = sum(x["in"] for x in info["rows"]), sum(x["out"] for x in info["rows"])
    info["sum_in"], info["sum_out"] = s_in, s_out
    if info["total_in"] is not None and abs(info["total_in"] - s_in) > 0.5:
        info["warnings"].append("Tổng ghi có trên sao kê %s ≠ tổng các dòng đọc được %s" % (
            "{:,.0f}".format(info["total_in"]), "{:,.0f}".format(s_in)))
    if info["total_out"] is not None and abs(info["total_out"] - s_out) > 0.5:
        info["warnings"].append("Tổng ghi nợ trên sao kê %s ≠ tổng các dòng đọc được %s" % (
            "{:,.0f}".format(info["total_out"]), "{:,.0f}".format(s_out)))
    if info["opening"] is not None and info["closing"] is not None and \
            abs(info["opening"] + s_in - s_out - info["closing"]) > 0.5:
        info["warnings"].append("Số dư đầu kỳ + thu − chi không bằng số dư cuối kỳ – kiểm tra lại file")
    return info


_AMOUNT_RE = re.compile(r"^-?(?:0|\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?|\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?|\d{1,3})$")
_PDF_SKIP_RE = re.compile(r"chungtunay|automaticallyexported|tongphatsinh|tongcong|soducuoi|closingbalance|bangchu|"
                          r"inwords|^trang\d|^page\d")


class _PdfSkip:
    """Dòng chân trang / tổng cộng / số dư cuối kỳ – không phải giao dịch (so khớp không dấu)."""
    @staticmethod
    def search(text):
        return _PDF_SKIP_RE.search(_bank_plain(text))


_PDF_SKIP = _PdfSkip()


def _unchunk35(s):
    """Nội dung giao dịch MB (core T24) bị cắt thành đoạn 35 ký tự nối bằng 2 dấu cách ("chuyen kh  oan") → nối lại."""
    i = 0
    while len(s) > i + 37 and s[i + 35:i + 37] == "  ":
        s = s[:i + 35] + s[i + 37:]
        i += 35
    return s


def _pdf_fragments(page):
    """Các đoạn chữ trên trang PDF kèm toạ độ đọc (x tăng sang phải, y tăng lên trên)."""
    out = []

    def visit(text, cm, tm, fd, fs):
        t = (text or "").replace("\n", " ").strip()
        if not t:
            return
        if abs(cm[1]) > 0.5 and abs(cm[2]) > 0.5:  # trang xoay ngang: toạ độ trong hệ của chữ
            x, y = tm[4], tm[5]
        else:
            x, y = tm[4] * cm[0] + cm[4], tm[5] * cm[3] + cm[5]
        out.append({"x": round(x, 1), "y": round(y, 1), "t": t})
    page.extract_text(visitor_text=visit)
    return out


def _bank_col_pdf(text):
    p = _bank_plain(text)
    if any(k in p for k in ("donvithuhuong", "donvichuyen", "beneficiary", "nguoithuhuong", "nguoichuyen")):
        return "name"
    if "hachtoan" in p or "accountingdate" in p:
        return "date_acc"
    return _bank_col(text)


def parse_bank_pdf(name, data):
    """Sổ phụ ngân hàng dạng PDF có chữ (xuất từ internet banking, không phải bản scan)."""
    pypdf = _pypdf()
    try:
        reader = pypdf.PdfReader(io.BytesIO(data))
        pages = [_pdf_fragments(p) for p in reader.pages]
        full = "\n".join((p.extract_text() or "") for p in reader.pages)
    except Exception as e:
        raise ValueError("Không đọc được PDF %s: %s" % (name, e))
    if len(re.sub(r"\s", "", full)) < 50:
        raise ValueError("%s: PDF không có chữ (bản scan/ảnh chụp) – cần file sổ phụ tải từ internet banking hoặc file Excel" % name)
    info = {"file": name, "bank": "", "account": "", "company": "", "from": "", "to": "", "opening": None, "closing": None,
            "total_in": None, "total_out": None, "rows": [], "warnings": []}
    head = full.split("\n")
    flat = _plain(" ".join(head[:6]))  # tên ngân hàng ở đầu sổ phụ (nội dung giao dịch có tên ngân hàng đối tác)
    for key, code in BANK_NAMES:
        if _plain(key).replace(" ", "") in flat.replace(" ", ""):
            info["bank"] = code
            break

    def grab(pattern):
        m = re.search(pattern, full, re.I)
        return m.group(1).strip() if m else ""
    info["account"] = re.sub(r"\D", "", grab(r"(?:Số tài khoản|Tài khoản|Account No\.?)\s*/?[^:\n]{0,25}:\s*([\d .\-]{6,25})"))[:20]
    info["company"] = grab(r"(?:Tên khách hàng|Tên tài khoản|Customer name|Account name)\s*/?[^:\n]{0,25}:\s*([^\n]+)")
    for key, pat in (("opening", r"(?:Số dư đầu kỳ|Opening Balance)[^:\n]*:\s*(-?[\d.,]+)"),
                     ("closing", r"(?:Số dư cuối kỳ|Closing Balance)[^:\n]*:\s*(-?[\d.,]+)")):
        v = grab(pat)
        if v:
            info[key] = _bank_amount(v)
    header = None

    def header_band(frags):
        """Dòng tiêu đề bảng: các nhãn cột ngắn nằm cùng dải với nhãn ngày."""
        for d in frags:
            if _bank_col_pdf(d["t"]) not in ("date", "date_acc") or len(d["t"]) > 30:
                continue
            cols = {}
            for f in sorted(frags, key=lambda f: (f["x"], -f["y"])):
                if abs(f["y"] - d["y"]) > 22 or len(f["t"]) > 40 or re.search(r":\s*\S", f["t"]):
                    continue  # nhãn cột không kèm giá trị ("Tên khách hàng: …" là thông tin tài khoản)
                k = _bank_col_pdf(f["t"])
                if k and (k not in cols or len(f["t"]) > len(cols[k]["t"])):  # nhãn đầy đủ nhất ("Phát sinh nợ", không phải "No")
                    cols[k] = f
            if ("date" in cols or "date_acc" in cols) and "in" in cols and "out" in cols:
                return cols
        return None
    for frags in pages:
        header = header_band(frags)
        if header:
            break
    if not header:
        raise ValueError("%s: không tìm thấy bảng giao dịch trong sổ phụ PDF" % name)
    amount_hdr = sorted((header[k]["x"], k) for k in ("out", "in"))
    amt_lo, amt_hi = amount_hdr[0][0] - 45, amount_hdr[-1][0] + 90
    date_x = header.get("date_acc", header.get("date"))["x"]
    ref_x = header["ref"]["x"] if "ref" in header else None
    text_hdrs = sorted((header[k]["x"], k) for k in ("desc", "name") if k in header)

    def is_amount(f):
        return amt_lo <= f["x"] <= amt_hi and _AMOUNT_RE.match(f["t"].replace(" ", ""))
    # Cột chữ (nội dung, tên đối ứng…): nhóm theo vị trí bắt đầu của chữ trên toàn bộ sổ phụ
    starts = {}
    body = []
    for pi, frags in enumerate(pages):
        band = header_band(frags)
        if not band:
            continue
        top = min(f["y"] for f in band.values()) - 6
        stop = max([f["y"] for f in frags if _PDF_SKIP.search(f["t"]) and f["y"] < top] or [-1e9])
        page_body = [f for f in frags if stop + 1 < f["y"] < top and not _PDF_SKIP.search(f["t"])]
        body.append(page_body)
        for f in page_body:
            if f["x"] > amt_hi - 60 and not is_amount(f):
                starts[round(f["x"] / 4)] = starts.get(round(f["x"] / 4), 0) + 1
    col_x = sorted({k * 4 for k, n in starts.items() if n >= 2})
    clusters = []
    for x in col_x:
        if clusters and x - clusters[-1][-1] <= 8:
            clusters[-1].append(x)
        else:
            clusters.append([x])
    text_cols = [min(c) for c in clusters]
    # gán cột chữ cho tiêu đề gần nhất (theo thứ tự trái → phải)
    col_kind = {}
    for hx, k in text_hdrs:
        free = [c for c in text_cols if c not in col_kind and c <= hx + 20]
        if free:
            col_kind[max(free, key=lambda c: (c <= hx + 20, -abs(c - hx)))] = k
    if text_hdrs and "desc" not in col_kind.values() and text_cols:
        col_kind[text_cols[0]] = "desc"

    def text_col(x):
        own = [c for c in text_cols if c <= x + 3]
        return col_kind.get(max(own)) if own else None
    for page_body in body:
        anchors = []
        for f in sorted((f for f in page_body if is_amount(f)), key=lambda f: -f["y"]):
            if anchors and abs(anchors[-1]["y"] - f["y"]) <= 2:
                anchors[-1]["amts"].append(f)
            else:
                anchors.append({"y": f["y"], "amts": [f], "frags": []})
        if not anchors:
            continue
        for f in page_body:
            if is_amount(f):
                continue
            a = min(anchors, key=lambda a: abs(a["y"] - f["y"]))
            if abs(a["y"] - f["y"]) <= 45:
                a["frags"].append(f)
        for a in anchors:
            amts = sorted(a["amts"], key=lambda f: f["x"])
            vals = {}
            if len(amts) == len(amount_hdr):
                for (hx, k), f in zip(amount_hdr, amts):
                    vals[k] = _bank_amount(f["t"])
            else:
                for f in amts:
                    k = min(amount_hdr, key=lambda h: abs(h[0] - f["x"]))[1]
                    vals[k] = _bank_amount(f["t"])
            dates = [f for f in a["frags"] if re.search(r"\d{1,2}/\d{1,2}/\d{4}", f["t"])]
            if not dates:
                continue
            dfrag = min(dates, key=lambda f: abs(f["x"] - date_x))
            d = _bank_date(re.search(r"\d{1,2}/\d{1,2}/\d{4}", dfrag["t"]).group(0))
            tin, tout = abs(vals.get("in", 0.0)), abs(vals.get("out", 0.0))
            if not d or (not tin and not tout):
                continue
            lines = {"desc": [], "name": []}
            ref = []
            for f in sorted(a["frags"], key=lambda f: (-f["y"], f["x"])):
                if f in dates or re.fullmatch(r"\d{1,2}:\d{2}(:\d{2})?", f["t"]):
                    continue
                if ref_x is not None and f["x"] < amt_lo and abs(f["x"] - ref_x) <= 30:
                    ref.append(f["t"])
                    continue
                k = text_col(f["x"]) if f["x"] >= amt_lo else None
                if k in lines:
                    lines[k].append(f["t"])
            clean = lambda parts: re.sub(r"\s+", " ", " ".join(parts)).strip()
            info["rows"].append({"date": d.strftime("%Y-%m-%d"), "time": "", "ref": "".join(ref).replace(" ", ""),
                                 "desc": clean([_unchunk35(" ".join(lines["desc"]))]), "name": clean(lines["name"]),
                                 "in": tin, "out": tout})
    # Tổng phát sinh (số có thể bị ngắt 2 dòng: "693,350,3" + "85")
    for frags in pages:
        lab = next((f for f in frags if re.search(r"^(tongphatsinh|tongcong|total)", _bank_plain(f["t"]))), None)
        if not lab:
            continue
        near = sorted((f for f in frags if abs(f["y"] - lab["y"]) <= 12 and f["x"] > lab["x"]
                       and re.fullmatch(r"[\d.,]+", f["t"])), key=lambda f: (-f["y"], f["x"]))
        groups = []  # số in 2 dòng: phần sau nằm dòng dưới, lệch phải
        for f in near:
            prev = [g for g in groups if g["x"] <= f["x"] + 2 and g["y"] > f["y"] + 2]
            if prev:
                max(prev, key=lambda g: g["x"])["t"] += f["t"]
            else:
                groups.append(dict(f))
        nums = [(g["x"], _bank_amount(g["t"])) for g in sorted(groups, key=lambda g: g["x"])]
        if len(nums) >= 2:
            for (hx, k), (x, v) in zip(amount_hdr, nums[:2]):
                info["total_" + ("in" if k == "in" else "out")] = v
    return info


def bank_ktsc_rows(rows, opts=None):
    """Giao dịch → các dòng KTSC. Mặc định giống Nibot: chỉ ghi TK ngân hàng, để trống TK đối ứng.
    opts: tk (1121), ma_dt (mã đối tượng của TK ngân hàng), tk_thu / tk_chi (TK đối ứng tiền vào / tiền ra),
    nghiep_vu (TIENHANG), ghi_chu."""
    o = opts or {}
    tk = str(o.get("tk") or "1121").strip()
    ma = str(o.get("ma_dt") or "").strip()
    tk_thu, tk_chi = str(o.get("tk_thu") or "").strip(), str(o.get("tk_chi") or "").strip()
    out = []
    for x in rows:
        incoming = x["in"] > 0
        amt = x["in"] if incoming else x["out"]
        amt = int(amt) if float(amt).is_integer() else amt
        out.append({"LCTG": "CTNH", "NGAYCT": datetime.strptime(x["date"], "%Y-%m-%d"), "SOCT": x.get("ref") or "",
                    "DIENGIAI": x.get("desc") or "",
                    "TKNO": tk if incoming else (str(x.get("tkdu") or "").strip() or tk_chi),
                    "MADTPNNO": ma if incoming else "",
                    "TKCO": (str(x.get("tkdu") or "").strip() or tk_thu) if incoming else tk,
                    "MADTPNCO": "" if incoming else ma,
                    "TTVND": amt, "TTVND_TT": amt, "TENKH": x.get("name") or "",
                    "ID_NGHIEPVU": str(o.get("nghiep_vu") or "TIENHANG"), "GHICHU": str(o.get("ghi_chu") or "TAIHOADON IMPORT"),
                    "GUID": ""})
    return out


def merge_bank_statements(infos):
    """Gộp nhiều sao kê, bỏ giao dịch trùng (sao kê tháng sau hay lặp giao dịch cuối tháng trước)."""
    seen, rows, dups = set(), [], 0
    for info in infos:
        for x in info["rows"]:
            k = (info.get("account"), x["date"], x["time"], x["ref"], x["in"], x["out"], x["desc"])
            if k in seen:
                dups += 1
                continue
            seen.add(k)
            rows.append(dict(x, account=info.get("account", ""), bank=info.get("bank", "")))
    return rows, dups


def write_bank_ktsc(rows, opts=None):
    """→ bytes file .xlsx sheet KTSC (14 cột như file Nibot)."""
    try:
        import openpyxl
        from openpyxl.styles import Font
    except ImportError:
        raise ValueError("Chưa cài openpyxl (pip install openpyxl)")
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "KTSC"
    ws.append(list(KTSC_BANK_COLS))
    for c in ws[1]:
        c.font = Font(bold=True)
    for r in bank_ktsc_rows(rows, opts):
        ws.append([r[k] for k in KTSC_BANK_COLS])
        ws.cell(ws.max_row, 2).number_format = "dd/mm/yyyy"
        for col in (9, 10):
            ws.cell(ws.max_row, col).number_format = "#,##0"
    for col, w in zip("ABCDEFGHIJKLMN", (7, 11, 20, 70, 7, 12, 7, 12, 14, 14, 40, 12, 16, 6)):
        ws.column_dimensions[col].width = w
    ws.freeze_panes = "A2"
    buf = io.BytesIO()
    wb.save(buf)
    return buf.getvalue()


# ---------------------------------------------------------------------------
# Giao diện web chạy trên máy (127.0.0.1)
# ---------------------------------------------------------------------------

LOGIN_PAGE = r"""<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đăng nhập – Tải hoá đơn</title>
<style>
:root{--bg:#f4f6f9;--card:#fff;--fg:#1d2733;--mute:#66758a;--line:#dde3ea;--pri:#2b3a8c;--acc:#5b5fe0;--err:#c0392b}
@media (prefers-color-scheme:dark){:root{--bg:#12161c;--card:#1b2129;--fg:#e6ebf1;--mute:#93a1b3;--line:#2c3540;--pri:#3a4bb0;--acc:#8b8ff5;--err:#ff6b5b}}
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;
font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--fg)}
form{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:28px;width:min(380px,100%)}
h1{font-size:22px;margin:0 0 4px;color:var(--pri)}p{margin:0 0 18px;color:var(--mute);font-size:13px}
label{display:block;font-size:13px;color:var(--mute);margin:12px 0 4px}
input{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--bg);color:var(--fg);font:inherit}
button{width:100%;margin-top:18px;padding:11px;border:0;border-radius:8px;background:var(--acc);color:#fff;font:inherit;font-weight:600;cursor:pointer}
button:disabled{opacity:.6}.err{color:var(--err);font-size:13px;margin-top:10px;min-height:1em}
.ok{color:#1e8449;font-size:13px;margin-top:10px}a{color:var(--acc);font-size:13px;cursor:pointer}.hide{display:none}
</style></head><body>
<form id="f"><h1>Tải hoá đơn điện tử</h1><p>Đăng nhập để làm việc với các doanh nghiệp được giao.</p>
<label for="u">Tên đăng nhập</label><input id="u" autocomplete="username" autofocus required>
<label for="p">Mật khẩu</label><input id="p" type="password" autocomplete="current-password" required>
<button id="b">Đăng nhập</button><div class="err" id="e"></div>
<div style="margin-top:12px;text-align:right"><a id="fg">Quên mật khẩu?</a></div></form>
<form id="f2" class="hide"><h1>Quên mật khẩu</h1><p>Nhập tên đăng nhập hoặc email của tài khoản. Phần mềm gửi link đặt mật khẩu mới về email đã đăng ký.</p>
<label for="q">Tên đăng nhập hoặc email</label><input id="q" required>
<button id="b2">Gửi link về email</button><div class="err" id="e2"></div><div class="ok" id="o2"></div>
<div style="margin-top:12px"><a href="/">← Quay lại đăng nhập</a></div></form>
<form id="f3" class="hide"><h1>Đặt mật khẩu mới</h1><p id="who3">Đặt mật khẩu cho tài khoản.</p>
<label for="n1">Mật khẩu mới (tối thiểu 8 ký tự)</label><input id="n1" type="password" autocomplete="new-password" required>
<label for="n2">Nhập lại</label><input id="n2" type="password" autocomplete="new-password" required>
<button id="b3">Lưu mật khẩu</button><div class="err" id="e3"></div><div class="ok" id="o3"></div>
<div style="margin-top:12px"><a href="/">← Đăng nhập</a></div></form>
<script>
const $ = id => document.getElementById(id);
const call = async (url, body) => { const r = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(body)});
  const d = await r.json().catch(() => ({})); if (!r.ok) throw new Error(d.error || ('Lỗi ' + r.status)); return d; };
$('fg').onclick = () => { $('f').classList.add('hide'); $('f2').classList.remove('hide'); $('q').value = $('u').value; $('q').focus(); };
$('f2').onsubmit = async ev => { ev.preventDefault(); $('e2').textContent = ''; $('o2').textContent = ''; $('b2').disabled = true;
  try { const d = await call('/api/forgot', {username: $('q').value});
        $('o2').textContent = d.mail ? 'Nếu tài khoản có email, link đặt lại mật khẩu đã được gửi (hiệu lực 30 phút). Kiểm tra cả thư mục Spam.'
                                     : 'Máy chủ chưa cấu hình gửi email – liên hệ quản trị văn phòng để đặt lại mật khẩu.'; }
  catch (x) { $('e2').textContent = x.message; } $('b2').disabled = false; };
const tok = new URLSearchParams(location.search).get('t');
if (location.pathname === '/dat-lai-mat-khau' && tok) {
  $('f').classList.add('hide'); $('f3').classList.remove('hide');
  call('/api/reset', {token: tok, check: true}).then(d => { $('who3').textContent = 'Đặt mật khẩu cho tài khoản: ' + d.username; })
    .catch(x => { $('e3').textContent = x.message; $('b3').disabled = true; });
  $('f3').onsubmit = async ev => { ev.preventDefault(); $('e3').textContent = '';
    if ($('n1').value !== $('n2').value) { $('e3').textContent = 'Hai lần nhập không khớp'; return; }
    $('b3').disabled = true;
    try { await call('/api/reset', {token: tok, password: $('n1').value});
          $('o3').textContent = 'Đã đặt mật khẩu. Đang chuyển tới trang đăng nhập…'; setTimeout(() => location.href = '/', 1500); }
    catch (x) { $('e3').textContent = x.message; $('b3').disabled = false; } };
}
document.getElementById('f').onsubmit = async ev => {
  ev.preventDefault(); const b = document.getElementById('b'), e = document.getElementById('e'); b.disabled = true; e.textContent = '';
  try {
    const r = await fetch('/api/login', {method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({username: document.getElementById('u').value, password: document.getElementById('p').value})});
    const d = await r.json().catch(() => ({}));
    if (r.ok && d.ok) { location.reload(); return; }
    e.textContent = d.error || ('Lỗi ' + r.status);
  } catch (x) { e.textContent = 'Không kết nối được máy chủ'; }
  b.disabled = false;
};
</script></body></html>
"""


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
input[type=text],input[type=search],input[type=password],input[type=date],textarea{padding:8px 10px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
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
#hTbl td.mh{max-width:260px;white-space:normal}
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
.staff .adm,.local .srv{display:none!important}
.lic-link{color:#fff;margin:0 10px;font-size:13px;cursor:pointer}.lic-link.warn{background:#c62828;padding:2px 8px;border-radius:10px}
.no-dongbo .q-dongbo,.no-hdgoc .q-hdgoc,.no-sua .q-sua,.no-ketxuat .q-ketxuat,.no-saoke .q-saoke,.no-dn .q-dn,
body:not(.owner) .own{display:none!important}
.sysbar{max-width:1400px;margin:10px auto 0;padding:0 16px;display:flex;flex-direction:column;gap:6px}
.sysbar>div{border-radius:8px;padding:8px 12px;font-size:13px;white-space:pre-line}
.sb-ann{background:#2b2b2b;color:#ffd54a;font-weight:600;border:1px solid #444}
.sb-hint{background:#fff8e1;color:#6d5300;border-left:4px solid #f4b400}
.sb-portal.warn{background:#fff3e0;color:#8a4b00;border-left:4px solid #fb8c00}.sb-portal.err{background:#fdecea;color:#a12622;border-left:4px solid #e53935}
.sb-goi{background:var(--card);border:1px solid var(--line);color:var(--fg)}.sb-goi.warn{border-color:#fb8c00;background:#fff3e0;color:#8a4b00}
.sb-goi.err{border-color:#e53935;background:#fdecea;color:#a12622}.sb-goi small{color:var(--mute)}
.vpname{font-size:12px;background:rgba(255,255,255,.18);border-radius:10px;padding:2px 8px;margin-left:6px}
.perms{display:grid;grid-template-columns:1fr 1fr;gap:4px 12px;margin:4px 0 8px}.perms label{font-weight:400}
.pw{-webkit-text-security:disc;text-security:disc}
/* Trang doanh nghiệp */
.wshead{padding-bottom:0}.wshead .crumb{margin-bottom:6px}
body.ws main{max-width:none}
.wsrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#wsPick{flex:1;min-width:260px;font-weight:600;font-size:15px;padding:8px 12px;border:1px solid var(--line);border-radius:8px;background:var(--bg);color:var(--fg)}
.wsmeta{font-size:12px;color:var(--mute);display:flex;gap:12px;flex-wrap:wrap}.wsmeta b{color:var(--fg)}
.subtabs{display:flex;gap:2px;margin-top:10px;overflow-x:auto}
.subtabs a{padding:9px 16px;font-weight:700;font-size:13px;letter-spacing:.3px;text-transform:uppercase;color:var(--mute);
  text-decoration:none;border-bottom:3px solid transparent;white-space:nowrap}
.subtabs a.on{color:var(--acc);border-bottom-color:var(--acc)}.subtabs a:hover{color:var(--fg)}
.filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px}
.filters select,.filters input{padding:7px 9px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
.filters .grow{flex:1;min-width:200px}
.seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
.seg button{border:0;border-radius:0;background:transparent;color:var(--fg);padding:7px 12px}
.seg button+button{border-left:1px solid var(--line)}.seg button.on{background:var(--acc);color:#fff}
.bulk{display:flex;gap:8px;flex-wrap:wrap;align-items:center;background:#eef0ff;border:1px solid #c9ceff;color:#222;border-radius:8px;padding:8px 10px;margin:6px 0}
.bulk select{width:auto}
.grid{overflow:auto;max-height:calc(100vh - 96px);min-height:260px;border:1px solid var(--line);border-radius:8px}
.grid table{border-collapse:separate;border-spacing:0;width:100%}
.grid th,.grid td{font-size:13px;padding:6px 6px;border-bottom:1px solid var(--line);background:var(--card)}
.grid thead{position:sticky;top:0;z-index:3}
.grid thead th{background:var(--bg);color:var(--acc);white-space:nowrap;user-select:none}
.grid thead tr.f th{padding:4px;font-weight:400}
.grid thead th.s{cursor:pointer}.grid thead th.s:hover{color:var(--fg)}
.grid thead tr.f input,.grid thead tr.f select{width:100%;min-width:52px;padding:4px 6px;font-size:12px;border:1px solid var(--line);border-radius:5px;background:var(--card);color:var(--fg)}
.grid tfoot td{position:sticky;bottom:0;z-index:2;background:var(--bg);font-weight:700;color:var(--acc);border-top:2px solid var(--line)}
.grid tbody tr{cursor:default}.grid tbody tr:hover td{background:rgba(91,95,224,.06)}
.grid tbody tr.sel td{background:rgba(91,95,224,.14)}.grid tbody tr.sel td:first-child{box-shadow:inset 3px 0 var(--acc)}
.grid tbody tr.bad td{color:var(--err)}.grid td.nw{white-space:nowrap}
.grid td.mh,.grid td.ten{white-space:normal;min-width:150px;max-width:300px}
#gTbl tr.g-grp td{background:#5b2c8f;color:#fff;font-weight:600}#gTbl input.note{width:100%;min-width:90px}
.grid td.ct{white-space:normal;min-width:130px;max-width:170px;line-height:1.5}.grid td.ct a.lk{margin:0 6px 0 0;display:inline-block}
.grid thead th{white-space:normal;vertical-align:bottom}
.clamp{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.35}
@media(max-width:760px){
  header{padding:8px 10px;gap:8px}header nav{order:3;width:100%;margin-left:0;overflow-x:auto}header small{display:none}
  main{padding:8px}.card{padding:10px;border-radius:8px}
  .wsrow #wsPick{order:-1;flex:1 1 100%;min-width:0;font-size:14px}
  .wsmeta span:nth-child(n+4){display:none}
  .filters>*{flex:1 1 calc(50% - 8px);min-width:0}.filters .grow{flex-basis:100%}
  .seg{flex-basis:100%;overflow-x:auto}.seg button{flex:1 0 auto}
  .grid{max-height:calc(100vh - 70px)}.grid td.mh,.grid td.ten{min-width:160px}
  .pager .tip{display:none}.subtabs a{padding:8px 10px}
}
.grid select{min-width:0;max-width:110px;padding:3px 2px;font-size:12px}
.grid thead tr.f select{width:100%;max-width:84px;min-width:62px}
@media(max-width:1700px){.grid th,.grid td{font-size:12px;padding:5px 4px}.grid td.mh,.grid td.ten{min-width:108px}
  .grid td.ct{min-width:100px;max-width:126px}.grid td.ct a.lk{margin-right:4px}
  .grid thead tr.f select{min-width:54px}.grid thead tr.f input{min-width:42px}.grid tbody select{max-width:92px}}
a.dn-link{color:inherit;text-decoration:none}a.dn-link:hover{color:var(--acc);text-decoration:underline}
.me{display:flex;gap:8px;align-items:center;font-size:13px}.me a{color:#fff;opacity:.85;text-decoration:none;cursor:pointer}.me a:hover{opacity:1;text-decoration:underline}
.bell{position:relative;background:transparent;border:0;color:#fff;font-size:18px;padding:2px 6px}.bell i{position:absolute;top:-4px;right:-4px;background:#e5534b;color:#fff;
font-style:normal;font-size:11px;border-radius:9px;padding:0 5px;min-width:16px;text-align:center}
#notes{position:absolute;right:16px;top:52px;width:min(520px,calc(100vw - 32px));max-height:70vh;overflow:auto;z-index:8;box-shadow:0 8px 30px rgba(0,0,0,.25)}
#notes .it{padding:8px 0;border-bottom:1px solid var(--line);font-size:13px}#notes .it.new{font-weight:600}#notes .warn{color:#b26a00}#notes .err2{color:var(--err)}
.ulist{max-height:260px;overflow:auto;border:1px solid var(--line);border-radius:6px;padding:6px}.ulist label{display:flex;gap:6px;color:var(--fg);margin:2px 0;font-size:13px}
.crumb{font-size:13px;color:var(--mute);margin-bottom:10px}.crumb b{color:var(--fg)}#sTbl td,#sTbl th{font-size:13px;padding:5px}.pager .cur{background:var(--err);border-color:var(--err)}a.lk{color:var(--acc);margin-right:6px}
</style></head><body>
<header><b>Tải hoá đơn</b><nav><a href="#" id="navDN" class="on" onclick="tab('DN');return false">Doanh nghiệp</a>
<a href="#" id="navHD" onclick="openCompany(wsMst || (companies.find(c => !c.an) || {}).mst);return false">Hoá đơn</a>
<a href="#" id="navTI" onclick="tab('TI');return false">Tiện ích</a>
<a href="#" id="navND" class="adm srv" onclick="tab('ND');return false">Quản trị</a></nav><span style="flex:1"></span><small id="capStat"></small>
<a href="#" id="licLink" class="hide lic-link" onclick="openLic();return false" title="Tài khoản bản quyền, hạn mức, sao lưu dữ liệu">Bản quyền</a>
<span class="me srv"><button class="bell" id="bell" title="Thông báo" onclick="toggleNotes()">&#128276;<i id="bellN" class="hide"></i></button>
<span id="meName"></span><span class="vpname hide" id="vpName"></span> · <a onclick="openPw()">Đổi mật khẩu</a> · <a onclick="logout()">Đăng xuất</a></span></header>
<div class="sysbar hide" id="sysBar">
  <div class="sb-ann hide" id="sbAnn"></div><div class="sb-hint hide" id="sbHint"></div>
  <div class="hide" id="sbUpd" style="margin:4px 0"><button class="sm" onclick="doUpdate()">⬆ Cập nhật ngay</button></div>
  <div class="sb-portal hide" id="sbPortal"></div><div class="sb-goi hide" id="sbGoi"></div>
</div>
<div class="card hide" id="notes"><div class="top"><b>Thông báo</b><button class="sm sec" onclick="toggleNotes()">Đóng</button></div><div id="notesList"></div></div>
<main>
<section class="card hide" id="tabND">
  <div class="hint srv" id="goiInfo"></div>
  <div class="srv adm"><b>Máy cài phần mềm</b> <span class="mute">(bản cài trên máy: tải hoá đơn về máy của văn phòng, dùng tài khoản trong danh sách dưới đây để đăng nhập)</span>
    <div id="myDevs"></div></div>
  <div class="top"><b>Người dùng</b><button onclick="openUser()">+ Thêm người dùng</button></div>
  <div class="hint">Quản trị xem và làm việc với tất cả doanh nghiệp, quản lý người dùng. Nhân viên chỉ thấy các doanh nghiệp được giao.</div>
  <div class="tbl"><table><thead><tr><th>Tên đăng nhập</th><th>Họ tên</th><th>Vai trò</th><th class="n">Số DN được giao</th><th>Quyền</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead>
    <tbody id="uRows"></tbody></table></div>
  <div class="err" id="uErr"></div>
  <div class="own">
    <h3 style="margin:24px 0 6px">Văn phòng khách hàng <span class="mute" style="font-weight:400;font-size:13px">(chỉ chủ hệ thống thấy)</span></h3>
    <div class="hint">Mỗi văn phòng (công ty dịch vụ kế toán) có doanh nghiệp, người dùng, cài đặt và dữ liệu riêng, không thấy nhau.
      Chủ hệ thống tạo văn phòng kèm tài khoản quản trị đầu tiên; quản trị văn phòng tự thêm doanh nghiệp, nhân viên và phân quyền.
      Chủ hệ thống không xem được hoá đơn của văn phòng khác.</div>
    <div class="bar" style="margin:6px 0"><button onclick="openVp()">+ Thêm văn phòng</button></div>
    <div class="tbl"><table><thead><tr><th>Mã</th><th>Tên văn phòng</th><th>Quản trị</th><th class="n">Số DN</th><th class="n">Giới hạn DN</th>
      <th class="n">HĐ đã tải / gói</th><th class="n">HĐ gốc đã tải / gói</th>
      <th class="n">Người dùng</th><th class="n">Lượt chạy cùng lúc</th><th class="n">Máy cài / tối đa</th><th>Hết hạn</th><th>Trạng thái</th><th>Ngày tạo</th><th></th></tr></thead><tbody id="vpRows"></tbody></table></div>
    <div class="err" id="vpErr"></div>
    <div class="bar" style="margin:10px 0"><a class="sec" href="/chan-doan.zip" style="text-decoration:none">
      <button type="button" class="sm sec">Tải file chẩn đoán (trang lỗi của nhà cung cấp – gửi người hỗ trợ)</button></a></div>
    <h3 style="margin:24px 0 6px">Thông báo hệ thống</h3>
    <div class="hint">Hiện trên đầu trang của mọi văn phòng. Cảnh báo "trang thuế chậm / quá tải" phần mềm tự hiện khi phát hiện nhiều lần gọi trang thuế bị lỗi hoặc chậm trong 10 phút gần nhất.</div>
    <label>Thông báo (vd: Trang thuế đang bảo trì phần máy tính tiền…)</label><textarea id="syAnn" rows="2" class="full"></textarea>
    <label>Gợi ý đồng bộ (vd: Trang thuế thường chậm giờ hành chính, khoảng 17h–22h ổn định hơn)</label><textarea id="syHint" rows="2" class="full"></textarea>
    <div class="chk" style="align-items:center"><label><input type="checkbox" id="syMtt"> Tạm bỏ qua đồng bộ HĐ máy tính tiền (khi trang thuế treo phần này)</label>
      <button class="sm" onclick="saveSys()">Lưu thông báo</button><span class="ok" id="syMsg"></span></div>
    <div class="chk" style="align-items:center;flex-wrap:wrap" title="Bản cài trên máy khách tự kiểm tra khi kết nối máy chủ">
      <label>Bản exe mới nhất <input type="text" id="syVer" placeholder="trống = theo file exe tải lên" style="width:150px"></label>
      <label>Link tải <input type="text" id="syLink" placeholder="trống = trang /tai-ve của máy chủ này" style="width:320px"></label>
      <label>Phiên bản tối thiểu (cũ hơn thì không tải được HĐ mới) <input type="text" id="syMin" style="width:90px"></label></div>
    <div class="chk" style="align-items:center;flex-wrap:wrap">
      <span>File cài cho khách: <a href="/tai-ve" target="_blank">/tai-ve</a> <span class="mute" id="syExe"></span></span>
      <label>Phiên bản <input type="text" id="syExeVer" placeholder="vd 4.7.1" style="width:80px"></label>
      <button class="sm sec" onclick="$('syExeFile').click()">Tải lên TaiHoaDon.exe</button><input type="file" id="syExeFile" accept=".exe" class="hide">
      <span class="ok" id="syExeMsg"></span></div>
    <h3 style="margin:24px 0 6px">Gửi email</h3>
    <div class="hint">Máy chủ gửi mail (SMTP) dùng cho: quên mật khẩu, báo khi đổi mật khẩu, mời người dùng mới tự đặt mật khẩu.
      Gmail: máy chủ smtp.gmail.com, cổng 587 STARTTLS, tên đăng nhập là địa chỉ Gmail, mật khẩu là <b>mật khẩu ứng dụng</b> (App Password, cần bật xác minh 2 bước).</div>
    <div class="chk" style="align-items:center;flex-wrap:wrap">
      <label>Máy chủ <input type="text" id="mlHost" placeholder="smtp.gmail.com" style="width:180px"></label>
      <label>Cổng <input type="number" id="mlPort" style="width:80px"></label>
      <label>Bảo mật <select id="mlMode" class="sm" style="width:auto"><option value="starttls">STARTTLS (587)</option><option value="ssl">SSL (465)</option><option value="none">Không</option></select></label>
      <label>Tên đăng nhập <input type="text" id="mlUser" autocomplete="off" style="width:200px"></label>
      <label>Mật khẩu <input type="text" class="pw" id="mlPw" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true" style="width:160px"></label>
      <label>Gửi từ (email) <input type="text" id="mlFrom" style="width:200px"></label>
      <label>Tên người gửi <input type="text" id="mlName" style="width:160px" placeholder="Tải hoá đơn"></label>
      <label>Địa chỉ trang web (cho link trong mail) <input type="text" id="mlSite" style="width:240px" placeholder="https://taihoadon.tinhoc119.com"></label>
    </div>
    <div class="chk" style="align-items:center"><button class="sm" onclick="saveMail()">Lưu</button>
      <input type="text" id="mlTo" placeholder="email nhận thử" style="width:220px"><button class="sm sec" onclick="testMail()">Gửi thử</button>
      <span class="ok" id="mlMsg"></span><span class="err" id="mlErr"></span></div>
  </div>
  <h3 style="margin:24px 0 6px">Đồng bộ tự động hằng ngày</h3>
  <div class="hint">Máy chủ tự đồng bộ mua vào + bán ra cho tất cả doanh nghiệp đang hiển thị (có mật khẩu) theo giờ đã đặt, tự giải captcha.
    HĐ mới, HĐ đổi trạng thái (huỷ, thay thế, điều chỉnh) và lỗi được báo ở chuông thông báo cho người phụ trách doanh nghiệp.</div>
  <div class="chk" style="align-items:center">
    <label><input type="checkbox" id="aOn"> Bật</label>
    <label>Giờ chạy <input type="time" id="aGio" value="06:00" style="width:auto"></label>
    <label>Kỳ <select id="aKy" class="sm" style="width:auto"></select></label>
    <button class="sm" onclick="saveAuto()">Lưu</button><button class="sm sec" onclick="runAuto()">Chạy ngay</button><span class="ok" id="aMsg"></span>
  </div>
  <div class="mute" id="aInfo"></div><pre id="aLog" class="hide" style="max-height:180px"></pre>
</section>
<section class="card hide" id="tabTI">
  <div class="tools">
    <div class="tmenu">
      <a href="#" data-t="xml" class="on">Đọc hoá đơn XML</a><a href="#" data-t="mst">Kiểm tra MST DN</a>
      <a href="#" data-t="merge">Nối file PDF</a><a href="#" data-t="split">Tách file PDF</a>
      <a href="#" data-t="bank" class="q-saoke">Sao kê ngân hàng</a>
    </div>
    <div>
      <div class="tpane" id="t-xml"><h3>Đọc và xem hoá đơn XML</h3>
        Chọn file hoá đơn XML: <input type="file" id="xmlFile" accept=".xml,text/xml">
        <button class="sec sm hide" id="xmlPrint" onclick="$('xmlFrame').contentWindow.print()">In / Lưu PDF</button>
        <a class="lk hide" id="xmlDl" title="Tải file XML (nén .zip để trình duyệt không chặn)">Tải file XML</a>
        <a class="lk hide" id="xmlCqt" target="_blank" title="Bản thể hiện hoá đơn theo dữ liệu cổng thuế (hoadondientu.gdt.gov.vn)">PDF thuế</a>
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
      <div class="tpane hide" id="t-bank"><h3>Sao kê ngân hàng → file nhập phần mềm kế toán (KTSC)</h3>
        <div class="hint">Chọn một hoặc nhiều file sao kê / sổ phụ tải từ internet banking (.xls, .xlsx, .csv, hoặc sổ phụ .pdf có chữ – không nhận bản scan). Phần mềm tự tìm bảng giao dịch,
          đối chiếu với tổng ghi có / ghi nợ và số dư trên sao kê, bỏ giao dịch trùng giữa các tháng, rồi xuất Excel sheet KTSC
          theo mẫu Nibot để nhập Smart Pro. Tiền vào: Nợ TK ngân hàng; tiền ra: Có TK ngân hàng.</div>
        <input type="file" id="bankFiles" multiple accept=".xls,.xlsx,.csv,.htm,.html,.txt,.pdf" style="margin-top:8px">
        <div class="flt" style="margin-top:10px">
          <label>TK ngân hàng<input type="text" id="bkTk" value="1121"></label>
          <label>Mã đối tượng TK ngân hàng<input type="text" id="bkMa" placeholder="vd 112_VIETTIN"></label>
          <label>TK đối ứng tiền vào<input type="text" id="bkThu" placeholder="để trống như Nibot, vd 131"></label>
          <label>TK đối ứng tiền ra<input type="text" id="bkChi" placeholder="để trống như Nibot, vd 331"></label>
          <label>Mã nghiệp vụ<input type="text" id="bkNv" value="TIENHANG"></label>
          <label>Ghi chú<input type="text" id="bkGc" value="NIBOT IMPORT"></label>
        </div>
        <div class="err" id="bankErr"></div><div id="bankInfo"></div>
        <div class="bar"><button id="bankExp" class="hide" onclick="bankExport()">Xuất Excel KTSC</button>
          <span class="mute" id="bankSum"></span></div>
        <div class="tbl"><table id="bankTbl"></table></div></div>
    </div>
  </div>
</section>
<section class="hide" id="tabWS">
  <div class="card wshead">
    <div class="crumb"><a href="#/" onclick="tab('DN');return false">Doanh nghiệp</a> › <b id="wsCrumb"></b></div>
    <div class="wsrow">
      <button class="sec sm" id="wsPrev" title="Doanh nghiệp trước (Alt+←)" onclick="stepCompany(-1)">‹</button>
      <input type="search" id="wsPick" list="wsList" autocomplete="off" placeholder="Gõ tên hoặc MST để chuyển doanh nghiệp…">
      <datalist id="wsList"></datalist>
      <button class="sec sm" id="wsNext" title="Doanh nghiệp sau (Alt+→)" onclick="stepCompany(1)">›</button>
      <button class="sec sm q-dn" title="Sửa thông tin doanh nghiệp" onclick="openEdit(companies.find(c => c.mst === wsMst))">✎ Sửa</button>
      <span class="wsmeta" id="wsMeta"></span>
    </div>
    <input type="hidden" id="hMst">
    <nav class="subtabs" id="wsTabs">
      <a href="#" data-s="hoa-don">Hoá đơn</a><a href="#" data-s="dong-bo" class="q-dongbo">Đồng bộ</a>
      <a href="#" data-s="hang-hoa">Hàng hoá</a><a href="#" data-s="quy-doi">Quy đổi ĐVT</a><a href="#" data-s="doi-tuong">ĐT P.nhân</a>
      <a href="#" data-s="sao-ke" class="q-saoke">Sao kê ng.hàng</a><a href="#" data-s="tra-cuu-mst">Tra cứu MST</a>
    </nav>
  </div>

  <div class="card" id="wsHD">
    <div class="filters">
      <div class="seg" id="hKindSeg"><button data-k="purchase" class="on">Mua vào</button><button data-k="sold">Bán ra</button>
        <button data-k="purchase_dv" title="Hoá đơn mua vào đã đánh dấu dịch vụ">Mua vào – HĐDV</button><button data-k="sold_dv">Bán ra – HĐDV</button></div>
      <input type="hidden" id="hKind" value="purchase">
      <select id="hPer"></select><input type="date" id="hFrom" title="Từ ngày"><input type="date" id="hTo" title="Đến ngày">
      <input id="hQ" type="search" autocomplete="off" placeholder="Tìm MST, tên, mặt hàng, ghi chú…" class="grow">
      <button onclick="hPage=0;loadInv()">Tìm kiếm</button>
      <button class="sec" id="hMoreBtn" onclick="$('hMore').classList.toggle('hide')">Bộ lọc khác ▾</button>
    </div>
    <div class="filters hide" id="hMore">
      <select id="hFile"><option value="">Tất cả file</option><option value="no_xml">Không có XML</option><option value="xml">Có XML</option>
        <option value="pdf">Có PDF</option><option value="no_pdf">Không có PDF</option><option value="xml_pdf">Có XML và PDF</option></select>
      <select id="hDuyet"><option value="">Duyệt nội bộ: tất cả</option><option>Chờ duyệt</option><option>Đã duyệt</option><option>Không duyệt</option></select>
      <select id="hTthai"><option value="">Trạng thái HĐ: tất cả</option></select>
      <select id="hKq"><option value="">Kết quả kiểm tra: tất cả</option></select>
      <input id="hKh" autocomplete="off" placeholder="Ký hiệu HĐ"><input id="hSo" autocomplete="off" placeholder="Số HĐ">
    </div>
    <div class="bar actions">
      <button class="sec" onclick="openSub('dong-bo')">⟳ Đồng bộ</button>
      <button class="sec q-hdgoc" id="hBulkPdf" onclick="bulkPdf()">Tải HĐ gốc hàng loạt</button>
      <span style="flex:1"></span>
      <button class="sec sm" onclick="resetGrid()" title="Bỏ sắp xếp và lọc theo cột">Bỏ lọc cột</button>
      <select class="sm q-ketxuat" id="hExp" style="width:auto" onchange="exportInv(this.value);this.value=''">
        <option value="">Kết xuất…</option><option value="xlsx">Excel (mẫu Nibot)</option><option value="xml">XML.ZIP</option>
        <option value="all">Tất cả (XML + PDF + Excel).ZIP</option><option value="html">HTML.ZIP</option>
        <option value="pdf">Hoá đơn gốc (PDF + XML).ZIP</option><option value="cqt">PDF thuế.ZIP</option></select>
    </div>
    <div class="bulk hide" id="hBulk">
      <b id="hSelInfo"></b>
      <select class="sm q-sua" id="hBulkDuyet" onchange="bulkUpdate({duyet: this.value});this.value=''">
        <option value="">Duyệt nội bộ…</option><option>Đã duyệt</option><option>Chờ duyệt</option><option>Không duyệt</option></select>
      <button class="sm sec q-sua" onclick="bulkUpdate({dv: true})">Đánh dấu HĐ dịch vụ</button>
      <button class="sm sec q-sua" onclick="bulkUpdate({dv: false})">Bỏ HĐ dịch vụ</button>
      <button class="sm sec q-hdgoc" onclick="bulkPdf()">Tải HĐ gốc</button>
      <button class="sm sec q-ketxuat" onclick="exportInv('xlsx')">Kết xuất Excel</button>
      <button class="sm red" onclick="hSel.clear();renderInv()">Bỏ chọn</button>
    </div>
    <div class="err" id="hErr"></div>
    <div class="grid"><table id="hTbl"><thead id="hHead"></thead><tbody id="hRows"></tbody><tfoot><tr id="hFoot"></tr></tfoot></table></div>
    <div class="pager" id="hPager"></div>
  </div>

  <div class="card hide" id="wsSYNC">
  <div class="sync">
    <div>
      <label>Chọn khoảng thời gian</label><div style="display:flex;gap:8px"><select id="sPer"></select>
        <input type="number" id="sYear" style="width:90px"></div>
      <label>Từ ngày</label><input type="date" id="sFrom"><label>Đến ngày</label><input type="date" id="sTo">
      <div class="chk"><label><input type="checkbox" id="sMtt" checked> Gồm máy tính tiền</label>
        <label><input type="checkbox" id="sXml" checked> Tải XML</label>
        <label title="Tự tải PDF và XML gốc của người bán (như nút Tải hoá đơn dạng PDF / XML trên trang tra cứu) khi nhà cung cấp cho phép (MISA, EasyInvoice, TS24, BKAV)"><input type="checkbox" id="sPdf" checked> Tải HĐ gốc (PDF + XML)</label></div>
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
</div>

  <div class="card hide" id="wsTOOL"></div>

  <div class="card hide" id="wsDT">
    <div class="filters">
      <select id="dLoai"><option value="">--Loại HĐ--</option><option value="V">V – mua vào (người bán)</option><option value="R">R – bán ra (người mua)</option></select>
      <select id="dPL"><option value="">--Tất cả ĐTPN--</option><option value="no">Chưa có mã ĐTPN</option><option value="yes">Đã có mã ĐTPN</option></select>
      <input id="dQ" type="search" class="grow" autocomplete="off" placeholder="nhập từ khoá cần tìm: tên, MST, mã ĐTPN">
    </div>
    <div class="bar q-sua">
      <button class="sec" onclick="dSyncBox()">⟳ Đồng bộ ĐTPN</button>
      <button class="sec" onclick="dAssign()">THAY THẾ HÀNG LOẠT</button>
      <button class="sec" onclick="dAutoBox()">TẠO MÃ ĐTPN TỰ ĐỘNG</button>
      <button class="sec" onclick="dAdd()">+ Thêm ĐTPN</button>
      <button class="sec" onclick="dRemarkBox()" title="Đánh dấu HĐ dịch vụ / duyệt nội bộ cho hoá đơn theo thông tin ĐTPN">Đánh dấu lại hoá đơn hàng loạt</button>
      <button class="red" onclick="dDelBox()">Xoá ĐTPN</button>
      <span style="flex:1"></span>
      <button class="sec" onclick="$('dFile').click()">Import Excel</button><input type="file" id="dFile" accept=".xlsx" class="hide">
      <button class="sec q-ketxuat" onclick="lpExport(DT)">Export Excel</button>
      <button id="dSave" class="hide" onclick="lpSave(DT)">Lưu thay đổi</button>
    </div>
    <div id="dBox" class="card hide" style="margin:8px 0;border-color:var(--acc)"></div>
    <div class="err" id="dErr"></div>
    <div class="grid"><table id="dTbl"><thead id="dHead"></thead><tbody id="dRows"></tbody></table></div>
    <div class="bar" id="dPager"></div>
    <div class="hint">Dùng khi kết xuất Excel (mẫu Nibot): Mã ĐTPN → MADTPNCO / MAKH của Smart_KTSC; Mã TK TM/CK → TKCO (thay cho 331 / 1111);
      Mã TK CP/DT → TKNO khi mặt hàng chưa có Mã TK; Loại HHDV SXKD 2 / 3 → mục 2 / 3 của bảng kê mua vào.</div>
  </div>

  <div class="card hide" id="wsQD">
    <div class="filters">
      <input id="qQ" type="search" class="grow" autocomplete="off" placeholder="nhập thông tin cần tìm (mã hàng, tên hàng, ĐVT)">
    </div>
    <div class="bar q-sua">
      <button class="sec" onclick="qAdd()">+ Thêm quy đổi ĐVT</button>
      <button class="red" onclick="qDel()">Xoá dòng đã chọn / tất cả</button>
      <span style="flex:1"></span>
      <button class="sec" onclick="$('qFile').click()">Import Excel</button><input type="file" id="qFile" accept=".xlsx" class="hide">
      <button class="sec q-ketxuat" onclick="lpExport(QD)">Export Excel</button>
      <button id="qSave" class="hide" onclick="lpSave(QD)">Lưu thay đổi</button>
    </div>
    <div id="qBox" class="card hide" style="margin:8px 0;border-color:var(--acc)"></div>
    <div class="err" id="qErr"></div>
    <div class="grid"><table id="qTbl"><thead id="qHead"></thead><tbody id="qRows"></tbody></table></div>
    <div class="bar" id="qPager"></div>
    <div class="hint">Ví dụ: ĐVT gốc "Thùng", ĐVT đích "Chai", số lượng quy đổi 24 → 1 thùng = 24 chai. Khi kết xuất Smart_KTSC:
      DONVI = ĐVT đích, LUONG = số lượng × 24, đơn giá = đơn giá ÷ 24 (thành tiền giữ nguyên). Khớp theo mã hàng (tab Hàng hoá) hoặc tên hàng, và ĐVT trên hoá đơn.</div>
  </div>

  <div class="card hide" id="wsHH">
    <div class="filters">
      <select id="gLoai" title="Loại HĐ"><option value="">--Loại HĐ--</option><option>V</option><option>R</option><option>VR</option></select>
      <select id="gPL"><option value="">--Tất cả hàng hoá--</option><option value="no">Chưa có mã hàng</option><option value="yes">Đã có mã hàng</option></select>
      <input id="gQ" type="search" class="grow" autocomplete="off" placeholder="nhập thông tin hàng hoá cần tìm (mã TK, mã hàng, tên hàng, ghi chú)">
      <label class="nw" title="Hiển thị đơn vị tính của hàng hoá trong hoá đơn đã tải"><input type="checkbox" id="gDvt"> Hiện ĐVT</label>
      <label class="nw" title="Gom các mặt hàng giống nhau vào 1 nhóm – giúp gán mã tiện hơn"><input type="checkbox" id="gNhom"> Gom nhóm giống nhau</label>
      <input type="number" id="gSim" value="80" min="30" max="100" style="width:64px" title="Độ tương đồng (%) – mặc định 80">
    </div>
    <div class="bar q-sua">
      <button class="sec" onclick="gSyncBox()">⟳ Đồng bộ hàng hoá</button>
      <button class="sec" onclick="gAssign('ma_hang')">GÁN MÃ HÀNG</button>
      <button class="sec" onclick="gAssign('ma_tk')">GÁN MÃ TK</button>
      <button class="sec" onclick="gAutoBox()">TẠO MÃ HÀNG TỰ ĐỘNG</button>
      <button class="sec" onclick="gAdd()">+ Thêm hàng hoá</button>
      <button class="sec" onclick="gUnit()" title="Thêm quy đổi đơn vị tính cho mặt hàng đang chọn">Thêm ĐVT</button>
      <button class="red" onclick="gDelBox()">Xoá hàng hoá</button>
      <span style="flex:1"></span>
      <button class="sec" onclick="$('gFile').click()">Import Excel</button><input type="file" id="gFile" accept=".xlsx" class="hide">
      <button class="sec q-ketxuat" onclick="gExport()">Export Excel</button>
      <button id="gSave" class="hide" onclick="gSave()">Lưu thay đổi</button>
    </div>
    <div id="gBox" class="card hide" style="margin:8px 0;border-color:var(--acc)"></div>
    <div class="err" id="gErr"></div>
    <div class="grid"><table id="gTbl"><thead><tr><th><input type="checkbox" id="gAll" title="Chọn tất cả dòng đang lọc"></th><th>Mã TK</th><th>Mã hàng</th>
      <th>Tên hàng</th><th class="g-dvt hide">ĐVT</th><th>Loại HĐ</th><th>Ghi chú</th></tr></thead><tbody id="gRows"></tbody></table></div>
    <div class="bar" id="gPager"></div>
    <div class="hint">Mã TK và Mã hàng gán ở đây được đưa vào cột TKNO / MADMNO của sheet Smart_KTSC khi kết xuất Excel (mẫu Nibot) – khớp theo tên hàng.
      Loại HĐ: V = mua vào, R = bán ra, để trống / VR = dùng cho cả hai.</div>
  </div>
</section>
<section class="card" id="tabDN">
  <div class="top">
    <div class="stat">Số doanh nghiệp: <b id="nDN">0</b> hiển thị; <b id="nAn">0</b> bị ẩn
      <a href="#" id="toggleAn" class="mute">xem doanh nghiệp bị ẩn</a></div>
    <input class="search" id="q" type="search" name="tim-doanh-nghiep" autocomplete="off" placeholder="nhập MST hoặc tên doanh nghiệp cần làm việc nhanh">
  </div>
  <div class="bar">
    <button class="q-dn" onclick="openEdit()">+ Thêm doanh nghiệp</button>
    <button class="sec adm" onclick="show('mImport')">Nhập danh sách từ Excel</button>
    <button class="red q-dongbo" onclick="openBatch()">Xử lý hàng loạt</button>
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
  <label>Mật khẩu trang hoadondientu.gdt.gov.vn</label><input type="text" class="full pw" id="ePw" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true">
  <div class="hint" id="ePwHint"></div>
  <div class="chk">
    <label><input type="checkbox" id="eVao"> Đồng bộ hoá đơn đầu vào</label>
    <label><input type="checkbox" id="eRa"> Đồng bộ hoá đơn đầu ra</label>
    <label><input type="checkbox" id="eAn"> Ẩn doanh nghiệp</label>
  </div>
  <div class="err" id="eErr"></div>
  <div class="bar"><button onclick="saveEdit()">Lưu</button><button class="sec" onclick="saveEdit(true)">Lưu &amp; Test đăng nhập</button>
    <span style="flex:1"></span><button class="red adm" id="eDel" onclick="delEdit()">Xoá</button><button class="sec" onclick="hide('mEdit')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mUser"><div class="card">
  <b id="uTitle">Người dùng</b>
  <label>Tên đăng nhập</label><input type="text" class="full" id="uName" autocomplete="off">
  <label>Họ tên</label><input type="text" class="full" id="uTen">
  <label>Vai trò</label><select id="uRole" class="sm"><option value="staff">Nhân viên – chỉ các DN được giao</option><option value="admin">Quản trị – tất cả DN, quản lý người dùng</option></select>
  <label>Email (nhận link đặt mật khẩu, báo khi đổi mật khẩu)</label><input type="text" class="full" id="uEmail" autocomplete="off" placeholder="ten@congty.vn">
  <label>Mật khẩu đăng nhập phần mềm</label><input type="text" class="full pw" id="uPw" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true">
  <div class="hint" id="uPwHint"></div>
  <div class="hide" id="uLinkBox"><button class="sm sec" type="button" onclick="sendUserLink()">Gửi link đặt lại mật khẩu qua email</button> <span class="ok" id="uLinkMsg"></span></div>
  <div class="chk"><label><input type="checkbox" id="uActive" checked> Đang hoạt động (bỏ tích để khoá tài khoản)</label></div>
  <div class="chk own" id="uOwnerBox"><label title="Quản lý các văn phòng khách hàng (không xem được hoá đơn của văn phòng khác)">
    <input type="checkbox" id="uOwner"> Chủ hệ thống – quản lý văn phòng khách hàng</label></div>
  <div id="uPermBox"><label>Quyền theo từng mục <span class="mute">(xem hoá đơn của DN được giao luôn có)</span></label>
    <div class="perms" id="uPerms"></div></div>
  <div id="uMstBox"><label>Doanh nghiệp được giao (<span id="uCnt">0</span>)</label>
    <div style="display:flex;gap:6px;margin-bottom:6px"><input type="search" id="uQ" autocomplete="off" placeholder="lọc MST / tên" style="flex:1">
      <button class="sm sec" type="button" onclick="uPickAll(true)">Chọn hết đang lọc</button><button class="sm sec" type="button" onclick="uPickAll(false)">Bỏ chọn</button></div>
    <div class="ulist" id="uList"></div></div>
  <div class="err" id="uEditErr"></div>
  <div class="bar"><button onclick="saveUser()">Lưu</button><span style="flex:1"></span>
    <button class="red" id="uDel" onclick="delUser()">Xoá</button><button class="sec" onclick="hide('mUser')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mLic"><div class="card">
  <b>Bản quyền phần mềm</b>
  <div class="hint">Phần mềm cài trên máy này tự tải hoá đơn về ổ cứng của máy; máy chủ chỉ cấp tài khoản và tính hạn mức
    (chỉ nhận mã băm của hoá đơn, không nhận nội dung hoá đơn hay mật khẩu cổng thuế). Không có mạng vẫn xem / kết xuất dữ liệu đã tải được.</div>
  <div id="licInfo" style="margin:8px 0"></div>
  <div id="licForm">
    <label>Máy chủ</label><input type="text" class="full" id="licServer" autocomplete="off">
    <label>Tên đăng nhập (nhà cung cấp cấp)</label><input type="text" class="full" id="licUser" autocomplete="username">
    <label>Mật khẩu</label><input type="password" class="full" id="licPw" autocomplete="current-password">
    <div class="bar"><button onclick="licLogin()">Đăng nhập / kích hoạt máy này</button></div>
  </div>
  <div class="err" id="licErr"></div>
  <h4 style="margin:14px 0 4px">Sao lưu dữ liệu</h4>
  <div class="hint">Dữ liệu hoá đơn nằm trên máy này – nên sao lưu định kỳ. Chọn thư mục Google Drive / OneDrive trên máy để có bản sao ngoài máy.</div>
  <div style="display:flex;gap:6px"><input type="text" id="licBkDir" class="grow" placeholder="(trống = Documents\TaiHoaDon-SaoLuu)">
    <button class="sec" onclick="licBackup()">Sao lưu ngay</button></div>
  <div class="ok" id="licBkMsg"></div>
  <div class="bar"><span style="flex:1"></span><button class="red hide" id="licOut" onclick="licLogout()">Đăng xuất bản quyền</button>
    <button class="sec hide" id="licChk" onclick="licRefresh()">Kiểm tra lại</button><button class="sec" onclick="hide('mLic')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mVp"><div class="card">
  <b id="vpTitle">Văn phòng</b>
  <label>Mã văn phòng (không đổi được, vd: ketoan-abc)</label><input type="text" class="full" id="vpId" autocomplete="off">
  <label>Tên văn phòng / công ty</label><input type="text" class="full" id="vpTen">
  <div class="chk"><label>Giới hạn số DN (0 = không giới hạn) <input type="number" id="vpMaxDn" min="0" style="width:100px"></label>
    <label>Lượt đồng bộ chạy cùng lúc (0 = theo máy chủ) <input type="number" id="vpMaxJobs" min="0" style="width:80px"></label></div>
  <label>Gói theo số hoá đơn (0 = không giới hạn)</label>
  <div class="chk" style="align-items:center"><label>Tổng số HĐ được tải <input type="number" id="vpHd" min="0" style="width:120px"></label>
    <span class="mute" id="vpHdUsed"></span><label class="hide" id="vpHdResetL"><input type="checkbox" id="vpHdReset"> Đặt lại số đã dùng về 0 (gia hạn gói)</label></div>
  <div class="chk" style="align-items:center"><label><input type="checkbox" id="vpGoc" checked> Gói gồm tự tải hoá đơn gốc (PDF/XML của người bán)</label>
    <label>Hạn mức HĐ gốc riêng <input type="number" id="vpHdGoc" min="0" style="width:110px"></label>
    <span class="mute" id="vpGocUsed"></span><label class="hide" id="vpGocResetL"><input type="checkbox" id="vpGocReset"> Đặt lại về 0</label></div>
  <label>Bản cài trên máy khách (exe)</label>
  <div class="chk" style="align-items:center"><label>Số máy được cài (0 = không giới hạn) <input type="number" id="vpMay" min="0" style="width:80px"></label>
    <label>Hết hạn gói <input type="date" id="vpHetHan"></label><span class="mute">(trống = không hạn)</span></div>
  <div id="vpDevBox" class="hide"><div class="mute">Máy đã kích hoạt:</div><div id="vpDevs"></div></div>
  <label>Ghi chú (gói, liên hệ…)</label><input type="text" class="full" id="vpNote">
  <div id="vpNew"><label>Tài khoản quản trị đầu tiên của văn phòng</label>
    <div style="display:flex;gap:6px;flex-wrap:wrap"><input type="text" id="vpAdm" placeholder="tên đăng nhập" autocomplete="off" style="flex:1">
      <input type="text" id="vpAdmTen" placeholder="họ tên" style="flex:1">
      <input type="text" class="pw" id="vpAdmPw" placeholder="mật khẩu (≥ 8 ký tự)" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true" style="flex:1"></div>
    <input type="text" class="full" id="vpAdmEmail" placeholder="email quản trị (để trống mật khẩu: gửi lời mời tự đặt mật khẩu qua email)" autocomplete="off" style="margin-top:6px"></div>
  <div class="chk" id="vpActBox"><label><input type="checkbox" id="vpActive" checked> Đang hoạt động (bỏ tích để khoá: mọi người dùng của văn phòng bị đăng xuất)</label></div>
  <div class="err" id="vpEditErr"></div>
  <div class="bar"><button onclick="saveVp()">Lưu</button><span style="flex:1"></span><button class="sec" onclick="hide('mVp')">Đóng</button></div>
</div></div>

<div class="modal hide" id="mPw"><div class="card">
  <b>Đổi mật khẩu đăng nhập</b>
  <label>Mật khẩu hiện tại</label><input type="text" class="full pw" id="pOld" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true">
  <label>Mật khẩu mới (tối thiểu 8 ký tự)</label><input type="text" class="full pw" id="pNew" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true">
  <label>Nhập lại mật khẩu mới</label><input type="text" class="full pw" id="pNew2" autocomplete="off" autocapitalize="off" spellcheck="false" data-lpignore="true">
  <div class="err" id="pErr"></div>
  <div class="bar"><button onclick="savePw()">Đổi mật khẩu</button><button class="sec" onclick="hide('mPw')">Đóng</button></div>
  <label style="margin-top:14px">Email của tôi (nhận link khi quên mật khẩu, báo khi mật khẩu bị đổi)</label>
  <div style="display:flex;gap:6px"><input type="text" id="pEmail" autocomplete="off" style="flex:1" placeholder="ten@congty.vn">
    <button class="sm sec" onclick="saveMyEmail()">Lưu email</button></div><span class="ok" id="pMsg"></span>
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
    <label title="MISA, EasyInvoice, TS24, BKAV"><input type="checkbox" id="bPdf" checked> Tải HĐ gốc (PDF + XML)</label>
  </div>
  <div class="hint">Chỉ đồng bộ chiều đầu vào/đầu ra đang bật trong cài đặt từng doanh nghiệp.</div>
  <div class="err" id="bErr"></div>
  <div class="bar"><button onclick="runBatch()">Bắt đầu</button><button class="sec" onclick="hide('mBatch')">Đóng</button></div>
</div></div>

<script>
const KEY = "__TOKEN__";
const $ = id => document.getElementById(id);
// Ô mật khẩu trong trang làm việc là ô chữ che bằng CSS: Chrome không coi trang là form đăng nhập nên không tự điền
// tên/mật khẩu đăng nhập web vào ô tìm kiếm và ô mật khẩu cổng thuế. Trình duyệt không hỗ trợ che → dùng ô mật khẩu.
if (!(window.CSS && (CSS.supports('-webkit-text-security', 'disc') || CSS.supports('text-security', 'disc'))))
  document.querySelectorAll('input.pw').forEach(i => { i.type = 'password'; i.autocomplete = 'new-password'; });
const show = id => $(id).classList.remove('hide'), hide = id => $(id).classList.add('hide');
const fmt = n => (n || 0).toLocaleString('en-US');
let me = {role: 'admin', server: false}, companies = [], showHidden = false, editing = null, batchMsts = [], timer = null;

async function api(path, body) {
  const r = await fetch(path, {method: body ? 'POST' : 'GET', headers: {'X-App-Key': KEY, 'Content-Type': 'application/json'},
                               body: body ? JSON.stringify(body) : undefined});
  const d = await r.json().catch(() => ({error: 'Phản hồi không hợp lệ'}));
  if (r.status === 401 && d.login) { location.reload(); throw new Error(d.error); }
  if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
  return d;
}
const post = (p, b) => api(p, b);
const PERM_NAMES = {dongbo: 'Đồng bộ / tải hoá đơn', hdgoc: 'Tải và gắn hoá đơn gốc', sua: 'Ghi chú, duyệt nội bộ, HĐ dịch vụ, hàng hoá',
                    ketxuat: 'Kết xuất Excel / ZIP', saoke: 'Sao kê ngân hàng', dn: 'Thêm / sửa doanh nghiệp, mật khẩu cổng thuế'};
const can = p => !me || !me.quyen || me.quyen.includes(p);
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
    const td = tr.insertCell(); const dn = el('a', 'dn dn-link', String(i + 1).padStart(3, '0') + '. ' + (c.ten || '(chưa đặt tên)') + ' ');
    dn.href = '#/dn/' + c.mst + '/hoa-don'; dn.title = 'Mở trang làm việc của doanh nghiệp';
    dn.onclick = ev => { ev.preventDefault(); openCompany(c.mst); };
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
    const b1 = el('button', 'sm q-dongbo', 'Đồng bộ'); b1.onclick = () => openCompany(c.mst, 'dong-bo');
    const b2 = el('button', 'sm sec q-dn', 'Sửa'); b2.onclick = () => openEdit(c); b2.style.marginLeft = '4px';
    const b3 = el('button', 'sm sec', 'Xem HĐ'); b3.onclick = () => openCompany(c.mst); b3.style.marginLeft = '4px';
    act.append(b1, b2, b3);
  });
}
async function refresh() {
  const s = await api('/api/state'); companies = s.companies; me = s.me; window.APPV = s.version;
  document.body.classList.toggle('staff', me.role !== 'admin'); document.body.classList.toggle('local', !me.server);
  document.body.classList.toggle('owner', !!me.owner);
  Object.keys(PERM_NAMES).forEach(p => document.body.classList.toggle('no-' + p, !(me.quyen || []).includes(p)));
  $('meName').textContent = me.ten ? (me.ten + ' (' + me.username + ')') : me.username;
  renderSys(s.sys || (me.lic ? {thong_bao: (me.lic.info || {}).thong_bao || ''} : null));
  if (me.lic) { show('licLink'); $('licLink').classList.toggle('warn', !me.lic.logged);
    $('licLink').textContent = me.lic.logged ? 'Bản quyền: ' + (me.lic.info.vp_ten || me.lic.username) : '⚠ Chưa đăng nhập bản quyền';
    if (!me.lic.logged && !licAsked) { licAsked = true; openLic(); } }
  if (me.goi && !me.goi.tai_goc) ['sPdf', 'bPdf'].forEach(id => { $(id).checked = false; $(id).disabled = true; $(id).parentNode.title = 'Gói của văn phòng không gồm tự tải hoá đơn gốc'; });
  $('vpName').textContent = me.server && me.vp_ten ? me.vp_ten : ''; $('vpName').classList.toggle('hide', !(me.server && me.vp_ten));
  $('bellN').textContent = s.unread; $('bellN').classList.toggle('hide', !s.unread);
  if (s.auto) renderAuto(s.auto);
  render(); if (curTab === 'WS') { fillMst(); renderWsHead(); }
  $('capStat').textContent = (s.captcha.count ? ('Captcha đã học ' + s.captcha.chars.length + ' ký tự · ') : '') + 'phiên bản ' + s.version;
  if (s.job.running || s.job.log.length) renderJob(s.job);
  return s;
}
function openEdit(c) {
  editing = c || null; $('eErr').textContent = '';
  $('editTitle').textContent = c ? 'Cập nhật thông tin' : 'Thêm doanh nghiệp';
  $('eMst').value = c ? c.mst : ''; $('eMst').disabled = !!c; $('eTen').value = c ? c.ten : ''; $('ePw').value = '';
  $('eInfo').textContent = ''; $('eLookup').classList.toggle('hide', !!c);
  $('ePwHint').textContent = c && c.co_mk ? 'Đã lưu mật khẩu – để trống nếu không đổi. Mật khẩu này khác mật khẩu đăng nhập phần mềm.' : 'Mật khẩu được mã hoá khi lưu.';
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
  try { await start({msts, kinds, from: $('bFrom').value, to: $('bTo').value, mtt: $('bMtt').checked, xml: $('bXml').checked,
                     pdf: $('bPdf').checked && $('bXml').checked});
        hide('mBatch'); } catch (e) { $('bErr').textContent = e.message; }
}
async function start(body) { await post('/api/run', body); show('jobCard'); poll(); }
async function sendCap(id) { id = id || 'capIn'; const v = $(id).value.trim(); if (!v) return; $(id).value = ''; hide('capBox'); hide('sCapBox');
  await post('/api/captcha', {answer: v}); poll(); }
let lastCap = null;
function renderJob(j) {
  if (curTab === 'WS' && wsSub === 'dong-bo') { hide('jobCard'); renderSync(j); return; }
  if (!j.running) { hide('jobCard'); return; }  // chỉ hiện khi đang tải; kết quả xem ở tab Đồng bộ của từng DN
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
  if (wasRunning && !s.job.running && curTab === 'WS') { loadInv(); }
  wasRunning = s.job.running;
  if (s.job.running || (s.auto && s.auto.running && curTab === 'ND')) timer = setTimeout(poll, 1500);
  else if (me.server) timer = setTimeout(poll, 60000);  // cập nhật chuông thông báo
}

// ---- Bản web: thông báo, tài khoản, người dùng, đồng bộ tự động ----
async function toggleNotes() {
  if (!$('notes').classList.contains('hide')) { hide('notes'); return; }
  const d = await post('/api/notices', {}); const box = $('notesList'); box.innerHTML = '';
  if (!d.items.length) box.append(el('div', 'mute', 'Chưa có thông báo.'));
  const unread = d.unread;
  d.items.forEach((n, i) => { const it = el('div', 'it' + (i < unread ? ' new' : ''));
    it.append(el('div', 'mute', n.t)); it.append(el('div', n.level === 'warn' ? 'warn' : n.level === 'err' ? 'err2' : '', n.text));
    if (n.mst && companies.some(c => c.mst === n.mst)) { const a = el('a', 'lk', 'Xem hoá đơn'); a.href = '#';
      a.onclick = ev => { ev.preventDefault(); hide('notes'); openCompany(n.mst); }; it.append(a); }
    box.append(it); });
  show('notes'); if (unread) { await post('/api/notices', {read: true}); $('bellN').classList.add('hide'); }
}
function openPw() { ['pOld', 'pNew', 'pNew2'].forEach(i => $(i).value = ''); $('pErr').textContent = ''; $('pMsg').textContent = '';
  $('pEmail').value = me.email || ''; show('mPw'); $('pOld').focus(); }
async function saveMyEmail() {
  try { await post('/api/me/email', {email: $('pEmail').value.trim()}); me.email = $('pEmail').value.trim(); $('pMsg').textContent = 'Đã lưu email.'; }
  catch (e) { $('pErr').textContent = e.message; }
}
async function savePw() {
  if ($('pNew').value !== $('pNew2').value) { $('pErr').textContent = 'Hai lần nhập mật khẩu mới không khớp'; return; }
  try { await post('/api/me/password', {old: $('pOld').value, new: $('pNew').value}); hide('mPw'); alert('Đã đổi mật khẩu.'); }
  catch (e) { $('pErr').textContent = e.message; }
}
async function logout() { try { await post('/api/logout', {}); } catch (e) {} location.reload(); }
let users = [], allCos = [], editingUser = null, uPicked = new Set();
async function loadUsers() {
  $('uErr').textContent = '';
  try { const d = await post('/api/users', {}); users = d.users; allCos = d.companies; } catch (e) { $('uErr').textContent = e.message; return; }
  if (me.owner) { loadVps(); loadMail(); loadSys(); loadExe(); }
  if (me.server && me.role === 'admin') renderDevs('myDevs');
  renderGoi();
  const tb = $('uRows'); tb.innerHTML = '';
  users.forEach(u => { const tr = tb.insertRow();
    const c0 = tr.insertCell(); c0.textContent = u.username;
    if (u.email) { c0.append(el('br'), el('span', 'mute', u.email)); }
    if ((u.pw_set === false)) c0.append(el('br'), el('span', 'err', 'chưa đặt mật khẩu'));
    tr.insertCell().textContent = u.ten || '';
    tr.insertCell().textContent = u.role === 'admin' ? 'Quản trị' : 'Nhân viên';
    const n = tr.insertCell(); n.className = 'n'; n.textContent = u.role === 'admin' ? 'tất cả' : (u.msts || []).length;
    const q = tr.insertCell(); q.className = 'mute'; q.style.maxWidth = '280px';
    q.textContent = u.role === 'admin' ? 'tất cả' + (u.owner ? ' · chủ hệ thống' : '')
      : ((u.quyen || []).map(p => PERM_NAMES[p]).join('; ') || 'chỉ xem hoá đơn');
    const st = tr.insertCell(); st.textContent = u.active === false ? 'Đã khoá' : 'Hoạt động'; st.className = u.active === false ? 'err' : 'ok';
    tr.insertCell().textContent = u.tao || '';
    const b = el('button', 'sm sec', 'Sửa'); b.onclick = () => openUser(u); tr.insertCell().append(b); });
}
function renderUList() {
  const q = $('uQ').value.trim().toLowerCase(), box = $('uList'); box.innerHTML = '';
  allCos.filter(c => !q || c.mst.includes(q) || (c.ten || '').toLowerCase().includes(q)).forEach(c => {
    const l = el('label'), cb = el('input'); cb.type = 'checkbox'; cb.checked = uPicked.has(c.mst);
    cb.onchange = () => { cb.checked ? uPicked.add(c.mst) : uPicked.delete(c.mst); $('uCnt').textContent = uPicked.size; };
    l.append(cb, document.createTextNode((c.ten || '(chưa đặt tên)') + ' (' + c.mst + ')')); box.append(l); });
  $('uCnt').textContent = uPicked.size;
}
function uPickAll(on) {
  const q = $('uQ').value.trim().toLowerCase();
  allCos.filter(c => !q || c.mst.includes(q) || (c.ten || '').toLowerCase().includes(q)).forEach(c => on ? uPicked.add(c.mst) : uPicked.delete(c.mst));
  renderUList();
}
function openUser(u) {
  editingUser = u || null; $('uEditErr').textContent = '';
  $('uTitle').textContent = u ? 'Sửa người dùng' : 'Thêm người dùng';
  $('uName').value = u ? u.username : ''; $('uName').disabled = !!u; $('uTen').value = u ? (u.ten || '') : '';
  $('uRole').value = u ? u.role : 'staff'; $('uActive').checked = !u || u.active !== false; $('uPw').value = '';
  $('uPwHint').textContent = u ? 'Để trống nếu không đổi. Đặt mật khẩu mới sẽ đăng xuất người này khỏi các máy khác (có email thì được báo qua mail).'
    : 'Tối thiểu 8 ký tự – gửi riêng cho nhân viên. Hoặc để trống và nhập email: phần mềm gửi lời mời để nhân viên tự đặt mật khẩu.';
  $('uEmail').value = u ? (u.email || '') : ''; $('uLinkBox').classList.toggle('hide', !(u && u.email)); $('uLinkMsg').textContent = '';
  uPicked = new Set(u ? (u.msts || []) : []); $('uQ').value = ''; renderUList();
  const has = new Set(u ? (u.quyen || []) : ['dongbo', 'hdgoc', 'sua', 'ketxuat', 'saoke']), box = $('uPerms'); box.innerHTML = '';
  Object.entries(PERM_NAMES).forEach(([k, t]) => { const l = el('label'), cb = el('input'); cb.type = 'checkbox'; cb.value = k; cb.checked = has.has(k);
    l.append(cb, document.createTextNode(' ' + t)); box.append(l); });
  $('uOwner').checked = !!(u && u.owner);
  $('uDel').classList.toggle('hide', !u || u.username === me.username); uRoleChanged(); show('mUser');
}
function uRoleChanged() { const adm = $('uRole').value === 'admin';
  $('uMstBox').classList.toggle('hide', adm); $('uPermBox').classList.toggle('hide', adm); $('uOwnerBox').classList.toggle('hide', !adm); }
$('uRole').onchange = uRoleChanged; $('uQ').oninput = renderUList;
async function saveUser() {
  const body = {username: $('uName').value.trim(), ten: $('uTen').value, role: $('uRole').value,
          password: $('uPw').value, active: $('uActive').checked, msts: [...uPicked], create: !editingUser,
          quyen: [...$('uPerms').querySelectorAll('input:checked')].map(x => x.value), email: $('uEmail').value.trim()};
  if (me.owner) body.owner = $('uRole').value === 'admin' && $('uOwner').checked;
  try { await post('/api/user/save', body);
        hide('mUser'); loadUsers(); }
  catch (e) { $('uEditErr').textContent = e.message; }
}
async function sendUserLink() {
  try { await post('/api/user/save', {username: editingUser.username, send_link: true}); $('uLinkMsg').textContent = 'Đã gửi link tới ' + editingUser.email; }
  catch (e) { $('uEditErr').textContent = e.message; }
}
// ---- Chủ hệ thống: văn phòng khách hàng ----
let vps = [], editingVp = null;
async function loadSys() {
  try { const s = await api('/api/state'); const y = s.sys || {};
    $('syAnn').value = y.thong_bao || ''; $('syHint').value = y.goi_y || ''; $('syMtt').checked = !!y.bo_qua_mtt;
    $('syVer').value = y.phien_ban_moi || ''; $('syLink').value = y.link_tai || ''; $('syMin').value = y.phien_ban_toi_thieu || ''; } catch (e) {}
}
async function loadExe() {
  try { const d = await post('/api/sys/exe', {info_only: true}); const x = d.exe;
    $('syExe').textContent = x ? '– đang có bản ' + (x.version || '?') + ', ' + (x.size / 1048576).toFixed(1) + ' MB, ' + x.time : '– chưa có file'; } catch (e) {}
}
$('syExeFile').onchange = async () => {
  const f = $('syExeFile').files[0]; $('syExeFile').value = ''; if (!f) return;
  $('syExeMsg').textContent = 'Đang tải lên ' + f.name + '…';
  try { await post('/api/sys/exe', {data: await readB64(f), version: $('syExeVer').value.trim()}); $('syExeMsg').textContent = 'Đã cập nhật.'; loadExe(); }
  catch (e) { $('syExeMsg').textContent = e.message; }
};
async function saveSys() {
  try { await post('/api/sys/save', {thong_bao: $('syAnn').value, goi_y: $('syHint').value, bo_qua_mtt: $('syMtt').checked,
                                      phien_ban_moi: $('syVer').value, link_tai: $('syLink').value, phien_ban_toi_thieu: $('syMin').value});
        $('syMsg').textContent = 'Đã lưu.'; refresh(); } catch (e) { $('syMsg').textContent = e.message; }
}
async function loadMail() {
  try { const m = await post('/api/mail/get', {});
    $('mlHost').value = m.host || ''; $('mlPort').value = m.port || 587; $('mlMode').value = m.mode || 'starttls'; $('mlUser').value = m.user || '';
    $('mlPw').value = ''; $('mlPw').placeholder = m.co_mk ? '(đã lưu – để trống nếu không đổi)' : ''; $('mlFrom').value = m.from_addr || '';
    $('mlName').value = m.from_name || ''; $('mlSite').value = m.site || location.origin; $('mlTo').value = me.email || ''; } catch (e) {}
}
async function saveMail() {
  $('mlMsg').textContent = ''; $('mlErr').textContent = '';
  try { await post('/api/mail/save', {host: $('mlHost').value, port: +$('mlPort').value || 587, mode: $('mlMode').value, user: $('mlUser').value,
          pw: $('mlPw').value, from_addr: $('mlFrom').value, from_name: $('mlName').value, site: $('mlSite').value});
        $('mlMsg').textContent = 'Đã lưu.'; loadMail(); } catch (e) { $('mlErr').textContent = e.message; }
}
async function testMail() {
  $('mlMsg').textContent = 'Đang gửi…'; $('mlErr').textContent = '';
  try { await post('/api/mail/test', {to: $('mlTo').value.trim()}); $('mlMsg').textContent = 'Đã gửi – kiểm tra hộp thư (cả Spam).'; }
  catch (e) { $('mlMsg').textContent = ''; $('mlErr').textContent = e.message; }
}
// Thanh thông báo: thông báo của chủ hệ thống, tình trạng trang thuế, số hoá đơn đã dùng / còn lại của gói.
function renderSys(sys) {
  if ((!me.server && !me.lic) || !sys) { hide('sysBar'); return; }
  if (me.lic) {  // bản cài máy: cảnh báo bản quyền + bản mới
    const L = me.lic, w = [];
    if (!L.logged) w.push('⚠ Chưa đăng nhập bản quyền – chưa tải được hoá đơn mới. Bấm "Bản quyền" ở đầu trang.');
    else if (L.error) w.push('⚠ ' + L.error);
    if (L.update) w.push('⬆ Có phiên bản mới ' + L.update.version + ' (đang dùng ' + (window.APPV || '') + ') – bấm nút "Cập nhật ngay" bên dưới.');
    sys = Object.assign({}, sys, {goi_y: w.join('\n')});
  }
  $('sbUpd').classList.toggle('hide', !(me.lic && me.lic.update));
  const set = (id, txt, cls) => { const e = $(id); e.textContent = txt || ''; e.className = id === 'sbAnn' ? 'sb-ann' : id === 'sbHint' ? 'sb-hint'
    : id === 'sbPortal' ? 'sb-portal ' + (cls || '') : 'sb-goi ' + (cls || ''); e.classList.toggle('hide', !txt); };
  set('sbAnn', sys.thong_bao ? '⚠ ' + sys.thong_bao + (sys.bo_qua_mtt ? '\n⚠ Tạm thời bỏ qua đồng bộ hoá đơn từ máy tính tiền – đối chiếu kỹ số lượng hoá đơn trước khi làm sổ sách.' : '')
    : (sys.bo_qua_mtt ? '⚠ Trang thuế đang lỗi phần hoá đơn máy tính tiền – tạm thời bỏ qua đồng bộ phần này. Đối chiếu kỹ số lượng hoá đơn trước khi làm sổ sách.' : ''));
  set('sbHint', [sys.goi_y ? '💡 ' + sys.goi_y : '', sys.mtt && !sys.bo_qua_mtt ? '⚠ ' + sys.mtt : ''].filter(Boolean).join('\n'));
  set('sbPortal', sys.portal && sys.portal.text ? (sys.portal.level === 'err' ? '⛔ ' : '⏳ ') + sys.portal.text : '', sys.portal && sys.portal.level);
  const g = me.goi; let gt = '', gc = '';
  if (g) {
    if (g.han_muc_hd) { const left = Math.max(0, g.han_muc_hd - g.da_dung_hd);
      gt = 'Gói hoá đơn: đã dùng ' + fmt(g.da_dung_hd) + ' / ' + fmt(g.han_muc_hd) + ' · còn lại ' + fmt(left) + ' hoá đơn';
      gc = left === 0 ? 'err' : left < g.han_muc_hd * 0.1 ? 'warn' : ''; }
    else gt = 'Hoá đơn đã tải: ' + fmt(g.da_dung_hd) + ' (gói không giới hạn)';
    if (g.tai_goc && g.han_muc_goc) { const lg = Math.max(0, g.han_muc_goc - g.da_dung_goc);
      gt += ' · HĐ gốc: còn lại ' + fmt(lg) + ' / ' + fmt(g.han_muc_goc); if (!lg && gc !== 'err') gc = 'warn'; }
    if (gc === 'err') gt += ' – HẾT HẠN MỨC, liên hệ nhà cung cấp để nâng gói.';
  }
  set('sbGoi', gt, gc);
  if (gt) $('sbGoi').append(el('br'), el('small', '', 'Hoá đơn đã tải rồi thì đồng bộ bao nhiêu lần cũng chỉ tính 1 hoá đơn – cứ đồng bộ lại thoải mái để cập nhật trạng thái.'));
  $('sysBar').classList.toggle('hide', !(sys.thong_bao || sys.bo_qua_mtt || sys.goi_y || sys.mtt || (sys.portal && sys.portal.text) || gt));
}
function renderGoi() {
  const g = me.goi; if (!g) { $('goiInfo').textContent = ''; return; }
  const part = (used, lim) => fmt(used) + (lim ? ' / ' + fmt(lim) : ' (không giới hạn)');
  $('goiInfo').textContent = 'Gói của văn phòng: hoá đơn đã tải ' + part(g.da_dung_hd, g.han_muc_hd) + ' · hoá đơn gốc tự tải '
    + (g.tai_goc ? part(g.da_dung_goc, g.han_muc_goc) : 'không gồm trong gói');
}
async function loadVps() {
  $('vpErr').textContent = '';
  try { vps = (await post('/api/vp/list', {})).items; } catch (e) { $('vpErr').textContent = e.message; return; }
  const tb = $('vpRows'); tb.innerHTML = '';
  vps.forEach(v => { const tr = tb.insertRow();
    [v.id, v.ten, v.quan_tri || '', v.so_dn, v.max_dn || 'không giới hạn',
     fmt(v.da_dung_hd || 0) + ' / ' + (v.han_muc_hd ? fmt(v.han_muc_hd) : '∞'),
     v.tai_goc === false ? 'không gồm' : fmt(v.da_dung_goc || 0) + ' / ' + (v.han_muc_goc ? fmt(v.han_muc_goc) : '∞'),
     v.so_nd, v.max_jobs || 'theo máy chủ', (v.so_may || 0) + ' / ' + (v.max_may === 0 ? '∞' : (v.max_may ?? 2)),
     v.het_han ? v.het_han.split('-').reverse().join('/') : ''].forEach((x, i) => {
      const c = tr.insertCell(); c.textContent = x; if (i >= 3) c.className = 'n';
      if ((i === 5 && v.han_muc_hd && v.da_dung_hd >= v.han_muc_hd) || (i === 6 && v.han_muc_goc && v.da_dung_goc >= v.han_muc_goc)) c.classList.add('err'); });
    const st = tr.insertCell(); st.textContent = v.active ? 'Hoạt động' : 'Đã khoá'; st.className = v.active ? 'ok' : 'err';
    tr.insertCell().textContent = v.tao || '';
    const b = el('button', 'sm sec', 'Sửa'); b.onclick = () => openVp(v); tr.insertCell().append(b); });
}
function openVp(v) {
  editingVp = v || null; $('vpEditErr').textContent = '';
  $('vpTitle').textContent = v ? 'Sửa văn phòng ' + v.id : 'Thêm văn phòng khách hàng';
  $('vpId').value = v ? v.id : ''; $('vpId').disabled = !!v; $('vpTen').value = v ? v.ten : '';
  $('vpMaxDn').value = v ? (v.max_dn || 0) : 0; $('vpMaxJobs').value = v ? (v.max_jobs || 0) : 2; $('vpNote').value = v ? (v.ghichu || '') : '';
  $('vpActive').checked = !v || v.active !== false; $('vpActBox').classList.toggle('hide', !!(v && v.id === 'goc'));
  $('vpNew').classList.toggle('hide', !!v); ['vpAdm', 'vpAdmTen', 'vpAdmPw', 'vpAdmEmail'].forEach(id => $(id).value = '');
  $('vpHd').value = v ? (v.han_muc_hd || 0) : 0; $('vpHdGoc').value = v ? (v.han_muc_goc || 0) : 0; $('vpGoc').checked = !v || v.tai_goc !== false;
  $('vpHdUsed').textContent = v ? 'đã dùng ' + fmt(v.da_dung_hd || 0) : ''; $('vpGocUsed').textContent = v ? 'đã dùng ' + fmt(v.da_dung_goc || 0) : '';
  ['vpHdReset', 'vpGocReset'].forEach(id => $(id).checked = false);
  $('vpHdResetL').classList.toggle('hide', !v); $('vpGocResetL').classList.toggle('hide', !v);
  $('vpMay').value = v ? (v.max_may ?? 2) : 2; $('vpHetHan').value = v ? (v.het_han || '') : '';
  $('vpDevBox').classList.toggle('hide', !v); if (v) renderDevs('vpDevs', v.id);
  show('mVp');
}
// Máy đã cài bản exe và đăng nhập (vid: văn phòng – chủ hệ thống; trống: văn phòng của mình)
async function renderDevs(boxId, vid) {
  const box = $(boxId); box.innerHTML = '';
  try {
    const d = vid ? await post('/api/vp/devices', {id: vid}) : await post('/api/devices', {});
    const items = Object.entries(d.items || {});
    if (!items.length) { box.append(el('div', 'mute', 'Chưa có máy nào cài bản trên máy.')); return; }
    const t = el('table'); const h = t.insertRow(); ['Tên máy', 'Người đăng nhập', 'Kích hoạt', 'Dùng gần nhất', 'Phiên bản', 'IP', ''].forEach(x => h.append(el('th', '', x)));
    items.forEach(([id, m]) => { const r = t.insertRow(); [m.ten, m.user, m.dang_ky, m.lan_cuoi, m.phien_ban, m.ip].forEach(x => r.insertCell().textContent = x || '');
      const b = el('button', 'sm red', 'Gỡ máy'); b.onclick = async () => { if (!confirm('Gỡ máy ' + (m.ten || id) + '? Máy đó phải đăng nhập lại (và chiếm 1 chỗ) mới tải được hoá đơn.')) return;
        try { await post(vid ? '/api/vp/device-remove' : '/api/device/remove', vid ? {id: vid, device: id} : {device: id}); renderDevs(boxId, vid); } catch (e) { alert(e.message); } };
      r.insertCell().append(b); });
    const w = el('div', 'tbl'); w.append(t); box.append(w);
    if (!vid && d.max) box.append(el('div', 'mute', 'Gói cho phép tối đa ' + d.max + ' máy.'));
  } catch (e) { box.append(el('div', 'err', e.message)); }
}
// ---- Bản cài máy: bản quyền + sao lưu ----
let licAsked = false;
function openLic() {
  const L = me.lic || {}; $('licErr').textContent = ''; $('licBkMsg').textContent = '';
  $('licServer').value = L.server || ''; $('licUser').value = L.username || ''; $('licPw').value = '';
  const box = $('licInfo'); box.innerHTML = '';
  if (L.logged) { const i = L.info || {}, g = i.goi || {};
    const line = (k, v) => { const d = el('div'); d.append(k + ': ', el('b', '', v)); box.append(d); };
    line('Tài khoản', (i.ten ? i.ten + ' – ' : '') + (i.user || L.username)); line('Văn phòng', i.vp_ten || i.vp || '');
    line('Hoá đơn đã tải', fmt(g.da_dung_hd || 0) + (g.han_muc_hd ? ' / ' + fmt(g.han_muc_hd) + ' (còn ' + fmt(Math.max(0, g.han_muc_hd - (g.da_dung_hd || 0))) + ')' : ' (không giới hạn)'));
    line('Hoá đơn gốc', g.tai_goc === false ? 'gói không gồm' : fmt(g.da_dung_goc || 0) + (g.han_muc_goc ? ' / ' + fmt(g.han_muc_goc) : ''));
    if (i.het_han) line('Hết hạn gói', i.het_han.split('-').reverse().join('/'));
    line('Kiểm tra lần cuối', L.checked || '');
    if (L.update) { const b = el('button', 'sm', '⬆ Cập nhật lên ' + L.update.version); b.onclick = doUpdate; box.append(b);
      const a = el('a', '', ' hoặc tải file về tự cài'); a.href = L.update.url; a.target = '_blank'; box.append(a); }
    if (L.error) box.append(el('div', 'err', L.error));
  }
  $('licForm').classList.toggle('hide', !!L.logged); $('licOut').classList.toggle('hide', !L.logged); $('licChk').classList.toggle('hide', !L.logged);
  show('mLic'); if (!L.logged) setTimeout(() => $(L.username ? 'licPw' : 'licUser').focus(), 50);
}
async function doUpdate() {
  const u = (me.lic || {}).update; if (!u) return;
  if (!confirm('Tải và cài phiên bản ' + u.version + '? Phần mềm sẽ tự tắt rồi mở lại sau khoảng 10–30 giây (dữ liệu giữ nguyên).')) return;
  const box = $('sbUpd'); box.textContent = 'Đang tải phiên bản ' + u.version + '… (vài phút tuỳ mạng, đừng tắt phần mềm)';
  try { await post('/api/banquyen/update', {});
    box.textContent = 'Đã tải xong – phần mềm đang khởi động lại. Nếu trang không tự mở, chạy lại TaiHoaDon.exe.';
    setTimeout(() => location.reload(), 20000); }
  catch (e) { box.innerHTML = '<button class="sm" onclick="doUpdate()">⬆ Cập nhật ngay</button>';
    alert('Không cập nhật được: ' + e.message + '\nCó thể tải file về tự cài: ' + u.url); }
}
async function licCall(path, body) {
  $('licErr').textContent = '';
  try { me.lic = await post(path, body || {}); await refresh(); openLic(); }
  catch (e) { $('licErr').textContent = e.message; }
}
const licLogin = () => licCall('/api/banquyen/login', {server: $('licServer').value, username: $('licUser').value, password: $('licPw').value});
const licRefresh = () => licCall('/api/banquyen/status');
$('licPw').addEventListener('keydown', e => { if (e.key === 'Enter') licLogin(); });
function licLogout() { if (confirm('Đăng xuất bản quyền trên máy này? Máy được gỡ khỏi danh sách, nhường chỗ cho máy khác; dữ liệu đã tải vẫn còn trên máy.')) licCall('/api/banquyen/logout'); }
async function licBackup() {
  $('licBkMsg').textContent = 'Đang sao lưu…';
  try { const d = await post('/api/banquyen/backup', {dir: $('licBkDir').value.trim()});
        $('licBkMsg').textContent = 'Đã sao lưu ' + fmt(d.files) + ' file (' + (d.size / 1048576).toFixed(1) + ' MB) vào ' + d.path; }
  catch (e) { $('licBkMsg').textContent = ''; $('licErr').textContent = e.message; }
}
async function saveVp() {
  const body = {id: $('vpId').value.trim(), ten: $('vpTen').value, max_dn: +$('vpMaxDn').value || 0, max_jobs: +$('vpMaxJobs').value || 0,
                ghichu: $('vpNote').value, active: $('vpActive').checked, create: !editingVp,
                han_muc_hd: +$('vpHd').value || 0, han_muc_goc: +$('vpHdGoc').value || 0, tai_goc: $('vpGoc').checked,
                dat_lai_hd: $('vpHdReset').checked, dat_lai_goc: $('vpGocReset').checked,
                max_may: +$('vpMay').value || 0, het_han: $('vpHetHan').value};
  if (!editingVp) Object.assign(body, {admin: $('vpAdm').value.trim(), admin_ten: $('vpAdmTen').value, admin_pw: $('vpAdmPw').value,
                                       admin_email: $('vpAdmEmail').value.trim()});
  if (editingVp && editingVp.active && !body.active && !confirm('Khoá văn phòng ' + editingVp.ten + '? Mọi người dùng của văn phòng sẽ bị đăng xuất.')) return;
  try { await post('/api/vp/save', body); hide('mVp'); loadVps();
        if (!editingVp) alert(body.admin_pw ? 'Đã tạo văn phòng. Gửi cho khách: địa chỉ trang web, tên đăng nhập ' + body.admin + ' và mật khẩu vừa đặt.'
                                            : 'Đã tạo văn phòng và gửi email mời ' + body.admin_email + ' tự đặt mật khẩu.'); }
  catch (e) { $('vpEditErr').textContent = e.message; }
}
async function delUser() {
  if (!editingUser || !confirm('Xoá người dùng ' + editingUser.username + '?')) return;
  try { await post('/api/user/delete', {username: editingUser.username}); hide('mUser'); loadUsers(); }
  catch (e) { $('uEditErr').textContent = e.message; }
}
const AUTO_KY = [['auto', 'Tháng này (tới ngày 20 gồm cả tháng trước)'], ['7', '7 ngày gần nhất'], ['month', 'Tháng này'], ['2month', 'Tháng này và tháng trước']];
AUTO_KY.forEach(([v, t]) => $('aKy').append(new Option(t, v)));
let autoLoaded = false;
function renderAuto(a) {
  if (!autoLoaded || document.activeElement === document.body) { $('aOn').checked = a.on; $('aGio').value = a.gio; $('aKy').value = a.ky; autoLoaded = true; }
  $('aInfo').textContent = (a.running ? 'Đang chạy' + (a.dn ? ' – doanh nghiệp ' + a.dn[0] + '/' + a.dn[1] : '') + (a.current ? ' (' + a.current + ')' : '') + '. '
    : '') + (a.lan_cuoi ? 'Lần chạy gần nhất: ' + a.lan_cuoi : 'Chưa chạy lần nào');
  $('aLog').classList.toggle('hide', !(a.log && a.log.length)); $('aLog').textContent = (a.log || []).join('\n');
}
async function saveAuto() {
  try { renderAuto(await post('/api/auto/save', {on: $('aOn').checked, gio: $('aGio').value, ky: $('aKy').value})); $('aMsg').textContent = 'Đã lưu.'; setTimeout(() => $('aMsg').textContent = '', 3000); }
  catch (e) { $('uErr').textContent = e.message; }
}
async function runAuto() {
  if (!confirm('Chạy đồng bộ tự động ngay cho tất cả doanh nghiệp?')) return;
  try { await post('/api/auto/run', {}); refresh(); } catch (e) { $('uErr').textContent = e.message; }
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
  const f = $('xmlFile').files[0]; if (!f) return; $('xmlErr').textContent = ''; hide('xmlDl'); hide('xmlCqt');
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

// ---- Sao kê ngân hàng ----
let bankRows = [], bankMeta = {};
const BK_OPTS = ['bkTk', 'bkMa', 'bkThu', 'bkChi', 'bkNv', 'bkGc'];
BK_OPTS.forEach(id => { try { const v = localStorage.getItem('opt_' + id); if (v !== null) $(id).value = v; } catch (e) {}
  $(id).addEventListener('change', () => { try { localStorage.setItem('opt_' + id, $(id).value); } catch (e) {} renderBank(); }); });
$('bankFiles').onchange = async () => {
  const fs = [...$('bankFiles').files]; $('bankFiles').value = ''; if (!fs.length) return;
  $('bankErr').textContent = 'Đang đọc ' + fs.length + ' file…'; $('bankInfo').innerHTML = ''; bankRows = []; renderBank();
  try {
    const files = []; for (const f of fs) files.push({name: f.name, data: await readB64(f)});
    const d = await post('/api/tool/bank-parse', {files});
    $('bankErr').innerHTML = ''; d.errors.forEach(x => $('bankErr').append(el('div', 'err', x)));
    const info = $('bankInfo');
    d.statements.forEach(st => {
      const ok = !st.warnings.length, box = el('div', ok ? 'ok' : 'err');
      box.textContent = st.file + ': ' + [st.bank, st.account, st.company].filter(Boolean).join(' – ') + ' · ' + vnd(st.from) + ' → ' + vnd(st.to) +
        ' · thu ' + fmt(st.sum_in) + ' · chi ' + fmt(st.sum_out) + (ok ? (st.total_in != null ? ' · khớp tổng trên sao kê ✓' : '') : '');
      info.append(box); st.warnings.forEach(w => info.append(el('div', 'err', '⚠ ' + w)));
    });
    if (d.dups) info.append(el('div', 'mute', 'Đã bỏ ' + d.dups + ' giao dịch trùng giữa các file.'));
    const st0 = d.statements[0] || {};
    bankMeta = {bank: st0.bank || '', account: st0.account || ''};
    if (!$('bkMa').value && st0.bank) $('bkMa').placeholder = 'vd 112_' + st0.bank;
    bankRows = d.rows; renderBank();
  } catch (e) { $('bankErr').textContent = e.message; }
};
function renderBank() {
  const t = $('bankTbl'); t.innerHTML = '';
  $('bankExp').classList.toggle('hide', !bankRows.length);
  if (!bankRows.length) { $('bankSum').textContent = ''; return; }
  const h = t.insertRow(); ['Ngày', 'Số GD', 'Diễn giải', 'Đối tượng', 'Tiền vào', 'Tiền ra', 'Nợ', 'Có'].forEach((x, i) => {
    const th = el('th', i === 4 || i === 5 ? 'n' : '', x); h.append(th); });
  const tk = $('bkTk').value || '1121';
  bankRows.forEach(r => {
    const tr = t.insertRow(), inc = r.in > 0;
    [vnd(r.date), r.ref, r.desc, r.name].forEach(v => tr.insertCell().textContent = v);
    [r.in, r.out].forEach(v => { const c = tr.insertCell(); c.className = 'n'; c.textContent = v ? fmt(v) : ''; });
    const du = el('input', 'note'); du.style.width = '70px'; du.value = r.tkdu || ''; du.placeholder = inc ? ($('bkThu').value || '') : ($('bkChi').value || '');
    du.title = 'TK đối ứng riêng cho dòng này (để trống = theo mặc định)'; du.onchange = () => { r.tkdu = du.value.trim(); };
    const no = tr.insertCell(), co = tr.insertCell();
    if (inc) { no.textContent = tk; co.append(du); } else { no.append(du); co.textContent = tk; }
  });
  const si = bankRows.reduce((a, r) => a + r.in, 0), so = bankRows.reduce((a, r) => a + r.out, 0);
  $('bankSum').textContent = bankRows.length + ' giao dịch · tiền vào ' + fmt(si) + ' · tiền ra ' + fmt(so);
}
async function bankExport() {
  try {
    const opts = {tk: $('bkTk').value, ma_dt: $('bkMa').value, tk_thu: $('bkThu').value, tk_chi: $('bkChi').value,
                  nghiep_vu: $('bkNv').value, ghi_chu: $('bkGc').value};
    const d = await post('/api/tool/bank-export', {rows: bankRows, opts, bank: bankMeta.bank, account: bankMeta.account});
    download(d.name, d.data, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  } catch (e) { $('bankErr').textContent = e.message; }
}

// ---- Trang Đồng bộ ----
let syncMst = '', syncRange = null, syncRows = [];
// Tháng 1, 2, 3, Quý 1, Tháng 4, 5, 6, Quý 2, …
const MQ = [1, 2, 3, 4].flatMap(q => [0, 1, 2].map(i => ['m' + (q * 3 - 2 + i), 'Tháng ' + (q * 3 - 2 + i)]).concat([['q' + q, 'Quý ' + q]]));
const weekStart = d => { const a = new Date(d); a.setDate(d.getDate() - (d.getDay() + 6) % 7); return a; };  // thứ Hai
const SPER = [['today', 'Hôm nay'], ['week', 'Tuần này'], ['month', 'Tháng này']].concat(MQ, [['y', 'Cả năm']]);
SPER.forEach(([v, t]) => $('sPer').append(new Option(t, v)));
function sPeriod() {
  const v = $('sPer').value, now = new Date(), y = +$('sYear').value || now.getFullYear(); let a, b;
  if (v === 'today') a = b = now;
  else if (v === 'week') { a = weekStart(now); b = now; }
  else if (v === 'month') { a = new Date(now.getFullYear(), now.getMonth(), 1); b = now; }
  else if (v[0] === 'm') { const m = +v.slice(1) - 1; a = new Date(y, m, 1); b = new Date(y, m + 1, 0); }
  else if (v === 'y') { a = new Date(y, 0, 1); b = new Date(y, 11, 31); }
  else { const q = +v.slice(1) - 1; a = new Date(y, q * 3, 1); b = new Date(y, q * 3 + 3, 0); }
  if (b > now && a <= now) b = now;  // năm nay: tới hôm nay
  $('sFrom').value = isoD(a); $('sTo').value = isoD(b);
  $('sYear').classList.toggle('hide', ['today', 'week', 'month'].includes(v));
}
$('sPer').onchange = sPeriod; $('sYear').onchange = sPeriod;
function openSync(mst) { openCompany(mst, 'dong-bo'); }
async function runSync(kinds) {
  syncRange = [$('sFrom').value, $('sTo').value];
  try { await start({msts: [syncMst], kinds, from: syncRange[0], to: syncRange[1], mtt: $('sMtt').checked, xml: $('sXml').checked,
                     pdf: $('sPdf').checked && $('sXml').checked}); }
  catch (e) { alert(e.message); }
}
function openInvoicesFromSync() {
  if (syncRange) { $('hPer').value = ''; $('hFrom').value = syncRange[0]; $('hTo').value = syncRange[1]; }
  hPage = 0; openSub('hoa-don');
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
    (j.gaps || []).forEach(g => line('<span class="err">⚠ Trang thuế lỗi – chưa tra được: ' + esc(g) + '. Đồng bộ lại sau để lấy đủ.</span>'));
    if (j.mtt_skipped) line('<span class="err">⚠ Phần hoá đơn MÁY TÍNH TIỀN của trang thuế đang treo – đã bỏ qua phần này, các hoá đơn khác vẫn đồng bộ. Đối chiếu lại sau.</span>');
    if (j.running && j.log.length) line('<span class="mute">' + esc(j.log[j.log.length - 1]) + '</span>');
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
const PERIODS = [['', '--Tuỳ chọn ngày--'], ['today', 'Hôm nay'], ['week', 'Tuần này'], ['month', 'Tháng này']]
  .concat(MQ, [['year', 'Cả năm (năm nay)'], ['lastyear', 'Năm trước']]);
PERIODS.forEach(([v, t]) => $('hPer').append(new Option(t, v)));
$('hPer').onchange = () => {
  const v = $('hPer').value, now = new Date(), y = now.getFullYear(); let a, b;
  if (v === 'today') a = b = now;
  else if (v === 'week') { a = weekStart(now); b = now; }
  else if (v === 'month') { a = new Date(y, now.getMonth(), 1); b = now; }
  else if (v[0] === 'm') { const m = +v.slice(1) - 1; a = new Date(y, m, 1); b = new Date(y, m + 1, 0); }
  else if (v[0] === 'q') { const q = +v.slice(1) - 1; a = new Date(y, q * 3, 1); b = new Date(y, q * 3 + 3, 0); }
  else if (v === 'year') { a = new Date(y, 0, 1); b = now; }
  else if (v === 'lastyear') { a = new Date(y - 1, 0, 1); b = new Date(y - 1, 11, 31); }
  if (a) { $('hFrom').value = isoD(a); $('hTo').value = isoD(b); hPage = 0; loadInv(); }
};
function tab(t, keepHash) {
  curTab = t; $('navDN').classList.toggle('on', t === 'DN'); $('navHD').classList.toggle('on', t === 'WS');
  document.body.classList.toggle('ws', t === 'WS');
  $('tabDN').classList.toggle('hide', t !== 'DN'); $('tabWS').classList.toggle('hide', t !== 'WS');
  $('tabTI').classList.toggle('hide', t !== 'TI'); $('navTI').classList.toggle('on', t === 'TI');
  $('tabND').classList.toggle('hide', t !== 'ND'); $('navND').classList.toggle('on', t === 'ND');
  if (t !== 'WS') toolsHome();
  if (t === 'ND') loadUsers();
  if (!keepHash) setHash(t === 'WS' ? '#/dn/' + wsMst + '/' + wsSub : t === 'DN' ? '#/' : '#/' + t.toLowerCase());
}

// ---- Trang doanh nghiệp ----
let wsMst = '', wsSub = 'hoa-don';
const coLabel = (c, i) => String(i + 1).padStart(3, '0') + '. ' + (c.ten || '(chưa đặt tên)') + ' (' + c.mst + ')';
const visibleCos = () => companies.filter(c => !c.an || c.mst === wsMst);
function setHash(h) { if (location.hash !== h) history.pushState(null, '', h); }
function fillMst() {
  const dl = $('wsList'); dl.innerHTML = '';
  visibleCos().forEach((c, i) => dl.append(new Option(coLabel(c, i), coLabel(c, i))));
}
function renderWsHead() {
  const list = visibleCos(), i = list.findIndex(c => c.mst === wsMst), c = list[i] || {mst: wsMst};
  $('wsPick').value = i >= 0 ? coLabel(c, i) : wsMst; $('wsCrumb').textContent = c.ten || wsMst;
  $('wsPrev').disabled = i <= 0; $('wsNext').disabled = i < 0 || i >= list.length - 1;
  const h = c.lich_su || {}, so = c.so_hd || {}, m = $('wsMeta'); m.innerHTML = '';
  const item = (label, val, cls) => { const sp = el('span', cls || '', label + ': '); sp.append(el('b', '', val)); m.append(sp); };
  item('MST', c.mst || '');
  item('HĐ mua vào', fmt(so.purchase || 0)); item('HĐ bán ra', fmt(so.sold || 0));
  if (h.purchase) item('Đồng bộ vào', h.purchase.luc, 'v'); if (h.sold) item('Đồng bộ ra', h.sold.luc, 'r');
  if (c.loi) m.append(el('span', 'err', c.loi));
  document.querySelectorAll('#wsTabs a').forEach(a => a.classList.toggle('on', a.dataset.s === wsSub));
}
function openCompany(mst, sub) {
  if (!mst) { tab('DN'); return; }
  const changed = mst !== wsMst;
  wsMst = mst; syncMst = mst; $('hMst').value = mst;
  if (changed) { hSel.clear(); hColF = {}; hRows = []; }
  tab('WS', true); fillMst(); openSub(sub || (changed ? 'hoa-don' : wsSub), changed);
}
function openSub(sub, force) {
  wsSub = sub; renderWsHead();
  ['wsHD', 'wsSYNC', 'wsTOOL', 'wsHH', 'wsDT', 'wsQD'].forEach(id => hide(id)); toolsHome();
  if (sub === 'dong-bo') {
    show('wsSYNC'); if (!$('sFrom').value) { $('sYear').value = new Date().getFullYear(); $('sPer').value = 'week'; sPeriod(); }
    poll();
  } else if (sub === 'hang-hoa') {
    show('wsHH'); gLoad();
  } else if (sub === 'doi-tuong') {
    show('wsDT'); lpLoad(DT);
  } else if (sub === 'quy-doi') {
    show('wsQD'); lpLoad(QD);
  } else if (sub === 'sao-ke' || sub === 'tra-cuu-mst') {
    const pane = $(sub === 'sao-ke' ? 't-bank' : 't-mst'); $('wsTOOL').append(pane); pane.classList.remove('hide'); show('wsTOOL');
    if (sub === 'tra-cuu-mst' && !$('mstText').value.trim()) $('mstText').value = wsMst;
  } else { wsSub = 'hoa-don'; show('wsHD'); if (force || !hRows.length) loadInv(); else renderInv(); }
  setHash('#/dn/' + wsMst + '/' + wsSub);
}
const toolHome = $('t-bank').parentNode;
function toolsHome() {
  ['t-bank', 't-mst'].forEach(id => { const p = $(id); if (p.parentNode !== toolHome) { toolHome.append(p);
    p.classList.toggle('hide', !document.querySelector('.tmenu a.on[data-t="' + id.slice(2) + '"]')); } });
}
function stepCompany(d) {
  const list = visibleCos(), i = list.findIndex(c => c.mst === wsMst);
  if (list[i + d]) openCompany(list[i + d].mst, wsSub);
}
document.querySelectorAll('#wsTabs a').forEach(a => a.onclick = ev => { ev.preventDefault(); openSub(a.dataset.s); });
$('wsPick').addEventListener('change', () => {
  const v = $('wsPick').value.trim(), m = v.match(/\((\d{10}(?:-\d{3})?)\)\s*$/) || v.match(/^(\d{10}(?:-\d{3})?)$/);
  let c = m && companies.find(x => x.mst === m[1]);
  if (!c && v) { const q = v.toLowerCase(), hits = companies.filter(x => (x.ten || '').toLowerCase().includes(q) || x.mst.includes(q)); if (hits.length === 1) c = hits[0]; }
  if (c) openCompany(c.mst, wsSub); else if (v) $('wsPick').select();
});
$('wsPick').addEventListener('focus', () => $('wsPick').select());
document.addEventListener('keydown', e => { if (curTab === 'WS' && e.altKey && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
  e.preventDefault(); stepCompany(e.key === 'ArrowLeft' ? -1 : 1); } });
function route() {
  const h = location.hash, m = h.match(/^#\/dn\/([\d-]+)(?:\/([\w-]+))?/);
  if (m) { if (m[1] !== wsMst || curTab !== 'WS') openCompany(m[1], m[2]); else if (m[2] && m[2] !== wsSub) openSub(m[2]); return; }
  const t = (h.match(/^#\/(\w+)/) || [])[1];
  tab(t === 'ti' ? 'TI' : t === 'nd' ? 'ND' : 'DN', true);
}
window.addEventListener('popstate', route);
document.querySelectorAll('#hKindSeg button').forEach(b => b.onclick = () => {
  document.querySelectorAll('#hKindSeg button').forEach(x => x.classList.toggle('on', x === b));
  $('hKind').value = b.dataset.k; hSel.clear(); hColF = {}; hPage = 0; loadInv();
});
function hFilters(useSel) {
  const keys = useSel ? hRows.filter(r => hSel.has(r.key)).map(r => r.key) : [];
  return {keys, kind: $('hKind').value, file: $('hFile').value, duyet: $('hDuyet').value, tthai: $('hTthai').value, kq: $('hKq').value,
          khhdon: $('hKh').value.trim(), shdon: $('hSo').value.trim(), q: $('hQ').value.trim(), from: $('hFrom').value, to: $('hTo').value};
}
async function loadInv() {
  $('hErr').textContent = '';
  if (!$('hMst').value) { hRows = []; buildHead(); renderInv(); return; }
  try { hRows = (await post('/api/invoices', {mst: $('hMst').value, filters: hFilters()})).rows; }
  catch (e) { $('hErr').textContent = e.message; hRows = []; }
  const live = new Set(hRows.map(r => r.key)); hSel.forEach(k => { if (!live.has(k)) hSel.delete(k); });
  buildHead(); renderInv();
}
function fileLink(rel, text) {
  const a = el('a', 'lk', text); a.href = '/file?k=' + encodeURIComponent(KEY) + '&mst=' + encodeURIComponent($('hMst').value) + '&p=' + encodeURIComponent(rel);
  a.target = '_blank';
  if (!/\.xml$/i.test(rel)) return a;
  // XML: bấm để xem ngay trong Tiện ích › Đọc hoá đơn XML; nút ⇩ tải file (nén .zip để trình duyệt không chặn)
  const url = a.href; a.title = 'Xem hoá đơn XML trong Tiện ích (có nút tải file XML)';
  a.onclick = ev => { ev.preventDefault(); openXml(url, rel.split('/').pop(), a.dataset.cqt); };
  return a;
}
async function openXml(url, name, cqt) {
  tab('TI'); document.querySelector('.tmenu a[data-t=xml]').click();
  $('xmlErr').textContent = 'Đang mở ' + name + '…'; hide('xmlFrame'); hide('xmlPrint'); hide('xmlDl'); hide('xmlCqt');
  try { const res = await fetch(url, {credentials: 'same-origin'}); if (!res.ok) throw new Error('Không mở được file (' + res.status + ')');
        const u = new Uint8Array(await res.arrayBuffer()); let bin = ''; for (let i = 0; i < u.length; i += 8192) bin += String.fromCharCode.apply(null, u.subarray(i, i + 8192));
        const d = await post('/api/tool/xml-view', {data: btoa(bin)});
        $('xmlErr').textContent = ''; $('xmlFrame').srcdoc = d.html; show('xmlFrame'); show('xmlPrint');
        $('xmlDl').href = url + '&zip=1'; show('xmlDl'); if (cqt) { $('xmlCqt').href = cqt; show('xmlCqt'); } }
  catch (e) { $('xmlErr').textContent = e.message; }
}

// ---- Bảng hoá đơn: sắp xếp, lọc theo cột, chọn dòng ----
const HCOLS = [
  {k: 'mst', t: 'MST', f: 'text'}, {k: 'ten', t: 'Người bán', f: 'text', cls: 'ten'}, {k: 'ngay', t: 'Ngày', f: 'text', sort: 'date', cls: 'nw'},
  {k: 'khhdon', t: 'Ký hiệu', f: 'text', cls: 'nw'}, {k: 'shdon', t: 'Số HĐ', f: 'text', n: 1, sort: 'num'},
  {k: 'cthue', t: 'Tiền C.Thuế', f: 'num', n: 1, sum: 1}, {k: 'thue', t: 'Tiền thuế', f: 'num', n: 1, sum: 1},
  {k: 'ck', t: 'CK.TM', f: 'num', n: 1, sum: 1}, {k: 'phi', t: 'Phí', f: 'num', n: 1, sum: 1},
  {k: 'tt', t: 'Tổng T.Toán', f: 'num', n: 1, sum: 1}, {k: 'tthai', t: 'T.thái HĐ', f: 'pick', cls: 'nw'},
  {k: 'kq', t: 'Kết quả k.tra', f: 'pick', cls: 'nw'}, {k: 'duyet', t: 'Duyệt nội bộ', f: 'pick'}, {k: 'dv', t: 'HĐ DV', f: 'bool'},
  {k: 'mat_hang', t: 'Mặt hàng', f: 'text', cls: 'mh'}, {k: 'note', t: 'Ghi chú', f: 'text'}, {k: '_ct', t: 'Chi tiết'}, {k: '_goc', t: 'HĐ gốc'}];
let hSort = {k: 'ngay', dir: 1}, hColF = {}, hLastClick = null, hHide = new Set();
// Cột CK.TM / Phí toàn số 0 (thường gặp) thì ẩn để bảng vừa màn hình; có số khác 0 là hiện lại.
const visCols = () => HCOLS.filter(c => !hHide.has(c.k));
try { const g = JSON.parse(localStorage.getItem('grid') || '{}'); if (g.sort) hSort = g.sort; if (g.size) hSize = g.size; } catch (e) {}
const saveGrid = () => { try { localStorage.setItem('grid', JSON.stringify({sort: hSort, size: hSize})); } catch (e) {} };
const dkey = s => s ? s.split('/').reverse().join('') : '';
function resetGrid() { hColF = {}; hSort = {k: 'ngay', dir: 1}; saveGrid(); buildHead(); renderInv(); }
function viewRows() {
  let rows = hRows.filter(r => HCOLS.every(c => {
    const f = hColF[c.k]; if (f == null || f === '') return true;
    if (c.f === 'pick') return r[c.k] === f;
    if (c.f === 'bool') return String(!!r[c.k]) === f;
    if (c.f === 'num') { const m = f.replace(/\s/g, '').match(/^(>=|<=|>|<|=)?([\d.,]+)$/);
      if (m) { const v = +m[2].replace(/[.,]/g, ''), x = r[c.k] || 0;
        return {'>': x > v, '<': x < v, '>=': x >= v, '<=': x <= v}[m[1]] ?? x === v; }
      return fmt(r[c.k]).includes(f); }
    return String(r[c.k] ?? '').toLowerCase().includes(f.toLowerCase());
  }));
  const c = HCOLS.find(x => x.k === hSort.k);
  if (c) {
    const key = c.sort === 'date' ? (r => dkey(r.ngay) + String(r.shdon).padStart(12, '0'))
      : c.sort === 'num' ? (r => +r[c.k] || 0) : c.n ? (r => r[c.k] || 0) : (r => String(r[c.k] ?? '').toLowerCase());
    rows = rows.slice().sort((a, b) => { const x = key(a), y = key(b); return (x > y ? 1 : x < y ? -1 : 0) * hSort.dir; });
  }
  return rows;
}
function buildHead() {
  const sold = $('hKind').value.startsWith('sold');
  HCOLS[1].t = sold ? 'Người mua' : 'Người bán';
  hHide = new Set(['ck', 'phi'].filter(k => hRows.every(r => !r[k])));
  const th = $('hHead'); th.innerHTML = '';
  const r1 = th.insertRow(), r2 = th.insertRow(); r2.className = 'f';
  const all = el('input'); all.type = 'checkbox'; all.id = 'hAll'; all.title = 'Chọn tất cả các dòng đang lọc';
  all.onchange = () => { const v = viewRows(); if (all.checked) v.forEach(r => hSel.add(r.key)); else v.forEach(r => hSel.delete(r.key)); renderInv(); };
  const c0 = el('th'); c0.append(all); r1.append(c0); r2.append(el('th'));
  visCols().forEach(c => {
    const h = el('th', (c.n ? 'n ' : '') + (c.k[0] !== '_' ? 's' : ''), c.t);
    if (hSort.k === c.k) h.textContent += hSort.dir > 0 ? ' ▲' : ' ▼';
    if (c.k[0] !== '_') { h.title = 'Bấm để sắp xếp'; h.onclick = () => { hSort = {k: c.k, dir: hSort.k === c.k ? -hSort.dir : 1}; saveGrid(); buildHead(); renderInv(); }; }
    r1.append(h);
    const f = el('th'); r2.append(f);
    if (c.f === 'pick' || c.f === 'bool') {
      const sel = el('select'); sel.append(new Option('(Tất cả)', ''));
      if (c.f === 'bool') { sel.append(new Option('Có', 'true')); sel.append(new Option('Không', 'false')); }
      else [...new Set(hRows.map(r => r[c.k]).filter(Boolean))].sort().forEach(v => sel.append(new Option(v, v)));
      sel.value = hColF[c.k] || ''; sel.onchange = () => { hColF[c.k] = sel.value; hPage = 0; renderInv(); }; f.append(sel);
    } else if (c.f) {
      const inp = el('input'); inp.type = 'search'; inp.value = hColF[c.k] || ''; inp.placeholder = '🔍';
      if (c.f === 'num') inp.title = 'Lọc số tiền: gõ 1000000, >1000000, <500000, >=…';
      inp.oninput = () => { hColF[c.k] = inp.value; hPage = 0; renderInv(); }; f.append(inp);
    }
  });
}
function selInfo(view) {
  const sel = hRows.filter(r => hSel.has(r.key));
  $('hBulk').classList.toggle('hide', !sel.length);
  $('hSelInfo').textContent = sel.length ? ('Đã chọn ' + sel.length + ' HĐ · tổng thanh toán ' + fmt(sel.reduce((a, r) => a + r.tt, 0))) : '';
  const all = $('hAll'); if (all) { const v = view || viewRows(); all.checked = v.length > 0 && v.every(r => hSel.has(r.key));
    all.indeterminate = !all.checked && v.some(r => hSel.has(r.key)); }
}
const BAD = /bị hủy|bị huỷ|bị thay thế|bị điều chỉnh|từ chối|k đủ/i;
function renderInv() {
  if (!$('hHead').rows.length) buildHead();
  const view = viewRows(), tb = $('hRows'); tb.innerHTML = '';
  const size = hSize || view.length || 1, pages = Math.max(1, Math.ceil(view.length / size)); hPage = Math.min(hPage, pages - 1);
  const kind = $('hKind').value.replace('_dv', '');
  view.slice(hPage * size, hPage * size + size).forEach(r => {
    const tr = tb.insertRow(); tr.dataset.key = r.key;
    if (hSel.has(r.key)) tr.classList.add('sel');
    if (BAD.test(r.tthai + ' ' + r.kq)) tr.classList.add('bad');
    const pick = el('input'); pick.type = 'checkbox'; pick.checked = hSel.has(r.key); tr.insertCell().append(pick);
    tr.onclick = ev => {
      if (ev.target.closest('a,select,button,input:not([type=checkbox]),label')) return;
      const keys = view.map(x => x.key), on = !hSel.has(r.key);
      if (ev.shiftKey && hLastClick && keys.includes(hLastClick)) {
        const [a, b] = [keys.indexOf(hLastClick), keys.indexOf(r.key)].sort((x, y) => x - y);
        keys.slice(a, b + 1).forEach(k => hSel.add(k));
      } else on ? hSel.add(r.key) : hSel.delete(r.key);
      hLastClick = r.key; renderInv();
    };
    visCols().forEach(c => {
      if (c.k === 'duyet') { const sel = el('select'); ['Chờ duyệt', 'Đã duyệt', 'Không duyệt'].forEach(o => sel.append(new Option(o, o)));
        sel.value = r.duyet; sel.disabled = !can('sua'); sel.onchange = () => updInv(r, {duyet: sel.value}); tr.insertCell().append(sel); return; }
      if (c.k === 'dv') { const cb = el('input'); cb.type = 'checkbox'; cb.checked = r.dv; cb.title = 'Hoá đơn dịch vụ';
        cb.disabled = !can('sua'); cb.onclick = ev => ev.stopPropagation(); cb.onchange = () => updInv(r, {dv: cb.checked}); tr.insertCell().append(cb); return; }
      if (c.k === 'note') { const note = el('input', 'note'); note.value = r.note; note.placeholder = 'ghi chú…'; note.disabled = !can('sua');
        note.onchange = () => updInv(r, {note: note.value}); tr.insertCell().append(note); return; }
      if (c.k === '_ct') { renderDetails(tr, r, kind); return; }
      if (c.k === '_goc') { renderOriginal(tr, r, kind); return; }
      const td = tr.insertCell(); td.className = (c.n ? 'n ' : '') + (c.cls || '');
      const val = c.sum ? fmt(r[c.k]) : (r[c.k] ?? '');
      if (c.cls === 'ten' || c.cls === 'mh') { const d = el('div', 'clamp', val); d.title = val; td.append(d); }  // tối đa 2 dòng
      else td.textContent = val;
    });
  });
  const f = $('hFoot'); f.innerHTML = '';
  const c0 = f.insertCell(); c0.colSpan = 6; c0.textContent = view.length + ' HĐ' + (view.length !== hRows.length ? ' (lọc từ ' + hRows.length + ')' : '');
  visCols().filter(c => c.sum).forEach(c => { const x = f.insertCell(); x.className = 'n'; x.textContent = fmt(view.reduce((a, r) => a + (r[c.k] || 0), 0)); });
  f.insertCell().colSpan = visCols().filter(c => !c.sum).length - 4;
  const pg = $('hPager'); pg.innerHTML = '';
  [20, 50, 100, 0].forEach(n => { const b = el('button', 'sm sec' + (n === hSize ? ' cur' : ''), n ? String(n) : 'Tất cả'); if (n === hSize) b.style.color = '#fff';
    b.onclick = () => { hSize = n; hPage = 0; saveGrid(); renderInv(); }; pg.append(b); });
  pg.append(el('span', 'mute', ' Trang ' + (hPage + 1) + '/' + pages + ' '));
  const prev = el('button', 'sm sec', '‹'); prev.disabled = hPage === 0; prev.onclick = () => { hPage--; renderInv(); };
  const next = el('button', 'sm sec', '›'); next.disabled = hPage >= pages - 1; next.onclick = () => { hPage++; renderInv(); };
  pg.append(prev, next, el('span', 'mute tip', '  Mẹo: bấm vào dòng để chọn, Shift+bấm để chọn liên tiếp, bấm tiêu đề cột để sắp xếp.'));
  selInfo(view);
}
function renderDetails(tr, r, kind) {
    // Chi tiết: chỉ để xem XML (mở trong Tiện ích, có PDF thuế + tải file) và link tra cứu hoá đơn gốc nếu có
    const ct = tr.insertCell(); ct.className = 'ct';
    if (r.xml) { const x = fileLink(r.xml, 'XML'); x.dataset.cqt = '/cqt?' + new URLSearchParams({k: KEY, mst: $('hMst').value, kind, key: r.key}); ct.append(x); }
    else { const cq = el('a', 'lk', 'PDF thuế'); cq.target = '_blank'; cq.title = 'Bản thể hiện hoá đơn theo dữ liệu cổng thuế';  // chưa có XML
           cq.href = '/cqt?' + new URLSearchParams({k: KEY, mst: $('hMst').value, kind, key: r.key}); ct.append(cq); }
    const t = r.tra_cuu || {};
    if (t.url || t.code) {
      const a = el('a', 'lk', 'Tra cứu'); a.href = '#';
      a.title = (t.ncc || '') + (t.code ? '\n' + t.field + ': ' + t.code + ' (đã chép, dán vào trang tra cứu)' : '') +
                (twoBox(t) ? '\nMST bên bán: ' + r.mst : '');
      a.onclick = ev => { ev.preventDefault(); if (t.code && navigator.clipboard) navigator.clipboard.writeText(t.code).catch(() => {});
        if (twoBox(t)) lookupPanel(t, r);  // Viettel, M-invoice: trang tra cứu cần 2 ô – MST bên bán + mã
        if (t.url) window.open(t.url, '_blank'); else prompt((t.ncc || '') + ' – mã tra cứu', t.code); };
      ct.append(a);
    }
}
function renderOriginal(tr, r, kind) {
    // HĐ gốc: file người bán phát hành (PDF/XML gốc) + tải tự động / gắn tay
    const ct = tr.insertCell(); ct.className = 'ct';
    if (r.pdf) ct.append(fileLink(r.pdf, 'PDF gốc')); if (r.xml_goc) ct.append(fileLink(r.xml_goc, 'XML gốc'));
    const t = r.tra_cuu || {};
    const onlyXml = r.pdf && !r.xml_goc && r.can_fetch;  // đã có PDF gốc, còn thiếu XML gốc
    if ((r.need_goc && (r.pdf_url || r.can_fetch)) || (onlyXml && r.xml_goc_loi)) {
      const label = onlyXml ? (r.xml_goc_loi ? 'Thử lại XML gốc' : 'Tải XML gốc') : 'Tải HĐ gốc';
      const g = el('a', 'lk q-hdgoc', label); g.href = '#';
      g.title = onlyXml ? 'Đã có PDF gốc – tải thêm file XML gốc từ ' + t.ncc + (r.xml_goc_loi ? '\nLần trước: ' + r.xml_goc_loi : '')
                        : 'Tải PDF + XML gốc từ ' + t.ncc + ' rồi tự gắn vào hoá đơn';
      g.onclick = async ev => { ev.preventDefault();
        if (onlyXml || me.server || !r.pdf_url || !browserFirst(r)) {  // máy chủ / phần mềm tự tải (EasyInvoice, TS24, BKAV)
          g.textContent = 'Đang tải…'; $('hErr').textContent = '';
          try { const d = await post('/api/invoice/fetch-pdf', {mst: $('hMst').value, kind, key: r.key}); await loadInv();
                if (d.xml_err) $('hErr').textContent = r.khhdon + '/' + r.shdon + ': đã có PDF gốc, chưa lấy được XML gốc – ' + d.xml_err; return; }
          catch (e) { g.textContent = label; if (onlyXml || me.server || !r.pdf_url) { $('hErr').textContent = r.khhdon + '/' + r.shdon + ': ' + e.message; return; } } }
        if (!me.server) browserDownload(browserUrls([r])); };
      ct.append(g);
    }
}
// Ô chép nhanh các thông tin cần dán vào trang tra cứu (trang có xác thực "trượt hình" nên phải làm tay).
const twoBox = t => /^(Viettel|M-invoice)/.test(t.ncc || '');
function lookupPanel(t, r) {
  const box = $('hErr'); box.innerHTML = '';
  box.append(el('div', 'ok', (t.ncc || '') + ' – HĐ ' + r.khhdon + '/' + r.shdon + ': dán 2 thông tin dưới đây vào trang tra cứu' +
    ((t.ncc || '').startsWith('Viettel') ? ', kéo thanh trượt' : '') + ' để xem / tải hoá đơn.'));
  [['MST bên bán', r.mst], [t.field || 'Mã số bí mật', t.code]].forEach(([k, v]) => {
    if (!v) return;
    const line = el('div', 'mute'); line.append(k + ': ', el('b', '', v), ' ');
    const c = el('a', 'lk', 'Chép'); c.href = '#';
    c.onclick = ev => { ev.preventDefault(); navigator.clipboard && navigator.clipboard.writeText(v).then(() => { c.textContent = 'Đã chép'; }).catch(() => {}); };
    line.append(c); box.append(line);
  });
}
async function bulkUpdate(fields) {
  const keys = hRows.filter(r => hSel.has(r.key)).map(r => r.key);
  if (!keys.length || Object.values(fields).every(v => v === '')) return;
  try { await post('/api/invoice/update-bulk', Object.assign({mst: wsMst, kind: $('hKind').value.replace('_dv', ''), keys}, fields));
        hRows.forEach(r => { if (hSel.has(r.key)) Object.assign(r, fields); }); buildHead(); renderInv(); }
  catch (e) { $('hErr').textContent = e.message; }
}
async function bulkPdf() {
  const sel = hRows.filter(r => hSel.has(r.key)), src = sel.length ? sel : hRows;
  let need = src.filter(r => r.need_goc && (r.pdf_url || r.can_fetch));
  if (!need.length) { $('hErr').textContent = 'Không có hoá đơn nào cần tải hoá đơn gốc (hiện tự tải được: MISA, EasyInvoice, TS24, BKAV, M-invoice). Nhà cung cấp khác: bấm "Tra cứu" để xem trên trang của nhà cung cấp.'; return; }
  // Phần mềm tự tải (bản web: mọi nhà cung cấp); MISA bản máy: trình duyệt tải về Downloads rồi phần mềm tự gắn.
  const auto = need.filter(r => r.can_fetch && (me.server || !r.pdf_url || !browserFirst(r)));
  if (auto.length) {
    const b = $('hBulkPdf'); b.disabled = true;
    const {done, errs} = await fetchOriginals(auto, n => { b.textContent = 'Đang tải HĐ gốc ' + n + '/' + auto.length + '…'; });
    b.disabled = false; b.textContent = 'Tải HĐ gốc hàng loạt';
    await loadInv();
    const keys = new Set(need.map(r => r.key)); need = me.server ? [] : hRows.filter(r => keys.has(r.key) && r.need_goc && r.pdf_url);
    $('hErr').innerHTML = ''; $('hErr').append(el('div', 'ok', 'Đã tải và gắn hoá đơn gốc ' + done + '/' + auto.length + ' hoá đơn.'));
    errs.slice(0, 10).forEach(x => $('hErr').append(el('div', 'err', x)));
    if (!need.length) return;
  }
  if (!me.server) browserDownload(browserUrls(need));
}
// Phần mềm tự tải HĐ gốc cho các dòng (từng nhóm 10 HĐ). Trả {done, errs}.
async function fetchOriginals(rows, progress) {
  let done = 0; const errs = [];
  for (let i = 0; i < rows.length; i += 10) {
    progress(Math.min(i + 10, rows.length));
    try { const f = hFilters(false); f.keys = rows.slice(i, i + 10).map(r => r.key);
      const d = await post('/api/invoice/fetch-pdf-bulk', {mst: $('hMst').value, filters: f}); done += d.ok; errs.push(...d.errors); }
    catch (e) { errs.push(e.message); if (/hạn mức/i.test(e.message)) break; }
  }
  return {done, errs};
}
// MISA chặn chương trình tự động → bản máy để trình duyệt tải trước; nhà cung cấp khác phần mềm tự tải.
function browserFirst(r) { return (r.tra_cuu || {}).ncc_mst === '0101243150'; }
// Link trình duyệt tải PDF + XML gốc (phần nào còn thiếu).
function browserUrls(rows) {
  return rows.flatMap(r => [r.pdf ? '' : r.pdf_url, r.xml_goc ? '' : r.xml_url]).filter(Boolean);
}
// Trình duyệt tải PDF gốc về thư mục Downloads (MISA chặn chương trình tự động nhưng không chặn trình duyệt);
// phần mềm theo dõi Downloads và tự gắn file mới vào đúng hoá đơn.
let scanTimer = null, promptOk = false;
async function browserDownload(urls) {
  if (me.server || !urls.length) return;  // bản web: máy chủ tự tải, không tải qua trình duyệt
  if (urls.length > 1 && !promptOk) {
    try { const d = await post('/api/invoice/scan-downloads', {mst: $('hMst').value, info_only: true});
      if (d.prompt && !confirm('Chrome/Edge đang bật "Hỏi vị trí lưu từng tệp trước khi tải xuống" nên sẽ hiện hộp thoại Lưu cho TỪNG file.\n\n' +
          'Tắt một lần: mở tab mới, gõ  chrome://settings/downloads  (Edge: edge://settings/downloads)  rồi TẮT mục ' +
          '"Hỏi vị trí lưu từng tệp trước khi tải xuống".\n\nBấm OK để vẫn tải (phải bấm Lưu từng file), Cancel để đi tắt trước.')) return;
      promptOk = true;
    } catch (e) {}
  }
  const since = Date.now() / 1000;
  urls.forEach((u, i) => setTimeout(() => {
    const f = el('iframe'); f.style.display = 'none'; f.src = u; document.body.append(f); setTimeout(() => f.remove(), 120000);
  }, i * 1200));
  $('hErr').innerHTML = ''; $('hErr').append(el('div', 'ok', 'Trình duyệt đang tải ' + urls.length + ' file hoá đơn gốc (PDF/XML) về thư mục Downloads… ' +
    '(nếu Chrome hỏi "tải nhiều tệp", chọn Cho phép). Phần mềm sẽ tự gắn khi file về.'));
  const box = el('div', 'mute'); $('hErr').append(box);
  urls.slice(0, 50).forEach(u => { const a = el('a', 'lk', 'link'); a.href = u; a.target = '_blank'; box.append(a); });
  if (urls.length) box.prepend('Nếu không thấy tải, bấm từng link: ');
  let tries = 0, attached = 0; clearInterval(scanTimer); $('hBulkPdf').disabled = true;
  scanTimer = setInterval(async () => {
    tries++;
    try { const d = await post('/api/invoice/scan-downloads', {mst: $('hMst').value, since});
      const ok = d.results.filter(r => r.ok); attached += ok.length;
      if (ok.length) { loadInv(); }
      $('hBulkPdf').textContent = attached ? ('Đã gắn ' + attached + '/' + urls.length + ' file') : 'Đang chờ file tải về…';
      if (attached >= urls.length || tries > 20 + urls.length * 3) { clearInterval(scanTimer); $('hBulkPdf').textContent = 'Tải HĐ gốc hàng loạt'; $('hBulkPdf').disabled = false;
        if (attached < urls.length) $('hErr').append(el('div', 'err', 'Mới gắn được ' + attached + '/' + urls.length +
          ' file. Kiểm tra thư mục tải về (' + d.folder + ').')); }
    } catch (e) { clearInterval(scanTimer); $('hBulkPdf').disabled = false; $('hErr').append(el('div', 'err', e.message)); }
  }, 3000);
}
async function updInv(r, fields) {
  try { await post('/api/invoice/update', Object.assign({mst: $('hMst').value, kind: $('hKind').value.replace('_dv', ''), key: r.key}, fields));
        Object.assign(r, fields); } catch (e) { $('hErr').textContent = e.message; }
}
async function exportInv(fmtx) {
  if (!fmtx) return; $('hErr').textContent = '';
  const sel = hRows.filter(r => hSel.has(r.key)), src = sel.length ? sel : hRows;
  let errs = [], done = 0, need = [];
  if ((fmtx === 'pdf' || fmtx === 'all') && can('hdgoc')) {
    // kết xuất HĐ gốc: phần mềm tự tải trước những hoá đơn còn thiếu mà tải được (MISA, EasyInvoice, TS24, BKAV) rồi mới nén
    need = src.filter(r => r.need_goc && r.can_fetch);
    if (need.length) {
      ({done, errs} = await fetchOriginals(need, n => { $('hErr').textContent = 'Đang tải hoá đơn gốc còn thiếu ' + n + '/' + need.length + ' trước khi kết xuất…'; }));
      await loadInv();
    }
  }
  try { const d = await post('/api/export', {mst: $('hMst').value, filters: hFilters(true), fmt: fmtx}); window.open(d.url, '_blank');
        $('hErr').textContent = ''; }
  catch (e) { $('hErr').textContent = e.message; }
  if (need.length) {
    $('hErr').append(el('div', 'ok', 'Đã tự tải thêm hoá đơn gốc ' + done + '/' + need.length + ' hoá đơn trước khi kết xuất.'));
    errs.slice(0, 10).forEach(x => $('hErr').append(el('div', 'err', x)));
  }
  const miss = hRows.filter(r => (!sel.length || hSel.has(r.key)) && !r.pdf).length;
  if (fmtx === 'pdf' && miss) $('hErr').append(el('div', 'mute', miss + ' hoá đơn chưa có hoá đơn gốc (nhà cung cấp chưa hỗ trợ tự tải hoặc tải lỗi) – không có trong file ZIP.'));
}
['hFile', 'hDuyet', 'hTthai', 'hKq'].forEach(id => $(id).onchange = () => { hPage = 0; loadInv(); });
['hKh', 'hSo', 'hQ'].forEach(id => $(id).addEventListener('keydown', e => { if (e.key === 'Enter') { hPage = 0; loadInv(); } }));
// ---- Hàng hoá (như Nibot): danh mục tên hàng từ hoá đơn, gán Mã TK / Mã hàng cho kết xuất KTSC ----
let gItems = [], gSel = new Set(), gDirty = new Map(), gPage = 0, gGroups = null;
const G_SIZE = 100;
const gMsg = (t, cls) => { $('gErr').innerHTML = ''; if (t) $('gErr').append(el('div', cls || 'err', t)); };
async function gLoad() {
  gMsg('');
  try { gItems = (await post('/api/goods', {mst: wsMst, action: 'list'})).items; } catch (e) { gMsg(e.message); gItems = []; }
  gDirty.clear(); gSel.clear(); hide('gSave'); gGroups = null; gRender();
}
const gPlain = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
function gBigrams(s) { const t = ' ' + gPlain(s).replace(/\s+/g, ' ').trim() + ' ', m = new Map();
  for (let i = 0; i < t.length - 1; i++) { const b = t.slice(i, i + 2); m.set(b, (m.get(b) || 0) + 1); } return m; }
function gSimilar(a, b) {  // hệ số Dice trên cặp ký tự – 100 = giống hệt
  let inter = 0, na = 0, nb = 0; a.forEach(v => na += v); b.forEach(v => nb += v);
  a.forEach((v, k) => { inter += Math.min(v, b.get(k) || 0); }); return na + nb ? 200 * inter / (na + nb) : 0;
}
function gGroup(rows) {  // gom các tên giống nhau (>= ngưỡng %) vào cùng nhóm
  const th = Math.max(30, Math.min(100, +$('gSim').value || 80)), reps = [], gid = new Map();
  rows.forEach(r => { const bg = gBigrams(r.ten);
    let g = reps.find(x => gSimilar(x.bg, bg) >= th);
    if (!g) { g = {id: reps.length + 1, bg, n: 0}; reps.push(g); }
    g.n++; gid.set(r.id, g); });
  return gid;
}
function gView() {
  const loai = $('gLoai').value, pl = $('gPL').value, q = gPlain($('gQ').value.trim());
  let v = gItems.filter(r => (!loai || r.loai === loai) && (!pl || (pl === 'yes') === !!r.ma_hang) &&
    (!q || gPlain([r.ma_tk, r.ma_hang, r.ten, r.ghi_chu].join(' ')).includes(q)));
  v.sort((a, b) => a.ten.localeCompare(b.ten, 'vi'));
  gGroups = null;
  if ($('gNhom').checked) {
    gGroups = gGroup(v);
    v = v.filter(r => gGroups.get(r.id).n > 1).sort((a, b) => gGroups.get(a.id).id - gGroups.get(b.id).id);
  }
  return v;
}
function gEdit(r, k, v) { r[k] = v; const d = gDirty.get(r.id) || {id: r.id}; d[k] = v; gDirty.set(r.id, d); show('gSave'); }
function gRender() {
  const view = gView(), tb = $('gRows'), edit = can('sua'), dvt = $('gDvt').checked;
  document.querySelectorAll('.g-dvt').forEach(x => x.classList.toggle('hide', !dvt));
  const pages = Math.max(1, Math.ceil(view.length / G_SIZE)); gPage = Math.min(gPage, pages - 1);
  tb.innerHTML = ''; let lastG = null;
  view.slice(gPage * G_SIZE, gPage * G_SIZE + G_SIZE).forEach(r => {
    if (gGroups) { const g = gGroups.get(r.id);
      if (g !== lastG) { lastG = g; const tr = tb.insertRow(); tr.className = 'g-grp'; const td = tr.insertCell(); td.colSpan = 7;
        const cb = el('input'); cb.type = 'checkbox'; cb.title = 'Chọn cả nhóm';
        cb.onchange = () => { view.filter(x => gGroups.get(x.id) === g).forEach(x => cb.checked ? gSel.add(x.id) : gSel.delete(x.id)); gRender(); };
        cb.checked = view.filter(x => gGroups.get(x.id) === g).every(x => gSel.has(x.id));
        td.append(cb, ' Nhóm ' + g.id + ' (' + g.n + ' mặt hàng)'); } }
    const tr = tb.insertRow(); if (gSel.has(r.id)) tr.classList.add('sel');
    const pick = el('input'); pick.type = 'checkbox'; pick.checked = gSel.has(r.id);
    pick.onchange = () => { pick.checked ? gSel.add(r.id) : gSel.delete(r.id); tr.classList.toggle('sel', pick.checked); gCount(view); };
    tr.insertCell().append(pick);
    const inp = (k, w) => { const c = tr.insertCell(); if (!edit) { c.textContent = r[k]; return; }
      const i = el('input', 'note'); i.value = r[k] || ''; if (w) i.style.width = w; i.onchange = () => gEdit(r, k, i.value.trim()); c.append(i); };
    inp('ma_tk', '70px'); inp('ma_hang', '130px');
    const tn = tr.insertCell(); tn.textContent = r.ten; tn.style.minWidth = '280px';
    const dv = tr.insertCell(); dv.className = 'g-dvt' + (dvt ? '' : ' hide'); dv.textContent = (r.dvt || []).join(', ');
    const lc = tr.insertCell();
    if (edit) { const s = el('select'); ['', 'V', 'R', 'VR'].forEach(o => s.append(new Option(o || '(cả hai)', o))); s.value = r.loai || '';
      s.onchange = () => gEdit(r, 'loai', s.value); lc.append(s); } else lc.textContent = r.loai;
    inp('ghi_chu');
  });
  const pg = $('gPager'); pg.innerHTML = '';
  const prev = el('button', 'sm sec', '‹'); prev.disabled = gPage === 0; prev.onclick = () => { gPage--; gRender(); };
  const next = el('button', 'sm sec', '›'); next.disabled = gPage >= pages - 1; next.onclick = () => { gPage++; gRender(); };
  pg.append(el('span', 'mute', 'Trang ' + (gPage + 1) + '/' + pages + ' '), prev, next, el('span', 'mute', ' ' + view.length + ' hàng hoá' +
    (view.length !== gItems.length ? ' (lọc từ ' + gItems.length + ')' : '') + ' · ' + gItems.filter(x => x.ma_hang).length + ' đã có mã hàng'));
  pg.append(el('span', 'mute', '')); gCount(view);
  $('gAll').checked = view.length > 0 && view.every(r => gSel.has(r.id));
  $('gAll').onchange = () => { view.forEach(r => $('gAll').checked ? gSel.add(r.id) : gSel.delete(r.id)); gRender(); };
}
function gCount(view) { const s = $('gPager').lastChild; if (s) s.textContent = gSel.size ? '  · đã chọn ' + gSel.size + ' dòng' : ''; }
['gLoai', 'gPL', 'gDvt', 'gNhom', 'gSim'].forEach(id => $(id).addEventListener('change', () => { gPage = 0; gRender(); }));
$('gQ').addEventListener('input', () => { gPage = 0; gRender(); });
async function gSave() {
  try { const d = await post('/api/goods', {mst: wsMst, action: 'update', rows: [...gDirty.values()]});
        gMsg('Đã lưu ' + d.n + ' dòng.', 'ok'); gDirty.clear(); hide('gSave'); }
  catch (e) { gMsg(e.message); }
}
function gForm(title, html, onOk, okText) {  // khung nhập ngay trên bảng (thay cho hộp thoại)
  const b = $('gBox'); b.innerHTML = '<b>' + title + '</b><div style="margin-top:8px">' + html + '</div>';
  const bar = el('div', 'bar'); const ok = el('button', '', okText || 'Thực hiện'), no = el('button', 'sec', 'Đóng');
  no.onclick = () => hide('gBox');
  ok.onclick = async () => { ok.disabled = true; try { await onOk(); hide('gBox'); } catch (e) { gMsg(e.message); } ok.disabled = false; };
  bar.append(ok, no); b.append(bar); show('gBox');
}
const gIds = () => [...gSel];
function gSyncBox() {
  const y = new Date().getFullYear();
  gForm('Đồng bộ hàng hoá', `Thời gian: <input type="date" id="gsFrom" value="${y}-01-01"> → <input type="date" id="gsTo" value="${y}-12-31">
    &nbsp; <label><input type="checkbox" id="gsVao" checked> Đầu vào</label> <label><input type="checkbox" id="gsRa" checked> Đầu ra</label><br>
    <label><input type="radio" name="gsTc" checked> Tất cả hàng hoá có trong hoá đơn</label><br>
    <label><input type="radio" name="gsTc" id="gsSkip"> Không đồng bộ HH là chiết khấu hoặc (SL = 0 và thành tiền = 0)</label><br>
    <label><input type="checkbox" id="gsCode"> Gắn luôn mã hàng nếu có sẵn trong hoá đơn XML</label>
    <div class="hint">Tên hàng chưa có trong danh mục thì được thêm vào; mã đã gán giữ nguyên.</div>`, async () => {
    gMsg('Đang đồng bộ hàng hoá… (lâu nếu nhiều hoá đơn)', 'mute');
    const d = await post('/api/goods', {mst: wsMst, action: 'sync', from: $('gsFrom').value, to: $('gsTo').value,
      vao: $('gsVao').checked, ra: $('gsRa').checked, skip_zero: $('gsSkip').checked, xml_code: $('gsCode').checked});
    await gLoad(); gMsg('Đồng bộ hàng hoá hoàn tất: thêm ' + d.added + ' mặt hàng (tổng ' + d.total + ').', 'ok');
  }, 'Đồng bộ hàng hoá');
}
function gAssign(col) {
  if (!gSel.size) { gMsg('Chưa chọn dòng cần gán – tích chọn các dòng (hoặc cả nhóm) trước.'); return; }
  gForm(col === 'ma_tk' ? 'Gán Mã TK hàng loạt' : 'Gán Mã hàng hàng loạt', `
    Giá trị ban đầu: <input id="gaOld" placeholder="để trống nếu không cần quan tâm giá trị ban đầu" style="width:300px"><br>
    Giá trị thay thế: <input id="gaNew" style="width:300px"> &nbsp; <b>${gSel.size}</b> dòng đã chọn`, async () => {
    const d = await post('/api/goods', {mst: wsMst, action: 'assign', ids: gIds(), col, old: $('gaOld').value, new: $('gaNew').value});
    await gLoad(); gMsg('Đã thay thế ' + d.n + ' dòng.', 'ok');
  }, 'Thay thế');
  setTimeout(() => $('gaNew').focus(), 50);
}
function gAutoBox() {
  const n = gSel.size || gItems.filter(x => !x.ma_hang).length;
  gForm('Tạo mã hàng tự động (cho các mặt hàng chưa có mã)', `
    <label><input type="radio" name="gaK" value="1" checked> Kiểu 1: dạng HH1, HH2, HH3…</label>
      &nbsp; Tiền tố <input id="gaPre" value="HH" style="width:80px"> Độ dài chữ số <input id="gaDig" type="number" value="6" min="1" max="15" style="width:60px">
      <small class="err" id="gaEx"></small><br>
    <label><input type="radio" name="gaK" value="2"> Kiểu 2: không dấu, không khoảng cách</label><br>
    <label><input type="radio" name="gaK" value="3"> Kiểu 3: lấy ký tự đầu của các từ</label><br>
    Số dòng: <b>${n}</b> ${gSel.size ? '(đã chọn)' : '(tất cả chưa có mã)'}`, async () => {
    const kind = document.querySelector('input[name=gaK]:checked').value;
    const d = await post('/api/goods', {mst: wsMst, action: 'autocode', ids: gIds(), kind, prefix: $('gaPre').value, digits: +$('gaDig').value});
    await gLoad(); gMsg('Đã tạo mã cho ' + d.n + ' mặt hàng.', 'ok');
  }, 'Tạo mã hàng tự động');
  const ex = () => { $('gaEx').textContent = ' ví dụ: ' + $('gaPre').value + '1'.padStart(Math.min(15, +$('gaDig').value || 1), '0'); };
  $('gaPre').oninput = $('gaDig').oninput = ex; ex();
}
function gDelBox() {
  gForm('Xoá hàng hoá', `<label><input type="radio" name="gdM" value="0"> Xoá tất cả hàng hoá</label><br>
    <label><input type="radio" name="gdM" value="1"> Xoá hàng hoá chưa được gán mã</label><br>
    <label><input type="radio" name="gdM" value="2" checked> Xoá dòng hàng hoá đã được chọn (${gSel.size} dòng)</label>`, async () => {
    const mode = +document.querySelector('input[name=gdM]:checked').value;
    if (mode === 2 && !gSel.size) throw new Error('Chưa chọn dòng cần xoá');
    if (!confirm(['Xoá TẤT CẢ hàng hoá', 'Xoá hàng hoá chưa được gán mã', 'Xoá ' + gSel.size + ' dòng đã chọn'][mode] + '?')) return;
    const d = await post('/api/goods', {mst: wsMst, action: 'delete', mode, ids: gIds()});
    await gLoad(); gMsg('Đã xoá ' + d.n + ' mặt hàng.', 'ok');
  }, 'Xoá hàng hoá');
}
function gAdd() {
  gForm('Thêm hàng hoá', `Mã TK <input id="gnTk" style="width:80px"> Mã hàng <input id="gnMa" style="width:140px">
    Tên hàng <input id="gnTen" style="width:320px"> Loại HĐ <select id="gnLoai"><option value="">(cả hai)</option><option>V</option><option>R</option></select>`, async () => {
    await post('/api/goods', {mst: wsMst, action: 'update', rows: [{ma_tk: $('gnTk').value, ma_hang: $('gnMa').value, ten: $('gnTen').value, loai: $('gnLoai').value}]});
    await gLoad(); gMsg('Đã thêm hàng hoá.', 'ok');
  }, 'Lưu');
  setTimeout(() => $('gnTk').focus(), 50);
}
function gUnit() {
  const r = gItems.find(x => gSel.has(x.id)); openSub('quy-doi'); setTimeout(() => qAdd(r), 300);
}
async function gExport() {
  try { const d = await post('/api/goods', {mst: wsMst, action: 'export'}); window.open(d.url, '_blank'); } catch (e) { gMsg(e.message); }
}
$('gFile').onchange = async () => {
  const f = $('gFile').files[0]; $('gFile').value = ''; if (!f) return;
  const data = await readB64(f), go = async drop => post('/api/goods', {mst: wsMst, action: 'import', data, drop_dup: drop});
  gMsg('Đang import ' + f.name + '…', 'mute');
  try { let d;
    try { d = await go(false); }
    catch (e) { if (!/trùng tên/.test(e.message) || !confirm(e.message.slice(0, 900) + '\n\nImport và loại bỏ dòng trùng?')) throw e; d = await go(true); }
    await gLoad(); gMsg('Import xong: thêm ' + d.added + ', cập nhật ' + d.updated + ' mặt hàng.', 'ok');
  } catch (e) { gMsg(e.message); }
};
// ---- Bảng danh mục dùng chung (ĐT P.nhân, Quy đổi ĐVT): lọc, chọn dòng, sửa trực tiếp, lưu, import / export ----
function lpMake(cfg) { return Object.assign({items: [], sel: new Set(), dirty: new Map(), page: 0}, cfg); }
const lpMsg = (P, t, cls) => { $(P.p + 'Err').innerHTML = ''; if (t) $(P.p + 'Err').append(el('div', cls || 'err', t)); };
async function lpLoad(P) {
  lpMsg(P, '');
  try { P.items = (await post(P.api, {mst: wsMst, action: 'list'})).items; } catch (e) { lpMsg(P, e.message); P.items = []; }
  P.dirty.clear(); P.sel.clear(); hide(P.p + 'Save'); lpRender(P);
}
function lpRender(P) {
  const view = P.view(P.items), tb = $(P.p + 'Rows'), edit = can('sua'), size = 100;
  const head = $(P.p + 'Head'); head.innerHTML = ''; const hr = head.insertRow();
  const all = el('input'); all.type = 'checkbox'; all.checked = view.length > 0 && view.every(r => P.sel.has(r.id));
  all.onchange = () => { view.forEach(r => all.checked ? P.sel.add(r.id) : P.sel.delete(r.id)); lpRender(P); };
  const th0 = el('th'); th0.append(all); hr.append(th0); P.cols.forEach(c => hr.append(el('th', '', c.t)));
  const pages = Math.max(1, Math.ceil(view.length / size)); P.page = Math.min(P.page, pages - 1);
  tb.innerHTML = '';
  view.slice(P.page * size, P.page * size + size).forEach(r => {
    const tr = tb.insertRow(); if (P.sel.has(r.id)) tr.classList.add('sel');
    const pick = el('input'); pick.type = 'checkbox'; pick.checked = P.sel.has(r.id);
    pick.onchange = () => { pick.checked ? P.sel.add(r.id) : P.sel.delete(r.id); tr.classList.toggle('sel', pick.checked); };
    tr.insertCell().append(pick);
    P.cols.forEach(c => {
      const td = tr.insertCell(), set = v => { r[c.k] = v; const d = P.dirty.get(r.id) || {id: r.id}; d[c.k] = v; P.dirty.set(r.id, d); show(P.p + 'Save'); };
      if (!edit || c.type === 'text') { td.textContent = c.type === 'check' ? (r[c.k] ? '✓' : '') : (r[c.k] ?? ''); if (c.w) td.style.minWidth = c.w; return; }
      if (c.type === 'select') { const s = el('select'); c.opts.forEach(o => s.append(new Option(o[1], o[0]))); s.value = r[c.k] ?? ''; s.onchange = () => set(s.value); td.append(s); return; }
      if (c.type === 'check') { const b = el('input'); b.type = 'checkbox'; b.checked = !!r[c.k]; b.onchange = () => set(b.checked); td.append(b); return; }
      const i = el('input', 'note'); i.value = r[c.k] ?? ''; if (c.w) i.style.width = c.w; i.onchange = () => set(i.value.trim()); td.append(i);
    });
  });
  const pg = $(P.p + 'Pager'); pg.innerHTML = '';
  const prev = el('button', 'sm sec', '‹'); prev.disabled = P.page === 0; prev.onclick = () => { P.page--; lpRender(P); };
  const next = el('button', 'sm sec', '›'); next.disabled = P.page >= pages - 1; next.onclick = () => { P.page++; lpRender(P); };
  pg.append(el('span', 'mute', 'Trang ' + (P.page + 1) + '/' + pages + ' '), prev, next, el('span', 'mute', ' ' + P.count(view)));
}
async function lpSave(P) {
  try { const d = await post(P.api, {mst: wsMst, action: 'update', rows: [...P.dirty.values()]}); lpMsg(P, 'Đã lưu ' + d.n + ' dòng.', 'ok'); P.dirty.clear(); hide(P.p + 'Save'); }
  catch (e) { lpMsg(P, e.message); }
}
async function lpExport(P) { try { window.open((await post(P.api, {mst: wsMst, action: 'export'})).url, '_blank'); } catch (e) { lpMsg(P, e.message); } }
function lpForm(P, title, html, onOk, okText) {
  const b = $(P.p + 'Box'); b.innerHTML = '<b>' + title + '</b><div style="margin-top:8px">' + html + '</div>';
  const bar = el('div', 'bar'), ok = el('button', '', okText || 'Thực hiện'), no = el('button', 'sec', 'Đóng');
  no.onclick = () => hide(P.p + 'Box');
  ok.onclick = async () => { ok.disabled = true; try { await onOk(); hide(P.p + 'Box'); } catch (e) { lpMsg(P, e.message); } ok.disabled = false; };
  bar.append(ok, no); b.append(bar); show(P.p + 'Box');
}
function lpImport(P, input) {
  $(input).onchange = async () => { const f = $(input).files[0]; $(input).value = ''; if (!f) return;
    lpMsg(P, 'Đang import ' + f.name + '…', 'mute');
    try { const d = await post(P.api, {mst: wsMst, action: 'import', data: await readB64(f)});
          await lpLoad(P); lpMsg(P, 'Import xong: thêm ' + d.added + ', cập nhật ' + d.updated + ' dòng.', 'ok'); }
    catch (e) { lpMsg(P, e.message); } };
}
const lpQ = id => gPlain($(id).value.trim());

// ĐT P.nhân
const DT = lpMake({p: 'd', api: '/api/partners',
  cols: [{k: 'loai', t: 'Loại HĐ', type: 'text'}, {k: 'ten', t: 'Tên ĐTPN', type: 'text', w: '260px'}, {k: 'mst', t: 'MST / khách lẻ', type: 'text'},
         {k: 'ma_kh', t: 'Mã ĐTPN (Smart Pro)', w: '130px'},
         {k: 'duyet', t: 'Duyệt nội bộ', type: 'select', opts: [['', ''], ['Chờ duyệt', 'Chờ duyệt'], ['Đã duyệt', 'Đã duyệt'], ['Không duyệt', 'Không duyệt']]},
         {k: 'hd_dv', t: 'HĐ DV', type: 'check'}, {k: 'tk_tt', t: 'Mã TK TM/CK', w: '70px'}, {k: 'tk_cpdt', t: 'Mã TK CP/DT', w: '70px'},
         {k: 'sxkd', t: 'Loại HHDV SXKD', type: 'select', opts: [['', ''], ['1', '1 – dùng riêng'], ['2', '2 – dùng chung'], ['3', '3 – dự án']]}],
  view: items => { const loai = $('dLoai').value, pl = $('dPL').value, q = lpQ('dQ');
    return items.filter(r => (!loai || r.loai === loai) && (!pl || (pl === 'yes') === !!r.ma_kh) &&
      (!q || gPlain([r.ten, r.mst, r.ma_kh].join(' ')).includes(q))).sort((a, b) => (a.loai + a.ten).localeCompare(b.loai + b.ten, 'vi')); },
  count: v => v.length + ' đối tượng · ' + DT.items.filter(x => x.ma_kh).length + ' đã có mã'});
['dLoai', 'dPL'].forEach(id => $(id).onchange = () => { DT.page = 0; lpRender(DT); }); $('dQ').oninput = () => { DT.page = 0; lpRender(DT); };
lpImport(DT, 'dFile');
function dSyncBox() {
  const y = new Date().getFullYear();
  lpForm(DT, 'Đồng bộ ĐTPN từ hoá đơn', `Thời gian <input type="date" id="dsFrom" value="${y}-01-01"> → <input type="date" id="dsTo" value="${y}-12-31">
    <label><input type="checkbox" id="dsV" checked> Mua vào</label> <label><input type="checkbox" id="dsR" checked> Bán ra</label>
    <div class="hint">MST người bán / người mua chưa có trong danh sách thì được thêm vào (người mua không MST: lấy tên khách lẻ).</div>`, async () => {
    const d = await post(DT.api, {mst: wsMst, action: 'sync', from: $('dsFrom').value, to: $('dsTo').value, vao: $('dsV').checked, ra: $('dsR').checked});
    await lpLoad(DT); lpMsg(DT, 'Đồng bộ ĐTPN xong: thêm ' + d.added + ' (tổng ' + d.total + ').', 'ok'); }, 'Đồng bộ');
}
function dAssign() {
  if (!DT.sel.size) { lpMsg(DT, 'Chưa chọn dòng cần thay thế.'); return; }
  lpForm(DT, 'Thay thế hàng loạt (' + DT.sel.size + ' dòng)', `Cột <select id="daCol"><option value="MaKh">Mã ĐTPN</option><option value="MaTaiKhoan">Mã TK TM/CK</option><option value="MaTkCpdt">Mã TK CP/DT</option></select>
    Giá trị ban đầu <input id="daOld" placeholder="để trống nếu không quan tâm"> Giá trị thay thế <input id="daNew">`, async () => {
    const d = await post(DT.api, {mst: wsMst, action: 'assign', ids: [...DT.sel], col: $('daCol').value, old: $('daOld').value, new: $('daNew').value});
    await lpLoad(DT); lpMsg(DT, 'Đã thay thế ' + d.n + ' dòng.', 'ok'); }, 'Thay thế');
}
function dAutoBox() {
  lpForm(DT, 'Tạo mã ĐTPN tự động (cho đối tượng chưa có mã' + (DT.sel.size ? ', ' + DT.sel.size + ' dòng đã chọn' : '') + ')', `
    <label><input type="radio" name="dK" value="1" checked> Kiểu 1: lấy MST hoặc tên cá nhân mua làm mã ĐTPN</label><br>
    <label><input type="radio" name="dK" value="2"> Kiểu 2: lấy 5 ký tự cuối của MST – kiểu của Smart Pro</label><br>
    <label><input type="radio" name="dK" value="3"> Kiểu 3: không dấu, không khoảng cách từ tên công ty / tên cá nhân mua</label><br>
    Tiền tố <input id="dPre" style="width:80px"> Hậu tố <input id="dSuf" style="width:80px"> <small class="mute">vd: tiền tố 131 → 131&lt;mã&gt;</small>`, async () => {
    const d = await post(DT.api, {mst: wsMst, action: 'autocode', ids: [...DT.sel], kind: document.querySelector('input[name=dK]:checked').value,
      prefix: $('dPre').value, suffix: $('dSuf').value});
    await lpLoad(DT); lpMsg(DT, 'Đã tạo mã cho ' + d.n + ' đối tượng.', 'ok'); }, 'Tạo mã');
}
function dAdd() {
  lpForm(DT, 'Thêm ĐTPN', `Loại <select id="dnL"><option value="V">V – mua vào</option><option value="R">R – bán ra</option></select>
    MST hoặc tên khách lẻ <input id="dnM"> Tên <input id="dnT" style="width:260px"> Mã ĐTPN <input id="dnK">`, async () => {
    await post(DT.api, {mst: wsMst, action: 'update', rows: [{loai: $('dnL').value, mst: $('dnM').value, ten: $('dnT').value, ma_kh: $('dnK').value}]});
    await lpLoad(DT); lpMsg(DT, 'Đã thêm.', 'ok'); }, 'Lưu');
}
function dDelBox() {
  lpForm(DT, 'Xoá ĐTPN', `<label><input type="radio" name="ddM" value="0"> Xoá tất cả</label><br>
    <label><input type="radio" name="ddM" value="1"> Xoá đối tượng chưa có mã ĐTPN</label><br>
    <label><input type="radio" name="ddM" value="2" checked> Xoá dòng đã chọn (${DT.sel.size} dòng)</label>`, async () => {
    const mode = +document.querySelector('input[name=ddM]:checked').value;
    if (mode === 2 && !DT.sel.size) throw new Error('Chưa chọn dòng cần xoá');
    if (!confirm('Xoá ĐTPN?')) return;
    const d = await post(DT.api, {mst: wsMst, action: 'delete', mode, ids: [...DT.sel]}); await lpLoad(DT); lpMsg(DT, 'Đã xoá ' + d.n + ' dòng.', 'ok'); }, 'Xoá');
}
function dRemarkBox() {
  lpForm(DT, 'Đánh dấu lại hoá đơn hàng loạt theo ĐTPN', `Năm <input type="number" id="drY" value="${new Date().getFullYear()}" style="width:90px">
    <label><input type="radio" name="drW" value="dv" checked> Tính chất hoá đơn (HĐ dịch vụ / HĐ thường theo cột HĐ DV)</label>
    <label><input type="radio" name="drW" value="duyet"> Duyệt nội bộ (theo cột Duyệt nội bộ, chỉ ĐTPN có đặt)</label>`, async () => {
    const d = await post(DT.api, {mst: wsMst, action: 'remark', year: +$('drY').value, what: document.querySelector('input[name=drW]:checked').value});
    lpMsg(DT, 'Đã đánh dấu lại ' + d.n + ' hoá đơn.', 'ok'); }, 'Đánh dấu');
}

// Quy đổi ĐVT
const QD = lpMake({p: 'q', api: '/api/units',
  cols: [{k: 'ma_hang', t: 'Mã hàng', type: 'text'}, {k: 'ten', t: 'Tên hàng', type: 'text', w: '280px'},
         {k: 'dvt', t: 'ĐVT gốc (hoá đơn)', w: '90px'}, {k: 'dvt_qd', t: 'ĐVT đích (quy đổi)', w: '90px'}, {k: 'sl', t: 'Số lượng quy đổi', w: '80px'}],
  view: items => { const q = lpQ('qQ'); return items.filter(r => !q || gPlain([r.ma_hang, r.ten, r.dvt, r.dvt_qd].join(' ')).includes(q)); },
  count: v => v.length + ' quy đổi'});
$('qQ').oninput = () => { QD.page = 0; lpRender(QD); };
lpImport(QD, 'qFile');
async function qAdd(pre) {
  let goods = gItems;
  if (!goods.length) { try { goods = gItems = (await post('/api/goods', {mst: wsMst, action: 'list'})).items; } catch (e) {} }
  const opts = goods.map(g => '<option value="' + (g.ma_hang ? g.ma_hang + ' || ' : '') + g.ten.replace(/"/g, '&quot;') + '">').join('');
  lpForm(QD, 'Thêm quy đổi ĐVT', `Hàng hoá <input id="qnH" list="qnList" style="width:360px" placeholder="nhập mã hàng, tên hàng cần quy đổi"><datalist id="qnList">${opts}</datalist><br>
    ĐVT gốc <input id="qnG" style="width:90px"> ĐVT đích (quy đổi) <input id="qnD" style="width:90px"> Số lượng (quy đổi) <input id="qnS" style="width:80px">`, async () => {
    const v = $('qnH').value.trim(), m = v.split(' || '), g = goods.find(x => (x.ma_hang ? x.ma_hang + ' || ' : '') + x.ten === v);
    if (!v) throw new Error('Chưa chọn hàng hoá');
    await post(QD.api, {mst: wsMst, action: 'update', rows: [{ma_hang: g ? g.ma_hang : (m.length > 1 ? m[0] : ''), ten: g ? g.ten : m[m.length - 1],
      dvt: $('qnG').value, dvt_qd: $('qnD').value, sl: $('qnS').value}]});
    await lpLoad(QD); lpMsg(QD, 'Đã thêm quy đổi.', 'ok'); }, 'Lưu');
  if (pre) { $('qnH').value = (pre.ma_hang ? pre.ma_hang + ' || ' : '') + pre.ten; $('qnG').value = (pre.dvt || [])[0] || ''; }
}
async function qDel() {
  const n = QD.sel.size;
  if (!confirm(n ? 'Xoá ' + n + ' dòng đã chọn?' : 'Chưa chọn dòng nào – xoá TẤT CẢ quy đổi ĐVT? (nên Export ra 1 bản trước)')) return;
  try { const d = await post(QD.api, {mst: wsMst, action: 'delete', ids: [...QD.sel]}); await lpLoad(QD); lpMsg(QD, 'Đã xoá ' + d.n + ' dòng.', 'ok'); }
  catch (e) { lpMsg(QD, e.message); }
}
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
  poll().then(() => { if (location.hash.length > 2) route(); });
})();
</script></body></html>
"""


AUTO_USER = {"username": "__tu-dong__", "ten": "Đồng bộ tự động", "role": "admin", "msts": [], "active": True}
AUTO_KY = {"auto": "Tháng này (tới ngày 20 gồm cả tháng trước)", "7": "7 ngày gần nhất", "month": "Tháng này",
           "2month": "Tháng này và tháng trước"}


def auto_range(ky, today=None):
    today = today or date.today()
    prev_first = (today.replace(day=1) - timedelta(days=1)).replace(day=1)
    if ky == "7":
        return today - timedelta(days=7), today
    if ky == "month":
        return today.replace(day=1), today
    if ky == "2month" or today.day <= 20:
        return prev_first, today
    return today.replace(day=1), today


def auto_sync_loop(app, interval=30):
    """Mỗi ngày đến giờ đã đặt thì đồng bộ tự động (bản web)."""
    while True:
        time.sleep(interval)
        for vp in [t["id"] for t in app.tenants.list() if t.get("active")]:
            try:
                a = app.settings(vp)["auto"]
                now = datetime.now()
                if not a.get("on") or now.strftime("%H:%M") < a.get("gio", "06:00"):
                    continue
                if (a.get("lan_cuoi") or "")[:10] == now.strftime("%Y-%m-%d"):
                    continue
                app.run_auto(wait=False, vp=vp)  # các văn phòng chạy song song, mỗi văn phòng một lượt
            except Exception as e:  # không để vòng lặp chết
                print("Đồng bộ tự động (%s) lỗi: %s" % (vp, e))


LOCAL_USER = {"username": "admin", "ten": "Máy này", "role": "admin", "msts": [], "active": True}
SESSION_TTL = 12 * 3600      # phiên đăng nhập bản web hết hạn sau 12 giờ không dùng
LOGIN_WINDOW = 900           # đăng nhập sai quá LOGIN_MAX lần trong 15 phút → tạm khoá
LOGIN_MAX = 8


class App:
    def __init__(self, out_root, client_factory=None, server=False):
        self.out_root = out_root
        self.server = server  # True: bản web nhiều người dùng (máy chủ); False: chạy trên máy, không cần đăng nhập
        cfg = os.path.join(out_root, "_cau-hinh")
        self.cfg = cfg
        self.lock = threading.RLock()  # RLock: t() được gọi cả khi đang giữ khoá (cài đặt, thông báo)
        self.tenants = TenantStore(os.path.join(cfg, "van-phong.json"))
        self._tctx = {}
        self.store = self.t(DEFAULT_VP).store       # văn phòng chính (bản chạy trên máy chỉ có văn phòng này)
        self.users = UserStore(os.path.join(cfg, "nguoi-dung.json"))  # tên đăng nhập duy nhất trên cả máy chủ
        self.solver = CaptchaSolver(os.path.join(cfg, "captcha-mau.json"))
        self.client_factory = client_factory or HoaDonClient
        self.clients = self.t(DEFAULT_VP).clients  # MST → client đã đăng nhập (giữ phiên)
        self.downloads = default_downloads_dir()  # nơi trình duyệt lưu PDF gốc tải về
        self.seen_downloads = set()
        self.key = secrets.token_urlsafe(24)
        self.jobs = {}          # tên đăng nhập → Job (mỗi người một lượt đồng bộ riêng)
        self.sessions = {}      # mã phiên (cookie) → {user, csrf, exp}
        self.fails = {}         # IP / tên đăng nhập → các lần đăng nhập sai gần đây
        self.allowed_hosts = set()  # bản web: tên miền được phép (rỗng = không kiểm tra)
        self.trust_proxy = False    # chạy sau Caddy/Nginx: tin X-Forwarded-For / X-Forwarded-Proto
        self.max_jobs = 4           # số lượt đồng bộ chạy cùng lúc trên máy chủ
        self._lic = None            # bản web: cấp phép cho bản cài trên máy khách (tạo khi cần)
        self.lic_client = None      # bản cài máy: kết nối bản quyền tới máy chủ (LicenseClient)

    @property
    def lic(self):
        with self.lock:
            if self._lic is None:
                self._lic = Licenses(self)
            return self._lic

    # ---- văn phòng ----
    def t(self, vid=DEFAULT_VP):
        vid = vid or DEFAULT_VP
        with self.lock:
            if vid not in self._tctx:
                root = self.out_root if vid == DEFAULT_VP else os.path.join(self.out_root, "_vp", vid)
                self._tctx[vid] = Tenant(vid, root)
            return self._tctx[vid]

    def tenant_of(self, user):
        return self.t(user_vp(user))

    def vp_info(self, vid):
        return self.tenants.get(vid or DEFAULT_VP) or {}

    def quota(self, vid):
        return Quota(self.tenants, self.t(vid))

    # ---- thông báo hệ thống (chủ hệ thống đặt, mọi văn phòng thấy) ----
    def system(self):
        d = {"thong_bao": "", "bo_qua_mtt": False, "goi_y": ""}
        d.update(_load_json(os.path.join(self.cfg, "he-thong.json"), {}))
        return d

    def save_system(self, **f):
        with self.lock:
            d = self.system()
            for k in ("thong_bao", "goi_y"):
                if f.get(k) is not None:
                    d[k] = str(f[k]).strip()[:2000]
            for k in ("phien_ban_moi", "link_tai", "phien_ban_toi_thieu"):  # bản cài trên máy khách
                if f.get(k) is not None:
                    d[k] = str(f[k]).strip()[:300]
            if f.get("bo_qua_mtt") is not None:
                d["bo_qua_mtt"] = bool(f["bo_qua_mtt"])
            _save_json(os.path.join(self.cfg, "he-thong.json"), d)
            return d

    # ---- email (SMTP do chủ hệ thống cấu hình) ----
    def mail_settings(self, raw=False):
        d = _load_json(os.path.join(self.cfg, "email.json"), {})
        d.setdefault("port", 587)
        d.setdefault("mode", "starttls")
        if not raw:
            d = {k: v for k, v in d.items() if k != "pw"}
            d["co_mk"] = bool(_load_json(os.path.join(self.cfg, "email.json"), {}).get("pw"))
        return d

    def save_mail(self, **f):
        with self.lock:
            d = self.mail_settings(raw=True)
            for k in ("host", "user", "from_addr", "from_name", "site", "mode"):
                if f.get(k) is not None:
                    d[k] = str(f[k]).strip()[:200]
            if f.get("port") is not None:
                d["port"] = int(f["port"] or 587)
            if f.get("pw"):
                d["pw"] = protect(str(f["pw"]))
            if d.get("mode") not in ("ssl", "starttls", "none"):
                d["mode"] = "starttls"
            if d.get("site") and not re.match(r"https?://", d["site"]):
                d["site"] = "https://" + d["site"]
            path = os.path.join(self.cfg, "email.json")
            _save_json(path, d)
            try:
                os.chmod(path, 0o600)
            except OSError:
                pass
            return self.mail_settings()

    def mail_ready(self):
        d = self.mail_settings(raw=True)
        return bool(d.get("host") and (d.get("from_addr") or d.get("user")))

    def send_mail(self, to, subject, body, wait=False):
        """Gửi email (chạy nền để không làm chậm trang). wait=True: gửi ngay, lỗi thì ném ValueError (nút Gửi thử)."""
        def go():
            import smtplib
            from email.message import EmailMessage
            from email.utils import formataddr
            d = self.mail_settings(raw=True)
            msg = EmailMessage()
            sender = d.get("from_addr") or d.get("user")
            msg["From"] = formataddr((d.get("from_name") or "Tải hoá đơn", sender))
            msg["To"] = to
            msg["Subject"] = subject
            msg.set_content(body)
            cls = smtplib.SMTP_SSL if d.get("mode") == "ssl" else smtplib.SMTP
            with cls(d["host"], int(d.get("port") or 587), timeout=30) as smtp:
                if d.get("mode") == "starttls":
                    smtp.starttls()
                if d.get("user"):
                    smtp.login(d["user"], unprotect(d.get("pw", "")))
                smtp.send_message(msg)
        if not self.mail_ready():
            if wait:
                raise ValueError("Chưa cấu hình máy chủ gửi email (SMTP)")
            return False
        if wait:
            try:
                go()
            except Exception as e:
                raise ValueError("Gửi email lỗi: %s" % e)
            return True

        def bg():
            try:
                go()
            except Exception as e:
                self.audit("email", "gửi tới %s lỗi: %s" % (to, e))
        threading.Thread(target=bg, daemon=True).start()
        return True

    def new_reset(self, username, hours=0.5):
        import hashlib
        tok = secrets.token_urlsafe(32)
        path = os.path.join(self.cfg, "dat-lai-mat-khau.json")
        with self.lock:
            d = {k: v for k, v in _load_json(path, {}).items() if v["exp"] > time.time()}
            d[hashlib.sha256(tok.encode()).hexdigest()] = {"u": username, "exp": time.time() + hours * 3600}
            _save_json(path, d)
        return tok

    def use_reset(self, tok, consume=True):
        import hashlib
        path = os.path.join(self.cfg, "dat-lai-mat-khau.json")
        h = hashlib.sha256(str(tok or "").encode()).hexdigest()
        with self.lock:
            d = _load_json(path, {})
            v = d.get(h)
            if not v or v["exp"] < time.time():
                return None
            if consume:
                d.pop(h)
                _save_json(path, d)
            return v["u"]

    def job_for(self, user):
        with self.lock:
            return self.jobs.setdefault(user["username"], Job())

    @property
    def job(self):  # bản chạy trên máy: một người dùng
        return self.job_for(LOCAL_USER)

    def running_jobs(self, vp=None):
        with self.lock:
            return sum(1 for j in self.jobs.values() if j.running and (vp is None or getattr(j, "vp", DEFAULT_VP) == vp))

    def audit(self, who, action, vp=DEFAULT_VP):
        try:
            cfg = self.t(vp).cfg
            os.makedirs(cfg, exist_ok=True)
            with open(os.path.join(cfg, "nhat-ky.log"), "a", encoding="utf-8") as f:
                f.write("%s\t%s\t%s\n" % (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), who, action))
        except OSError:
            pass

    # ---- đăng nhập bản web ----
    def too_many_fails(self, *keys):
        now = time.time()
        with self.lock:
            for k in keys:
                self.fails[k] = [t for t in self.fails.get(k, []) if now - t < LOGIN_WINDOW]
            return any(len(self.fails[k]) >= LOGIN_MAX for k in keys)

    def add_fail(self, *keys):
        with self.lock:
            for k in keys:
                self.fails.setdefault(k, []).append(time.time())

    def new_session(self, username):
        sid, csrf = secrets.token_urlsafe(32), secrets.token_urlsafe(24)
        with self.lock:
            now = time.time()
            for k in [k for k, v in self.sessions.items() if v["exp"] < now]:
                del self.sessions[k]
            self.sessions[sid] = {"user": username, "csrf": csrf, "exp": now + SESSION_TTL}
        return sid

    def session_user(self, sid):
        if not sid:
            return None, None
        with self.lock:
            s = self.sessions.get(sid)
            if not s or s["exp"] < time.time():
                self.sessions.pop(sid, None)
                return None, None
            s["exp"] = time.time() + SESSION_TTL
        u = self.users.get(s["user"])
        if not u or not u.get("active", True) or not self.vp_active(u):
            self.drop_sessions(s["user"])
            return None, None
        return u, s["csrf"]

    def vp_active(self, user):
        return bool(self.vp_info(user_vp(user)).get("active", False))

    def drop_sessions(self, username, keep=None):
        with self.lock:
            for k in [k for k, v in self.sessions.items() if v["user"] == username and k != keep]:
                del self.sessions[k]

    def visible(self, user):
        return [c for c in self.tenant_of(user).store.public() if self.users.can(user, c["mst"])]

    # ---- chạy đồng bộ ----
    def start_job(self, user, msts, kinds, start, end, mtt=True, xml=True, test=False, pdf=False, unattended=False):
        lic_quota = None
        if self.lic_client is not None and not test:  # bản cài máy: phải còn hiệu lực trên máy chủ cấp phép
            try:
                self.lic_client.status()
            except LicenseError as e:
                raise ValueError(str(e))
            lic_quota = RemoteQuota(self.lic_client)
        job = Job()
        job.running = True
        job.want_pdf = pdf
        job.unattended = unattended
        job.owner = user["username"]
        job.vp = user_vp(user)
        if self.server:
            job.quota = self.quota(job.vp)
        elif lic_quota:
            job.quota = lic_quota
        with self.lock:
            self.jobs[user["username"]] = job
        t = threading.Thread(target=self._run_job, daemon=True,
                             args=(job, msts, kinds, start, end, mtt, xml, test))
        t.start()
        return job, t

    def _run_job(self, job, msts, kinds, start, end, mtt, xml, test):
        T = self.t(job.vp)
        if mtt and self.server and self.system()["bo_qua_mtt"]:
            mtt = False
            job.say("Tạm bỏ qua hoá đơn từ máy tính tiền (trang thuế đang lỗi phần này – theo thông báo hệ thống). "
                    "Khi trang thuế ổn định, đồng bộ lại để lấy đủ.")
        run_batch(job, T.store, self.solver, msts, kinds, start, end, mtt, xml, T.root, test,
                  self.client_factory, T.clients)
        if not test:
            try:
                self.post_notices(job)
            except Exception as e:  # thông báo lỗi không được làm hỏng lượt đồng bộ
                job.say("Không ghi được thông báo: %s" % e)

    # ---- thông báo (chuông) ----
    def _notes_path(self, vp=DEFAULT_VP):
        return os.path.join(self.t(vp).cfg, "thong-bao.json")

    def notify(self, mst, text, level="info", vp=DEFAULT_VP):
        path = self._notes_path(vp)
        with self.lock:
            data = _load_json(path, {})
            seq = data.get("seq", 0) + 1
            items = data.get("items", [])
            items.append({"id": seq, "t": datetime.now().strftime("%d/%m/%Y %H:%M"), "mst": mst, "text": text,
                          "level": level})
            data.update(seq=seq, items=items[-2000:])
            _save_json(path, data)

    def notices_for(self, user, limit=100):
        path = self._notes_path(user_vp(user))
        with self.lock:
            data = _load_json(path, {})
        read = data.get("read", {}).get(user["username"], 0)
        items = [n for n in data.get("items", []) if not n["mst"] or self.users.can(user, n["mst"])]
        return {"items": items[::-1][:limit], "unread": sum(1 for n in items if n["id"] > read)}

    def mark_read(self, user):
        path = self._notes_path(user_vp(user))
        with self.lock:
            data = _load_json(path, {})
            data.setdefault("read", {})[user["username"]] = data.get("seq", 0)
            _save_json(path, data)

    def post_notices(self, job):
        """Sau mỗi lượt đồng bộ: báo HĐ đổi trạng thái (huỷ, thay thế, điều chỉnh); lượt tự động báo thêm HĐ mới và lỗi."""
        per = {}
        for r in job.rows:
            if r.get("mst"):
                per.setdefault(r["mst"], []).append(r)
        who = "Đồng bộ tự động" if job.unattended else "Đồng bộ (%s)" % job.owner
        vp = getattr(job, "vp", DEFAULT_VP)
        store = self.t(vp).store
        for mst, rows in per.items():
            c = store.get(mst) or {}
            name = "%s (%s)" % (c.get("ten") or "", mst)
            changed = [r for r in rows if r["dongbo"] == "Đổi trạng thái"]
            new = [r for r in rows if r["dongbo"] == "Mới"]
            if changed:
                ex = ", ".join("%s %s%s/%s – %s" % (r["loai"], r["mau"], r["kh"], r["so"], r.get("tthai") or "?")
                               for r in changed[:5])
                self.notify(mst, "%s: %s có %d HĐ đổi trạng thái: %s%s" % (
                    who, name, len(changed), ex, " …" if len(changed) > 5 else ""), "warn", vp)
            if new and job.unattended:
                self.notify(mst, "%s: %s có %d HĐ mới" % (who, name, len(new)), vp=vp)
        for g in getattr(job, "gaps", []):
            self.notify(g.split(" ")[0], "%s: trang thuế lỗi, THIẾU dữ liệu %s – đồng bộ lại sau" % (who, g), "warn", vp)
        if job.unattended:
            for line in job.log:
                m = re.match(r"\S+\s+(\d{10}(?:-\d{3})?): LỖI (.*)", line)
                if m:
                    c = store.get(m.group(1)) or {}
                    self.notify(m.group(1), "%s: %s (%s) lỗi – %s" % (who, c.get("ten") or "", m.group(1), m.group(2)),
                                "err", vp)

    # ---- cài đặt đồng bộ tự động ----
    def _settings_path(self, vp=DEFAULT_VP):
        return os.path.join(self.t(vp).cfg, "cai-dat.json")

    def settings(self, vp=DEFAULT_VP):
        d = _load_json(self._settings_path(vp), {})
        auto = {"on": False, "gio": "06:00", "ky": "auto", "lan_cuoi": ""}
        auto.update(d.get("auto") or {})
        return {"auto": auto}

    def save_auto(self, vp=DEFAULT_VP, **fields):
        with self.lock:
            d = _load_json(self._settings_path(vp), {})
            auto = self.settings(vp)["auto"]
            auto.update({k: v for k, v in fields.items() if v is not None})
            d["auto"] = auto
            _save_json(self._settings_path(vp), d)
            return auto

    @staticmethod
    def auto_user(vp=DEFAULT_VP):
        return dict(AUTO_USER, vp=vp, username=AUTO_USER["username"] + ("" if vp == DEFAULT_VP else "@" + vp))

    def run_auto(self, wait=True, vp=DEFAULT_VP):
        """Đồng bộ tự động tất cả DN đang hiển thị, có mật khẩu (mua vào + bán ra theo cài đặt từng DN)."""
        au = self.auto_user(vp)
        job = self.jobs.get(au["username"])
        if job and job.running:
            return job
        msts = [c["mst"] for c in self.t(vp).store.public() if not c.get("an") and c.get("co_mk")]
        start, end = auto_range(self.settings(vp)["auto"]["ky"])
        self.save_auto(vp, lan_cuoi=datetime.now().strftime("%Y-%m-%d %H:%M"))
        job, t = self.start_job(au, msts, ["purchase", "sold"], start, end, unattended=True)
        job.say("Đồng bộ tự động %d doanh nghiệp, từ %s đến %s" % (
            len(msts), start.strftime("%d/%m/%Y"), end.strftime("%d/%m/%Y")))
        if wait:
            t.join()
        return job


def make_handler(app, port):
    allowed_hosts = {"127.0.0.1:%d" % port, "localhost:%d" % port}
    COOKIE = "thd_sid"

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
            self._sec_headers()
            for h in getattr(self, "_extra", []):
                self.send_header(*h)
            self.end_headers()
            self.wfile.write(body)

        def _sec_headers(self):
            self.send_header("X-Frame-Options", "DENY")
            self.send_header("X-Content-Type-Options", "nosniff")
            self.send_header("Referrer-Policy", "same-origin")

        def _https(self):
            return app.trust_proxy and (self.headers.get("X-Forwarded-Proto", "").lower() == "https"
                                        or self.headers.get("X-Forwarded-Ssl", "").lower() == "on")

        def _public_host(self):
            """Tên miền người dùng gõ; nginx (FASTPANEL) có thể đổi Host thành 127.0.0.1 và giữ tên thật ở X-Forwarded-Host."""
            if app.trust_proxy and self.headers.get("X-Forwarded-Host"):
                return self.headers["X-Forwarded-Host"].split(",")[0].strip()
            return self.headers.get("Host") or ""

        def _host_ok(self):
            if self._public_host().rsplit(":", 1)[0].lower() in app.allowed_hosts:
                return True
            # nginx của bảng điều khiển (FASTPANEL…) có thể gửi Host 127.0.0.1:cổng – chấp nhận khi app chỉ nghe trong máy
            host = (self.headers.get("Host") or "").rsplit(":", 1)[0]
            return (app.trust_proxy and not self.headers.get("X-Forwarded-Host")
                    and host in ("127.0.0.1", "localhost") and self.client_address[0] == "127.0.0.1")

        def _ip(self):
            if app.trust_proxy and self.headers.get("CF-Connecting-IP"):  # tên miền đi qua Cloudflare
                return self.headers["CF-Connecting-IP"].strip()
            if app.trust_proxy and self.headers.get("X-Forwarded-For"):
                return self.headers["X-Forwarded-For"].split(",")[-1].strip()
            return self.client_address[0]

        def _cookie(self, name):
            import http.cookies
            try:
                c = http.cookies.SimpleCookie(self.headers.get("Cookie", ""))
            except http.cookies.CookieError:
                return ""
            return c[name].value if name in c else ""

        def _set_cookie(self, value, max_age):
            self._extra = getattr(self, "_extra", []) + [("Set-Cookie", "%s=%s; Path=/; HttpOnly; SameSite=Lax; Max-Age=%d%s" % (
                COOKIE, value, max_age, "; Secure" if self._https() else ""))]

        def _k(self):
            """Khoá đặt trong link /file, /view (chỉ bản chạy trên máy; bản web dùng cookie đăng nhập)."""
            return "" if app.server else app.key

        def _guard(self):
            # Chặn DNS rebinding và yêu cầu giả mạo từ trang web khác.
            self._extra = []
            host = self.headers.get("Host") or ""
            if app.server:
                if app.allowed_hosts and not self._host_ok():
                    self._send(403, {"error": "Host không hợp lệ"})
                    return False
                self.user, self.csrf = app.session_user(self._cookie(COOKIE))
                self.T = app.tenant_of(self.user) if self.user else app.t(DEFAULT_VP)
            else:
                if host not in allowed_hosts:
                    self._send(403, {"error": "Host không hợp lệ"})
                    return False
                self.user, self.csrf = LOCAL_USER, app.key
                self.T = app.t(DEFAULT_VP)
            path = self.path.split("?")[0]
            if path.startswith("/api/lic/") and app.server:
                return True
            if path.startswith("/api/") and path not in ("/api/login", "/api/forgot", "/api/reset"):
                if not self.user:
                    self._send(401, {"error": "Phiên đăng nhập đã hết – tải lại trang để đăng nhập lại", "login": True})
                    return False
                if not secrets.compare_digest(self.headers.get("X-App-Key", ""), self.csrf):
                    self._send(403, {"error": "Phiên giao diện không hợp lệ, hãy tải lại trang"})
                    return False
            return True

        def _site(self):
            """Địa chỉ trang web dùng trong link gửi email."""
            site = app.mail_settings().get("site")
            if site:
                return site.rstrip("/")
            host = self._public_host()
            if host.split(":")[0] in ("127.0.0.1", "localhost") and app.allowed_hosts:
                host = sorted(app.allowed_hosts)[0]
            return ("https" if (self._https() or app.trust_proxy) else "http") + "://" + host

        def _mail_user(self, u, kind):
            """kind: 'doi' (báo đã đổi mật khẩu), 'moi' (mời tự đặt mật khẩu), 'quen' (quên mật khẩu)."""
            if not u or not u.get("email"):
                return False
            vp = app.vp_info(user_vp(u)).get("ten", "")
            if kind == "doi":
                return app.send_mail(u["email"], "Mật khẩu tài khoản %s đã được đổi" % u["username"],
                                     "Chào %s,\n\nMật khẩu đăng nhập phần mềm Tải hoá đơn (%s) của tài khoản %s vừa được đổi "
                                     "lúc %s.\n\nNếu không phải bạn đổi, hãy báo ngay cho quản trị văn phòng hoặc dùng "
                                     "\"Quên mật khẩu\" tại %s để đặt lại.\n" % (u.get("ten") or u["username"], vp, u["username"],
                                                                         datetime.now().strftime("%H:%M %d/%m/%Y"), self._site()))
            hours = 24 if kind == "moi" else 0.5
            link = "%s/dat-lai-mat-khau?t=%s" % (self._site(), app.new_reset(u["username"], hours))
            if kind == "moi":
                return app.send_mail(u["email"], "Tài khoản phần mềm Tải hoá đơn – %s" % vp,
                                     "Chào %s,\n\nBạn được tạo tài khoản phần mềm Tải hoá đơn (%s).\nTên đăng nhập: %s\n\n"
                                     "Bấm link sau để tự đặt mật khẩu (hiệu lực 24 giờ):\n%s\n" % (
                                         u.get("ten") or u["username"], vp, u["username"], link))
            return app.send_mail(u["email"], "Đặt lại mật khẩu phần mềm Tải hoá đơn",
                                 "Chào %s,\n\nCó yêu cầu đặt lại mật khẩu cho tài khoản %s.\nBấm link sau để đặt mật khẩu "
                                 "mới (hiệu lực 30 phút):\n%s\n\nNếu không phải bạn yêu cầu, hãy bỏ qua email này.\n" % (
                                     u.get("ten") or u["username"], u["username"], link))

        def _audit(self, action):
            app.audit(self.user["username"], action, self.T.id)

        def _perm(self, p):
            if p not in UserStore.perms(self.user):
                raise PermissionError("Tài khoản chưa được cấp quyền: " + PERMS[p])

        def _owner(self):
            if not (app.server and self.user.get("owner") and user_vp(self.user) == DEFAULT_VP):
                raise PermissionError("Chỉ chủ hệ thống mới được làm việc này")

        def _auto_state(self):
            a = app.settings(self.T.id)["auto"]
            job = app.jobs.get(app.auto_user(self.T.id)["username"])
            a["ky_text"] = AUTO_KY.get(a["ky"], "")
            a["running"] = bool(job and job.running)
            if job:
                a["current"], a["dn"] = job.current, getattr(job, "dn", None)
                a["log"] = job.log[-30:]
            return a

        def _can(self, mst):
            return bool(mst) and app.users.can(self.user, mst) and self.T.store.get(mst) is not None

        def _admin(self):
            if self.user["role"] != "admin":
                raise PermissionError("Chỉ quản trị mới được làm việc này")

        def _body(self):
            n = int(self.headers.get("Content-Length") or 0)
            if n > 80000000:
                raise ValueError("Dữ liệu gửi lên quá lớn (tối đa ~60MB một lần) – chọn ít file hơn")
            try:
                return json.loads(self.rfile.read(n).decode("utf-8") or "{}")
            except ValueError:
                return {}

        def do_GET(self):
            path = self.path.split("?")[0]
            if app.server and path in ("/tai-ve", "/tai-ve/", "/tai-ve/" + EXE_NAME):  # trang tải bản exe (công khai)
                if app.allowed_hosts and not self._host_ok():
                    return self._send(403, {"error": "Host không hợp lệ"})
                self._extra = []
                info = exe_info(app)
                if path.endswith(".exe"):
                    if not info:
                        return self._send(404, {"error": "Chưa có file cài đặt – liên hệ nhà cung cấp"})
                    with open(exe_path(app), "rb") as f:
                        body = f.read()
                    self._extra = [("Content-Disposition", 'attachment; filename="%s"' % EXE_NAME)]
                    return self._send(200, body, "application/octet-stream")
                site = ("https" if (self._https() or app.trust_proxy) else "http") + "://" + self._public_host()
                btn = ('<p><a class=b href="/tai-ve/%s">Tải TaiHoaDon.exe%s</a> <span class=m>%.1f MB · cập nhật %s</span></p>'
                       % (EXE_NAME, (" " + info["version"]) if info.get("version") else "", info["size"] / 1048576, info["time"])
                       if info else "<p><b>Chưa có file cài đặt – liên hệ nhà cung cấp.</b></p>")
                return self._send(200, (DOWNLOAD_PAGE % (btn, site)).encode("utf-8"), "text/html; charset=utf-8")
            if not self._guard():
                return
            if path == "/chan-doan.zip":  # chủ hệ thống: tải các trang lỗi của nhà cung cấp để gửi người hỗ trợ
                if not self.user or not (self.user.get("owner") or not app.server):
                    return self._send(403, {"error": "Không có quyền"})
                buf = io.BytesIO()
                with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
                    for n in sorted(os.listdir(DEBUG_DIR)) if DEBUG_DIR and os.path.isdir(DEBUG_DIR) else []:
                        z.write(os.path.join(DEBUG_DIR, n), n)
                    z.writestr("phien-ban.txt", __version__)
                self._extra = [("Content-Disposition", 'attachment; filename="chan-doan.zip"')]
                return self._send(200, buf.getvalue(), "application/zip")
            if path == "/dat-lai-mat-khau":  # link trong email: trang đặt mật khẩu mới
                return self._send(200, LOGIN_PAGE.encode("utf-8"), "text/html; charset=utf-8")
            if path == "/":
                if not self.user:
                    return self._send(200, LOGIN_PAGE.encode("utf-8"), "text/html; charset=utf-8")
                return self._send(200, PAGE.replace("__TOKEN__", self.csrf).encode("utf-8"), "text/html; charset=utf-8")
            if path == "/api/state":
                u = self.user
                return self._send(200, {"companies": app.visible(u), "job": app.job_for(u).snapshot(),
                                        "me": {"username": u["username"], "ten": u.get("ten", ""), "role": u["role"],
                                               "server": app.server, "quyen": UserStore.perms(u),
                                               "owner": bool(app.server and u.get("owner") and user_vp(u) == DEFAULT_VP),
                                               "vp": self.T.id, "vp_ten": app.vp_info(self.T.id).get("ten", ""),
                                               "goi": app.quota(self.T.id).info() if app.server else
                                               ((app.lic_client.info().get("goi") or None) if app.lic_client else None),
                                               "lic": app.lic_client.state() if app.lic_client and not app.server else None,
                                               "email": u.get("email", "")},
                                        "sys": dict(app.system(), portal=PORTAL_HEALTH.status(), mtt=MTT_HEALTH.status())
                                        if app.server else None,
                                        "unread": app.notices_for(u, 0)["unread"] if app.server else 0,
                                        "auto": self._auto_state() if app.server and u["role"] == "admin" else None,
                                        "captcha": {"count": app.solver.count(), "chars": app.solver.chars()},
                                        "version": __version__})
            if path == "/file":
                return self._file()
            if path == "/view":
                q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(self.path).query))
                if not self._file_ok(q):
                    return self._send(403, {"error": "Không có quyền"})
                try:
                    page = render_invoice_html(self.T.root, q["mst"], q.get("kind"), q.get("key"))
                except (ValueError, OSError) as e:
                    return self._send(404, {"error": str(e)})
                return self._send(200, page.encode("utf-8"), "text/html; charset=utf-8")
            if path == "/cqt":  # PDF bản thể hiện theo dữ liệu cổng thuế (tạo khi cần, lưu lại)
                q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(self.path).query))
                if not self._file_ok(q):
                    return self._send(403, {"error": "Không có quyền"})
                try:
                    rel = tax_pdf(self.T.root, q["mst"], q.get("kind"), q.get("key"), self.T.clients.get(q["mst"]))
                    with open(os.path.join(company_dir(self.T.root, q["mst"]), rel), "rb") as f:
                        body = f.read()
                except (ValueError, OSError) as e:
                    return self._send(404, {"error": str(e)})
                self._extra = [("Content-Disposition", 'inline; filename="%s"' % os.path.basename(rel))]
                return self._send(200, body, "application/pdf")
            self._send(404, {"error": "Không tìm thấy"})

        def _file_ok(self, q):
            if not self.user or not self._can(q.get("mst", "")):
                return False
            return app.server or secrets.compare_digest(q.get("k", ""), app.key)

        def _file(self):
            """Mở file đã tải (XML/HTML/PDF/Excel/ZIP) – chỉ trong thư mục của doanh nghiệp."""
            q = dict(urllib.parse.parse_qsl(urllib.parse.urlparse(self.path).query))
            if not self._file_ok(q):
                return self._send(403, {"error": "Không có quyền"})
            base = os.path.realpath(company_dir(self.T.root, q["mst"]))
            full = os.path.realpath(os.path.join(base, q.get("p", "")))
            if not full.startswith(base + os.sep) or not os.path.isfile(full):
                return self._send(404, {"error": "Không tìm thấy file"})
            ext = os.path.splitext(full)[1].lower()
            ctype = {".xml": "application/xml", ".html": "text/html; charset=utf-8", ".htm": "text/html; charset=utf-8",
                     ".pdf": "application/pdf", ".zip": "application/zip",
                     ".xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"}.get(ext, "application/octet-stream")
            with open(full, "rb") as fh:
                body = fh.read()
            name = os.path.basename(full)
            if q.get("zip") == "1":  # nén trước khi tải: trình duyệt hay chặn / cảnh báo file .xml tải trực tiếp
                buf = io.BytesIO()
                with zipfile.ZipFile(buf, "w", zipfile.ZIP_DEFLATED) as z:
                    z.writestr(name, body)
                body, ext, ctype = buf.getvalue(), ".zip", "application/zip"
                name = os.path.splitext(name)[0] + ".zip"
            self.send_response(200)
            self.send_header("Content-Type", ctype)
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Cache-Control", "private, no-store")
            self._sec_headers()
            if ext in (".zip", ".xlsx"):
                self.send_header("Content-Disposition", 'attachment; filename="%s"' % name)
            if ext in (".html", ".htm"):
                self.send_header("Content-Security-Policy", "script-src 'none'")  # HTML hoá đơn chỉ để xem
            self.end_headers()
            self.wfile.write(body)

        def do_POST(self):
            if not self._guard():
                return
            path = self.path.split("?")[0]
            try:
                return self._post(path, self._body())
            except PermissionError as e:
                return self._send(403, {"error": str(e)})
            except ValueError as e:
                return self._send(400, {"error": str(e)})

        def _auth_post(self, path, data):
            """Đăng nhập / đăng xuất / đổi mật khẩu / quản lý người dùng (bản web)."""
            if path == "/api/login":
                if not app.server:
                    return self._send(200, {"ok": True})
                origin = self.headers.get("Origin")
                o = urllib.parse.urlparse(origin or "")
                if origin and o.netloc.lower() != self._public_host().lower() \
                        and (o.hostname or "").lower() not in app.allowed_hosts:
                    return self._send(403, {"error": "Yêu cầu không hợp lệ"})
                name = str(data.get("username") or "").strip().lower()[:60]
                ip = self._ip()
                if app.too_many_fails("ip:" + ip, "u:" + name):
                    return self._send(429, {"error": "Đăng nhập sai quá nhiều lần – thử lại sau 15 phút"})
                u = app.users.authenticate(name, str(data.get("password") or ""))
                if u and not app.vp_active(u):
                    return self._send(403, {"error": "Văn phòng của tài khoản này đang bị khoá – liên hệ nhà cung cấp phần mềm"})
                if not u:
                    app.add_fail("ip:" + ip, "u:" + name)
                    app.audit(name or "?", "đăng nhập sai từ " + ip)
                    return self._send(401, {"error": "Sai tên đăng nhập hoặc mật khẩu"})
                with app.lock:
                    app.fails.pop("u:" + name, None)
                self._set_cookie(app.new_session(u["username"]), SESSION_TTL)
                app.audit(u["username"], "đăng nhập từ " + ip, user_vp(u))
                return self._send(200, {"ok": True})
            if path in ("/api/forgot", "/api/reset"):
                if not app.server:
                    raise ValueError("Bản chạy trên máy không có đăng nhập")
                ip = self._ip()
                if app.too_many_fails("ip:" + ip):
                    return self._send(429, {"error": "Thử quá nhiều lần – thử lại sau 15 phút"})
                if path == "/api/forgot":
                    app.add_fail("ip:" + ip)  # giới hạn số lần gửi email
                    q = str(data.get("username") or "").strip().lower()[:100]
                    u = app.users.get(q) or next((x for x in app.users.items if (x.get("email") or "").lower() == q), None)
                    if u and u.get("active", True) and app.vp_active(u) and u.get("email"):
                        self._mail_user(u, "quen")
                        app.audit(u["username"], "yêu cầu đặt lại mật khẩu từ " + ip, user_vp(u))
                    # luôn trả lời giống nhau để không lộ tài khoản / email nào có thật
                    return self._send(200, {"ok": True, "mail": app.mail_ready()})
                name = app.use_reset(data.get("token"), consume=False)
                if not name:
                    app.add_fail("ip:" + ip)
                    raise ValueError("Link đã hết hạn hoặc đã dùng – bấm \"Quên mật khẩu\" để nhận link mới")
                if data.get("check"):
                    return self._send(200, {"ok": True, "username": name})
                if not str(data.get("password") or ""):
                    raise ValueError("Nhập mật khẩu mới")
                app.users.upsert(name, password=str(data["password"]))
                app.use_reset(data.get("token"))
                app.drop_sessions(name)
                u = app.users.get(name)
                app.audit(name, "đặt lại mật khẩu qua email từ " + ip, user_vp(u))
                self._mail_user(u, "doi")
                return self._send(200, {"ok": True, "username": name})
            if path == "/api/logout":
                sid = self._cookie(COOKIE)
                with app.lock:
                    app.sessions.pop(sid, None)
                self._set_cookie("", 0)
                return self._send(200, {"ok": True})
            if path == "/api/me/password":
                if not app.server:
                    raise ValueError("Bản chạy trên máy không có mật khẩu đăng nhập")
                if not UserStore.check(str(data.get("old") or ""), self.user["pw"]):
                    raise ValueError("Mật khẩu hiện tại không đúng")
                app.users.upsert(self.user["username"], password=str(data.get("new") or "") or None)
                app.drop_sessions(self.user["username"], keep=self._cookie(COOKIE))
                self._audit("đổi mật khẩu")
                self._mail_user(app.users.get(self.user["username"]), "doi")
                return self._send(200, {"ok": True})
            if path == "/api/me/email":
                if not app.server:
                    raise ValueError("Bản chạy trên máy không có email")
                app.users.upsert(self.user["username"], email=str(data.get("email") or ""))
                return self._send(200, {"ok": True})
            if path.startswith("/api/vp/") or path.startswith("/api/mail/") or path in ("/api/sys/save", "/api/sys/exe"):
                return self._vp_post(path, data)
            self._admin()
            if path == "/api/devices":  # quản trị văn phòng: máy đã cài bản trên máy và đăng nhập
                return self._send(200, {"items": app.lic.devices(self.T.id) if app.server else {},
                                        "max": app.vp_info(self.T.id).get("max_may", DEFAULT_MAX_MAY)})
            if path == "/api/device/remove":
                app.lic.remove_device(self.T.id, str(data.get("device") or ""))
                self._audit("gỡ máy cài phần mềm %s" % data.get("device"))
                return self._send(200, {"ok": True})
            if path == "/api/users":
                return self._send(200, {"users": app.users.public(self.T.id), "perms": PERMS,
                                        "companies": [{"mst": c["mst"], "ten": c["ten"]} for c in self.T.store.public()]})
            if path == "/api/user/save":
                msts = data.get("msts")
                if msts is not None:
                    msts = [m for m in msts if self.T.store.get(m)]
                other = app.users.get(data.get("username"))
                if other and user_vp(other) != self.T.id:
                    raise ValueError("Tên đăng nhập %s đã có người dùng (văn phòng khác) – chọn tên khác"
                                     % str(data.get("username")).lower())
                if data.get("create") and other:
                    raise ValueError("Tên đăng nhập %s đã tồn tại" % str(data.get("username")).lower())
                owner = None
                if data.get("owner") is not None and self.user.get("owner") and self.T.id == DEFAULT_VP:
                    owner = bool(data["owner"])
                    if not owner and str(data.get("username") or "").lower() == self.user["username"]:
                        raise ValueError("Không tự bỏ quyền chủ hệ thống của chính mình")
                invite = bool(data.get("create")) and not data.get("password") and bool(data.get("email"))
                if invite and not app.mail_ready():
                    raise ValueError("Máy chủ chưa cấu hình gửi email – nhập mật khẩu cho người dùng mới")
                u = app.users.upsert(data.get("username"), ten=data.get("ten"), role=data.get("role"),
                                     password=data.get("password") or None, msts=msts, active=data.get("active"),
                                     create=bool(data.get("create")), vp=self.T.id, quyen=data.get("quyen"), owner=owner,
                                     email=data.get("email"), invite=invite)
                if invite or data.get("send_link"):
                    self._mail_user(u, "moi" if invite else "quen")
                elif data.get("password") and not data.get("create"):
                    self._mail_user(u, "doi")
                if data.get("password") or not u.get("active", True):
                    app.drop_sessions(u["username"], keep=self._cookie(COOKIE))
                self._audit("lưu người dùng " + u["username"])
                return self._send(200, {"ok": True})
            if path == "/api/user/delete":
                if str(data.get("username") or "").lower() == self.user["username"]:
                    raise ValueError("Không tự xoá tài khoản đang đăng nhập")
                other = app.users.get(data.get("username"))
                if not other or user_vp(other) != self.T.id:
                    raise ValueError("Không tìm thấy người dùng")
                app.users.delete(data.get("username"))
                app.drop_sessions(str(data.get("username") or "").lower())
                self._audit("xoá người dùng %s" % data.get("username"))
                return self._send(200, {"ok": True})
            self._send(404, {"error": "Không tìm thấy"})

        def _vp_post(self, path, data):
            """Chủ hệ thống: quản lý các văn phòng (khách hàng dùng phần mềm). Không xem được hoá đơn của văn phòng khác."""
            self._owner()
            if path == "/api/sys/save":
                d = app.save_system(thong_bao=data.get("thong_bao"), goi_y=data.get("goi_y"), bo_qua_mtt=data.get("bo_qua_mtt"),
                                    phien_ban_moi=data.get("phien_ban_moi"), link_tai=data.get("link_tai"),
                                    phien_ban_toi_thieu=data.get("phien_ban_toi_thieu"))
                self._audit("cập nhật thông báo hệ thống")
                return self._send(200, d)
            if path == "/api/sys/exe":  # chủ hệ thống tải lên file TaiHoaDon.exe để khách tải ở /tai-ve
                if data.get("info_only"):
                    return self._send(200, {"exe": exe_info(app)})
                import base64
                try:
                    raw = base64.b64decode(data.get("data") or "", validate=True)
                except ValueError:
                    raise ValueError("Dữ liệu file không hợp lệ")
                if raw[:2] != b"MZ" or len(raw) < 1000000:
                    raise ValueError("Không phải file TaiHoaDon.exe (Windows)")
                os.makedirs(os.path.dirname(exe_path(app)), exist_ok=True)
                tmp = exe_path(app) + ".tmp"
                with open(tmp, "wb") as f:
                    f.write(raw)
                os.replace(tmp, exe_path(app))
                ver = str(data.get("version") or "").strip()[:20]
                _save_json(exe_path(app) + ".json", {"version": ver})
                self._audit("tải lên TaiHoaDon.exe %s" % ver)
                return self._send(200, {"exe": exe_info(app)})
            if path == "/api/vp/devices":  # máy đã kích hoạt bản cài của một văn phòng
                return self._send(200, {"items": app.lic.devices(str(data.get("id") or ""))})
            if path == "/api/vp/device-remove":
                app.lic.remove_device(str(data.get("id") or ""), str(data.get("device") or ""))
                self._audit("gỡ máy %s của văn phòng %s" % (data.get("device"), data.get("id")))
                return self._send(200, {"ok": True})
            if path == "/api/mail/get":
                return self._send(200, app.mail_settings())
            if path == "/api/mail/save":
                return self._send(200, app.save_mail(**{k: data.get(k) for k in (
                    "host", "port", "user", "pw", "from_addr", "from_name", "site", "mode")}))
            if path == "/api/mail/test":
                to = str(data.get("to") or self.user.get("email") or "")
                if not EMAIL_RE.fullmatch(to):
                    raise ValueError("Nhập email nhận thử")
                app.send_mail(to, "Thử gửi email – phần mềm Tải hoá đơn",
                              "Email thử từ %s. Cấu hình gửi email đã hoạt động.\n" % self._site(), wait=True)
                return self._send(200, {"ok": True})
            if path == "/api/vp/list":
                rows = []
                for t in app.tenants.list():
                    T = app.t(t["id"])
                    us = app.users.public(t["id"])
                    rows.append(dict(t, so_dn=len(T.store.public()), so_nd=len(us), **{
                                         k: v for k, v in app.quota(t["id"]).info().items() if k.startswith("da_dung")},
                                     quan_tri=", ".join(u["username"] for u in us if u["role"] == "admin"),
                                     so_may=len(app.lic.devices(t["id"])),
                                     dang_chay=app.running_jobs(t["id"])))
                return self._send(200, {"items": rows})
            if path == "/api/vp/save":
                create = bool(data.get("create"))
                vid = str(data.get("id") or "").strip().lower()
                admin = str(data.get("admin") or "").strip().lower()
                if create:
                    invite = not data.get("admin_pw") and bool(data.get("admin_email")) and app.mail_ready()
                    if not admin or not (data.get("admin_pw") or invite):
                        raise ValueError("Nhập tên đăng nhập và mật khẩu (hoặc email, khi đã cấu hình gửi email) cho quản trị "
                                         "đầu tiên của văn phòng")
                    if app.users.get(admin):
                        raise ValueError("Tên đăng nhập %s đã tồn tại – chọn tên khác" % admin)
                    if not invite and len(str(data.get("admin_pw"))) < 8:
                        raise ValueError("Mật khẩu phần mềm tối thiểu 8 ký tự")
                    if data.get("admin_email") and not EMAIL_RE.fullmatch(str(data["admin_email"]).strip()):
                        raise ValueError("Email quản trị không hợp lệ")
                    if not re.fullmatch(r"[a-z0-9][a-z0-9._@-]{2,39}", admin):
                        raise ValueError("Tên đăng nhập 3–40 ký tự: chữ thường không dấu, số, . _ - @")
                t = app.tenants.upsert(vid, create=create, ten=data.get("ten"), ghichu=data.get("ghichu"),
                                       max_dn=data.get("max_dn"), max_jobs=data.get("max_jobs"), active=data.get("active"),
                                       han_muc_hd=data.get("han_muc_hd"), tai_goc=data.get("tai_goc"),
                                       han_muc_goc=data.get("han_muc_goc"), max_may=data.get("max_may"),
                                       het_han=data.get("het_han"))
                for k in ("hd", "goc"):  # gia hạn gói: đặt lại số đã dùng
                    if data.get("dat_lai_" + k):
                        app.quota(t["id"]).reset(k)
                        self._audit("đặt lại số %s đã dùng của văn phòng %s" % ("HĐ" if k == "hd" else "HĐ gốc", t["id"]))
                if create:
                    u = app.users.upsert(admin, ten=data.get("admin_ten") or "Quản trị", role="admin",
                                         password=str(data.get("admin_pw") or "") or None, create=True, vp=t["id"],
                                         email=str(data.get("admin_email") or ""), invite=invite)
                    if invite:
                        self._mail_user(u, "moi")
                if t.get("active") is False:
                    for u in app.users.public(t["id"]):
                        app.drop_sessions(u["username"])
                self._audit("%s văn phòng %s (%s)" % ("tạo" if create else "sửa", t["id"], t["ten"]))
                return self._send(200, {"ok": True, "vp": t})
            self._send(404, {"error": "Không tìm thấy"})

        def _check_max_dn(self, adding=1):
            lim = app.vp_info(self.T.id).get("max_dn") or 0
            if lim and len(self.T.store.public()) + adding > lim:
                raise ValueError("Văn phòng đã đủ %d doanh nghiệp theo gói – liên hệ nhà cung cấp để nâng gói" % lim)

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
            if name in ("bank-parse", "bank-export"):
                self._perm("saoke")
            if name == "bank-parse":
                infos, errors = [], []
                for f in (data.get("files") or [])[:24]:
                    try:
                        infos.append(parse_bank_statement(f.get("name") or "sao-ke", b64(f.get("data"))))
                    except ValueError as e:
                        errors.append(str(e))
                rows, dups = merge_bank_statements(infos)
                for i in infos:
                    i.pop("rows")
                return self._send(200, {"statements": infos, "rows": rows, "dups": dups, "errors": errors})
            if name == "bank-export":
                rows = [r for r in (data.get("rows") or []) if isinstance(r, dict) and r.get("date")]
                if not rows:
                    raise ValueError("Chưa có giao dịch nào")
                for r in rows:
                    r["in"], r["out"] = _float(r.get("in")), _float(r.get("out"))
                    datetime.strptime(str(r["date"]), "%Y-%m-%d")
                out = write_bank_ktsc(rows, data.get("opts") or {})
                ds = sorted(r["date"] for r in rows)
                label = safe_name("_".join(x for x in ("SAO_KE", str(data.get("bank") or ""), str(data.get("account") or ""),
                                                       ds[0].replace("-", ""), ds[-1].replace("-", "")) if x))
                return self._send(200, {"name": label + ".xlsx", "data": base64.b64encode(out).decode()})
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

        def _lic_post(self, path, data):
            """API cấp phép cho bản cài trên máy khách. Lỗi trả về {error, lic: true} để máy con biết phải đăng nhập lại."""
            ver, dev, ip = str(data.get("version") or ""), str(data.get("device_id") or ""), self._ip()
            try:
                if path == "/api/lic/login":
                    name = str(data.get("username") or "").strip().lower()[:60]
                    if app.too_many_fails("ip:" + ip, "u:" + name):
                        return self._send(429, {"error": "Đăng nhập sai quá nhiều lần – thử lại sau 15 phút"})
                    try:
                        r = app.lic.login(name, str(data.get("password") or ""), dev, data.get("device_name"), ver, ip)
                    except LookupError as e:
                        app.add_fail("ip:" + ip, "u:" + name)
                        app.audit(name or "?", "máy con đăng nhập sai từ " + ip)
                        return self._send(401, {"error": str(e), "lic": True})
                    app.audit(name, "kích hoạt / đăng nhập máy %s (%s) từ %s" % (str(data.get("device_name") or "")[:40], dev[:8], ip))
                    return self._send(200, r)
                u, vid = app.lic.session(data.get("token"), dev, ver, ip)
                if path == "/api/lic/status":
                    return self._send(200, {"info": app.lic.info(vid, u)})
                if path == "/api/lic/take":  # xin tải hoá đơn mới (mã băm) – trừ hạn mức, mỗi hoá đơn tính 1 lần
                    return self._send(200, app.lic.take(vid, data.get("keys") or []))
                if path == "/api/lic/goc":  # hoá đơn gốc: xin / hoàn lại 1 lượt
                    q = app.quota(vid)
                    if data.get("refund"):
                        q.refund_goc()
                        return self._send(200, {"ok": True})
                    err = q.goc_error()
                    return self._send(200, {"ok": not err and q.take_goc(), "error": err})
                if path == "/api/lic/logout":
                    app.lic.remove_device(vid, dev)
                    return self._send(200, {"ok": True})
                return self._send(404, {"error": "Không tìm thấy"})
            except LicenseBlocked as e:
                return self._send(403, {"error": str(e)})
            except PermissionError as e:
                return self._send(403, {"error": str(e), "lic": True})

        def _post(self, path, data):
            if path.startswith("/api/lic/") and app.server:
                return self._lic_post(path, data)
            if path.startswith("/api/banquyen/") and not app.server:  # bản cài máy: bản quyền + sao lưu
                lc = app.lic_client
                try:
                    if path == "/api/banquyen/backup":
                        return self._send(200, backup_data(app.out_root, data.get("dir") or default_backup_dir()))
                    if lc is None:
                        raise ValueError("Bản này không dùng bản quyền máy chủ")
                    if path == "/api/banquyen/update":
                        st = lc.state()
                        if not st.get("update"):
                            raise ValueError("Đang dùng phiên bản mới nhất (%s)" % __version__)
                        r = self_update(st["update"]["url"], st.get("server"))
                        return self._send(200, dict(r, version=st["update"]["version"]))
                    if path == "/api/banquyen/login":
                        lc.login(data.get("server"), str(data.get("username") or "").strip(), str(data.get("password") or ""))
                    elif path == "/api/banquyen/logout":
                        lc.logout()
                    elif path == "/api/banquyen/status":
                        lc.status()
                    return self._send(200, lc.state())
                except (LicenseError, ValueError, OSError) as e:
                    return self._send(400, {"error": str(e), "state": lc.state() if lc else None})
            if path in ("/api/login", "/api/logout", "/api/me/password", "/api/users", "/api/user/save",
                        "/api/user/delete", "/api/vp/list", "/api/vp/save", "/api/forgot", "/api/reset", "/api/me/email",
                        "/api/mail/get", "/api/mail/save", "/api/mail/test", "/api/sys/save", "/api/vp/devices", "/api/sys/exe",
                        "/api/vp/device-remove", "/api/devices", "/api/device/remove"):
                return self._auth_post(path, data)
            if path == "/api/company/save":
                existing = self.T.store.get(str(data.get("mst", "")).strip())
                if existing and not self._can(existing["mst"]):
                    raise PermissionError("Không có quyền với doanh nghiệp này")
                only_note = existing and {k for k in data if data.get(k) is not None} <= {"mst", "ghichu"}
                if not only_note:  # ghi chú công việc: ai được giao DN cũng sửa được
                    self._perm("dn")
                if not existing:
                    if not data.get("password"):
                        raise ValueError("Nhập mật khẩu trang hoadondientu.gdt.gov.vn")
                    self._check_max_dn()
                fields = {k: data.get(k) for k in ("ghichu", "an")}
                for k in ("vao", "ra"):
                    fields[k] = data.get(k)
                c = self.T.store.upsert(data.get("mst"), data.get("ten"), data.get("password") or None, **fields)
                if not existing:
                    app.users.add_mst(self.user["username"], c["mst"])  # nhân viên tự thêm DN thì được giao luôn
                    self._audit("thêm DN %s" % c["mst"])
                return self._send(200, {"mst": c["mst"]})
            if path.startswith("/api/tool/"):
                return self._tool(path[len("/api/tool/"):], data)
            if path == "/api/mst-lookup":
                try:
                    return self._send(200, app.client_factory().lookup_company(data.get("mst")))
                except PortalError as e:
                    raise ValueError(str(e))
            if path == "/api/company/delete":
                self._admin()
                self.T.store.delete(data.get("mst"))
                app.users.remove_mst(data.get("mst"), self.T.id)
                self._audit("xoá DN %s" % data.get("mst"))
                return self._send(200, {"ok": True})
            if path == "/api/company/import":
                self._admin()
                if app.vp_info(self.T.id).get("max_dn"):
                    new = {m for m in re.findall(r"(?m)^\s*(\d{10}(?:-\d{3})?)", data.get("text") or "")
                           if not self.T.store.get(m)}
                    self._check_max_dn(len(new))
                added, errors = self.T.store.import_text(data.get("text"))
                client, filled = None, 0
                for c in self.T.store.public():
                    if c["ten"] or filled >= 100:
                        continue
                    try:
                        client = client or app.client_factory()
                        self.T.store.upsert(c["mst"], client.lookup_company(c["mst"])["ten"])
                        filled += 1
                    except (PortalError, ValueError) as e:
                        errors.append("%s: không lấy được tên (%s)" % (c["mst"], e))
                return self._send(200, {"added": added, "errors": errors})
            if path == "/api/run":
                self._perm("dongbo")
                job = app.job_for(self.user)
                if job.running:
                    return self._send(409, {"error": "Đang xử lý, vui lòng chờ hoặc bấm Dừng"})
                if app.server and app.running_jobs() >= app.max_jobs:
                    return self._send(429, {"error": "Máy chủ đang chạy %d lượt đồng bộ – chờ một chút rồi thử lại" % app.max_jobs})
                vlim = app.vp_info(self.T.id).get("max_jobs") or 0
                if app.server and vlim and app.running_jobs(self.T.id) >= vlim:
                    return self._send(429, {"error": "Văn phòng đang chạy %d lượt đồng bộ (tối đa theo gói) – chờ xong rồi thử lại" % vlim})
                msts = [m for m in (data.get("msts") or []) if self._can(m)]
                if not msts:
                    raise ValueError("Chưa có doanh nghiệp nào để xử lý")
                busy = busy_msts(self.T.root)
                if all(m in busy for m in msts):
                    raise ValueError("Doanh nghiệp đang được đồng bộ (bởi bạn hoặc người khác) – chờ xong rồi thử lại")
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
                app.start_job(self.user, msts, kinds, start, end, bool(data.get("mtt")), bool(data.get("xml")), test,
                              bool(data.get("pdf")) and "hdgoc" in UserStore.perms(self.user))
                return self._send(200, {"ok": True})
            if path == "/api/notices":
                if data.get("read"):
                    app.mark_read(self.user)
                return self._send(200, app.notices_for(self.user))
            if path in ("/api/auto/save", "/api/auto/run"):
                self._admin()
                if path == "/api/auto/run":
                    job = app.jobs.get(app.auto_user(self.T.id)["username"])
                    if job and job.running:
                        raise ValueError("Đồng bộ tự động đang chạy")
                    app.run_auto(wait=False, vp=self.T.id)
                    self._audit("chạy đồng bộ tự động")
                    return self._send(200, {"ok": True})
                gio = str(data.get("gio") or "06:00")
                if not re.fullmatch(r"([01]\d|2[0-3]):[0-5]\d", gio):
                    raise ValueError("Giờ không hợp lệ (vd 06:00)")
                if data.get("ky") not in AUTO_KY:
                    raise ValueError("Kỳ không hợp lệ")
                return self._send(200, app.save_auto(self.T.id, on=bool(data.get("on")), gio=gio, ky=data["ky"]))
            if path == "/api/captcha":
                app.job_for(self.user).answer(data.get("answer"))
                return self._send(200, {"ok": True})
            if path in ("/api/invoices", "/api/export", "/api/invoice/update", "/api/invoice/update-bulk", "/api/invoice/pdf",
                        "/api/invoice/fetch-pdf", "/api/invoice/fetch-pdf-bulk", "/api/invoice/pdf-import",
                        "/api/invoice/scan-downloads"):
                mst = data.get("mst", "")
                company = self.T.store.get(mst)
                if not company:
                    raise ValueError("Chọn doanh nghiệp")
                if not self._can(mst):
                    raise PermissionError("Không có quyền với doanh nghiệp này")
                need = {"/api/export": "ketxuat", "/api/invoice/update": "sua", "/api/invoice/update-bulk": "sua",
                        "/api/invoice/pdf": "hdgoc", "/api/invoice/fetch-pdf": "hdgoc",
                        "/api/invoice/fetch-pdf-bulk": "hdgoc", "/api/invoice/pdf-import": "hdgoc",
                        "/api/invoice/scan-downloads": "hdgoc"}.get(path)
                if need:
                    self._perm(need)
                if path == "/api/invoices":
                    return self._send(200, {"rows": query_invoices(self.T.root, mst, data.get("filters") or {})})
                if path == "/api/invoice/scan-downloads":
                    if app.server:  # máy chủ không nhìn thấy thư mục Downloads trên máy người dùng
                        return self._send(200, {"results": [], "folders": [], "folder": "", "prompt": False, "server": True})
                    folders, prompt = watch_dirs(app)
                    if data.get("info_only"):
                        return self._send(200, {"folders": folders, "prompt": prompt})
                    res = scan_downloads(self.T.root, mst, folders, float(data.get("since") or 0), app.seen_downloads)
                    return self._send(200, {"results": res, "folder": ", ".join(folders), "prompt": prompt})
                if path == "/api/invoice/pdf-import":
                    import base64
                    files = []
                    for f in (data.get("files") or [])[:500]:
                        try:
                            files.append((f.get("name"), base64.b64decode(f.get("data") or "", validate=True)))
                        except ValueError:
                            files.append((f.get("name"), b""))
                    return self._send(200, {"results": import_pdfs(self.T.root, mst, files)})
                quota = app.quota(self.T.id) if app.server else (RemoteQuota(app.lic_client) if app.lic_client else None)
                if path in ("/api/invoice/fetch-pdf", "/api/invoice/fetch-pdf-bulk") and quota and quota.goc_error():
                    raise ValueError(quota.goc_error())
                if path == "/api/invoice/fetch-pdf":
                    if quota and not quota.take_goc():
                        raise ValueError(quota.goc_error() or "Hết hạn mức hoá đơn gốc")
                    try:
                        return self._send(200, fetch_original(self.T.root, mst, data.get("kind"), data.get("key")))
                    except (PortalError, ValueError) as e:
                        if quota:
                            quota.refund_goc()
                        raise ValueError(str(e))
                if path == "/api/invoice/fetch-pdf-bulk":
                    f = data.get("filters") or {}
                    kind = (f.get("kind") or "purchase").replace("_dv", "")
                    rows = [r for r in query_invoices(self.T.root, mst, f) if r["need_goc"]][:300]
                    ok, errs = 0, []
                    for r in rows:
                        if quota and not quota.take_goc():
                            errs.append(quota.goc_error() or "Hết hạn mức hoá đơn gốc")
                            break
                        try:
                            got = fetch_original(self.T.root, mst, kind, r["key"])
                            ok += 1
                            if got["xml_err"]:
                                errs.append("%s/%s: XML gốc – %s" % (r["khhdon"], r["shdon"], got["xml_err"]))
                        except (PortalError, ValueError) as e:
                            if quota:
                                quota.refund_goc()
                            errs.append("%s/%s: %s" % (r["khhdon"], r["shdon"], e))
                        time.sleep(0.3)
                    return self._send(200, {"ok": ok, "total": len(rows), "errors": errs[:20]})
                if path == "/api/invoice/pdf":
                    import base64
                    try:
                        raw = base64.b64decode(data.get("data") or "", validate=True)
                    except ValueError:
                        raise ValueError("Dữ liệu file không hợp lệ")
                    return self._send(200, {"pdf": attach_pdf(self.T.root, mst, data.get("kind"), data.get("key"), raw)})
                if path == "/api/invoice/update-bulk":
                    if data.get("duyet") is not None and data["duyet"] not in DUYET_OPTIONS:
                        raise ValueError("Trạng thái duyệt không hợp lệ")
                    keys = [str(k) for k in (data.get("keys") or [])][:5000]
                    n = update_invoices(self.T.root, mst, data.get("kind"), keys, duyet=data.get("duyet"),
                                        dv=data.get("dv"))
                    return self._send(200, {"ok": True, "updated": n})
                if path == "/api/invoice/update":
                    if data.get("duyet") is not None and data["duyet"] not in DUYET_OPTIONS:
                        raise ValueError("Trạng thái duyệt không hợp lệ")
                    update_invoice(self.T.root, mst, data.get("kind"), data.get("key"), note=data.get("note"),
                                   duyet=data.get("duyet"), dv=data.get("dv"))
                    return self._send(200, {"ok": True})
                out = export_invoices(self.T.root, mst, company.get("ten"), data.get("filters") or {}, data.get("fmt"))
                rel = os.path.relpath(out, company_dir(self.T.root, mst))
                return self._send(200, {"url": "/file?" + urllib.parse.urlencode({"k": self._k(), "mst": mst, "p": rel})})
            if path == "/api/goods":  # trang Hàng hoá
                mst, act = data.get("mst", ""), data.get("action", "list")
                company = self.T.store.get(mst)
                if not company:
                    raise ValueError("Chọn doanh nghiệp")
                if not self._can(mst):
                    raise PermissionError("Không có quyền với doanh nghiệp này")
                root = self.T.root
                if act == "list":
                    return self._send(200, {"items": goods_load(root, mst)})
                if act == "export":
                    self._perm("ketxuat")
                    rel = os.path.relpath(goods_export(root, mst), company_dir(root, mst))
                    return self._send(200, {"url": "/file?" + urllib.parse.urlencode({"k": self._k(), "mst": mst, "p": rel})})
                self._perm("sua")
                if act == "sync":
                    kinds = [k for k, on in (("purchase", data.get("vao", True)), ("sold", data.get("ra", True))) if on]
                    if not kinds:
                        raise ValueError("Chọn ít nhất một loại hoá đơn (đầu vào / đầu ra)")
                    return self._send(200, goods_sync(root, mst, data.get("from") or "", data.get("to") or "", kinds,
                                                      bool(data.get("skip_zero")), bool(data.get("xml_code"))))
                if act == "update":
                    return self._send(200, {"n": goods_update(root, mst, data.get("rows") or [])})
                if act == "assign":
                    return self._send(200, {"n": goods_assign(root, mst, data.get("ids") or [], data.get("col"),
                                                              data.get("old"), data.get("new"))})
                if act == "autocode":
                    return self._send(200, {"n": goods_autocode(root, mst, data.get("ids") or [], data.get("kind") or 0,
                                                                data.get("prefix", "HH"), data.get("digits") or 6)})
                if act == "delete":
                    return self._send(200, {"n": goods_delete(root, mst, data.get("mode", 2), data.get("ids") or [])})
                if act == "import":
                    import base64
                    try:
                        raw = base64.b64decode(data.get("data") or "", validate=True)
                    except ValueError:
                        raise ValueError("Dữ liệu file không hợp lệ")
                    return self._send(200, goods_import(root, mst, raw, bool(data.get("drop_dup"))))
                raise ValueError("Thao tác không hợp lệ")
            if path in ("/api/partners", "/api/units"):  # ĐT P.nhân / Quy đổi ĐVT
                mst, act = data.get("mst", ""), data.get("action", "list")
                if not self.T.store.get(mst):
                    raise ValueError("Chọn doanh nghiệp")
                if not self._can(mst):
                    raise PermissionError("Không có quyền với doanh nghiệp này")
                root, pt = self.T.root, path == "/api/partners"
                if act == "list":
                    return self._send(200, {"items": partner_load(root, mst) if pt else unit_load(root, mst)})
                if act == "export":
                    self._perm("ketxuat")
                    out = partner_export(root, mst) if pt else unit_export(root, mst)
                    rel = os.path.relpath(out, company_dir(root, mst))
                    return self._send(200, {"url": "/file?" + urllib.parse.urlencode({"k": self._k(), "mst": mst, "p": rel})})
                self._perm("sua")
                if act == "import":
                    import base64
                    try:
                        raw = base64.b64decode(data.get("data") or "", validate=True)
                    except ValueError:
                        raise ValueError("Dữ liệu file không hợp lệ")
                    return self._send(200, partner_import(root, mst, raw) if pt else unit_import(root, mst, raw))
                if act == "update":
                    n = partner_update(root, mst, data.get("rows") or []) if pt else \
                        unit_update(root, mst, data.get("rows") or [], data.get("delete") or [])
                    return self._send(200, {"n": n})
                if act == "delete":
                    n = partner_delete(root, mst, data.get("mode", 2), data.get("ids") or []) if pt else \
                        unit_delete(root, mst, data.get("ids") or [])
                    return self._send(200, {"n": n})
                if pt and act == "sync":
                    kinds = [k for k, on in (("purchase", data.get("vao", True)), ("sold", data.get("ra", True))) if on]
                    if not kinds:
                        raise ValueError("Chọn ít nhất một loại hoá đơn (mua vào / bán ra)")
                    return self._send(200, partner_sync(root, mst, data.get("from") or "", data.get("to") or "", kinds))
                if pt and act == "assign":
                    return self._send(200, {"n": partner_assign(root, mst, data.get("ids") or [], data.get("col"),
                                                                data.get("old"), data.get("new"))})
                if pt and act == "autocode":
                    return self._send(200, {"n": partner_autocode(root, mst, data.get("ids") or [], data.get("kind") or 0,
                                                                  data.get("prefix", ""), data.get("suffix", ""))})
                if pt and act == "remark":
                    return self._send(200, {"n": partner_remark(root, mst, data.get("year"), data.get("what"))})
                raise ValueError("Thao tác không hợp lệ")
            if path == "/api/stop":
                app.job_for(self.user).stop()
                return self._send(200, {"ok": True})
            self._send(404, {"error": "Không tìm thấy"})

    return Handler


def migrate_owner(app):
    """Lên bản nhiều văn phòng: chưa ai là chủ hệ thống → các quản trị đang hoạt động của văn phòng chính là chủ hệ thống.
    Dữ liệu cũ giữ nguyên chỗ (văn phòng chính dùng thư mục dữ liệu cũ), người dùng cũ thuộc văn phòng chính."""
    if any(u.get("owner") for u in app.users.items):
        return 0
    n = 0
    for u in list(app.users.items):
        if u["role"] == "admin" and u.get("active", True) and user_vp(u) == DEFAULT_VP:
            app.users.upsert(u["username"], owner=True)
            n += 1
    return n


def self_test():
    """Kiểm tra bản cài / bản .exe: đủ thư viện, có font tiếng Việt, dựng được PDF và Excel."""
    import importlib
    ok = True
    for mod in ("openpyxl", "pypdf", "xlrd", "reportlab", "cryptography"):
        try:
            importlib.import_module(mod)
            print("OK  thư viện %s" % mod)
        except ImportError as e:
            ok = False
            print("LỖI thư viện %s: %s" % (mod, e))
    try:
        v = invoice_view_from_detail({"nbten": "CÔNG TY THỬ NGHIỆM ĐẦU VÀO", "khmshdon": 1, "khhdon": "C26TAA", "shdon": 1,
                                      "tgtttbso": 1000, "hdhhdvu": [{"stt": 1, "ten": "Hàng hoá có dấu tiếng Việt",
                                                                      "thtien": 1000}]})
        data = build_tax_pdf(v)
        import pypdf
        text = pypdf.PdfReader(io.BytesIO(data)).pages[0].extract_text()
        assert "Hàng hoá có dấu tiếng Việt" in text, text[:200]
        print("OK  PDF tiếng Việt (%d byte)" % len(data))
    except Exception as e:
        ok = False
        print("LỖI tạo PDF: %s" % e)
    try:
        write_bank_ktsc([{"date": "2026-01-02", "ref": "1", "desc": "Thử", "name": "", "in": 1000.0, "out": 0.0}])
        print("OK  Excel KTSC")
    except Exception as e:
        ok = False
        print("LỖI tạo Excel: %s" % e)
    print("Trình duyệt in PDF thuế: %s" % (find_browser() or "không có (dùng bản dựng sẵn)"))
    print("Phiên bản %s: %s" % (__version__, "TẤT CẢ OK" if ok else "CÓ LỖI"))
    return 0 if ok else 1


def main(argv=None):
    for stream in (sys.stdout, sys.stderr):  # cửa sổ dòng lệnh không hỗ trợ tiếng Việt → không được làm dừng phần mềm
        try:
            stream.reconfigure(errors="replace")
        except (AttributeError, ValueError):
            pass
    ap = argparse.ArgumentParser(description="Phần mềm tải hoá đơn điện tử (hoadondientu.gdt.gov.vn)")
    ap.add_argument("--port", type=int, default=int(os.environ.get("TAIHOADON_PORT") or 8765),
                    help="cổng giao diện (mặc định 8765)")
    ap.add_argument("--out", default=os.environ.get("TAIHOADON_DATA") or os.path.join(
        app_dir() if getattr(sys, "frozen", False) else os.getcwd(), "HoaDon"),
                    help="thư mục lưu (mặc định ./HoaDon)")
    ap.add_argument("--no-browser", action="store_true", help="không tự mở trình duyệt")
    ap.add_argument("--insecure", action="store_true", help="bỏ kiểm tra chứng chỉ SSL (chỉ dùng khi máy báo lỗi SSL)")
    ap.add_argument("--downloads", help="thư mục trình duyệt lưu file tải về (mặc định: Downloads của Windows)")
    ap.add_argument("--license-server", default=os.environ.get("TAIHOADON_LICENSE_SERVER") or "",
                    help="bản cài máy: địa chỉ máy chủ cấp tài khoản / hạn mức (mặc định %s)" % DEFAULT_LICENSE_SERVER)
    web = ap.add_argument_group("bản web (máy chủ, nhiều người dùng)")
    web.add_argument("--server", action="store_true", default=os.environ.get("TAIHOADON_SERVER") == "1",
                     help="chạy bản web: đăng nhập, phân quyền nhân viên theo doanh nghiệp")
    web.add_argument("--bind", default=os.environ.get("TAIHOADON_BIND") or "0.0.0.0", help="địa chỉ lắng nghe (mặc định 0.0.0.0)")
    web.add_argument("--domain", default=os.environ.get("TAIHOADON_DOMAIN") or "",
                     help="tên miền được phép, ngăn cách bằng dấu phẩy (vd hoadon.congty.vn)")
    web.add_argument("--trust-proxy", action="store_true", default=os.environ.get("TAIHOADON_TRUST_PROXY") == "1",
                     help="chạy sau Caddy/Nginx (HTTPS): tin X-Forwarded-For / X-Forwarded-Proto")
    web.add_argument("--max-jobs", type=int, default=int(os.environ.get("TAIHOADON_MAX_JOBS") or 4),
                     help="số lượt đồng bộ chạy cùng lúc (mặc định 4)")
    web.add_argument("--set-password", metavar="TÊN", help="tạo / đặt lại mật khẩu quản trị TÊN rồi thoát")
    ap.add_argument("--self-test", action="store_true", help="kiểm tra thư viện, font, tạo PDF rồi thoát")
    args = ap.parse_args(argv)
    if args.self_test:
        return self_test()

    out = os.path.abspath(args.out)
    cfg = os.path.join(out, "_cau-hinh")
    if args.server or args.set_password:
        set_secret_key(os.path.join(cfg, "khoa-bi-mat.key"), os.environ.get("TAIHOADON_SECRET_KEY"))
    app = App(out, lambda: HoaDonClient(verify_ssl=not args.insecure), server=args.server)

    if args.set_password:
        import getpass
        pw = os.environ.get("TAIHOADON_NEW_PASSWORD") or getpass.getpass("Mật khẩu mới cho %s: " % args.set_password)
        exists = app.users.get(args.set_password)
        if exists and user_vp(exists) != DEFAULT_VP:
            print("%s thuộc văn phòng %s – đổi mật khẩu trong trang Quản trị của văn phòng đó" % (
                args.set_password, user_vp(exists)))
            return 1
        app.users.upsert(args.set_password, password=pw, role="admin", active=True, owner=True,
                         ten=None if exists else "Quản trị")
        print("Đã đặt mật khẩu quản trị cho %s" % args.set_password.lower())
        return 0

    if not app.server and not args.set_password:  # bản cài trên máy khách: tài khoản + hạn mức do máy chủ cấp
        app.lic_client = LicenseClient(cfg, args.license_server or None)
        if app.lic_client.logged:  # cập nhật hạn mức / thông báo / bản mới khi mở phần mềm (không chờ, lỗi mạng thì bỏ qua)
            threading.Thread(target=lambda: _quiet(app.lic_client.status), daemon=True).start()
    if args.downloads:
        app.downloads = os.path.abspath(args.downloads)
    host = "127.0.0.1"
    if app.server:
        host = args.bind
        app.allowed_hosts = {h.strip().lower() for h in args.domain.split(",") if h.strip()}
        app.trust_proxy = args.trust_proxy
        app.max_jobs = max(1, args.max_jobs)
        n = sum(app.t(t["id"]).store.reencrypt() for t in app.tenants.list())
        if n:
            print("Đã mã hoá lại %d mật khẩu doanh nghiệp bằng khoá máy chủ." % n)
        migrate_owner(app)
        if not app.users.count():
            name = (os.environ.get("TAIHOADON_ADMIN_USER") or "admin").lower()
            pw = os.environ.get("TAIHOADON_ADMIN_PASSWORD") or secrets.token_urlsafe(9)
            app.users.upsert(name, ten="Quản trị", role="admin", password=pw, owner=True)
            print("=" * 60)
            print("Tạo tài khoản quản trị đầu tiên: %s" % name)
            if not os.environ.get("TAIHOADON_ADMIN_PASSWORD"):
                print("Mật khẩu: %s   (đăng nhập rồi đổi ngay)" % pw)
                # để xem được qua Trình quản lý file của bảng điều khiển khi không có SSH; xoá file sau khi đăng nhập
                note = os.path.join(cfg, "MAT-KHAU-ADMIN-BAN-DAU.txt")
                fd = os.open(note, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
                with os.fdopen(fd, "w", encoding="utf-8") as f:
                    f.write("Tên đăng nhập: %s\nMật khẩu: %s\nĐăng nhập, đổi mật khẩu rồi XOÁ file này.\n" % (name, pw))
            print("=" * 60)
    global DEBUG_DIR
    DEBUG_DIR = os.path.join(cfg, "chan-doan")  # trang lỗi gần nhất của nhà cung cấp (gửi cho người hỗ trợ)
    server = ThreadingHTTPServer((host, args.port), make_handler(app, args.port))
    if app.server:
        threading.Thread(target=auto_sync_loop, args=(app,), daemon=True).start()
        print("Bản web %s đang chạy tại %s:%d%s" % (__version__, host, args.port,
                                                    (" – tên miền: " + ", ".join(sorted(app.allowed_hosts))) if app.allowed_hosts else ""))
    else:
        url = "http://127.0.0.1:%d/" % args.port
        print("Phần mềm tải hoá đơn %s đang chạy tại %s" % (__version__, url))
        if os.name != "nt":
            print("Lưu ý: ngoài Windows, mật khẩu lưu dạng mã hoá đơn giản – hãy bảo vệ thư mục _cau-hinh.")
        print("Đóng cửa sổ này (hoặc Ctrl+C) để thoát.")
        if not args.no_browser:
            threading.Timer(0.8, lambda: webbrowser.open(url)).start()
    print("Hoá đơn lưu vào: %s" % app.out_root)
    sys.stdout.flush()
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()


if __name__ == "__main__":
    sys.exit(main())
