# Phase 2: Follow System - Testing Guide

## Overview
Phase 2 implements a complete social follow system, enabling users to follow/unfollow each other and view social relationships.

## 🚀 What Was Implemented

### 1. Database Schema
- ✅ Created `follows` table with many-to-many self-referential relationship
- ✅ Unique constraint on `(follower_id, following_id)` for idempotency
- ✅ Indexes for performance on both foreign keys and timestamps
- ✅ Cascade delete on user deletion

### 2. User Model Enhancements
- ✅ `following()` relationship - users this user follows
- ✅ `followers()` relationship - users following this user
- ✅ `follow($user)` method - idempotent follow action
- ✅ `unfollow($user)` method - unfollow action
- ✅ `isFollowing($user)` method - check if following
- ✅ `isFollowedBy($user)` method - check if followed by
- ✅ `followersCount()` method - get follower count
- ✅ `followingCount()` method - get following count

### 3. Follow Controller
- ✅ Follow user endpoint
- ✅ Unfollow user endpoint
- ✅ Get followers list (paginated)
- ✅ Get following list (paginated)
- ✅ Get follow stats (counts)
- ✅ Get user profile with social stats

### 4. Follow Policy
- ✅ Prevent self-following
- ✅ Prevent duplicate follows

### 5. API Routes
- ✅ Protected routes: Follow, Unfollow, Stats
- ✅ Public routes: Followers list, Following list, User profiles

---

## 🧪 Testing the Follow System

### Prerequisites
1. Start the server:
```bash
cd Blog-Php
php artisan serve
```

2. Create two test users for testing follows:

**User A:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alice",
    "email": "alice@example.com",
    "password": "Password123!@",
    "password_confirmation": "Password123!@"
  }'
```

Save Alice's token as `TOKEN_A`

**User B:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Bob",
    "email": "bob@example.com",
    "password": "Password123!@",
    "password_confirmation": "Password123!@"
  }'
```

Save Bob's token as `TOKEN_B`

---

### 1. Follow a User (Protected)

**Alice follows Bob (User ID 2):**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully followed user",
  "data": {
    "user": {
      "id": 2,
      "name": "Bob",
      "email": "bob@example.com"
    },
    "is_following": true
  }
}
```

---

### 2. Attempt Duplicate Follow (Idempotency Test)

**Alice tries to follow Bob again:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "You are already following this user"
}
```

**Status:** 400 Bad Request ✅ (Idempotent - prevents duplicate follows)

---

### 3. Attempt Self-Follow (Security Test)

**Alice tries to follow herself (User ID 1):**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "You cannot follow yourself"
}
```

**Status:** 400 Bad Request ✅ (Security enforced)

---

### 4. Get User Profile with Stats (Public)

**Get Bob's profile (anyone can view):**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/2/profile
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Bob",
      "email": "bob@example.com",
      "created_at": "2026-02-05T18:00:00.000000Z",
      "followers_count": 1,
      "following_count": 0,
      "posts_count": 0
    }
  }
}
```

**With Authentication (Alice viewing Bob):**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/2/profile \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Bob",
      "email": "bob@example.com",
      "created_at": "2026-02-05T18:00:00.000000Z",
      "followers_count": 1,
      "following_count": 0,
      "posts_count": 0,
      "is_following": true,
      "is_followed_by": false
    }
  }
}
```

**Note:** Authenticated requests include `is_following` and `is_followed_by` flags

---

### 5. Get Followers List (Public)

**Get Bob's followers:**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/2/followers
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Bob"
    },
    "followers": [
      {
        "id": 1,
        "name": "Alice",
        "email": "alice@example.com",
        "created_at": "2026-02-05T18:00:00.000000Z"
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

**With Authentication (Bob viewing his own followers):**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/2/followers \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Bob"
    },
    "followers": [
      {
        "id": 1,
        "name": "Alice",
        "email": "alice@example.com",
        "created_at": "2026-02-05T18:00:00.000000Z",
        "is_following": false
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

**Note:** Each follower includes `is_following` flag when authenticated

---

### 6. Get Following List (Public)

**Get Alice's following list:**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/1/following
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Alice"
    },
    "following": [
      {
        "id": 2,
        "name": "Bob",
        "email": "bob@example.com",
        "created_at": "2026-02-05T18:00:00.000000Z"
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

### 7. Get My Follow Stats (Protected)

**Alice gets her own stats:**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/follow/stats \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "followers_count": 0,
    "following_count": 1
  }
}
```

---

### 8. Mutual Follow Scenario

**Now Bob follows Alice back:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully followed user",
  "data": {
    "user": {
      "id": 1,
      "name": "Alice",
      "email": "alice@example.com"
    },
    "is_following": true
  }
}
```

**Now check Alice's profile again:**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/1/profile \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Alice",
      "email": "alice@example.com",
      "created_at": "2026-02-05T18:00:00.000000Z",
      "followers_count": 1,
      "following_count": 1,
      "posts_count": 0,
      "is_following": true,
      "is_followed_by": true
    }
  }
}
```

