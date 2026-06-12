#!/bin/bash
set -e

DOMAIN="${1:-your-domain.com}"
EMAIL="${2:-admin@${DOMAIN}}"
SSL_DIR="$(dirname "$0")/../nginx/ssl"

echo "=== SSL Certificate Setup for ${DOMAIN} ==="
echo ""

mkdir -p "${SSL_DIR}"

if command -v certbot &> /dev/null; then
    echo "Using Certbot (Let's Encrypt)..."

    sudo certbot certonly --webroot \
        -w ./public \
        -d "${DOMAIN}" \
        -d "www.${DOMAIN}" \
        --email "${EMAIL}" \
        --agree-tos \
        --non-interactive

    sudo cp "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" "${SSL_DIR}/"
    sudo cp "/etc/letsencrypt/live/${DOMAIN}/privkey.pem" "${SSL_DIR}/"
    sudo cp "/etc/letsencrypt/live/${DOMAIN}/chain.pem" "${SSL_DIR}/"

    echo ""
    echo "Setting up auto-renewal..."
    echo "0 3 * * * sudo certbot renew --quiet && sudo cp /etc/letsencrypt/live/${DOMAIN}/*.pem ${SSL_DIR}/ && sudo docker exec hospital-app nginx -s reload" | sudo crontab -

    echo ""
    echo "Generating DH parameters (4096-bit)..."
    openssl dhparam -out "${SSL_DIR}/dhparam.pem" 4096

    echo ""
    echo "✅ SSL certificates installed successfully!"
    echo "  - Domain: ${DOMAIN}"
    echo "  - Certificates: ${SSL_DIR}/"
    echo "  - Auto-renewal: Configured via cron"

elif command -v openssl &> /dev/null; then
    echo "WARNING: certbot not found. Generating self-signed certificate for testing..."
    echo ""

    openssl req -x509 -nodes -days 365 -newkey rsa:4096 \
        -keyout "${SSL_DIR}/privkey.pem" \
        -out "${SSL_DIR}/fullchain.pem" \
        -subj "/C=BD/ST=Dhaka/L=Dhaka/O=Xet Specialized Hospital/CN=${DOMAIN}"

    cp "${SSL_DIR}/fullchain.pem" "${SSL_DIR}/chain.pem"

    openssl dhparam -out "${SSL_DIR}/dhparam.pem" 4096

    echo ""
    echo "⚠️  Self-signed certificate generated. For production:"
    echo "  1. Install certbot: sudo apt install certbot"
    echo "  2. Run: sudo certbot --nginx -d ${DOMAIN}"
    echo ""
    echo "  Self-signed cert files:"
    echo "  - Private Key: ${SSL_DIR}/privkey.pem"
    echo "  - Certificate: ${SSL_DIR}/fullchain.pem"
    echo "  - DH Params: ${SSL_DIR}/dhparam.pem"
else
    echo "ERROR: Neither certbot nor openssl found."
    echo "Install with: sudo apt install openssl certbot"
    exit 1
fi
