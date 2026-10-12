#!/usr/bin/env python3
"""
Đồng bộ máy chấm công (ZKTeco / Ronald Jack / Mitaco... giao thức ZK, cổng 4370) lên website.

Dùng khi máy KHÔNG có chức năng ADMS / Cloud Server (nếu có, hãy dùng Cách 1 – máy tự gửi lên website).
Script chạy trên 1 máy tính trong cùng mạng LAN với máy chấm công.

Cài đặt (Windows / macOS / Linux):
  1. Cài Python 3.8+ (python.org – Windows: tick "Add Python to PATH").
  2. Mở Command Prompt / Terminal:   pip install pyzk requests
  3. Sửa phần CẤU HÌNH bên dưới: IP máy chấm công, địa chỉ website, khoá API
     (Quản lý DN → Máy chấm công → Cách 3 → Tạo khoá API).
  4. Chạy thử:   python zk-sync.py
     Lần đầu gửi toàn bộ dữ liệu trong máy; các lần sau chỉ gửi lần chấm mới.
  5. Chạy tự động mỗi 10 phút:
     - Windows: Task Scheduler → Create Basic Task → Daily → lặp lại mỗi 10 phút,
       Action: Start a program = python, Arguments = "C:\\duong-dan\\zk-sync.py"
     - Linux / macOS: crontab -e  →  */10 * * * * /usr/bin/python3 /duong-dan/zk-sync.py

Nhiều máy: thêm dòng vào DEVICES.
"""

import json
import os
import sys
from datetime import datetime

# ===================== CẤU HÌNH =====================
WEBSITE = "https://ten-mien-cua-ban.vn"      # Địa chỉ website (không có / ở cuối)
API_KEY = "DAN-KHOA-API-VAO-DAY"             # Quản lý DN → Máy chấm công → Khoá API
DEVICES = [
    # (IP máy chấm công, cổng, mật khẩu kết nối (Comm Key, mặc định 0))
    ("192.168.1.201", 4370, 0),
]
CLEAR_AFTER_SYNC = False   # True = xoá dữ liệu trong máy sau khi gửi thành công (máy đầy bộ nhớ)
# ====================================================

STATE_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "zk-sync-state.json")
BATCH = 2000


def load_state():
    try:
        with open(STATE_FILE, encoding="utf-8") as f:
            return json.load(f)
    except (OSError, ValueError):
        return {}


def save_state(state):
    with open(STATE_FILE, "w", encoding="utf-8") as f:
        json.dump(state, f, ensure_ascii=False, indent=2)


def send(device_sn, punches):
    import requests

    total = {"new": 0, "dup": 0, "invalid": 0, "unknown": 0, "locked": 0}
    for i in range(0, len(punches), BATCH):
        r = requests.post(
            WEBSITE.rstrip("/") + "/wp-json/qldn/v1/punches",
            json={"device": device_sn, "punches": punches[i:i + BATCH]},
            headers={"X-QLDN-Key": API_KEY},
            timeout=60,
        )
        if r.status_code != 200:
            raise RuntimeError("Website trả lỗi %s: %s" % (r.status_code, r.text[:300]))
        for k, v in r.json().items():
            total[k] = total.get(k, 0) + int(v)
    return total


def sync_device(ip, port, password, state):
    from zk import ZK

    conn = ZK(ip, port=port, timeout=15, password=password, force_udp=False, ommit_ping=True).connect()
    try:
        conn.disable_device()  # Tạm khoá máy trong lúc đọc để không mất lần chấm.
        sn = (conn.get_serialnumber() or ip).strip()
        last = state.get(sn, "")
        punches = []
        newest = last
        for a in conn.get_attendance():
            t = a.timestamp.strftime("%Y-%m-%d %H:%M:%S")
            if t <= last:
                continue
            punches.append({"pin": str(a.user_id), "time": t, "state": a.status, "verify": a.punch})
            newest = max(newest, t)
        if punches:
            res = send(sn, punches)
            state[sn] = newest
            save_state(state)
            print("[%s] Máy %s (%s): gửi %d lần chấm – mới %d, trùng %d, chưa gán nhân viên %d"
                  % (datetime.now().strftime("%H:%M"), sn, ip, len(punches), res["new"], res["dup"], res["unknown"]))
            if CLEAR_AFTER_SYNC:
                conn.clear_attendance()
        else:
            print("Máy %s (%s): không có lần chấm mới." % (sn, ip))
    finally:
        conn.enable_device()
        conn.disconnect()


def main():
    try:
        import requests  # noqa: F401
        import zk  # noqa: F401
    except ImportError:
        sys.exit("Thiếu thư viện. Chạy:  pip install pyzk requests")
    state = load_state()
    errors = 0
    for ip, port, password in DEVICES:
        try:
            sync_device(ip, port, password, state)
        except Exception as e:  # Lỗi 1 máy không chặn các máy khác.
            errors += 1
            print("LỖI máy %s: %s" % (ip, e), file=sys.stderr)
    sys.exit(1 if errors else 0)


if __name__ == "__main__":
    main()
