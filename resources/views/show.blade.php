<!DOCTYPE html>
<html>
<head>
    <title>{{ $post->title }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <a href="/" class="btn btn-secondary mb-3">Back to All Posts</a>
        <h1>{{ $post->tittle }}</h1>
        <p>{{ $post->content }}</p>
    </div>
</body>
</html>