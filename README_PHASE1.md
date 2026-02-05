# SocialBlog API - Phase 1 Complete ✅

Production-grade social blogging platform built with Laravel 12 and Sanctum authentication.

## 🚀 Quick Start

### 1. Start the Server
```bash
cd Blog-Php
php artisan serve
```

API available at: `http://127.0.0.1:8000`

### 2. Quick Test with cURL

**Register a user:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "Password123!@",
    "password_confirmation": "Password123!@"
  }'
```

**Create a post (use token from registration):**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Hello World",
    "content": "My first post!"
  }'
```

**Get all posts (no auth needed):**
```bash
curl http://127.0.0.1:8000/api/v1/posts
```

## 📚 Documentation

- **[Complete Testing Guide](PHASE1_TESTING.md)** - All endpoints with examples
- **[Changes Summary](PHASE1_CHANGES.md)** - What was built and modified

## 🎯 What's Implemented

### ✅ Authentication & Security
- Token-based auth with Laravel Sanctum
- Secure password hashing with complexity requirements
- Authorization policies (users can only edit their own posts)
- Input validation on all endpoints
- Rate limiting ready

### ✅ User Management
- Register
- Login
- Logout
- Get Profile
- Revoke all tokens

### ✅ Post Management
- Create post (authenticated)
- Update post (owner only)
- Delete post (owner only)
- List all posts (public, paginated)
- Get single post (public)
- Get my posts (authenticated)

### ✅ Production Features
- Pagination (max 50 per page)
- Error handling with try-catch
- Consistent JSON responses
- Health check endpoint
- Database indexes for performance

## 📊 API Endpoints Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/health` | No | Health check |
| POST | `/api/v1/register` | No | Create account |
| POST | `/api/v1/login` | No | Get auth token |
| POST | `/api/v1/logout` | Yes | Revoke token |
| GET | `/api/v1/profile` | Yes | Get user info |
| GET | `/api/v1/posts` | No | List all posts |
| GET | `/api/v1/posts/{id}` | No | Get one post |
| POST | `/api/v1/posts` | Yes | Create post |
| PUT | `/api/v1/posts/{id}` | Yes | Update post |
| DELETE | `/api/v1/posts/{id}` | Yes | Delete post |
| GET | `/api/v1/my-posts` | Yes | Get my posts |

## 🛠️ Tech Stack

- **Backend:** Laravel 12
- **Authentication:** Laravel Sanctum
- **Database:** SQLite (dev), MySQL/PostgreSQL ready
- **Server:** PHP 8.2
- **Package Manager:** Composer

## 🔐 Security Features

✅ Password hashing (bcrypt)
✅ Token authentication
✅ Authorization policies
✅ Input validation
✅ SQL injection prevention (Eloquent ORM)
✅ XSS prevention

## 📈 Performance Features

✅ Database indexes on frequently queried columns
✅ Pagination on list endpoints
✅ Eager loading relationships
✅ Efficient queries

## 🧪 Testing

See [PHASE1_TESTING.md](PHASE1_TESTING.md) for:
- Complete cURL examples
- Expected responses
- Error handling tests
- Authorization tests

## 🎯 Next: Phase 2

Coming next:
- Follow/Unfollow system
- Social relationships
- Follower/Following counts
- User profiles

## 📝 Notes

- All responses use consistent JSON structure
- All errors include proper HTTP status codes
- Tokens don't expire (can be configured)
- Images will be handled in Phase 3 (Cloudinary/S3)

---

**Phase 1 Status: Production Ready! 🎉**

Ready for testing and deployment.
