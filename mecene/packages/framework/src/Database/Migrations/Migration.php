<?php

declare(strict_types=1);

namespace Mecene\Database\Migrations;

/**
 * Classe abstraite de base pour toute migration.
 */
abstract class Migration
{
    abstract public function up(): void;

    abstract public function down(): void;
}
