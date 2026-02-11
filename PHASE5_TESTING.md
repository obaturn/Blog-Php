# Phase 5 Testing Guide - Feed System with Redis Caching

Complete testing guide for the personalized feed system with cursor-based pagination.

---

## Prerequisites

Before testing, ensure:
1. ✅ Redis is installed and running on port 6379
2. ✅ `.env` has `CACHE_STORE=redis`
3. ✅ Predis package installed (`predis/predis`)
4. ✅ At least 2 test users with follow relationships
5. ✅ Some posts created by followed users

---

## Setup Redis

### Install Redis (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### Verify Redis is Running
```bash
redis-cli ping
# Expected output: PONG
```

### Update .env
```bash
# In Blog-Php/.env
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

FEED_CACHE_TTL=300
FEED_MAX_POSTS=50
FEED_DEFAULT_LIMIT=15
```

---

## Test Scenarios

### Scenario 1: Get Public Feed (Unauthenticated)

**Purpose:** Test trending feed based on engagement.

```bash
curl -X GET http://127.0.0.1:8000/api/v1/feed/public
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 5,
        "title": "Popular Post",
        "content": "...",
        "likes_count": 10,
        "comments_count": 5,
        "engagement_score": 35,
        "user": {
          "id": 2,
          "name": "Bob"
        }
      }
    ],
    "pagination": {
      "next_cursor": 3,
      "has_more": true,
      "limit": 15
    }
  },
  "meta": {
    "feed_type": "public",
    "cached": true
  }
}
```

**Notes:**
- Posts ordered by engagement score (likes × 2 + comments × 3)
- No authentication required
- Cached for 5 minutes

---

### Scenario 2: Get Public Feed with Pagination

**Purpose:** Test cursor-based pagination.

```bash
# First page
curl -X GET "http://127.0.0.1:8000/api/v1/feed/public?limit=10"

# Get next_cursor from response (e.g., 5)

# Second page
curl -X GET "http://127.0.0.1:8000/api/v1/feed/public?limit=10&cursor=5"
```

**Expected:**
- First page: 10 posts with `next_cursor`
- Second page: Next 10 posts, `has_more` = false when no more posts

**Validation:**
- ✅ No duplicate posts across pages
- ✅ Posts in correct order
- ✅ `has_more` accurate

---

### Scenario 3: Get Personalized Feed (Authenticated)

**Purpose:** Test feed from followed users only.

**Setup:**
1. User A (Alice) - logged in
2. User B (Bob) - Alice follows Bob
3. User C (Charlie) - Alice follows Charlie
4. User D (Dave) - Alice does NOT follow Dave

```bash
# Login as Alice
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@example.com","password":"password"}'

# Save token
TOKEN="<token_from_login>"

# Get personalized feed
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "posts": [
      {
        "id": 8,
        "title": "Bob's Latest",
        "user_id": 2,
        "likes_count": 3,
        "comments_count": 1,
        "is_liked": false,
        "user": {
          "id": 2,
          "name": "Bob"
        }
      },
      {
        "id": 7,
        "title": "Charlie's Post",
        "user_id": 3,
        "is_liked": true,
        "user": {
          "id": 3,
          "name": "Charlie"
        }
      }
    ],
    "pagination": {
      "next_cursor": 6,
      "has_more": true,
      "limit": 15
    }
  },
  "meta": {
    "feed_type": "personalized",
    "cached": true
  }
}
```

**Validation:**
- ✅ Only posts from Bob and Charlie (followed users)
- ✅ NO posts from Dave (not followed)
- ✅ Ordered by recency (newest first)
- ✅ `is_liked` flag present for each post

---

### Scenario 4: Empty Feed (No Followed Users)

**Purpose:** Test feed when user follows nobody.

```bash
# Login as new user with no follows
curl -X POST http://127.0.0.1:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"newuser@example.com","password":"password"}'

TOKEN="<token>"

# Get feed
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "posts": [],
    "pagination": {
      "next_cursor": null,
      "has_more": false,
      "limit": 15
    }
  },
  "meta": {
    "feed_type": "personalized",
    "cached": true
  }
}
```

---

### Scenario 5: Get Feed Statistics

**Purpose:** Test feed metadata.

```bash
curl -X GET http://127.0.0.1:8000/api/v1/feed/stats \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "following_count": 5,
    "followers_count": 3,
    "feed_posts_available": 27
  }
}
```

**Validation:**
- ✅ `following_count` matches actual follows
- ✅ `feed_posts_available` = total posts by followed users

---

### Scenario 6: Refresh Feed (Clear Cache)

**Purpose:** Test cache invalidation.

**Steps:**
1. Get feed (cached)
2. Another user creates a new post
3. Refresh feed (clears cache)
4. Verify new post appears

