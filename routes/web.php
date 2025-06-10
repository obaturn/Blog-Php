<?php

use App\Http\Controllers\postController;
use Illuminate\Support\Facades\Route;

Route::get('/post/create', [postController::class ,'create'] );
Route::post('/post', [PostController::class, 'store']);  
Route::get('/', [PostController::class, 'index']);
Route::get('/post/{post}', [PostController::class, 'show']);