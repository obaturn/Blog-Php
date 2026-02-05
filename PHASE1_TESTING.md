# Phase 1: Authentication & Foundation - Testing Guide

## Overview
Phase 1 implements secure authentication using Laravel Sanctum and production-grade API endpoints for user and post management.

## 🚀 What Was Implemented

### 1. Database Schema
- ✅ Fixed typo: `tittle` → `title` in posts table
- ✅ Added `user_id` foreign key to posts
- ✅ Renamed `image` → `image_url`
- ✅ Added indexes for performance (`user_id`, `created_at`)
- ✅ Installed Laravel Sanctum with `personal_access_tokens` table

### 2. Models & Relationships
- ✅ Updated `Post` model with proper fillable fields and relationships
- ✅ Updated `User` model with `posts()` relationship

### 3. Authentication System
- ✅ `AuthController` with register, login, logout, profile endpoints
- ✅ Token-based authentication using Sanctum
- ✅ Secure password hashing with validation

### 4. Post Management
- ✅ `PostController` with CRUD operations
- ✅ Authorization using `PostPolicy` (users can only edit/delete their own posts)
- ✅ Pagination support

### 5. Validation
- ✅ `RegisterRequest` - Password complexity, email uniqueness
- ✅ `LoginRequest` - Email/password validation
- ✅ `StorePostRequest` - Title, content, image validation
- ✅ `UpdatePostRequest` - Partial update validation

### 6. API Routes
- ✅ Public routes: `/api/v1/posts`, `/api/v1/posts/{id}`
- ✅ Auth routes: `/api/v1/register`, `/api/v1/login`, `/api/v1/logout`
- ✅ Protected routes: Create/update/delete posts
- ✅ Health check endpoint: `/api/health`

---

## 🧪 Testing the API

### Prerequisites
1. Start the Laravel development server:
```bash
cd Blog-Php
php artisan serve
```

The API will be available at: `http://127.0.0.1:8000/api/v1`

---

### 1. Health Check (No Auth Required)

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "timestamp": "2026-02-05T17:30:00.000000Z",
  "service": "SocialBlog API",
  "version": "1.0.0"
}
```

---

### 2. User Registration

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!@",
    "password_confirmation": "SecurePass123!@"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2026-02-05T17:30:00.000000Z"
    },
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
    "token_type": "Bearer"
  }
}
```

**Save the token** - You'll need it for authenticated requests!

---

### 3. User Login

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!@"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|XyZaBcDeFgHiJkLmNoPqRsTuV...",
    "token_type": "Bearer"
  }
}
```

---

### 4. Get User Profile (Protected)

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "created_at": "2026-02-05T17:30:00.000000Z",
      "updated_at": "2026-02-05T17:30:00.000000Z"
    }
  }
}
```

---

### 5. Create a Post (Protected)

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My First Blog Post",
    "content": "This is the content of my first post on SocialBlog!"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 1,
      "user_id": 1,
      "title": "My First Blog Post",
      "content": "This is the content of my first post on SocialBlog!",
      "image_url": null,
      "created_at": "2026-02-05T17:35:00.000000Z",
      "updated_at": "2026-02-05T17:35:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

---

### 6. Get All Posts (Public)

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/posts
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 1,
        "user_id": 1,
        "title": "My First Blog Post",
        "content": "This is the content of my first post on SocialBlog!",
        "image_url": null,
        "created_at": "2026-02-05T17:35:00.000000Z",
        "updated_at": "2026-02-05T17:35:00.000000Z",
        "user": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

---

### 7. Get Single Post (Public)

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
      "title": "My First Blog Post",
      "content": "This is the content of my first post on SocialBlog!",
      "image_url": null,
      "created_at": "2026-02-05T17:35:00.000000Z",
      "updated_at": "2026-02-05T17:35:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

---

### 8. Update a Post (Protected - Owner Only)

