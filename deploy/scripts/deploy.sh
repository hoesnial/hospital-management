#!/bin/bash
set -e

APP_DIR="/var/www/hospital-management"
DOMAIN="${1:-your-domain.com}"

echo "=== Deploying Hospital Management System ==="
echo ""

# Check requirements
command -v docker >/dev/null 2>&1 || { echo "ERROR: Docker required"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || command -v docker compose >/dev/null 2>&1 || { echo "ERROR: Docker Compose required"; exit 1; }

cd "${APP_DIR}"

# Create .env if not exists
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Set production environment
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" .env

# Generate unique passwords if not set
if ! grep -q "^DB_PASSWORD=" .env || [ -z "$(grep ^DB_PASSWORD= .env | cut -d= -f2)" ]; then
    echo "DB_PASSWORD=$(openssl rand -base64 32)" >> .env
fi
if ! grep -q "^DB_ROOT_PASSWORD=" .env || [ -z "$(grep ^DB_ROOT_PASSWORD= .env | cut -d= -f2)" ]; then
    echo "DB_ROOT_PASSWORD=$(openssl rand -base64 48)" >> .env
fi
if ! grep -q "^REDIS_PASSWORD=" .env || [ -z "$(grep ^REDIS_PASSWORD= .env | cut -d= -f2)" ]; then
    echo "REDIS_PASSWORD=$(openssl rand -base64 32)" >> .env
fi

# Update domain in nginx config
sed -i "s/your-domain.com/${DOMAIN}/g" deploy/nginx/conf.d/app.conf

# Generate JWT secret if not set
if ! grep -q "^JWT_SECRET=" .env || [ -z "$(grep ^JWT_SECRET= .env | cut -d= -f2)" ]; then
    echo "JWT_SECRET=base64:$(openssl rand -base64 64)" >> .env
fi

# Build and start containers
echo "Building Docker images..."
docker compose build --no-cache

echo "Starting services..."
docker compose up -d

echo "Running migrations..."
docker compose exec -T app php artisan migrate --force

echo "Caching config..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo ""
echo "✅ Deployment complete!"
echo "  - Application: https://${DOMAIN}"
echo "  - Database: Secured with random credentials"
echo "  - Redis: Secured with password"
echo "  - SSL: Configure via deploy/scripts/setup-ssl.sh"
echo ""
echo "Next steps:"
echo "  1. Run: bash deploy/scripts/setup-ssl.sh ${DOMAIN}"
echo "  2. Set JWT_SECRET in .env"
echo "  3. Verify all services: docker compose ps"
echo "  4. Check logs: docker compose logs -f"
