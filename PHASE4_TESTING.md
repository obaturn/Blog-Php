# Phase 4: Likes & Comments System - Testing Guide

## Overview
Phase 4 implements a complete engagement system with likes and comments, featuring idempotent operations and production-grade reliability.

## 🚀 What Was Implemented

### 1. Database Schema
- ✅ `likes` table with unique constraint `(user_id, post_id)` for idempotency
- ✅ `comments` table with soft deletes for moderation
- ✅ Indexes on all foreign keys and timestamps
- ✅ Cascade deletes when user or post is removed

### 2. Like System
- ✅ Idempotent like operation (safe to call multiple times)
- ✅ Idempotent unlike operation
- ✅ Toggle like endpoint (convenience)
- ✅ Get users who liked a post (paginated)
- ✅ Like counts on all posts

### 3. Comment System
- ✅ Create comments on posts
- ✅ Update own comments
- ✅ Delete own comments (soft delete)
- ✅ Get comments for a post (paginated)
- ✅ Comment counts on all posts
- ✅ Authorization checks (owner only)

### 4. Post Model Enhancements
- ✅ `likes()` relationship
- ✅ `comments()` relationship
- ✅ `likesCount()` method
- ✅ `commentsCount()` method
- ✅ `isLikedBy($userId)` method
- ✅ Counts loaded on all post responses

### 5. API Enhancements
- ✅ All post responses include `likes_count` and `comments_count`
- ✅ Authenticated requests include `is_liked` flag
- ✅ Comment responses include `can_edit` and `can_delete` flags

---

## 🧪 Testing the Likes & Comments System

### Prerequisites

**Start the server:**
```bash
cd Blog-Php
php artisan serve
```

**Create test data:**
```bash
# User A (Alice)
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Alice","email":"alice@test.com","password":"Pass123!@","password_confirmation":"Pass123!@"}'
# Save token as TOKEN_A

# User B (Bob)
curl -X POST http://127.0.0.1:8000/api/v1/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Bob","email":"bob@test.com","password":"Pass123!@","password_confirmation":"Pass123!@"}'
# Save token as TOKEN_B

# Alice creates a post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Post","content":"This is a test post for likes and comments!"}'
# Post ID will be 1
```

---

## 📝 LIKES SYSTEM TESTING

### Test 1: Like a Post

**Alice likes her own post:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post liked successfully",
  "data": {
    "post_id": 1,
    "is_liked": true,
    "likes_count": 1
  }
}
```

**Status:** 201 Created ✅

---

### Test 2: Idempotent Like (Call Multiple Times)

**Alice likes the same post again:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post already liked",
  "data": {
    "post_id": 1,
    "is_liked": true,
    "likes_count": 1
  }
}
```

**Status:** 200 OK ✅ (Not 201 - indicates already liked)

**Idempotency verified!** Safe to retry requests.

---

### Test 3: Unlike a Post

**Alice unlikes the post:**

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1/unlike \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post unliked successfully",
  "data": {
    "post_id": 1,
    "is_liked": false,
    "likes_count": 0
  }
}
```

**Status:** 200 OK ✅

---

### Test 4: Idempotent Unlike

**Alice unlikes again (already unliked):**

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1/unlike \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Post was not liked",
  "data": {
    "post_id": 1,
    "is_liked": false,
    "likes_count": 0
  }
}
```

**Status:** 200 OK ✅

**Idempotency verified!** Safe to retry.

---

### Test 5: Toggle Like

**Convenience endpoint - likes if not liked, unlikes if liked:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/toggle-like \
  -H "Authorization: Bearer TOKEN_A"
