# Phase 3: Media Upload - Testing Guide

## Overview
Phase 3 implements production-grade image upload with Cloudinary integration, featuring async processing, automatic optimization, and CDN delivery.

## 🚀 What Was Implemented

### 1. Cloudinary Integration
- ✅ Cloudinary PHP SDK installed
- ✅ Secure credential management via `.env`
- ✅ Image upload to cloud storage
- ✅ Automatic optimization and format conversion
- ✅ CDN delivery for fast loading

### 2. Media Upload Service
- ✅ `MediaUploadService` - Handles all Cloudinary operations
- ✅ File validation (type, size, extension)
- ✅ Unique public ID generation
- ✅ Image transformations (thumbnail, medium, large)
- ✅ Delete images from Cloudinary
- ✅ Extract public ID from URLs

### 3. Async Image Processing
- ✅ `ProcessPostImageJob` - Background job for uploads
- ✅ Queue-based processing (non-blocking)
- ✅ Retry mechanism with exponential backoff (3 attempts)
- ✅ Automatic cleanup of temporary files
- ✅ Error logging and monitoring

### 4. Post Controller Updates
- ✅ Accept image files in create/update
- ✅ Async upload dispatch
- ✅ Delete old images on update
- ✅ Delete images on post deletion
- ✅ Image processing status in responses

### 5. Configuration
- ✅ `config/media.php` - Centralized media settings
- ✅ Environment variables for credentials
- ✅ Configurable upload limits and transformations

---

## 🧪 Prerequisites

### 1. Cloudinary Account Setup

**If you haven't set up Cloudinary yet:**
1. Read [CLOUDINARY_SETUP.md](CLOUDINARY_SETUP.md) for detailed instructions
2. Get your credentials from Cloudinary dashboard
3. Add credentials to `.env`:

```env
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
CLOUDINARY_SECURE=true
MEDIA_DRIVER=cloudinary
MEDIA_MAX_SIZE=5120
```

### 2. Start Queue Worker

**IMPORTANT:** Images are processed asynchronously, so you must run the queue worker:

```bash
cd Blog-Php
php artisan queue:work
```

Keep this terminal open while testing!

### 3. Prepare Test Images

Download or create test images:
- **Small:** 100KB JPG
- **Medium:** 500KB PNG  
- **Large:** 2MB JPG
- **Too large:** 6MB JPG (for testing validation)
- **Invalid:** PDF file (for testing validation)

---

## 🧪 Testing the Upload System

### Test 1: Create Post with Image

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Beautiful Sunset" \
  -F "content=Check out this amazing sunset I captured!" \
  -F "image=@/path/to/your/image.jpg"
```

**Expected Response (Immediate):**
```json
{
  "success": true,
  "message": "Post created successfully. Image is being processed.",
  "data": {
    "post": {
      "id": 1,
      "user_id": 1,
      "title": "Beautiful Sunset",
      "content": "Check out this amazing sunset I captured!",
      "image_url": null,
      "created_at": "2026-02-05T19:00:00.000000Z",
      "updated_at": "2026-02-05T19:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "Alice",
        "email": "alice@example.com"
      }
    },
    "image_processing": true
  }
}
```

**Note:** `image_url` is `null` initially because processing is async.

---

### Test 2: Check Image After Processing

Wait 5-10 seconds for the queue worker to process, then fetch the post again:

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/posts/1
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "post": {
      "id": 1,
      "user_id": 1,
      "title": "Beautiful Sunset",
      "content": "Check out this amazing sunset I captured!",
      "image_url": "https://res.cloudinary.com/your-cloud/image/upload/v1234567890/socialblog/posts/posts_abc123xyz.jpg",
      "created_at": "2026-02-05T19:00:00.000000Z",
      "updated_at": "2026-02-05T19:00:15.000000Z",
      "user": {
        "id": 1,
        "name": "Alice",
        "email": "alice@example.com"
      }
    }
  }
}
```

**Success!** `image_url` now contains the Cloudinary URL.

---

### Test 3: Verify Image in Cloudinary Dashboard