**Request:**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Blog Post Title",
    "content": "Updated content here"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post updated successfully",
  "data": {
    "post": {
      "id": 1,
      "user_id": 1,
      "title": "Updated Blog Post Title",
      "content": "Updated content here",
      "image_url": null,
      "created_at": "2026-02-05T17:35:00.000000Z",
      "updated_at": "2026-02-05T17:36:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  }
}
```

---

### 9. Delete a Post (Protected - Owner Only)

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post deleted successfully"
}
```

---

### 10. Get My Posts (Protected)

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/my-posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "posts": [...],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 5,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

---

### 11. Logout (Protected)

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

**Note:** After logout, the token becomes invalid.

---

### 12. Revoke All Tokens (Protected)

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/revoke-all \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "All tokens revoked successfully"
}
```

---

## 🔒 Security Features Implemented

1. **Password Security:**
   - Minimum 8 characters
   - Must include: uppercase, lowercase, numbers, symbols
   - Hashed using bcrypt

2. **Token-Based Auth:**
   - Sanctum personal access tokens
   - Secure token generation
   - Token revocation on logout

3. **Authorization:**
   - PostPolicy ensures users can only edit/delete their own posts
   - Unauthorized attempts return 403 Forbidden

4. **Input Validation:**
   - All requests validated using Form Requests
   - SQL injection prevention via Eloquent ORM
   - XSS prevention via proper sanitization

5. **Rate Limiting:**
   - Throttle middleware ready (configured in routes)
   - Can be applied per endpoint

---

## 🧪 Testing Authorization (Important!)

### Test Case: User Cannot Edit Another User's Post

1. Register User A and get token
2. Create a post with User A's token
3. Register User B and get token
4. Try to update User A's post with User B's token

**Expected:** `403 Forbidden` response

```bash
# User A creates post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer USER_A_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "A Post", "content": "Content"}'

# User B tries to update (should fail)
curl -X PUT http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer USER_B_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title": "Hacked!"}'
```

**Response:**
```json
{
  "success": false,
  "message": "Unauthorized",
  "error": "You are not authorized to update this post"
}
```

---

## 📊 Pagination Testing

Test pagination by creating multiple posts and using query parameters:

```bash
# Get page 2 with 5 items per page
curl -X GET "http://127.0.0.1:8000/api/v1/posts?per_page=5&page=2"
```

---

## ⚠️ Error Handling Examples

### 1. Invalid Credentials
```bash
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email": "wrong@example.com", "password": "wrong"}'
```

**Response: 401 Unauthorized**

### 2. Missing Required Fields
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Response: 422 Validation Error**

### 3. Unauthorized Access
```bash
curl -X GET http://127.0.0.1:8000/api/v1/profile
# Without Authorization header
```

**Response: 401 Unauthenticated**

---

## ✅ Production-Grade Features

### What Makes This Production-Ready:

1. **Separation of Concerns:**
   - Controllers handle HTTP
   - Policies handle authorization
   - Form Requests handle validation
   - Models handle data

2. **Error Handling:**
   - Try-catch blocks in all controllers
   - Meaningful error messages
   - Proper HTTP status codes

3. **Security:**
   - Password hashing
   - Token authentication
   - Authorization policies
   - Input validation

4. **Scalability Ready:**
   - Pagination implemented
   - Indexed database columns
   - Stateless API design

5. **Code Quality:**
   - PSR-4 autoloading
   - Type hints
   - DocBlocks
   - Consistent naming

---

## 🎯 Next Steps (Phase 2)

After testing Phase 1, we'll implement:
- Follow/Unfollow system
- Social relationships
- Follower/Following counts

---

## 📝 Notes

- All timestamps are in ISO 8601 format
- All responses follow consistent JSON structure
- Tokens are long-lived (default: no expiration)
- Database uses SQLite for development (can switch to MySQL/PostgreSQL for production)

---

**Phase 1 Complete! 🎉**

Test all endpoints and verify everything works before moving to Phase 2.
