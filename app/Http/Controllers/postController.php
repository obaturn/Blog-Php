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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $imagePath=null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        Post::create([
            'tittle' => $request->tittle,
            'content' => $request->content,
            'image' => $imagePath,
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