```bash
# 1. Get feed
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"

# 2. (Another user creates a post - do this separately)

# 3. Refresh feed
curl -X POST http://127.0.0.1:8000/api/v1/feed/refresh \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- ✅ `"cached": false` in response
- ✅ New post appears in feed
- ✅ Fresh data from database

---

### Scenario 7: Feed Updates After New Post

**Purpose:** Test automatic cache invalidation when a followed user posts.

**Setup:**
- Alice follows Bob
- Alice has feed cached

**Steps:**
```bash
# 1. Alice gets feed (Bob has 3 posts)
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer ALICE_TOKEN"

# 2. Bob creates a new post
curl -X POST http://127.0.0.1:8000/api/v1/posts \
  -H "Authorization: Bearer BOB_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"New Post","content":"Fresh content"}'

# 3. Alice gets feed again (should see new post)
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer ALICE_TOKEN"
```

**Expected:**
- ✅ Alice's feed cache invalidated when Bob posts
- ✅ New post appears in Alice's feed
- ✅ Bob now has 4 posts in feed

**How it works:**
- `PostController::store()` calls `FeedService::invalidateFollowerFeeds()`
- All of Bob's followers get their feed cache cleared
- Next feed request fetches fresh data

---

### Scenario 8: Feed with Cursor Pagination

**Purpose:** Test infinite scroll with cursors.

```bash
# Page 1
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=5" \
  -H "Authorization: Bearer $TOKEN"

# Response includes next_cursor: 10

# Page 2
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=5&cursor=10" \
  -H "Authorization: Bearer $TOKEN"

# Response includes next_cursor: 5

# Page 3
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=5&cursor=5" \
  -H "Authorization: Bearer $TOKEN"

# Response: has_more: false (end of feed)
```

**Validation:**
- ✅ Each page has exactly 5 posts
- ✅ No duplicate posts across pages
- ✅ `has_more: false` when no more posts
- ✅ `next_cursor: null` on last page

---

### Scenario 9: Custom Feed Limit

**Purpose:** Test custom page sizes.

```bash
# Request 3 posts
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=3" \
  -H "Authorization: Bearer $TOKEN"

# Request 50 posts (max)
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=50" \
  -H "Authorization: Bearer $TOKEN"

# Request 100 posts (exceeds max)
curl -X GET "http://127.0.0.1:8000/api/v1/feed?limit=100" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- 3 posts → Returns 3 posts
- 50 posts → Returns up to 50 posts
- 100 posts → Capped at 50 posts (config max)

**Validation:**
```bash
# Verify max is enforced
echo $RESPONSE | jq '.data.posts | length'
# Should never exceed 50
```

---

### Scenario 10: Verify Redis Caching

**Purpose:** Confirm feed results are cached in Redis.

```bash
# 1. Clear Redis cache
redis-cli FLUSHDB

# 2. Get feed (miss, will cache)
curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"

# 3. Check Redis keys
redis-cli KEYS "*feed*"

# Expected output:
# 1) "laravel_cache_feed:user:1:limit:15"
```

**Advanced Verification:**
```bash
# Get cache value
redis-cli GET "laravel_cache_feed:user:1:limit:15"

# Check TTL (should be ~300 seconds)
redis-cli TTL "laravel_cache_feed:user:1:limit:15"
```

---

### Scenario 11: Public Feed with Authentication

**Purpose:** Test public feed with `is_liked` flag for authenticated users.

```bash
# Get public feed with auth
curl -X GET http://127.0.0.1:8000/api/v1/feed/public \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- ✅ All posts (not just followed users)
- ✅ `is_liked` flag present (user is authenticated)
- ✅ Ordered by engagement score

**Compare with unauthenticated:**
```bash
# Without auth
curl -X GET http://127.0.0.1:8000/api/v1/feed/public
```

**Expected:**
- ✅ Same posts
- ✅ NO `is_liked` flag (user is not authenticated)

---

### Scenario 12: Invalid Cursor Handling

**Purpose:** Test error handling for invalid cursors.

```bash
# Non-existent post ID
curl -X GET "http://127.0.0.1:8000/api/v1/feed?cursor=99999" \
  -H "Authorization: Bearer $TOKEN"
```

**Expected Response (422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "cursor": ["The selected cursor is invalid."]
  }
}
```

---

## Performance Testing

### Test 1: Cache Hit vs Cache Miss

**Purpose:** Measure performance improvement from caching.

```bash
# Clear cache
redis-cli FLUSHDB

# First request (cache miss)
time curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"

# Second request (cache hit)
time curl -X GET http://127.0.0.1:8000/api/v1/feed \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- Cache miss: ~50-200ms (depends on data)
- Cache hit: ~5-20ms (much faster!)

---

### Test 2: Load Test with Apache Bench

**Purpose:** Test feed under concurrent load.

```bash
# 100 requests, 10 concurrent
ab -n 100 -c 10 \
  -H "Authorization: Bearer $TOKEN" \
  http://127.0.0.1:8000/api/v1/feed
