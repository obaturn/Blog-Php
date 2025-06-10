<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostController extends Controller
{
    public function create()
    {
        return view('create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'tittle' => 'required',
            'content' => 'required',
        ]);

        Post::create([
            'tittle' => $request->tittle,
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'Post created!');
    }

    public function index()
    {
        $posts = Post::latest()->get();
        return view('home', compact('posts'));
    }

    public function show(Post $post)
    {
        return view('show', compact('post'));
    }
}