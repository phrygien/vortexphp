<?php

declare(strict_types=1);

use App\Controllers\PostController;
use Mecene\Http\Response;
use Mecene\Routing\Router;

/** @var Router $router */

$router->get('/', fn (): Response => redirect(route('posts.index')));

$router->group(['prefix' => 'posts'], function (Router $router): void {
    $router->get('/', [PostController::class, 'index'])->name('posts.index');
    $router->get('/create', [PostController::class, 'create'])->name('posts.create');
    $router->post('/', [PostController::class, 'store'])->name('posts.store');
    $router->get('/{id}', [PostController::class, 'show'])->name('posts.show');
    $router->get('/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    $router->put('/{id}', [PostController::class, 'update'])->name('posts.update');
    $router->delete('/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
});

$router->group(['prefix' => 'api'], function (Router $router): void {
    $router->get('/health', fn (): Response => Response::json([
        'framework' => 'Mecene',
        'php' => PHP_VERSION,
        'status' => 'ok',
    ]))->name('api.health');

    $router->get('/posts', [PostController::class, 'apiIndex'])->name('api.posts');
});

$router->get('/debug-test', function (): never {
    throw new RuntimeException('Erreur volontaire de démonstration pour inspecter la page de debug Mecene.');
});
