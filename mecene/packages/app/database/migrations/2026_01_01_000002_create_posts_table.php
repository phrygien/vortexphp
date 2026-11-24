<?php

declare(strict_types=1);

use Mecene\Database\Migrations\Migration;
use Mecene\Database\Schema\Blueprint;
use Mecene\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id', 'users', 'id');
            $table->string('title', 120);
            $table->string('slug', 160)->unique();
            $table->text('content');
            $table->boolean('published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
