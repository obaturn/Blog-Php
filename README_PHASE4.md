# SocialBlog API - Phase 4: Likes & Comments Complete ✅

Production-grade engagement system with idempotent likes and comments with soft deletes.

## 🚀 Quick Start

### Test Likes System

```bash
# Like a post
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer YOUR_TOKEN"

# Unlike a post
curl -X DELETE http://127.0.0.1:8000/api/v1/posts/1/unlike \
  -H "Authorization: Bearer YOUR_TOKEN"

# Toggle like (like if not liked, unlike if liked)
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/toggle-like \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get users who liked
curl http://127.0.0.1:8000/api/v1/posts/1/likes
```

---

### Test Comments System

```bash
# Add a comment
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Great post!"}'

# Get all comments
curl http://127.0.0.1:8000/api/v1/posts/1/comments

# Update your comment
curl -X PUT http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Updated comment!"}'

# Delete your comment (soft delete)
curl -X DELETE http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🎯 What's New in Phase 4

### ✅ Likes System
- **Idempotent like** - Safe to call multiple times
- **Idempotent unlike** - Safe to call multiple times
- **Toggle like** - Convenience endpoint
- **Get likers** - See who liked a post
- **Like counts** - On every post response

### ✅ Comments System
- **Create comments** - Add comments to posts
- **Update comments** - Edit your own comments
- **Delete comments** - Soft delete (can be restored)
- **Get comments** - Paginated list
- **Comment counts** - On every post response
- **Authorization** - Owner-only edit/delete

### ✅ Production Features
- **Idempotent operations** - Network retry safe
- **Soft deletes** - Comments can be restored
- **Unique constraints** - Database-level enforcement
- **Authorization** - Policy-based access control
- **Validation** - Input sanitization
- **Pagination** - All list endpoints

---

## 📊 New API Endpoints

### Likes

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/posts/{id}/like` | ✅ | Like a post |
| DELETE | `/api/v1/posts/{id}/unlike` | ✅ | Unlike a post |
| POST | `/api/v1/posts/{id}/toggle-like` | ✅ | Toggle like |
| GET | `/api/v1/posts/{id}/likes` | ❌ | Get likers |

### Comments

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/posts/{id}/comments` | ✅ | Add comment |
| GET | `/api/v1/posts/{id}/comments` | ❌ | Get comments |
| PUT | `/api/v1/comments/{id}` | ✅ | Update comment |
| DELETE | `/api/v1/comments/{id}` | ✅ | Delete comment |

---

## 🔥 Key Features

### 1. Idempotent Likes

**Safe to retry:**
```bash
# Call like 5 times
for i in {1..5}; do
  curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
    -H "Authorization: Bearer TOKEN"
done
```

**Result:**
- First call: `201 Created` - Like created
- Subsequent calls: `200 OK` - "Post already liked"
- Only **1 like** in database

**Why it matters:**
- ✅ Network retries don't create duplicates
- ✅ Production-grade reliability
- ✅ No client-side state tracking needed

---

### 2. Soft Delete Comments

**Delete a comment:**
```bash
curl -X DELETE http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer TOKEN"
```

**What happens:**
- Comment marked as `deleted_at = NOW()`
- Not shown in public lists
- Can be restored by admin (future)
- Audit trail preserved

**Benefits:**
- ✅ Content moderation
- ✅ Undo deletions
- ✅ Compliance (data retention)

---

### 3. Enhanced Post Responses

**Before Phase 4:**
```json
{
  "id": 1,
  "title": "My Post",
  "content": "...",
  "user": { "id": 1, "name": "Alice" }
}
```

**After Phase 4:**
```json
{
  "id": 1,
  "title": "My Post",
  "content": "...",
  "likes_count": 15,
  "comments_count": 8,
  "is_liked": true,
  "user": { "id": 1, "name": "Alice" }
}
```

**New fields:**
- `likes_count` - Number of likes
- `comments_count` - Number of comments
- `is_liked` - Whether current user liked (if authenticated)

---

### 4. Privacy-Aware Responses

**Public view (no auth):**
```bash
curl http://127.0.0.1:8000/api/v1/posts/1/comments
```

**Response:**
```json
{
  "comments": [
    {
      "id": 1,
      "body": "Great post!",
      "user": { "id": 2, "name": "Bob" }
    }
  ]
}
```

**Authenticated view:**
```bash
curl -X GET http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer TOKEN"
```

**Response:**
```json
{
  "comments": [
    {
      "id": 1,
      "body": "Great post!",
      "user": { "id": 2, "name": "Bob" },
      "can_edit": true,
      "can_delete": true
    }
  ]
}
```

**Extra fields when authenticated:**
- `can_edit` - Whether current user can edit
- `can_delete` - Whether current user can delete

---

## 🗄️ Database Schema

### Likes Table
```sql
CREATE TABLE likes (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    post_id BIGINT,
    created_at TIMESTAMP,
    UNIQUE (user_id, post_id)  -- Idempotency!
)
```

**Unique constraint ensures:**
- One user can only like a post once
- Database-level enforcement
- Race condition safe

---

### Comments Table
```sql
CREATE TABLE comments (
    id BIGINT PRIMARY KEY,
    post_id BIGINT,
    user_id BIGINT,
    body TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL  -- Soft deletes
)
```

**Soft deletes:**
- `deleted_at` is NULL when active
- `deleted_at` set when deleted
- Query only active: `WHERE deleted_at IS NULL`

---

## 📚 Documentation

- **[PHASE4_TESTING.md](PHASE4_TESTING.md)** - 18 test scenarios with cURL examples
- **[PHASE4_CHANGES.md](PHASE4_CHANGES.md)** - Detailed implementation summary

---

## 🧪 Quick Tests

### Test Idempotency

**Like 3 times:**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like -H "Authorization: Bearer TOKEN"
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like -H "Authorization: Bearer TOKEN"
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like -H "Authorization: Bearer TOKEN"

# Check likes count
curl http://127.0.0.1:8000/api/v1/posts/1/likes
```

