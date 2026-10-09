#!/usr/bin/env bash
# Sao lưu dữ liệu (DN, người dùng, hoá đơn, khoá bí mật) ra file nén; giữ 14 bản gần nhất.
# Hằng ngày lúc 23:30: crontab -e →  30 23 * * * /đường/dẫn/web/sao-luu.sh
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p sao-luu
f="sao-luu/hoadon_$(date +%Y%m%d_%H%M).tar.gz"
tar -czf "$f" data
chmod 600 "$f"
ls -1t sao-luu/hoadon_*.tar.gz | tail -n +15 | xargs -r rm --
echo "Đã sao lưu: $f"
