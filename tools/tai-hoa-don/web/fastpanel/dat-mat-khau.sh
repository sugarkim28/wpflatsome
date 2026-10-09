#!/usr/bin/env bash
# Tạo / đặt lại mật khẩu quản trị:  sudo bash dat-mat-khau.sh admin
set -euo pipefail
NAME="${1:-admin}"
set -a; . /etc/taihoadon.env; set +a
cd /opt/taihoadon
runuser -u taihoadon -- /opt/taihoadon/venv/bin/python taihoadon.py --set-password "$NAME"
systemctl restart taihoadon