**Expected:** Only 1 like created ✅

---

### Test Authorization

**User B tries to edit User A's comment:**
```bash
curl -X PUT http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer USER_B_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Hacked!"}'
```

**Expected:** `403 Forbidden` ✅

---

### Test Soft Deletes

```bash
# Delete comment
curl -X DELETE http://127.0.0.1:8000/api/v1/comments/1 \
  -H "Authorization: Bearer TOKEN"

# Get comments (should not show deleted)
curl http://127.0.0.1:8000/api/v1/posts/1/comments
```

**Expected:** Deleted comment not in list ✅

---

## 🔐 Security

✅ **Authorization** - Users can only edit/delete their own comments
✅ **Validation** - Comments max 2000 chars, required field
✅ **Idempotency** - Database constraints prevent duplicates
✅ **Soft Deletes** - Content can be reviewed before permanent deletion
✅ **SQL Injection** - Protected via Eloquent ORM
✅ **XSS Prevention** - Laravel escapes output

---

## 📈 Performance

**Database Indexes:**
- ✅ `likes(user_id)` - Fast "posts I liked"
- ✅ `likes(post_id)` - Fast "who liked this post"
- ✅ `comments(post_id)` - Fast "comments on this post"
- ✅ `comments(deleted_at)` - Fast soft delete queries

**Eager Loading:**
```php
// Prevents N+1 queries
Comment::with('user:id,name,email')->paginate(15);
```

**Count Optimization:**
```php
// Single query for counts
Post::withCount(['likes', 'comments'])->get();
```

---

## 🎨 Example: Full Engagement Flow

```bash
# 1. Alice creates a post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer ALICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Post","content":"Hello!"}'

# 2. Bob likes the post
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer BOB_TOKEN"

# 3. Bob comments
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer BOB_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Nice post, Alice!"}'

# 4. Charlie also likes
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/like \
  -H "Authorization: Bearer CHARLIE_TOKEN"

# 5. Alice responds
curl -X POST http://127.0.0.1:8000/api/v1/posts/1/comments \
  -H "Authorization: Bearer ALICE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Thanks, Bob!"}'

# 6. Get post with full engagement
curl http://127.0.0.1:8000/api/v1/posts/1
```

**Response:**
```json
{
  "post": {
    "id": 1,
    "title": "My Post",
    "likes_count": 2,
    "comments_count": 2,
    "user": { "id": 1, "name": "Alice" }
  }
}
```

**Full engagement captured!** 🎉

---

## 🎯 What's Working (Phases 1-4)

- ✅ Authentication & authorization
- ✅ Post CRUD with images
- ✅ Follow/unfollow system
- ✅ **Like/unlike posts** (NEW!)
- ✅ **Comments on posts** (NEW!)
- ✅ **Engagement counts** (NEW!)
- ✅ User profiles with stats
- ✅ Pagination everywhere
- ✅ Production-grade security

---

## 💡 Design Highlights

### Idempotent Operations

**Why it matters:**
In distributed systems, network failures are common. Without idempotency:
```
Request: Like post 1
Network fails after DB write
Client retries
Result: 2 likes created ❌
```

With idempotency:
```
Request: Like post 1
Network fails after DB write
Client retries
Result: 1 like (database unique constraint prevents duplicate) ✅
```

**Implementation:**
```php
// firstOrCreate is idempotent
$like = Like::firstOrCreate([
    'user_id' => $user->id,
    'post_id' => $post->id,
]);
```

---

### Soft Deletes

**Why it matters:**
- User deletes comment by mistake → Can restore
- Spam comment → Mark deleted, review later
- Legal requirements → Keep audit trail

**Implementation:**
```php
// Soft delete
$comment->delete();  // Sets deleted_at

// Restore
$comment->restore();

// Permanent delete
$comment->forceDelete();
```

---

## 🚀 Next: Phase 5

Ready to implement:
- **Personalized Feed** - Posts from followed users
- **Redis Caching** - Fast feed generation
- **Cursor Pagination** - Efficient scrolling
- **Fan-out on Read** - Initial strategy

---

**Phase 1 + 2 + 3 + 4 Complete! 🎉**

You now have a full-featured social platform with engagement!
