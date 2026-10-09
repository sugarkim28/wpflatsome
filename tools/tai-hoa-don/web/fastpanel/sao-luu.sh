#!/usr/bin/env bash
# Sao lưu /var/lib/taihoadon (DN, người dùng, hoá đơn, khoá bí mật) – giữ 14 bản gần nhất.
# Hằng ngày 23:30:  crontab -e  →  30 23 * * * bash /đường/dẫn/sao-luu.sh
set -euo pipefail
OUT=/root/sao-luu-taihoadon
mkdir -p "$OUT" && chmod 700 "$OUT"
f="$OUT/hoadon_$(date +%Y%m%d_%H%M).tar.gz"
tar -czf "$f" -C /var/lib taihoadon
chmod 600 "$f"
ls -1t "$OUT"/hoadon_*.tar.gz | tail -n +15 | xargs -r rm --
echo "Đã sao lưu: $f"
