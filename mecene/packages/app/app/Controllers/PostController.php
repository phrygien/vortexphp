<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\User;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Routing\Controller;

class PostController extends Controller
{
    public function index(): Response
    {
        $posts = Post::all('id', 'DESC');

        return $this->view('posts.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): Response
    {
        return $this->view('posts.create');
    }

    public function store(Request $request): Response
    {
        $validated = $this->validate($request, [
            'title' => 'required|string|min:3|max:120',
            'content' => 'required|string|min:10',
        ]);

        $author = $this->ensureDefaultAuthor();
        $slug = $this->slugify((string) $validated['title']) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $post = Post::create([
            'user_id' => $author->id,
            'title' => $validated['title'],
            'slug' => $slug,
            'content' => $validated['content'],
            'published' => $request->input('published') ? 1 : 0,
        ]);

        return $this->redirect(route('posts.show', ['id' => $post->id]))
            ->with('success', 'Article publié avec succès.');
    }

    public function show(int $id): Response
    {
        $post = Post::findOrFail($id);

        return $this->view('posts.show', [
            'post' => $post,
            'author' => $post->user(),
        ]);
    }

    public function edit(int $id): Response
    {
        $post = Post::findOrFail($id);

        return $this->view('posts.edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $post = Post::findOrFail($id);

        $validated = $this->validate($request, [
            'title' => 'required|string|min:3|max:120',
            'content' => 'required|string|min:10',
        ]);

        $post->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'published' => $request->input('published') ? 1 : 0,
        ]);

        return $this->redirect(route('posts.show', ['id' => $post->id]))
            ->with('success', 'Article mis à jour avec succès.');
    }

    public function destroy(int $id): Response
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return $this->redirect(route('posts.index'))
            ->with('success', 'Article supprimé.');
    }

    public function apiIndex(): Response
    {
        $posts = array_map(fn (Post $post): array => $post->toArray(), Post::all());
        return $this->json(['data' => $posts]);
    }

    protected function ensureDefaultAuthor(): User
    {
        $users = User::all('id', 'ASC');
        if ($users !== []) {
            return $users[0];
        }

        return User::create([
            'name' => 'Camille Laurent',
            'email' => 'camille@mecene.local',
        ]);
    }

    protected function slugify(string $text): string
    {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
        $clean = strtolower(trim($clean));
        $clean = preg_replace('/[^a-z0-9]+/', '-', $clean) ?? 'post';
        return trim($clean, '-') ?: 'post';
    }
}
