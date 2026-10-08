#!/usr/bin/env python3
"""Phần mềm tải hoá đơn điện tử từ cổng hoadondientu.gdt.gov.vn.

Chạy: python taihoadon.py  → trình duyệt mở http://127.0.0.1:8765
- Đăng nhập bằng tài khoản doanh nghiệp trên cổng hoá đơn điện tử (nhập captcha).
- Chọn hoá đơn mua vào / bán ra, khoảng ngày (tự chia theo tháng vì cổng chỉ cho tra 1 tháng/lần).
- Xuất bảng kê Excel (.xlsx nếu có openpyxl, luôn kèm .csv) và tải file XML gốc từng hoá đơn.

Chỉ dùng thư viện chuẩn Python 3.8+. openpyxl là tuỳ chọn (pip install openpyxl).
Mật khẩu không được lưu; token đăng nhập chỉ nằm trong bộ nhớ khi chương trình chạy.
"""

import argparse
import calendar
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
import urllib.error
import urllib.parse
import urllib.request
import webbrowser
import zipfile
from datetime import date, datetime, timedelta
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

__version__ = "1.0.0"

BASE_URL = os.environ.get("HDDT_BASE_URL", "https://hoadondientu.gdt.gov.vn:30000")
PAGE_SIZE = 50
USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36"

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
        self._ctx = None if verify_ssl else ssl._create_unverified_context()

    # -- HTTP ---------------------------------------------------------------
    def _request(self, method, path, params=None, body=None, raw=False, retries=3):
        url = self.base_url + path
        if params:
            url += "?" + urllib.parse.urlencode(params, safe=":,;=/")
        headers = {"User-Agent": USER_AGENT, "Accept": "application/json, text/plain, */*"}
        data = None
        if body is not None:
            data = json.dumps(body).encode("utf-8")
            headers["Content-Type"] = "application/json"
        if self.token:
            headers["Authorization"] = "Bearer " + self.token

        for attempt in range(retries + 1):
            req = urllib.request.Request(url, data=data, headers=headers, method=method)
            try:
                with urllib.request.urlopen(req, timeout=self.timeout, context=self._ctx) as resp:
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
                raise PortalError(self._error_message(content) or "Cổng hoá đơn trả lỗi HTTP %d" % e.code)
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
    def get_captcha(self):
        """Trả về {'key': ..., 'content': '<svg ...>'}."""
        data = self._request("GET", "/captcha")
        if not data.get("key") or not data.get("content"):
            raise PortalError("Không lấy được captcha.")
        return {"key": data["key"], "content": data["content"]}

    def login(self, username, password, captcha_value, captcha_key):
        self.token = None
        data = self._request(
            "POST",
            "/security-taxpayer/authenticate",
            body={"username": username, "password": password, "cvalue": captcha_value, "ckey": captcha_key},
            retries=0,
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
                             params=self._invoice_params(inv))

    def export_xml(self, inv):
        """Trả về bytes file zip (invoice.xml, details.js, ...) do cổng cung cấp."""
        return self._request("GET", "%s/invoices/export-xml" % inv.get("_prefix", "/query"),
                             params=self._invoice_params(inv), raw=True)


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
# ---------------------------------------------------------------------------

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


def write_reports(invoices, folder, basename):
    """Ghi bảng kê .csv (luôn có) và .xlsx (nếu cài openpyxl). Trả về list đường dẫn."""
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

    wb = Workbook()
    ws = wb.active
    ws.title = "Bang ke"
    ws.append(headers)
    for cell in ws[1]:
        cell.font = Font(bold=True, color="FFFFFF")
        cell.fill = PatternFill("solid", fgColor="1F6FB2")
    for row in rows:
        ws.append(row)
    money_cols = [i + 1 for i, (_, k) in enumerate(COLUMNS) if k in MONEY_KEYS]
    if rows:
        total_row = len(rows) + 2
        ws.cell(row=total_row, column=1, value="Tổng cộng").font = Font(bold=True)
        for col in money_cols:
            letter = get_column_letter(col)
            c = ws.cell(row=total_row, column=col, value="=SUM(%s2:%s%d)" % (letter, letter, total_row - 1))
            c.font = Font(bold=True)
    for col in money_cols:
        for cells in ws.iter_cols(min_col=col, max_col=col, min_row=2):
            for c in cells:
                c.number_format = "#,##0"
    widths = [6, 12, 10, 12, 10, 14, 40, 14, 40, 16, 14, 16, 8, 18, 22, 16, 30, 50]
    for i, wdt in enumerate(widths, 1):
        ws.column_dimensions[get_column_letter(i)].width = wdt
    ws.freeze_panes = "A2"
    ws.auto_filter.ref = ws.dimensions
    xlsx_path = os.path.join(folder, basename + ".xlsx")
    wb.save(xlsx_path)
    paths.append(xlsx_path)
    return paths