**Note:** Both `is_following` and `is_followed_by` are `true` = Mutual follow! 🎉

---

### 9. Unfollow a User (Protected)

**Alice unfollows Bob:**

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/users/2/unfollow \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Successfully unfollowed user",
  "data": {
    "user": {
      "id": 2,
      "name": "Bob",
      "email": "bob@example.com"
    },
    "is_following": false
  }
}
```

---

### 10. Attempt Invalid Unfollow

**Alice tries to unfollow Bob again (not following):**

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/users/2/unfollow \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "You are not following this user"
}
```

**Status:** 400 Bad Request ✅

---

### 11. Pagination Testing

Create multiple users and test pagination:

**Request:**
```bash
# Get page 2 with 5 followers per page
curl -X GET "http://127.0.0.1:8000/api/v1/users/2/followers?per_page=5&page=2"
```

---

## 🔒 Security Features

### 1. Self-Follow Prevention
```bash
# Alice tries to follow herself
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Response:** 400 - "You cannot follow yourself" ✅

---

### 2. Idempotency
```bash
# Alice follows Bob twice
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"

curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Second Request:** 400 - "You are already following this user" ✅

---

### 3. Authorization Required
```bash
# Follow without token
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow
```

**Response:** 401 Unauthenticated ✅

---

## 📊 Production Features

### 1. Database Efficiency
- ✅ Unique constraint prevents duplicate follows
- ✅ Indexes on `follower_id`, `following_id`, `created_at`
- ✅ Cascade delete when user is deleted

### 2. Idempotent Operations
- ✅ `syncWithoutDetaching()` prevents duplicate follows
- ✅ Safe to call follow endpoint multiple times

### 3. Relationship Queries
- ✅ Efficient many-to-many queries
- ✅ Eager loading support
- ✅ Pagination on all lists

### 4. Privacy-Aware
- ✅ Public can view follower/following lists
- ✅ `is_following` flag only for authenticated users
- ✅ Own profile shows mutual follow status

---

## 🧪 Advanced Test Scenarios

### Scenario 1: Mutual Follow Check

**Setup:**
1. Alice follows Bob
2. Bob follows Alice

**Expected:**
- Alice's profile (viewed by Bob): `is_following: true`, `is_followed_by: true`
- Bob's profile (viewed by Alice): `is_following: true`, `is_followed_by: true`

---

### Scenario 2: Follow Chain

**Setup:**
1. Alice follows Bob
2. Bob follows Charlie
3. Charlie follows Alice

**Test:**
- Alice → Following: [Bob] → Followers: [Charlie]
- Bob → Following: [Charlie] → Followers: [Alice]
- Charlie → Following: [Alice] → Followers: [Bob]

---

### Scenario 3: Bulk Operations

**Create 20 users, have User 1 follow all:**

```bash
# Follow users 2-21
for i in {2..21}; do
  curl -X POST http://127.0.0.1:8000/api/v1/users/$i/follow \
    -H "Authorization: Bearer TOKEN_USER_1"
done

# Check stats
curl -X GET http://127.0.0.1:8000/api/v1/follow/stats \
  -H "Authorization: Bearer TOKEN_USER_1"
```

**Expected:** `following_count: 20`

---

## ⚠️ Error Handling Examples

### 1. Following Non-Existent User
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/9999/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**Response:** 404 Not Found

---

### 2. Invalid Pagination
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/users/1/followers?per_page=999"
```

**Response:** Max capped at 50 per page ✅

---

## 📈 Performance Considerations

### Database Queries
- `followers()` - Single JOIN query
- `following()` - Single JOIN query
- `isFollowing()` - `EXISTS` query (very fast)
- `followersCount()` - `COUNT` query with index

### Optimization Tips
- Use `withCount()` for efficient counting
- Cache follower counts for popular users (Phase 5)
- Use cursor pagination for very large lists

---

## ✅ Production-Grade Checklist

- ✅ Idempotent follow/unfollow operations
- ✅ Self-follow prevention
- ✅ Duplicate follow prevention
- ✅ Efficient database queries with indexes
- ✅ Pagination on all list endpoints
- ✅ Proper HTTP status codes
- ✅ Consistent JSON responses
- ✅ Error handling
- ✅ Authorization checks
- ✅ Privacy-aware responses

---

## 🎯 Integration with Existing Features

### Posts by Followed Users (Ready for Phase 5 - Feed)

Once Phase 5 is implemented, you can query:

```php
// Get posts from users I follow
$posts = Post::whereIn('user_id', $user->following()->pluck('id'))
    ->orderBy('created_at', 'desc')
    ->paginate(15);
```

---

## 🎉 Phase 2 Complete!

**What's Working:**
✅ Users can follow/unfollow each other
✅ Follower/Following lists with pagination
✅ User profiles with social stats
✅ Mutual follow detection
✅ Idempotent operations
✅ Security and validation

**Next Steps (Phase 3):**
- Media upload with Cloudinary/S3
- Async image processing
- Image optimization

---

**Test all scenarios before moving to Phase 3!**