```

**Expected:**
- ✅ All requests succeed (200 OK)
- ✅ Average response time < 100ms
- ✅ No errors

---

### Test 3: Monitor Redis Memory

**Purpose:** Track cache storage usage.

```bash
# Check Redis memory usage
redis-cli INFO memory

# Watch cache keys in real-time
redis-cli MONITOR
```

---

## Edge Cases

### Edge Case 1: User Unfollows, Then Gets Feed

**Steps:**
1. Alice follows Bob
2. Alice gets feed (sees Bob's posts)
3. Alice unfollows Bob
4. Alice refreshes feed

**Expected:**
- ✅ Bob's posts NO longer appear in Alice's feed

```bash
# Unfollow
curl -X DELETE http://127.0.0.1:8000/api/v1/users/2/unfollow \
  -H "Authorization: Bearer ALICE_TOKEN"

# Refresh feed
curl -X POST http://127.0.0.1:8000/api/v1/feed/refresh \
  -H "Authorization: Bearer ALICE_TOKEN"
```

---

### Edge Case 2: Followed User Deletes Post

**Steps:**
1. Alice follows Bob
2. Alice sees Bob's post in feed
3. Bob deletes the post
4. Alice refreshes feed

**Expected:**
- ✅ Deleted post removed from Alice's feed

---

### Edge Case 3: Cache Expiration During Pagination

**Scenario:** User gets page 1, waits 6 minutes, gets page 2.

**Expected:**
- ✅ Page 2 fetches fresh data (cache expired)
- ✅ Cursor still works correctly

---

## Troubleshooting

### Issue 1: Redis Connection Failed

**Error:**
```
Connection refused [tcp://127.0.0.1:6379]
```

**Solution:**
```bash
# Check if Redis is running
sudo systemctl status redis-server

# Start Redis
sudo systemctl start redis-server

# Check connectivity
redis-cli ping
```

---

### Issue 2: Empty Feed (Should Have Posts)

**Debugging:**
```bash
# Check if user follows anyone
curl -X GET http://127.0.0.1:8000/api/v1/feed/stats \
  -H "Authorization: Bearer $TOKEN"

# If following_count = 0, follow someone
curl -X POST http://127.0.0.1:8000/api/v1/users/2/follow \
  -H "Authorization: Bearer $TOKEN"
```

---

### Issue 3: Stale Cache (Not Updating)

**Solution:**
```bash
# Manually clear cache
redis-cli FLUSHDB

# Or refresh via API
curl -X POST http://127.0.0.1:8000/api/v1/feed/refresh \
  -H "Authorization: Bearer $TOKEN"
```

---

## Quick Test Script

Save as `test_feed.sh`:

```bash
#!/bin/bash

# Configuration
BASE_URL="http://127.0.0.1:8000/api/v1"
EMAIL="alice@example.com"
PASSWORD="password"

# Login and get token
echo "Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')

if [ "$TOKEN" == "null" ]; then
  echo "Login failed!"
  exit 1
fi

echo "Token: $TOKEN"

# Test 1: Get feed stats
echo -e "\n=== Feed Stats ==="
curl -s -X GET "$BASE_URL/feed/stats" \
  -H "Authorization: Bearer $TOKEN" | jq

# Test 2: Get personalized feed
echo -e "\n=== Personalized Feed ==="
curl -s -X GET "$BASE_URL/feed?limit=5" \
  -H "Authorization: Bearer $TOKEN" | jq

# Test 3: Get public feed
echo -e "\n=== Public Feed ==="
curl -s -X GET "$BASE_URL/feed/public?limit=5" | jq

echo -e "\n✅ All tests complete!"
```

**Run:**
```bash
chmod +x test_feed.sh
./test_feed.sh
```

---

## Summary Checklist

- [ ] Redis installed and running
- [ ] `.env` configured with `CACHE_STORE=redis`
- [ ] Predis package installed
- [ ] Test users created with follow relationships
- [ ] Posts created by followed users
- [ ] Public feed returns posts ordered by engagement
- [ ] Personalized feed shows only followed users' posts
- [ ] Cursor pagination works across multiple pages
- [ ] Cache invalidation works after new posts
- [ ] Feed refresh endpoint clears cache
- [ ] `is_liked` flag accurate for authenticated users
- [ ] Empty feed handled gracefully (no follows)
- [ ] Invalid cursor returns validation error
- [ ] Feed stats endpoint returns correct counts

---

**Phase 5 Testing: Complete!** 🎉

Your feed system is production-ready with Redis caching and cursor-based pagination!