# ---------------------------------------------------------------------------
# Tác vụ tải (chạy nền, giao diện hỏi tiến độ)
# ---------------------------------------------------------------------------

class Job:
    def __init__(self):
        self.lock = threading.Lock()
        self.running = False
        self.cancel = False
        self.log = []
        self.done = 0
        self.total = 0
        self.invoices = []
        self.files = []
        self.error = None
        self.folder = None

    def say(self, msg):
        with self.lock:
            self.log.append("%s  %s" % (datetime.now().strftime("%H:%M:%S"), msg))
            self.log = self.log[-200:]

    def snapshot(self):
        with self.lock:
            inv = self.invoices
            return {
                "running": self.running, "log": list(self.log), "done": self.done, "total": self.total,
                "error": self.error, "files": list(self.files), "folder": self.folder,
                "count": len(inv),
                "sum_cthue": sum(_money(i.get("tgtcthue")) for i in inv),
                "sum_thue": sum(_money(i.get("tgtthue")) for i in inv),
                "sum_tt": sum(_money(i.get("tgtttbso")) for i in inv),
                "rows": table_rows(inv[:500]),
                "headers": [c[0] for c in COLUMNS],
            }


def run_job(job, client, kinds, start, end, include_mtt, want_xml, out_root):
    try:
        mst = safe_name(client.username or "mst")
        period = "%s_%s" % (start.strftime("%Y%m%d"), end.strftime("%Y%m%d"))
        for kind in kinds:
            if job.cancel:
                break
            label = "mua-vao" if kind == "purchase" else "ban-ra"
            folder = os.path.join(out_root, mst, "%s_%s" % (label, period))
            with job.lock:
                job.folder = os.path.abspath(os.path.join(out_root, mst))
            invoices = client.list_invoices(kind, start, end, include_mtt, progress=job.say)
            job.say("Tìm thấy %d hoá đơn %s." % (len(invoices), "mua vào" if kind == "purchase" else "bán ra"))
            with job.lock:
                job.invoices = job.invoices + invoices
                job.total += len(invoices) if want_xml else 0
            if want_xml:
                xml_dir = os.path.join(folder, "xml")
                for inv in invoices:
                    if job.cancel:
                        job.say("Đã dừng theo yêu cầu.")
                        break
                    name = invoice_basename(inv)
                    existing = os.path.join(xml_dir, name + ".xml")
                    try:
                        if os.path.exists(existing):
                            path = existing
                        else:
                            path = save_xml(client.export_xml(inv), xml_dir, name)
                            time.sleep(client.delay)
                        inv["_xml"] = os.path.relpath(path, folder)
                    except PortalError as e:
                        inv["_xml"] = "Lỗi: %s" % e
                        job.say("Không tải được XML %s/%s: %s" % (inv.get("khhdon"), inv.get("shdon"), e))
                        if "hết hạn" in str(e):
                            raise
                    with job.lock:
                        job.done += 1
            paths = write_reports(invoices, folder, "bang-ke-%s_%s" % (label, period))
            with job.lock:
                job.files.extend(os.path.abspath(p) for p in paths)
            job.say("Đã lưu bảng kê: %s" % ", ".join(os.path.basename(p) for p in paths))
        job.say("Hoàn tất.")
    except Exception as e:  # hiển thị mọi lỗi cho người dùng thay vì làm chết luồng
        with job.lock:
            job.error = str(e)
        job.say("Lỗi: %s" % e)
    finally:
        with job.lock:
            job.running = False


