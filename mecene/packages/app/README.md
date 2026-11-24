# Mecene App — Squelette officiel

Squelette applicatif prêt pour la production pour le micro-framework PHP 8.3+ **Mecene** (`mecene/framework`).

## Installation via Composer

```bash
composer create-project mecene/app mon-projet
cd mon-projet
php artisan migrate
php artisan serve
```

Ouvrez ensuite [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Commandes Artisan

```bash
php artisan key:generate
php artisan migrate
php artisan migrate --rollback
php artisan make:controller CommentController
php artisan make:model Comment
php artisan make:migration create_comments_table
php artisan serve --port=8080
```
