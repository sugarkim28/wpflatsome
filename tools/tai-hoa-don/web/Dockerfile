FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 TZ=Asia/Ho_Chi_Minh \
    TAIHOADON_SERVER=1 TAIHOADON_DATA=/data TAIHOADON_TRUST_PROXY=1 TAIHOADON_PORT=8765

RUN apt-get update && apt-get install -y --no-install-recommends tzdata chromium fonts-dejavu-core \
    && rm -rf /var/lib/apt/lists/* \
    && pip install --no-cache-dir openpyxl==3.1.5 pypdf==5.1.0 cryptography==43.0.3 xlrd==2.0.1 reportlab==4.2.5 \
    && useradd -m -u 1000 app && mkdir /data && chown app /data

WORKDIR /app
COPY taihoadon.py .
COPY fonts ./fonts
USER app
EXPOSE 8765
CMD ["python", "taihoadon.py", "--server"]