# ---------------------------------------------------------------------------
# Giao diện web chạy trên máy (127.0.0.1)
# ---------------------------------------------------------------------------

PAGE = r"""<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tải hoá đơn điện tử</title>
<style>
:root{--bg:#f4f6f9;--card:#fff;--fg:#1d2733;--mute:#66758a;--line:#dde3ea;--pri:#1f6fb2;--err:#c0392b;--ok:#1e8449}
@media (prefers-color-scheme:dark){:root{--bg:#12161c;--card:#1b2129;--fg:#e6ebf1;--mute:#93a1b3;--line:#2c3540;--pri:#4b9be0;--err:#ff6b5b;--ok:#4cc38a}}
*{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:var(--bg);color:var(--fg)}
main{max-width:1100px;margin:0 auto;padding:16px}h1{font-size:22px;margin:8px 0 16px}
.card{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:16px;margin-bottom:16px}
label{display:block;font-size:13px;color:var(--mute);margin:8px 0 4px}
input[type=text],input[type=password],input[type=date]{width:100%;padding:9px 10px;border:1px solid var(--line);border-radius:6px;background:var(--bg);color:var(--fg);font:inherit}
.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
button{padding:9px 16px;border:0;border-radius:6px;background:var(--pri);color:#fff;font:inherit;cursor:pointer}
button.sec{background:transparent;color:var(--pri);border:1px solid var(--pri)}button:disabled{opacity:.5;cursor:default}
.cap{display:flex;gap:8px;align-items:center}.cap .img{background:#fff;border-radius:6px;height:44px;min-width:150px;display:flex;align-items:center}
.cap .img svg{height:44px;width:auto}.chk{display:flex;gap:16px;flex-wrap:wrap;margin:12px 0}.chk label{display:flex;gap:6px;align-items:center;color:var(--fg);margin:0;font-size:14px}
.msg{margin-top:10px;font-size:14px}.err{color:var(--err)}.ok{color:var(--ok)}
.bar{height:8px;background:var(--line);border-radius:4px;overflow:hidden;margin:10px 0}.bar i{display:block;height:100%;background:var(--pri);width:0}
pre{background:var(--bg);border:1px solid var(--line);border-radius:6px;padding:10px;max-height:180px;overflow:auto;font-size:12px;margin:0}
.stats{display:flex;gap:24px;flex-wrap:wrap;margin:8px 0}.stats b{display:block;font-size:18px}
.tbl{overflow:auto;max-height:420px;border:1px solid var(--line);border-radius:6px}table{border-collapse:collapse;width:100%;font-size:13px}
th,td{padding:6px 8px;border-bottom:1px solid var(--line);white-space:nowrap;text-align:left}th{position:sticky;top:0;background:var(--card)}
td.n{text-align:right;font-variant-numeric:tabular-nums}.hide{display:none}small{color:var(--mute)}
</style></head><body><main>
<h1>Tải hoá đơn điện tử <small>hoadondientu.gdt.gov.vn</small></h1>

<section class="card" id="login">
  <div class="row">
    <div><label>Tên đăng nhập (MST)</label><input type="text" id="u" autocomplete="username"></div>
    <div><label>Mật khẩu</label><input type="password" id="p" autocomplete="current-password"></div>
    <div><label>Mã captcha</label><div class="cap"><span class="img" id="capimg"></span>
      <button class="sec" type="button" onclick="loadCaptcha()" title="Đổi captcha">↻</button></div>
      <input type="text" id="c" style="margin-top:6px" placeholder="Nhập ký tự trong ảnh"></div>
  </div>
  <div style="margin-top:14px"><button id="btnLogin" onclick="login()">Đăng nhập</button></div>
  <div class="msg" id="loginMsg"></div>
</section>

<section class="card hide" id="query">
  <div class="msg ok" id="who"></div>
  <div class="row">
    <div><label>Từ ngày</label><input type="date" id="from"></div>
    <div><label>Đến ngày</label><input type="date" id="to"></div>
  </div>
  <div class="chk">
    <label><input type="checkbox" id="kPurchase" checked> Hoá đơn mua vào</label>
    <label><input type="checkbox" id="kSold" checked> Hoá đơn bán ra</label>
    <label><input type="checkbox" id="mtt" checked> Gồm hoá đơn máy tính tiền</label>
    <label><input type="checkbox" id="xml" checked> Tải file XML từng hoá đơn</label>
  </div>
  <button id="btnRun" onclick="run()">Tải hoá đơn</button>
  <button id="btnStop" class="sec hide" onclick="stop()">Dừng</button>
  <button class="sec" onclick="logout()">Đăng xuất</button>
  <div class="msg" id="runMsg"></div>
</section>

<section class="card hide" id="result">
  <div class="bar"><i id="barI"></i></div>
  <div class="stats">
    <div>Số hoá đơn<b id="sCount">0</b></div><div>Tiền chưa thuế<b id="sCt">0</b></div>
    <div>Tiền thuế<b id="sT">0</b></div><div>Tổng thanh toán<b id="sTt">0</b></div>
  </div>
  <div class="msg" id="files"></div>
  <pre id="log"></pre>
  <div class="tbl" style="margin-top:12px"><table id="tbl"></table></div>
  <small>Bảng chỉ hiển thị tối đa 500 dòng; bảng kê Excel/CSV có đủ toàn bộ.</small>
</section>
</main>
<script>
const KEY = "__TOKEN__";
const $ = id => document.getElementById(id);
const fmt = n => Math.round(n).toLocaleString('vi-VN');
let capKey = null, timer = null;

async function api(path, body) {
  const r = await fetch(path, {method: body ? 'POST' : 'GET', headers: {'X-App-Key': KEY, 'Content-Type': 'application/json'},
                               body: body ? JSON.stringify(body) : undefined});
  const d = await r.json().catch(() => ({error: 'Phản hồi không hợp lệ'}));
  if (!r.ok || d.error) throw new Error(d.error || ('HTTP ' + r.status));
  return d;
}
async function loadCaptcha() {
  $('capimg').textContent = '…';
  try { const d = await api('/api/captcha'); capKey = d.key; $('capimg').innerHTML = d.content; $('c').value = ''; }
  catch (e) { $('capimg').textContent = ''; $('loginMsg').className = 'msg err'; $('loginMsg').textContent = e.message; }
}
async function login() {
  $('btnLogin').disabled = true; $('loginMsg').className = 'msg'; $('loginMsg').textContent = 'Đang đăng nhập…';
  try {
    await api('/api/login', {username: $('u').value.trim(), password: $('p').value, captcha: $('c').value.trim(), key: capKey});
    $('p').value = ''; showQuery($('u').value.trim());
  } catch (e) { $('loginMsg').className = 'msg err'; $('loginMsg').textContent = e.message; loadCaptcha(); }
  $('btnLogin').disabled = false;
}
function showQuery(user) {
  $('login').classList.add('hide'); $('query').classList.remove('hide');
  $('who').textContent = 'Đã đăng nhập: ' + user;
}
async function logout() { await api('/api/logout', {}); location.reload(); }
async function run() {
  const kinds = [];
  if ($('kPurchase').checked) kinds.push('purchase');
  if ($('kSold').checked) kinds.push('sold');
  $('runMsg').className = 'msg';
  try {
    await api('/api/run', {from: $('from').value, to: $('to').value, kinds, mtt: $('mtt').checked, xml: $('xml').checked});
    $('runMsg').textContent = ''; $('result').classList.remove('hide'); poll();
  } catch (e) { $('runMsg').className = 'msg err'; $('runMsg').textContent = e.message; }
}
async function stop() { await api('/api/stop', {}); }
async function poll() {
  clearTimeout(timer);
  let s; try { s = await api('/api/status'); } catch (e) { timer = setTimeout(poll, 2000); return; }
  $('btnRun').disabled = s.running; $('btnStop').classList.toggle('hide', !s.running);
  $('barI').style.width = (s.total ? Math.round(100 * s.done / s.total) : (s.running ? 5 : 100)) + '%';
  $('sCount').textContent = s.count; $('sCt').textContent = fmt(s.sum_cthue); $('sT').textContent = fmt(s.sum_thue); $('sTt').textContent = fmt(s.sum_tt);
  $('log').textContent = s.log.join('\n'); $('log').scrollTop = 1e9;
  $('files').innerHTML = '';
  if (s.folder) $('files').append('Thư mục lưu: ' + s.folder);
  if (s.error) { const e = document.createElement('div'); e.className = 'err'; e.textContent = s.error; $('files').append(e);
                 if (/đăng nhập/i.test(s.error)) { $('query').classList.add('hide'); $('login').classList.remove('hide'); loadCaptcha(); } }
  const t = $('tbl'); t.innerHTML = '';
  if (s.rows.length) {
    const h = t.insertRow(); s.headers.forEach(x => { const th = document.createElement('th'); th.textContent = x; h.append(th); });
    s.rows.forEach(r => { const tr = t.insertRow(); r.forEach(v => { const td = tr.insertCell();
      if (typeof v === 'number') { td.className = 'n'; td.textContent = fmt(v); } else td.textContent = v; }); });
  }
  if (s.running) timer = setTimeout(poll, 1500);
}
(async () => {
  const now = new Date(), first = new Date(now.getFullYear(), now.getMonth() - 1, 1), last = new Date(now.getFullYear(), now.getMonth(), 0);
  const iso = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  $('from').value = iso(first); $('to').value = iso(last);
  $('c').addEventListener('keydown', e => { if (e.key === 'Enter') login(); });
  const s = await api('/api/session').catch(() => ({}));
  if (s.user) { showQuery(s.user); poll(); } else loadCaptcha();
})();
</script></body></html>
"""


