<?php

declare(strict_types=1);

namespace Mecene\Routing;

use Mecene\Http\Request;
use Mecene\Http\Response;

/**
 * Contrôleur de base fourni par le framework.
 */
abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::view($template, $data, $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }
}
