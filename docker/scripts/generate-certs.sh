#!/usr/bin/env bash
set -euo pipefail

CERTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/certs"

mkdir -p "$CERTS_DIR"

if [ -f "$CERTS_DIR/premind.crt" ] && [ -f "$CERTS_DIR/premind.key" ]; then
    echo "[certs] already exist at $CERTS_DIR — refusing to overwrite."
    echo "[certs] delete them first if you want fresh ones: rm $CERTS_DIR/premind.{crt,key}"
    exit 0
fi

openssl req -x509 -newkey rsa:4096 -nodes -days 365 \
    -keyout "$CERTS_DIR/premind.key" \
    -out "$CERTS_DIR/premind.crt" \
    -subj "/CN=localhost" \
    -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"

chmod 644 "$CERTS_DIR/premind.crt"
chmod 640 "$CERTS_DIR/premind.key"

echo "[certs] generated at $CERTS_DIR"
echo "[certs] for browser-trusted certs without warnings, install mkcert and run:"
echo "         mkcert -install"
echo "         mkcert -cert-file $CERTS_DIR/premind.crt -key-file $CERTS_DIR/premind.key localhost 127.0.0.1"
