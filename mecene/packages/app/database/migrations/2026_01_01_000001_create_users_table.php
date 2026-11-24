<?php

declare(strict_types=1);

use Mecene\Database\Migrations\Migration;
use Mecene\Database\Schema\Blueprint;
use Mecene\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