```

**First call:** Likes the post
**Second call:** Unlikes the post
**Third call:** Likes again

---

### Test 6: Multiple Users Liking

**Bob likes Alice's post:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected:**
```json
{
  "success": true,
  "message": "Post liked successfully",
  "data": {
    "post_id": 1,
    "is_liked": true,
    "likes_count": 1
  }
}
```

**Alice also likes:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected:** `likes_count: 2`

---

### Test 7: Get Users Who Liked

**Request:**
```bash
curl http://127.0.0.1:8000/api/v1/posts/1/likes
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "post_id": 1,
    "likes": [
      {
        "id": 1,
        "name": "Alice",
        "email": "alice@test.com",
        "liked_at": "2026-02-05T20:00:00.000000Z"
      },
      {
        "id": 2,
        "name": "Bob",
        "email": "bob@test.com",
        "liked_at": "2026-02-05T20:01:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 2,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

---

### Test 8: Post with Likes Count

**Get post (includes likes count):**

**Request:**
```bash
curl http://127.0.0.1:8000/api/v1/posts/1
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "post": {
      "id": 1,
      "title": "Test Post",
      "content": "This is a test post...",
      "likes_count": 2,
      "comments_count": 0,
      "user": { "id": 1, "name": "Alice" }
    }
  }
}
```

**With authentication (includes is_liked flag):**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/posts/1 \
  -H "Authorization: Bearer TOKEN_A"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "post": {
      "id": 1,
      "title": "Test Post",
      "likes_count": 2,
      "comments_count": 0,
      "is_liked": true
    }
  }
}
```

---

## 💬 COMMENTS SYSTEM TESTING

### Test 9: Add a Comment

**Bob comments on Alice's post:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN_B" \
  -H "Content-Type: application/json" \
  -d '{"body":"Great post, Alice!"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Comment added successfully",
  "data": {
    "comment": {
      "id": 1,
      "body": "Great post, Alice!",
      "user": {
        "id": 2,
        "name": "Bob",
        "email": "bob@test.com"
      },
      "created_at": "2026-02-05T20:10:00.000000Z",
      "updated_at": "2026-02-05T20:10:00.000000Z"
    },
    "comments_count": 1
  }
}
```

**Status:** 201 Created ✅

---

### Test 10: Add Multiple Comments

