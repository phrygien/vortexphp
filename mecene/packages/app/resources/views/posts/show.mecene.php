@extends('layouts.app')

@section('title', $post->title . ' — Mecene')

@section('content')
    <article class="card">
        <div style="margin-bottom:16px;">
            <a href="{{ route('posts.index') }}" class="meta" style="text-decoration:none;">← Retour aux articles</a>
        </div>

        <h1 style="margin:0 0 10px;font-size:28px;">{{ $post->title }}</h1>

        <p class="meta" style="margin:0 0 24px;">
            @if($author)
                Par {{ $author->name }} ({{ $author->email }}) ·
            @endif
            Publié le {{ $post->created_at }}
        </p>

        <div style="white-space:pre-line;color:#1e293b;margin-bottom:28px;">
            {{ $post->content }}
        </div>

        <div style="display:flex;gap:12px;border-top:1px solid #e2e8f0;padding-top:20px;">
            <a href="{{ route('posts.edit', ['id' => $post->id]) }}" class="btn btn-secondary">Modifier</a>

            <form method="POST" action="{{ route('posts.destroy', ['id' => $post->id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </article>
@endsection
