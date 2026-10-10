#!/bin/bash
# Khởi động bản web Tải hoá đơn – dùng làm "lệnh chạy" của site Systemd trong FASTPANEL (không cần SSH).
# Lần đầu tự tạo môi trường Python và cài thư viện (mất 1–3 phút), các lần sau chạy ngay.
cd "$(dirname "$0")" || exit 1
# Ghi nhật ký ra chay.log để xem bằng Quản lý tập tin (giữ ~200 KB cuối)
[ -f chay.log ] && [ "$(wc -c < chay.log)" -gt 200000 ] && tail -c 100000 chay.log > chay.log.tmp && mv chay.log.tmp chay.log
exec >> chay.log 2>&1
echo "===== $(date '+%F %T') khởi động – người dùng $(id -un), thư mục $PWD, cổng ${SERVICE_PORT:-?} host ${SERVICE_HOST:-?}"
command -v python3 >/dev/null || { echo "LỖI: máy chủ chưa có python3"; exit 1; }
python3 --version
set -a; . ./taihoadon.env; set +a
[ -n "$SERVICE_PORT" ] && TAIHOADON_PORT="$SERVICE_PORT"   # cổng FASTPANEL cấp cho backend Systemd
export TAIHOADON_PORT
export TAIHOADON_SERVER=1 TAIHOADON_DATA="$PWD/data" TAIHOADON_BIND=127.0.0.1 TAIHOADON_TRUST_PROXY=1
mkdir -p data && chmod 700 data
if [ ! -x venv/bin/python ] || [ ! -f venv/.da-cai ]; then
  echo "Chuẩn bị môi trường Python…"
  rm -rf venv
  python3 -m venv venv || { echo "venv đầy đủ không được, thử --without-pip"; rm -rf venv; python3 -m venv --without-pip venv; } || { echo "LỖI: không tạo được venv"; exit 1; }
  if [ ! -x venv/bin/pip ]; then
    curl -fsSL https://bootstrap.pypa.io/get-pip.py -o get-pip.py && venv/bin/python get-pip.py -q && rm -f get-pip.py || { echo "LỖI: không cài được pip"; exit 1; }
  fi
  venv/bin/pip install -q openpyxl==3.1.5 pypdf==5.1.0 cryptography==43.0.3 xlrd==2.0.1 reportlab==4.2.5 || { echo "LỖI: không cài được thư viện"; exit 1; }
  echo "Đã cài xong thư viện."
  touch venv/.da-cai
fi
# Bản mới cần thêm thư viện (vd xlrd đọc sao kê .xls) → tự cài bổ sung
if ! venv/bin/python -c "import openpyxl, pypdf, cryptography, xlrd, reportlab" 2>/dev/null; then
  echo "Cài bổ sung thư viện…"
  venv/bin/pip install -q openpyxl==3.1.5 pypdf==5.1.0 cryptography==43.0.3 xlrd==2.0.1 reportlab==4.2.5 || echo "LỖI: không cài được thư viện bổ sung"
fi
# Trình duyệt chạy ngầm để in bản HTML của cổng thuế ra "PDF thuế" (tải một lần ~150 MB vào browsers/)
export PLAYWRIGHT_BROWSERS_PATH="$PWD/browsers"
if [ ! -f browsers/.da-thu ]; then
  mkdir -p browsers
  echo "Cài Chromium để in PDF thuế (một lần)…"
  if venv/bin/pip install -q playwright==1.48.0 && venv/bin/python -m playwright install chromium; then
    CH=$(ls -d browsers/chromium-*/chrome-linux*/chrome 2>/dev/null | head -1)
    if [ -n "$CH" ] && "$CH" --headless=new --no-sandbox --disable-gpu --dump-dom about:blank >/dev/null 2>&1; then
      echo "Chromium chạy được – PDF thuế dùng bản HTML của cổng."
    else
      echo "Chromium đã tải nhưng không chạy được (máy chủ thiếu thư viện hệ thống) – PDF thuế dùng bản dựng sẵn."
      [ -n "$CH" ] && ldd "$CH" 2>/dev/null | grep "not found" | head -20
    fi
  else
    echo "Không cài được Chromium – PDF thuế dùng bản dựng sẵn."
  fi
  touch browsers/.da-thu
fi
export PYTHONUNBUFFERED=1
exec venv/bin/python taihoadon.py --server
