@extends('layouts.app')

@section('title', 'Modifier : ' . $post->title)

@section('content')
    <div class="card">
        <h1 style="margin:0 0 20px;font-size:22px;">Modifier l'article</h1>

        <form method="POST" action="{{ route('posts.update', ['id' => $post->id]) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="title">Titre</label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}">
                @error('title')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" rows="7">{{ old('content', $post->content) }}</textarea>
                @error('content')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="published" name="published" value="1" @if($post->published) checked @endif>
                <label for="published" style="margin:0;font-weight:500;">Article publié</label>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('posts.show', ['id' => $post->id]) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
