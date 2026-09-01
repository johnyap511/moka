<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;

class BlogController extends Controller
{
    public function index()
    {
        return view('blog.index', ['posts' => $this->posts()]);
    }

    public function show(string $slug)
    {
        $posts = $this->posts();

        $post = $posts->firstWhere('slug', $slug);
        abort_if($post === null, 404);

        // Three other posts to link to from the foot of the article. Internal
        // links are the main way a new blog gets crawled past its index page.
        $related = $posts->reject(function ($candidate) use ($slug) {
            return $candidate['slug'] === $slug;
        })->take(3)->values();

        return view('blog.posts.' . $post['view'], compact('post', 'related'));
    }

    /** Newest first. */
    private function posts(): Collection
    {
        return collect(config('blog.posts', []))
            ->sortByDesc('published')
            ->values();
    }
}
