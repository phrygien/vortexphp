<?php

declare(strict_types=1);

namespace App\Models;

use Mecene\Database\ORM\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    protected array $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'published',
    ];

    public function user(): ?User
    {
        /** @var User|null $author */
        $author = $this->belongsTo(User::class, 'user_id', 'id');
        return $author;
    }
}