**Alice replies:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"body":"Thanks, Bob!"}'
```

**Expected:** `comments_count: 2`

---

### Test 11: Get All Comments for a Post

**Request:**
```bash
curl http://127.0.0.1:8000/api/v1/posts/1/comments
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "post_id": 1,
    "comments": [
      {
        "id": 2,
        "body": "Thanks, Bob!",
        "user": {
          "id": 1,
          "name": "Alice",
          "email": "alice@test.com"
        },
        "created_at": "2026-02-05T20:11:00.000000Z",
        "updated_at": "2026-02-05T20:11:00.000000Z"
      },
      {
        "id": 1,
        "body": "Great post, Alice!",
        "user": {
          "id": 2,
          "name": "Bob",
          "email": "bob@test.com"
        },
        "created_at": "2026-02-05T20:10:00.000000Z",
        "updated_at": "2026-02-05T20:10:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 2,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

**Note:** Comments ordered by `created_at DESC` (newest first)

---

### Test 12: Get Comments with Auth (Includes Permissions)

**Bob views comments (authenticated):**

**Request:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected Response (for Bob's comment):**
```json
{
  "id": 1,
  "body": "Great post, Alice!",
  "user": { "id": 2, "name": "Bob" },
  "can_edit": true,
  "can_delete": true,
  "created_at": "..."
}
```

**Expected Response (for Alice's comment):**
```json
{
  "id": 2,
  "body": "Thanks, Bob!",
  "user": { "id": 1, "name": "Alice" },
  "can_edit": false,
  "can_delete": false,
  "created_at": "..."
}
```

**Bob can only edit/delete his own comments!**

---

### Test 13: Update a Comment

**Bob edits his comment:**

**Request:**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer TOKEN_B" \
  -H "Content-Type: application/json" \
  -d '{"body":"Excellent post, Alice!"}'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Comment updated successfully",
  "data": {
    "comment": {
      "id": 1,
      "body": "Excellent post, Alice!",
      "user": { "id": 2, "name": "Bob" },
      "created_at": "2026-02-05T20:10:00.000000Z",
      "updated_at": "2026-02-05T20:15:00.000000Z"
    }
  }
}
```

**Status:** 200 OK ✅

---

### Test 14: Unauthorized Comment Update

**Alice tries to edit Bob's comment:**

**Request:**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"body":"Hacked!"}'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Unauthorized",
  "error": "You can only edit your own comments"
}
```

**Status:** 403 Forbidden ✅

---

### Test 15: Delete a Comment (Soft Delete)

**Bob deletes his comment:**

**Request:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer TOKEN_B"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Comment deleted successfully",
  "data": {
    "post_id": 1,
    "comments_count": 1
  }
}
```

**Status:** 200 OK ✅

**Verify deletion:**
```bash
curl http://127.0.0.1:8000/api/v1/posts/1/comments
```

**Expected:** Only 1 comment (Alice's) - Bob's comment is soft-deleted

---

### Test 16: Comment Validation

**Empty comment:**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"body":""}'
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "body": ["Comment text is required."]
  }
}
```

**Status:** 422 Unprocessable Entity ✅

---

**Too long comment (>2000 chars):**

**Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d "{\"body\":\"$(printf 'a%.0s' {1..2001})\"}"
```

**Expected Response:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "body": ["Comment cannot exceed 2000 characters."]
  }
}
```

---

## 🔄 INTEGRATION TESTS

### Test 17: Full Engagement Flow

**Complete user journey:**

```bash
# 1. Create post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"title":"Engagement Test","content":"Testing likes and comments"}'

# 2. Bob likes the post
curl -X POST http://127.0.0.1:8000/api/v1/posts/2/like \
  -H "Authorization: Bearer TOKEN_B"

# 3. Bob comments
curl -X POST http://127.0.0.1:8000/api/v1/posts/2/comments \
  -H "Authorization: Bearer TOKEN_B" \
  -H "Content-Type: application/json" \
  -d '{"body":"Nice post!"}'

# 4. Alice likes her own post
curl -X POST http://127.0.0.1:8000/api/v1/posts/2/like \
  -H "Authorization: Bearer TOKEN_A"

# 5. Alice replies to comment
curl -X POST http://127.0.0.1:8000/api/v1/posts/2/comments \
  -H "Authorization: Bearer TOKEN_A" \
  -H "Content-Type: application/json" \
  -d '{"body":"Thanks!"}'

# 6. Get post with all engagement data
curl http://127.0.0.1:8000/api/v1/posts/2
```

**Expected Final State:**
- `likes_count: 2`
- `comments_count: 2`
- Full engagement visible

---

### Test 18: Cascade Deletes

**Delete a post (should delete likes and comments):**

```bash
# Post ID 2 has 2 likes and 2 comments

# Delete post
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/2 \
  -H "Authorization: Bearer TOKEN_A"

# Verify likes deleted
curl http://127.0.0.1:8000/api/v1/posts/2/likes
# Expected: 404 Not Found

# Verify comments deleted
curl http://127.0.0.1:8000/api/v1/posts/2/comments
# Expected: 404 Not Found
```

**Success!** Cascade deletes work properly.

---

## 📊 Production Features Testing

### Idempotency Tests

**Like - Safe to retry:**
```bash
# Call 5 times
for i in {1..5}; do
  curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
    -H "Authorization: Bearer TOKEN_A"
done
```

**Expected:** First returns 201, rest return 200. Only 1 like created.

**Unlike - Safe to retry:**
```bash
# Call 5 times
for i in {1..5}; do
  curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1/unlike \
    -H "Authorization: Bearer TOKEN_A"
done
```

**Expected:** First returns 200 with "unliked", rest return 200 with "was not liked". Only 1 delete happens.

---

### Soft Delete Verification

**Check database directly:**
```bash
cd Blog-Php
php artisan tinker
```

```php
// See soft-deleted comments
Comment::onlyTrashed()->get();

// Restore a soft-deleted comment
Comment::withTrashed()->find(1)->restore();

// Permanently delete
Comment::withTrashed()->find(1)->forceDelete();
```

---

## ✅ Phase 4 Completion Checklist

### Database
- [ ] Likes table created with unique constraint
- [ ] Comments table created with soft deletes
- [ ] Indexes on all foreign keys
- [ ] Cascade deletes configured

### API Endpoints
- [ ] POST `/posts/{id}/like` - Like a post
- [ ] DELETE `/posts/{id}/unlike` - Unlike a post
- [ ] POST `/posts/{id}/toggle-like` - Toggle like
- [ ] GET `/posts/{id}/likes` - Get likers
- [ ] POST `/posts/{id}/comments` - Add comment
- [ ] GET `/posts/{id}/comments` - Get comments
- [ ] PUT `/comments/{id}` - Update comment
- [ ] DELETE `/comments/{id}` - Delete comment

### Testing
- [ ] Idempotent like/unlike
- [ ] Multiple users can like
- [ ] Comments CRUD works
- [ ] Authorization enforced
- [ ] Validation works
- [ ] Soft deletes work
- [ ] Cascade deletes work
- [ ] Counts appear on posts

---

## 🎉 Phase 4 Complete!

**What's Working:**
✅ Idempotent like/unlike
✅ Comments with soft deletes
✅ Authorization (owner-only edits)
✅ Validation on all inputs
✅ Engagement counts on posts
✅ `is_liked` flags for authenticated users
✅ Pagination on likes and comments

**Next: Phase 5 - Feed System**

Ready to implement personalized feeds with cache!
