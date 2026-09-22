# SocialBlog Deployment Guide

## Overview

This project is a Laravel API backend + React SPA frontend. Each platform handles environment variables securely without needing to commit secrets.

## Vercel (Frontend React SPA)

1. Push code to GitHub
2. Import repo at [vercel.com/new](https://vercel.com/new)
3. Vercel auto-detects the Vite project
4. Go to Settings → Environment Variables and add:
   | Variable | Value |
   |----------|-------|
   | `VITE_API_URL` | `https://your-app.onrender.com` |
5. Deploy

Vercel injects these at build time and runtime. No `.env` file needed on Vercel.

## Render (Backend Laravel API)

1. Import same repo at [render.com/deploy](https://render.com/deploy)
2. Render auto-detects the `render.yaml` file
3. Render creates a **Web Service** (API) and a **PostgreSQL Database** together
4. Database credentials are automatically injected as environment variables (`DATABASE_URL`)
5. Go to the Web Service settings → Environment and add all variables from `.env.example` (replace placeholders with real values):
   - `APP_KEY` - generate with: `php artisan key:generate`
   - `APP_URL` - your Render URL (e.g., `https://socialblog-api.onrender.com`)
   - `CORS_ALLOWED_ORIGINS` - your Vercel URL + API URL (e.g., `https://socialblog.vercel.app,https://socialblog-api.onrender.com`)
   - `MAIL_MAILER` - use `log` for testing, `smtp`/`ses`/`postmark` for production
   - `CLOUDINARY_*` - if using Cloudinary for media uploads
   - All other values can stay as defaults for free tier
6. First deploy runs `php artisan migrate --force` automatically (from Dockerfile)
7. Deploy

## No secrets in Git

- `.env` - ignored by Git, contains real secrets locally
- `.env.example` - committed to Git, has placeholder values only
- Render/Vercel - environment variables set in platform dashboard, injected at runtime
- `render.yaml` - uses `fromGroup` for database credentials (auto-injected by Render, never stored)

## Pre-deployment checklist

- [ ] Generate APP_KEY: `php artisan key:generate --force`
- [ ] Test locally with production settings: `APP_ENV=production APP_DEBUG=false php artisan serve`
- [ ] Set up a real mail service for production emails
- [ ] Configure Cloudinary or local storage for media uploads
- [ ] Verify all API endpoints work from the frontend
- [ ] Run a security audit on user input validation
