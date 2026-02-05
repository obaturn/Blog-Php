# Cloudinary Setup Guide

## 📸 Getting Started with Cloudinary

### Step 1: Create a Cloudinary Account

1. Go to [https://cloudinary.com/users/register_free](https://cloudinary.com/users/register_free)
2. Sign up for a free account (includes 25GB storage and 25GB bandwidth/month)
3. Verify your email address

### Step 2: Get Your Credentials

1. After login, go to your **Dashboard**
2. You'll see your credentials in the "Account Details" section:
   - **Cloud Name**
   - **API Key**
   - **API Secret**

### Step 3: Configure Your Laravel Application

1. **Copy environment variables:**

Open your `.env` file and add:

```env
CLOUDINARY_CLOUD_NAME=your_cloud_name_here
CLOUDINARY_API_KEY=your_api_key_here
CLOUDINARY_API_SECRET=your_api_secret_here
CLOUDINARY_SECURE=true

MEDIA_DRIVER=cloudinary
MEDIA_MAX_SIZE=5120
```

Replace `your_cloud_name_here`, `your_api_key_here`, and `your_api_secret_here` with your actual credentials.

2. **Example configuration:**

```env
CLOUDINARY_CLOUD_NAME=demo-cloud
CLOUDINARY_API_KEY=123456789012345
CLOUDINARY_API_SECRET=abcdefghijklmnopqrstuvwxyz123456
CLOUDINARY_SECURE=true

MEDIA_DRIVER=cloudinary
MEDIA_MAX_SIZE=5120
```

### Step 4: Test the Integration

Run a test upload using cURL:

```bash
# 1. Register and login to get token
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"your@email.com","password":"your_password"}'

# 2. Create a post with an image
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=My First Photo Post" \
  -F "content=Check out this amazing photo!" \
  -F "image=@/path/to/your/image.jpg"
```

### Step 5: Set Up Queue Worker (Required for Async Processing)

The image upload happens asynchronously using Laravel queues. You need to run the queue worker:

**Development:**
```bash
php artisan queue:work
```

**Production (using Supervisor):**

Create a supervisor configuration file at `/etc/supervisor/conf.d/socialblog-worker.conf`:

```ini
[program:socialblog-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/Blog-Php/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/project/Blog-Php/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start socialblog-worker:*
```

---

## 🎯 Cloudinary Features in SocialBlog

### 1. Automatic Optimization
- **Quality:** Auto-optimized based on content
- **Format:** Auto-converted to best format (WebP when supported)
- **Compression:** Automatic smart compression

### 2. Responsive Images
Multiple sizes generated automatically:
- **Thumbnail:** 300x300px (for lists, previews)
- **Medium:** 800x800px (for single post view)
- **Large:** 1200x1200px (for full-screen view)

### 3. Secure URLs
All images use HTTPS by default for security.

### 4. CDN Delivery
Images are delivered via Cloudinary's global CDN for fast loading worldwide.

---

## 📂 Folder Structure in Cloudinary

Your images will be organized as:

```
socialblog/
├── posts/
│   ├── posts_abc123.jpg
│   ├── posts_def456.png
│   └── posts_ghi789.webp
└── profiles/  (ready for Phase 8)
    ├── profiles_user1.jpg
    └── profiles_user2.png
```

---

## 🔧 Configuration Options

### Maximum Upload Size

Default: **5MB** (5120 KB)

To change, update in `.env`:
```env
MEDIA_MAX_SIZE=10240  # 10MB
```

Or in `config/media.php`:
```php
'max_size' => env('MEDIA_MAX_SIZE', 10240),
```

### Allowed Image Types

Current: JPEG, PNG, JPG, GIF, WebP

To modify, edit `config/media.php`:
```php
'allowed_types' => [
    'image/jpeg',
    'image/png',
    'image/jpg',
    'image/gif',
    'image/webp',
    'image/svg+xml',  // Add SVG
],
```

### Image Transformations

Customize sizes in `config/media.php`:
```php
'transformations' => [
    'thumbnail' => [
        'width' => 200,
        'height' => 200,
        'crop' => 'fill',
        'quality' => 'auto',
    ],
    'medium' => [
        'width' => 600,
        'height' => 600,
        'crop' => 'limit',
        'quality' => 'auto',
    ],
],
```

---

## 🚨 Troubleshooting

### Error: "Cloudinary credentials not configured"

**Fix:** Ensure your `.env` has all three Cloudinary variables set correctly.

### Error: "Failed to upload image"

**Possible causes:**
1. Invalid Cloudinary credentials
2. File size exceeds limit
3. Invalid file type
4. Network issues

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

### Images not appearing after upload

**Cause:** Queue worker not running

**Fix:**
```bash
# Check if queue worker is running
php artisan queue:work

# Or restart queue worker
php artisan queue:restart
php artisan queue:work
```

### Job stuck in queue

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

---

## 📊 Monitoring Cloudinary Usage

### Via Cloudinary Dashboard
1. Login to Cloudinary
2. Go to **Dashboard**
3. View:
   - Storage usage
   - Bandwidth usage
   - Transformations used
   - API calls

### Free Plan Limits
- **Storage:** 25GB
- **Bandwidth:** 25GB/month
- **Transformations:** 25,000/month
- **Max file size:** 10MB

### Upgrade Plans
If you exceed limits:
- **Plus Plan:** $99/month (104GB storage, 104GB bandwidth)
- **Advanced Plan:** $224/month (214GB storage, 214GB bandwidth)
- **Custom Enterprise:** Contact sales

---

## 🔐 Security Best Practices

### 1. Never Commit Credentials
✅ Use `.env` file
❌ Don't hardcode credentials in code

### 2. Use Environment Variables
```php
// ✅ Good
config('media.cloudinary.cloud_name')

// ❌ Bad
'cloud_name' => 'my-cloud-name'
```

### 3. Rotate API Secrets Regularly
- Go to Cloudinary Dashboard
- Settings → Security
- Regenerate API Secret
- Update `.env` file

### 4. Use Signed URLs for Sensitive Content
(Can be implemented in future phases)

---

## 🎨 Advanced Features (Future Enhancements)

### 1. Video Upload
Add to `config/media.php`:
```php
'allowed_types' => [
    'video/mp4',
    'video/mpeg',
],
```

### 2. Face Detection
Automatically crop to faces:
```php
'transformation' => [
    'gravity' => 'face',
    'crop' => 'thumb',
],
```

### 3. AI-Powered Tagging
Auto-tag images with AI:
```php
$result = $cloudinary->uploadApi()->upload($file, [
    'categorization' => 'google_tagging',
]);
```

---

## 📚 Useful Resources

- **Cloudinary Docs:** https://cloudinary.com/documentation
- **PHP SDK Docs:** https://cloudinary.com/documentation/php_integration
- **Transformation Reference:** https://cloudinary.com/documentation/image_transformations
- **Media Library:** https://cloudinary.com/console/media_library

---

## ✅ Setup Checklist

- [ ] Created Cloudinary account
- [ ] Got Cloud Name, API Key, API Secret
- [ ] Added credentials to `.env`
- [ ] Tested upload via cURL
- [ ] Started queue worker
- [ ] Verified image appears in Cloudinary dashboard
- [ ] Checked image URL in post response

---

**Setup complete! Ready to upload images! 📸**
