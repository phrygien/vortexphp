<?php

declare(strict_types=1);

namespace App\Models;

use Mecene\Database\ORM\Model;

class User extends Model
{
    protected static string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
    ];

    /**
     * @return Post[]
     */
    public function posts(): array
    {
        /** @var Post[] $posts */
        $posts = $this->hasMany(Post::class, 'user_id', 'id');
        return $posts;
    }
}
