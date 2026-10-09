#!/usr/bin/env bash
# Cài bản web Tải hoá đơn lên VPS Ubuntu/Debian mới: sudo bash cai-dat.sh
set -euo pipefail
cd "$(dirname "$0")"

if ! command -v docker >/dev/null 2>&1; then
  echo ">> Cài Docker…"
  curl -fsSL https://get.docker.com | sh
fi

if [ ! -f .env ]; then
  read -rp "Tên miền (đã trỏ bản ghi A về IP VPS này), ví dụ hoadon.congty.vn: " DOMAIN
  [ -n "$DOMAIN" ] || { echo "Cần nhập tên miền"; exit 1; }
  sed "s/^DOMAIN=.*/DOMAIN=$DOMAIN/" .env.example > .env
  chmod 600 .env
fi

mkdir -p data
chown -R 1000:1000 data
chmod 700 data

if command -v ufw >/dev/null 2>&1; then
  ufw allow 22/tcp >/dev/null || true
  ufw allow 80/tcp >/dev/null || true
  ufw allow 443/tcp >/dev/null || true
fi

docker compose up -d --build
sleep 5
echo
echo ">> Đã chạy. Mở https://$(grep ^DOMAIN= .env | cut -d= -f2)"
echo ">> Tài khoản quản trị đầu tiên:"
docker compose logs app | grep -A2 "Tạo tài khoản quản trị" || echo "   (đã tạo từ trước – quên mật khẩu: docker compose exec app python taihoadon.py --set-password admin)"