1. Login to [Cloudinary Dashboard](https://cloudinary.com/console)
2. Go to **Media Library**
3. Look for folder: `socialblog/posts/`
4. You should see your uploaded image
5. Click on it to view details

**Expected:**
- Image displays correctly
- Automatic optimizations applied
- CDN URL generated

---

### Test 4: Update Post with New Image

**Request:**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Updated Sunset Photo" \
  -F "content=Added a better version of the photo!" \
  -F "image=@/path/to/new-image.jpg"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post updated successfully. New image is being processed.",
  "data": {
    "post": {
      "id": 1,
      "title": "Updated Sunset Photo",
      "content": "Added a better version of the photo!",
      "image_url": "https://res.cloudinary.com/your-cloud/image/upload/v1234567890/socialblog/posts/posts_abc123xyz.jpg",
      "user": { "id": 1, "name": "Alice", "email": "alice@example.com" }
    },
    "image_processing": true
  }
}
```

**What happens:**
1. Old image is deleted from Cloudinary
2. New image is uploaded asynchronously
3. Post `image_url` will update once processing completes

---

### Test 5: Create Post Without Image

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Text-Only Post",
    "content": "No image needed for this one!"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 2,
      "title": "Text-Only Post",
      "content": "No image needed for this one!",
      "image_url": null,
      "user": { "id": 1, "name": "Alice", "email": "alice@example.com" }
    },
    "image_processing": false
  }
}
```

**Success!** Posts can still be created without images.

---

### Test 6: Delete Post with Image

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

**What happens:**
1. Post is deleted from database
2. Image is deleted from Cloudinary automatically
3. Check Cloudinary dashboard - image should be gone

---

## 🚨 Error Handling Tests

### Test 7: Upload Too Large File (>5MB)

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Large File Test" \
  -F "content=Testing size limit" \
  -F "image=@/path/to/large-file.jpg"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "image": ["The image field must not be greater than 5120 kilobytes."]
  }
}
```

**Status:** 422 Unprocessable Entity ✅

---

### Test 8: Upload Invalid File Type (PDF)

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Invalid Type Test" \
  -F "content=Testing file type validation" \
  -F "image=@/path/to/document.pdf"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "image": ["The uploaded file must be an image."]
  }
}
```

**Status:** 422 Unprocessable Entity ✅

---

### Test 9: Upload Without Authentication

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -F "title=No Auth Test" \
  -F "content=Testing auth" \
  -F "image=@/path/to/image.jpg"
```

**Expected Response:**
```json
{
  "message": "Unauthenticated."
}
```

**Status:** 401 Unauthorized ✅

---

## 📊 Monitoring and Logs

### Check Queue Jobs

**View queue status:**
```bash
php artisan queue:listen --verbose
```

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Retry failed jobs:**
```bash
php artisan queue:retry all
```

---

### Check Application Logs

**Laravel logs:**
```bash
tail -f storage/logs/laravel.log
```

**Look for:**
```
[INFO] Image upload job dispatched {"post_id":1,"temp_path":"temp/post-images/xyz.jpg"}
[INFO] Processing post image {"post_id":1,"temp_file":"temp/post-images/xyz.jpg"}
[INFO] Image uploaded to Cloudinary {"public_id":"socialblog/posts/posts_xyz","url":"https://..."}
[INFO] Post image processed successfully {"post_id":1,"image_url":"https://..."}
```

**On errors:**
```
[ERROR] Failed to process post image {"post_id":1,"error":"...","attempt":1}
```

---

## 🎯 Production Features Testing

### Test 10: Async Processing (Non-Blocking)

**Measure response time:**

```bash
time curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Speed Test" \
  -F "content=Testing async upload" \
  -F "image=@/path/to/large-image.jpg"
```

**Expected:** Response returns in <2 seconds (not waiting for upload to complete)

---

### Test 11: Retry Mechanism

**Simulate Cloudinary downtime:**
1. Temporarily set wrong Cloudinary credentials in `.env`
2. Create post with image
3. Check failed jobs: `php artisan queue:failed`
4. Fix credentials
5. Retry: `php artisan queue:retry all`

**Expected:** Job retries 3 times with exponential backoff (10s, 30s, 60s)

---

### Test 12: Automatic Cleanup

**Create post with image, then check temp folder:**

```bash
# Create post with image
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Cleanup Test" \
  -F "content=Testing temp cleanup" \
  -F "image=@/path/to/image.jpg"

# Wait for processing
sleep 10