class App:
    def __init__(self, client, out_root):
        self.client = client
        self.out_root = out_root
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
            if n > 100000:
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
            if path == "/api/session":
                return self._send(200, {"user": app.client.username if app.client.token else None})
            if path == "/api/captcha":
                try:
                    return self._send(200, app.client.get_captcha())
                except PortalError as e:
                    return self._send(502, {"error": str(e)})
            if path == "/api/status":
                return self._send(200, app.job.snapshot())
            self._send(404, {"error": "Không tìm thấy"})

        def do_POST(self):
            if not self._guard():
                return
            path = self.path.split("?")[0]
            data = self._body()
            if path == "/api/login":
                if not all(data.get(k) for k in ("username", "password", "captcha", "key")):
                    return self._send(400, {"error": "Nhập đủ tên đăng nhập, mật khẩu và captcha"})
                try:
                    app.client.login(data["username"], data["password"], data["captcha"], data["key"])
                    return self._send(200, {"user": app.client.username})
                except PortalError as e:
                    return self._send(400, {"error": str(e)})
            if path == "/api/logout":
                app.client.token = None
                app.client.username = None
                return self._send(200, {"ok": True})
            if path == "/api/run":
                if not app.client.token:
                    return self._send(401, {"error": "Chưa đăng nhập"})
                if app.job.running:
                    return self._send(409, {"error": "Đang tải, vui lòng chờ"})
                try:
                    start, end = parse_date(data.get("from")), parse_date(data.get("to"))
                    if end < start:
                        raise ValueError("Ngày kết thúc phải sau ngày bắt đầu")
                except ValueError as e:
                    return self._send(400, {"error": str(e)})
                kinds = [k for k in (data.get("kinds") or []) if k in ("purchase", "sold")]
                if not kinds:
                    return self._send(400, {"error": "Chọn ít nhất một loại hoá đơn"})
                app.job = Job()
                app.job.running = True
                threading.Thread(target=run_job, daemon=True, args=(
                    app.job, app.client, kinds, start, end, bool(data.get("mtt")), bool(data.get("xml")),
                    app.out_root)).start()
                return self._send(200, {"ok": True})
            if path == "/api/stop":
                app.job.cancel = True
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

    client = HoaDonClient(verify_ssl=not args.insecure)
    app = App(client, os.path.abspath(args.out))
    server = ThreadingHTTPServer(("127.0.0.1", args.port), make_handler(app, args.port))
    url = "http://127.0.0.1:%d/" % args.port
    print("Phần mềm tải hoá đơn %s đang chạy tại %s" % (__version__, url))
    print("Hoá đơn lưu vào: %s" % app.out_root)
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
