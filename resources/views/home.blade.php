<!DOCTYPE html>
<html>
<head>
    <title>RiseBlog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Hero Section Styles */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://img.freepik.com/free-photo/young-happy-smiling-businesswoman-holding-laptop-isolated_231208-241.jpg');
            background-size: cover;
            min-height: 90vh;
            display: flex;
            align-items: center;
            text-align: center;
            color: white;
            padding: 90px 0;
        }
        
        /* Form Styles */
        .create-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            color: #333;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-create {
            padding: 10px 30px;
            font-size: 1.1rem;
            margin-top: 15px;
        }
        
        /* Image Preview Styles */
        .image-preview-container {
            margin: 15px 0;
            text-align: center;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            display: none;
            margin: 0 auto;
        }
        
        /* Post Card Styles */
        .post-card {
            transition: transform 0.3s ease;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .post-card:hover {
            transform: translateY(-5px);
        }
        
        .post-card-img {
            height: 250px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        
        .post-card-body {
            padding: 20px;
        }
        
        /* Section Styles */
        .posts-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        
        .section-title {
            margin-bottom: 40px;
            font-weight: 700;
            color: #333;
        }
        
        /* Navigation */
        .main-nav a {
            margin: 0 15px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .main-nav a:hover {
            color: #ff6b6b !important;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Hero Section with Create Form -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-3 mb-4">RISEBLOG</h1>
            <nav class="mb-4 main-nav">
                <a href="/" class="text-white mx-3 underline none">Home</a>
                <a href="#" class="text-white mx-3 underline none">Dashboard</a>
                <a href="#" class="text-white mx-3 underline none">Login</a>
                <a href="/post" class="text-white mx-3 underline none">Create Account</a>
                <a href="/post/create" class="text-white mx-3 underline none">Create Post</a>
            </nav>
            
            <h2 class="mb-4" style="color: #ff6b6b;">Create Your Post Here</h2>
            
            <!-- Create Post Form -->
            <div class="create-form">
                <form method="POST" action="/post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Post Title" name="tittle" required>
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" rows="5" placeholder="Your content here..." name="content" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Upload Picture</label>
                        <input type="file" class="form-control" id="imageInput" name="image" required>
                        <div class="image-preview-container">
                            <img id="imagePreview" class="image-preview" alt="Image preview">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-create">Publish Post</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Posts Section -->
    <div class="posts-section">
        <div class="container">
            <h2 class="text-center section-title">Recent Posts</h2>
            <div class="row">
                @foreach($posts as $post)
                <div class="col-md-6 col-lg-4">
                    <div class="card post-card">
                        @if($post->image)
                            <img src="{{ asset('storage/' . $post->image) }}" class="card-img-top post-card-img" alt="Post image">
                        @endif
                        <div class="post-card-body">
                            <h3>{{ $post->tittle }}</h3>
                            <p class="text-muted">{{ Str::limit($post->content, 100) }}</p>
                            <a href="/post/{{ $post->id }}" class="btn btn-outline-primary">Read Full Article</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Image Preview Script -->
    <script>
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>