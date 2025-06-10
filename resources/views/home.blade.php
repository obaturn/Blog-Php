<!DOCTYPE html>
<html>
<head>
    <title>My Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>My Blog</h1>
        <a href="/post/create" class="btn btn-primary mb-3">Create New Post</a>
        
        @foreach($posts as $post)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ $post->tittle }}</h5>
                    <p class="card-text">{{ Str::limit($post->content, 100) }}</p>
                    <a href="/post/{{ $post->id }}" class="btn btn-sm btn-primary">Read More</a>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>