# Check temp folder (should be empty)
ls storage/app/temp/post-images/
```

**Expected:** Temp folder is empty (files cleaned up after processing)

---

## 🎨 Image Transformation Testing

### Test 13: Responsive Images

Cloudinary automatically generates optimized versions. Test by modifying URL:

**Original URL:**
```
https://res.cloudinary.com/demo/image/upload/v123/socialblog/posts/posts_abc.jpg
```

**Thumbnail (300x300):**
```
https://res.cloudinary.com/demo/image/upload/c_fill,h_300,w_300/v123/socialblog/posts/posts_abc.jpg
```

**Medium (800x800):**
```
https://res.cloudinary.com/demo/image/upload/c_limit,h_800,w_800/v123/socialblog/posts/posts_abc.jpg
```

**Auto WebP:**
```
https://res.cloudinary.com/demo/image/upload/f_auto,q_auto/v123/socialblog/posts/posts_abc.jpg
```

Access these URLs in browser - all should work!

---

## 📈 Performance Metrics

### Expected Metrics

| Metric | Value |
|--------|-------|
| API Response Time (with image) | <2 seconds |
| Image Upload Time (background) | 5-10 seconds |
| Queue Processing Time | 5-15 seconds |
| Image CDN Load Time | <500ms (worldwide) |

### Measure Upload Performance

```bash
# Start timer
START=$(date +%s)

# Create post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Perf Test" \
  -F "content=Performance testing" \
  -F "image=@/path/to/image.jpg" \
  -o /dev/null -s

# End timer
END=$(date +%s)
DIFF=$((END - START))
echo "API Response Time: $DIFF seconds"
```

---

## 🔐 Security Testing

### Test 14: File Type Validation

Try uploading:
- ✅ JPG - Should work
- ✅ PNG - Should work
- ✅ GIF - Should work
- ✅ WebP - Should work
- ❌ PDF - Should fail
- ❌ EXE - Should fail
- ❌ HTML - Should fail

### Test 15: Size Limit Enforcement

- ✅ 100KB - Should work
- ✅ 1MB - Should work
- ✅ 5MB - Should work
- ❌ 6MB - Should fail with validation error

---

## 🎯 Integration Tests

### Test 16: Full User Journey

```bash
# 1. Register
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Charlie","email":"charlie@test.com","password":"Pass123!@","password_confirmation":"Pass123!@"}'

# 2. Create post with image
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer CHARLIE_TOKEN" \
  -F "title=My First Photo" \
  -F "content=Hello world!" \
  -F "image=@/path/to/photo.jpg"

# 3. Wait for processing
sleep 10

# 4. Get post (should have image URL)
curl http://127.0.0.1:8000/api/v1/posts/1

# 5. Update with new image
curl -X PUT http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer CHARLIE_TOKEN" \
  -F "title=Updated Photo" \
  -F "image=@/path/to/new-photo.jpg"

# 6. Delete post (should remove from Cloudinary)
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer CHARLIE_TOKEN"
```

---

## ✅ Phase 3 Completion Checklist

### Setup
- [ ] Cloudinary account created
- [ ] Credentials added to `.env`
- [ ] Queue worker started
- [ ] Test images prepared

### Testing
- [ ] Create post with image
- [ ] Verify image uploaded to Cloudinary
- [ ] Update post with new image
- [ ] Delete post and verify Cloudinary cleanup
- [ ] Test file size validation
- [ ] Test file type validation
- [ ] Check async processing works
- [ ] Verify logs for errors
- [ ] Test retry mechanism
- [ ] Verify temp file cleanup

### Verification
- [ ] Images appear in Cloudinary dashboard
- [ ] Images load via CDN URLs
- [ ] Old images deleted on update
- [ ] Images deleted on post deletion
- [ ] No temp files left behind
- [ ] Queue jobs complete successfully

---

## 🚨 Common Issues

### Issue: "Image not uploading"
**Fix:** Ensure queue worker is running: `php artisan queue:work`

### Issue: "Invalid Cloudinary credentials"
**Fix:** Double-check `.env` values match Cloudinary dashboard

### Issue: "Image URL still null after 1 minute"
**Fix:** Check `storage/logs/laravel.log` for errors. Likely Cloudinary credentials issue.

### Issue: "Queue jobs failing"
**Fix:** 
```bash
php artisan queue:failed
# Check error message
php artisan queue:retry all
```

---

## 📚 Next Steps (Phase 4)

After Phase 3, you can implement:
- Likes system
- Comments system
- Idempotent operations
- Soft deletes for comments

---

**Phase 3 Testing Complete! 🎉**

Your image upload system is production-ready with async processing, automatic optimization, and CDN delivery!
