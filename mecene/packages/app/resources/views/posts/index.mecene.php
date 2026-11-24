@extends('layouts.app')

@section('title', 'Articles — Mecene')

@section('content')
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="margin:0;font-size:26px;">Articles publiés</h1>
        <span class="meta">{{ count($posts) }} article(s)</span>
    </div>

    @forelse($posts as $post)
        <article class="card">
            <h2 style="margin:0 0 8px;font-size:20px;">
                <a href="{{ route('posts.show', ['id' => $post->id]) }}" style="color:#0f172a;text-decoration:none;">
                    {{ $post->title }}
                </a>
            </h2>
            <p class="meta" style="margin:0 0 12px;">
                Slug : {{ $post->slug }} · Créé le {{ $post->created_at }}
                @if($post->published)
                    · Publié
                @else
                    · Brouillon
                @endif
            </p>
            <p style="margin:0 0 16px;color:#334155;">
                {{ mb_strimwidth((string) $post->content, 0, 180, '...') }}
            </p>
            <div style="display:flex;gap:10px;">
                <a href="{{ route('posts.show', ['id' => $post->id]) }}" class="btn btn-secondary">Lire</a>
                <a href="{{ route('posts.edit', ['id' => $post->id]) }}" class="btn btn-secondary">Modifier</a>
            </div>
        </article>
    @empty
        <div class="card" style="text-align:center;padding:48px 24px;">
            <p style="margin:0 0 16px;color:#475569;">Aucun article n'a encore été publié.</p>
            <a href="{{ route('posts.create') }}" class="btn btn-primary">Rédiger le premier article</a>
        </div>
    @endforelse
@endsection
