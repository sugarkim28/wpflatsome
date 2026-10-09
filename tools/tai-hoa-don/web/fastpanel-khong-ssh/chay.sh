#!/bin/bash
# Khởi động bản web Tải hoá đơn – dùng làm "lệnh chạy" của site Systemd trong FASTPANEL (không cần SSH).
# Lần đầu tự tạo môi trường Python và cài thư viện (mất 1–3 phút), các lần sau chạy ngay.
cd "$(dirname "$0")" || exit 1
set -a; . ./taihoadon.env; set +a
[ -n "$SERVICE_PORT" ] && TAIHOADON_PORT="$SERVICE_PORT"   # cổng FASTPANEL cấp cho backend Systemd
export TAIHOADON_PORT
export TAIHOADON_SERVER=1 TAIHOADON_DATA="$PWD/data" TAIHOADON_BIND=127.0.0.1 TAIHOADON_TRUST_PROXY=1
mkdir -p data && chmod 700 data
if [ ! -x venv/bin/python ] || [ ! -f venv/.da-cai ]; then
  echo "Chuẩn bị môi trường Python…"
  rm -rf venv
  python3 -m venv venv 2>/dev/null || python3 -m venv --without-pip venv || exit 1
  if [ ! -x venv/bin/pip ]; then
    curl -fsSL https://bootstrap.pypa.io/get-pip.py -o get-pip.py && venv/bin/python get-pip.py -q && rm -f get-pip.py || exit 1
  fi
  venv/bin/pip install -q openpyxl==3.1.5 pypdf==5.1.0 cryptography==43.0.3 || exit 1
  touch venv/.da-cai
fi
exec venv/bin/python taihoadon.py --server
