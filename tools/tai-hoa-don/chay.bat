@echo off
chcp 65001 >nul
cd /d "%~dp0"
where py >nul 2>nul && (set PY=py -3) || (set PY=python)
%PY% -c "import openpyxl" 2>nul || %PY% -m pip install --quiet openpyxl
%PY% taihoadon.py %*
pause
