#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=== Starting SocialBlog servers ==="
echo ""
echo "Backend API:  http://localhost:8000"
echo "Frontend:     http://localhost:5173 (dev) or http://localhost:4173 (preview)"
echo ""

# Start backend in background
echo "Starting backend API on port 8000..."
php artisan serve --port=8000 &
BACKEND_PID=$!

# Wait for backend to be ready
sleep 3

# Start frontend preview (production build) in background
echo "Starting frontend preview on port 4173..."
cd frontend
npm run preview -- --port=4173 &
FRONTEND_PID=$!

echo ""
echo "Both servers running."
echo "  Backend:  http://localhost:8000"
echo "  Frontend: http://localhost:4173"
echo ""
echo "Press Ctrl+C to stop both servers."

# Wait for interrupt
trap 'echo ""; echo "Stopping servers..."; kill $BACKEND_PID $FRONTEND_PID 2>/dev/null; exit 0' INT
wait $BACKEND_PID $FRONTEND_PID
