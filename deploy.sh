#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=== SocialBlog Deployment ==="
echo ""

echo "Step 1: Building frontend..."
cd frontend
npm run build
cd ..
echo ""

echo "Step 2: Setting up production environment..."
if [ ! -f ".env.production" ]; then
  cp .env .env.production
fi

echo "Step 3: Running migrations..."
php artisan migrate:fresh --force --seed

echo ""
echo "=== Deployment complete ==="
echo ""
echo "To start the servers, run:"
echo "  ./start-servers.sh"
