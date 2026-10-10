#!/usr/bin/env bash
# Cài bản web Tải hoá đơn trên VPS có FASTPANEL (chạy nền bằng systemd, nginx của FASTPANEL lo HTTPS).
#   sudo bash cai-dat.sh hoadon.congty.vn
# Chạy lại lệnh này để cập nhật phiên bản mới (dữ liệu giữ nguyên).
set -euo pipefail
DIR="$(cd "$(dirname "$0")" && pwd)"
SRC="$DIR/../../taihoadon.py"
APP=/opt/taihoadon
DATA=/var/lib/taihoadon
ENVF=/etc/taihoadon.env
PORT=8765

[ "$(id -u)" = 0 ] || { echo "Chạy bằng root: sudo bash $0 <tên-miền>"; exit 1; }
[ -f "$SRC" ] || { echo "Không thấy $SRC"; exit 1; }

if [ ! -f "$ENVF" ]; then
  DOMAIN="${1:-}"
  [ -n "$DOMAIN" ] || read -rp "Tên miền của site Reverse proxy trong FASTPANEL (vd hoadon.congty.vn): " DOMAIN
  [ -n "$DOMAIN" ] || { echo "Cần tên miền"; exit 1; }
  cat > "$ENVF" <<EOT
TAIHOADON_SERVER=1
TAIHOADON_DATA=$DATA
TAIHOADON_BIND=127.0.0.1
TAIHOADON_PORT=$PORT
TAIHOADON_DOMAIN=$DOMAIN
TAIHOADON_TRUST_PROXY=1
TAIHOADON_MAX_JOBS=4
TAIHOADON_ADMIN_USER=admin
TZ=Asia/Ho_Chi_Minh
EOT
  chmod 600 "$ENVF"
fi

echo ">> Cài Python…"
if command -v apt-get >/dev/null; then
  apt-get update -qq && apt-get install -y -qq python3 python3-venv >/dev/null
fi
id taihoadon >/dev/null 2>&1 || useradd --system --home "$APP" --shell /usr/sbin/nologin taihoadon
mkdir -p "$APP" "$DATA"
[ -x "$APP/venv/bin/python" ] || python3 -m venv "$APP/venv"
"$APP/venv/bin/pip" install -q --upgrade pip
"$APP/venv/bin/pip" install -q openpyxl==3.1.5 pypdf==5.1.0 cryptography==43.0.3 xlrd==2.0.1
install -m 644 "$SRC" "$APP/taihoadon.py"
chown -R taihoadon:taihoadon "$DATA"
chmod 700 "$DATA"

cat > /etc/systemd/system/taihoadon.service <<EOT
[Unit]
Description=Tai hoa don dien tu (ban web)
After=network-online.target
Wants=network-online.target

[Service]
User=taihoadon
Group=taihoadon
EnvironmentFile=$ENVF
WorkingDirectory=$APP
ExecStart=$APP/venv/bin/python $APP/taihoadon.py --server
Restart=always
RestartSec=5
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=$DATA

[Install]
WantedBy=multi-user.target
EOT
systemctl daemon-reload
systemctl enable -q taihoadon
systemctl restart taihoadon
sleep 3
systemctl --no-pager --lines=0 status taihoadon | head -3
echo
echo ">> App chạy tại http://127.0.0.1:$PORT (chỉ trong máy). Trong FASTPANEL tạo site Reverse proxy trỏ về địa chỉ này."
echo ">> Tài khoản quản trị đầu tiên:"
journalctl -u taihoadon --no-pager | grep -A2 "Tạo tài khoản quản trị" | tail -3 \
  || echo "   (đã tạo từ trước – quên mật khẩu: sudo bash $DIR/dat-mat-khau.sh admin)"
