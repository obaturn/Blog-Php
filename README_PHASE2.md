# SocialBlog API - Phase 2: Follow System Complete ✅

Social networking features with follow/unfollow functionality, follower lists, and user profiles.

## 🚀 Quick Start

### Test the Follow System

**1. Create two users:**
```bash
# User A (Alice)
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Alice",
    "email": "alice@test.com",
    "password": "Password123!@",
    "password_confirmation": "Password123!@"
  }'
# Save token as TOKEN_A

# User B (Bob)
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Bob",
    "email": "bob@test.com",
    "password": "Password123!@",
    "password_confirmation": "Password123!@"
  }'
# Save token as TOKEN_B
```

**2. Alice follows Bob (User ID 2):**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
```

**3. Check Bob's profile:**
```bash
curl http://127.0.0.1:8000/api/v1/users/2/profile
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 2,
      "name": "Bob",
      "followers_count": 1,
      "following_count": 0,
      "posts_count": 0
    }
  }
}
```

---

## 🎯 What's New in Phase 2

### ✅ Follow System
- Follow/Unfollow users
- Idempotent operations (safe to retry)
- Self-follow prevention
- Duplicate follow prevention

### ✅ Social Relationships
- Get user's followers (paginated)
- Get user's following (paginated)
- Mutual follow detection
- Follow statistics

### ✅ User Profiles
- Enhanced profiles with social stats
- Followers count
- Following count
- Posts count
- `is_following` / `is_followed_by` flags (when authenticated)

### ✅ Production Features
- Unique database constraints
- Indexed queries for performance
- Pagination on all lists
- Privacy-aware responses

---

## 📊 New API Endpoints

### Protected Endpoints (Require Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/users/{id}/follow` | Follow a user |
| DELETE | `/api/v1/users/{id}/unfollow` | Unfollow a user |
| GET | `/api/v1/follow/stats` | Get my stats |

### Public Endpoints (No Auth)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/users/{id}/profile` | User profile with stats |
| GET | `/api/v1/users/{id}/followers` | User's followers |
| GET | `/api/v1/users/{id}/following` | Who user follows |

---

## 🔥 Key Features

### 1. Idempotent Follow
Call the follow endpoint multiple times safely:
```bash
# First call - creates follow
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
# Response: 200 OK

# Second call - returns error (already following)
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"
# Response: 400 - "You are already following this user"
```

---

### 2. Self-Follow Prevention
```bash
# Alice tries to follow herself (User ID 1)
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_A"
# Response: 400 - "You cannot follow yourself"
```

---

### 3. Mutual Follow Detection
When both users follow each other:
```bash
# Alice follows Bob
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"

# Bob follows Alice back
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_B"

# Check Alice's profile (from Bob's view)
curl -X GET http://127.0.0.1:8000/api/v1/users/1/profile \
  -H "Authorization: Bearer TOKEN_B"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Alice",
      "followers_count": 1,
      "following_count": 1,
      "is_following": true,
      "is_followed_by": true
    }
  }
}
```

---

### 4. Privacy-Aware Responses

**Public view (no auth):**
```bash
curl http://127.0.0.1:8000/api/v1/users/2/profile
```
Response includes: `id`, `name`, `email`, `followers_count`, `following_count`

**Authenticated view:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/users/2/profile \
  -H "Authorization: Bearer TOKEN_A"
```
Response includes: Everything above **+** `is_following`, `is_followed_by`

---

## 🗄️ Database Schema

### New Table: `follows`
```
id               | BIGINT PRIMARY KEY
follower_id      | BIGINT (FK to users.id)
following_id     | BIGINT (FK to users.id)
created_at       | TIMESTAMP
updated_at       | TIMESTAMP

UNIQUE (follower_id, following_id)
INDEX (follower_id)
INDEX (following_id)
CASCADE DELETE on user deletion
```

---

## 📚 Documentation

- **[PHASE2_TESTING.md](PHASE2_TESTING.md)** - Complete testing guide with 11 test scenarios
- **[PHASE2_CHANGES.md](PHASE2_CHANGES.md)** - Detailed implementation changes
- **[PHASE1_TESTING.md](PHASE1_TESTING.md)** - Auth & posts testing guide

---

## 🧪 Quick Tests

### Test 1: Follow Flow
```bash
# Alice follows Bob
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"

# Check Bob's followers
curl http://127.0.0.1:8000/api/v1/users/2/followers

# Alice unfollows Bob
curl -X DELETE http://127.0.0.1:8000/api/v1/users/2/unfollow \
  -H "Authorization: Bearer TOKEN_A"
```

---

### Test 2: Get My Stats
```bash
curl -X GET http://127.0.0.1:8000/api/v1/follow/stats \
  -H "Authorization: Bearer TOKEN_A"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "followers_count": 1,
    "following_count": 3
  }
}
```

---

### Test 3: Pagination
```bash
# Get first 5 followers
curl "http://127.0.0.1:8000/api/v1/users/2/followers?per_page=5&page=1"
```

---

## 🔐 Security

✅ Authentication required for follow/unfollow
✅ Self-follow prevention at policy level
✅ Duplicate follow prevention via DB constraint
✅ Cascade delete when user is deleted
✅ Proper authorization checks

---

## 📈 Performance

✅ Database indexes on `follower_id`, `following_id`
✅ Efficient many-to-many queries
✅ Pagination on all list endpoints (max 50 per page)
✅ Select only needed columns in queries

---

## 🎯 What's Working

**Follow System:**
✅ Follow users
✅ Unfollow users
✅ View followers list
✅ View following list
✅ Get follow counts
✅ Idempotent operations

**User Profiles:**
✅ View any user's profile
✅ See follower/following counts
✅ Mutual follow detection
✅ Posts count

**From Phase 1:**
✅ Authentication (register, login, logout)
✅ Posts CRUD (create, read, update, delete)
✅ Authorization policies

---

## 🚀 Combined Example: Full User Journey

```bash
# 1. Alice registers
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@test.com","password":"Pass123!@","password_confirmation":"Pass123!@"}'

# 2. Alice creates a post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"title":"Hello World","content":"My first post!"}'

# 3. Bob registers
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Bob","email":"bob@test.com","password":"Pass123!@","password_confirmation":"Pass123!@"}'

# 4. Bob follows Alice
curl -X POST http://127.0.0.1:8000/api/v1/users/1/follow \
  -H "Authorization: Bearer TOKEN_B"

# 5. Bob views Alice's profile
curl -X GET http://127.0.0.1:8000/api/v1/users/1/profile \
  -H "Authorization: Bearer TOKEN_B"

# 6. Alice follows Bob back
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer TOKEN_A"

# Now they're mutually following! 🎉
```

---

## 🎉 Phase 1 + 2 Complete!

**Total Features:**
- ✅ User authentication
- ✅ Post management
- ✅ Follow system
- ✅ User profiles
- ✅ Pagination
- ✅ Authorization
- ✅ Validation
- ✅ Error handling

**Next: Phase 3 - Media Upload**
- Cloudinary/S3 integration
- Async image processing
- Image optimization

---

**Ready for production testing! 🚀**
