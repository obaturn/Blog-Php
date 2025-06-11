<!DOCTYPE html>
<html>
<head>
    <title>{{ $post->tittle }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .single-post-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .post-header {
            margin-bottom: 30px;
        }
        
        .post-image-container {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .post-main-image {
            max-width: 100%;
            max-height: 500px;
            width: auto;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
            white-space: pre-line;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="single-post-container">
        <a href="/" class="btn btn-secondary back-button">← Back to All Posts</a>
        
        <div class="post-header">
            <h1>{{ $post->tittle }}</h1>
        </div>
        
        @if($post->image)
        <div class="post-image-container">
            <img src="{{ asset('storage/' . $post->image) }}" 
                 class="post-main-image" 
                 alt="Post image">
        </div>
        @endif
        
        <div class="post-content">
            {{ $post->content }}
        </div>
    </div>
</body>
</html>