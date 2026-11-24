<?php

declare(strict_types=1);

namespace Mecene\Exceptions;

use Throwable;
use Mecene\Foundation\Application;
use Mecene\Http\Request;
use Mecene\Http\Response;
use Mecene\Session\SessionManager;
use Mecene\Validation\ValidationException;

/**
 * Gestionnaire global d'exceptions avec page de diagnostic détaillée en mode APP_DEBUG=true.
 */
class Handler
{
    public function __construct(
        protected readonly Application $app,
    ) {
    }

    public function render(Request $request, Throwable $e): Response
    {
        if ($e instanceof ValidationException) {
            if ($request->expectsJson()) {
                return Response::json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors,
                ], 422);
            }

            /** @var SessionManager $session */
            $session = $this->app->make(SessionManager::class);
            $session->flashErrors($e->errors);
            $session->flashInput($e->input);

            $referer = $request->header('Referer', '/');
            return Response::redirect($referer, 302);
        }

        $status = $e instanceof HttpException ? $e->statusCode : 500;

        if ($request->expectsJson()) {
            $payload = ['message' => $e->getMessage() ?: 'Erreur serveur'];
            if ($this->isDebug()) {
                $payload['exception'] = $e::class;
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }
            return Response::json($payload, $status);
        }

        if ($this->isDebug() && $status >= 500) {
            return Response::make($this->renderDebugPage($request, $e), $status);
        }

        return Response::make($this->renderHttpErrorPage($status, $e->getMessage()), $status);
    }

    protected function isDebug(): bool
    {
        return (bool) config('app.debug', false);
    }

    protected function renderHttpErrorPage(int $status, string $message): string
    {
        $title = match ($status) {
            403 => 'Accès refusé',
            404 => 'Page introuvable',
            419 => 'Session expirée',
            default => 'Erreur serveur interne',
        };

        $desc = $message !== '' ? e($message) : 'Une erreur est survenue lors du traitement de votre requête.';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$status} — {$title}</title>
    <style>
        body{font-family:system-ui,-apple-system,sans-serif;background:#f8fafc;color:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:40px;max-width:480px;width:90%;text-align:center}
        .code{font-size:48px;font-weight:700;color:#4f46e5;margin:0 0 8px}
        h1{font-size:20px;margin:0 0 12px}
        p{color:#475569;font-size:15px;line-height:1.6;margin:0 0 24px}
        a{display:inline-block;padding:10px 20px;background:#0f172a;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:500}
    </style>
</head>
<body>
    <div class="card">
        <div class="code">{$status}</div>
        <h1>{$title}</h1>
        <p>{$desc}</p>
        <a href="/">Retour à l'accueil</a>
    </div>
</body>
</html>
HTML;
    }

    protected function renderDebugPage(Request $request, Throwable $e): string
    {
        $class = e($e::class);
        $message = e($e->getMessage() ?: '(aucun message)');
        $file = e($e->getFile());
        $line = $e->getLine();
        $snippet = $this->extractCodeSnippet($e->getFile(), $line);
        $traceHtml = $this->formatTrace($e->getTrace());
        $method = e($request->method());
        $uri = e($request->uri());

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mecene Debug — {$class}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;background:#0f172a;color:#e2e8f0;font-family:ui-sans-serif,system-ui,sans-serif;line-height:1.5}
        .header{background:#1e293b;border-bottom:1px solid #334155;padding:28px 36px}
        .badge{display:inline-block;background:#dc2626;color:#fff;font-size:12px;font-weight:600;padding:3px 10px;border-radius:6px;margin-bottom:10px}
        .title{font-size:24px;font-weight:700;margin:0 0 8px;color:#f8fafc}
        .meta{font-family:ui-monospace,monospace;font-size:13px;color:#94a3b8}
        .container{max-width:1160px;margin:28px auto;padding:0 24px;display:grid;gap:24px}
        .panel{background:#1e293b;border:1px solid #334155;border-radius:10px;overflow:hidden}
        .panel-head{padding:12px 18px;background:#0f172a;border-bottom:1px solid #334155;font-size:13px;font-weight:600;color:#cbd5e1}
        pre{margin:0;padding:16px;font-family:ui-monospace,monospace;font-size:13px;overflow-x:auto}
        .line{display:flex;gap:16px;padding:2px 8px;border-radius:4px}
        .line.active{background:rgba(220,38,38,0.25);color:#fecaca;font-weight:600}
        .ln{color:#64748b;min-width:42px;text-align:right;user-select:none}
        .trace-item{padding:12px 18px;border-bottom:1px solid #334155;font-family:ui-monospace,monospace;font-size:12.5px}
        .trace-item:last-child{border-bottom:none}
    </style>
</head>
<body>
    <div class="header">
        <div class="badge">{$class}</div>
        <h1 class="title">{$message}</h1>
        <div class="meta">{$method} {$uri} — {$file}:{$line}</div>
    </div>
    <div class="container">
        <div class="panel">
            <div class="panel-head">Extrait du fichier source ({$file})</div>
            <pre>{$snippet}</pre>
        </div>
        <div class="panel">
            <div class="panel-head">Pile d'appels (Stack Trace)</div>
            {$traceHtml}
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function extractCodeSnippet(string $file, int $errorLine, int $radius = 8): string
    {
        if (!is_readable($file)) {
            return '';
        }

        $lines = file($file) ?: [];
        $start = max(1, $errorLine - $radius);
        $end = min(count($lines), $errorLine + $radius);
        $html = '';

        for ($i = $start; $i <= $end; $i++) {
            $cls = $i === $errorLine ? 'line active' : 'line';
            $code = e(rtrim($lines[$i - 1] ?? '', "\r\n"));
            $html .= "<div class=\"{$cls}\"><span class=\"ln\">{$i}</span><span>{$code}</span></div>";
        }

        return $html;
    }

    /**
     * @param array<int, array<string, mixed>> $trace
     */
    protected function formatTrace(array $trace): string
    {
        $html = '';
        foreach ($trace as $index => $frame) {
            $call = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '');
            $location = isset($frame['file']) ? ($frame['file'] . ':' . ($frame['line'] ?? 0)) : '[internal]';
            $html .= '<div class="trace-item">#' . $index . ' ' . e($call) . '() — <span style="color:#94a3b8">' . e($location) . '</span></div>';
        }
        return $html;
    }
}
