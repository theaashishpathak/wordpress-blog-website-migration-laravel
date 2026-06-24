<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    // public function index()
    // {
    //     $posts = Post::published()
    //         ->select('ID', 'post_title', 'post_name', 'post_date', 'post_author')
    //         ->orderBy('post_date', 'desc')
    //         ->limit(5)
    //         ->get();

    //     return view('posts.index', compact('posts'));
    // }


    public function index()
    {
        $posts = Post::published()
            ->with([
        'author',
        'categories'
    ])
            ->select(
                'ID',
                'post_title',
                'post_name',
                'post_date',
                'post_author',
                'post_content'
                
            )
            ->orderBy('post_date', 'desc')
            ->limit(500)
            ->get();

        return view('posts.index', compact('posts'));
    }

   
    public function show($slug)
    {
        $post = Post::published()
            ->where('post_name', $slug)
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }
}