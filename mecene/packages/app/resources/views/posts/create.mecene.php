@extends('layouts.app')

@section('title', 'Nouvel article — Mecene')

@section('content')
    <div class="card">
        <h1 style="margin:0 0 20px;font-size:22px;">Créer un article</h1>

        <form method="POST" action="{{ route('posts.store') }}">
            @csrf

            <div class="form-group">
                <label for="title">Titre de l'article</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" placeholder="Ex: Découvrir le conteneur PSR-11">
                @error('title')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="content">Contenu</label>
                <textarea id="content" name="content" rows="7" placeholder="Rédigez votre article (minimum 10 caractères)...">{{ old('content') }}</textarea>
                @error('content')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;gap:8px;">
                <input type="checkbox" id="published" name="published" value="1" checked>
                <label for="published" style="margin:0;font-weight:500;">Publier immédiatement</label>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('posts.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
@endsection
