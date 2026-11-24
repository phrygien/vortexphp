<?php

declare(strict_types=1);

namespace Mecene\Session;

/**
 * Gestionnaire de session PHP native, messages flash, old() et jeton CSRF.
 */
class SessionManager
{
    protected bool $started = false;

    public function start(): void
    {
        if ($this->started || PHP_SAPI === 'cli') {
            $this->started = true;
            $_SESSION ??= [];
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
            session_start();
        }

        $this->started = true;

        if (!isset($_SESSION['_token'])) {
            $this->regenerateToken();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash']['new'][$key] = $value;
        unset($_SESSION['_flash']['old'][$key]);
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION['_flash']['old'][$key]
            ?? $_SESSION['_flash']['new'][$key]
            ?? $default;
    }

    public function hasFlash(string $key): bool
    {
        $this->start();
        return isset($_SESSION['_flash']['old'][$key]) || isset($_SESSION['_flash']['new'][$key]);
    }

    /**
     * @param array<string, string[]> $errors
     */
    public function flashErrors(array $errors): void
    {
        $this->flash('_errors', $errors);
    }

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        $errors = $this->getFlash('_errors', []);
        return is_array($errors) ? $errors : [];
    }

    /**
     * @param array<string, mixed> $input
     */
    public function flashInput(array $input): void
    {
        unset($input['_token'], $input['password'], $input['password_confirmation']);
        $this->flash('_old_input', $input);
    }

    public function old(string $key, mixed $default = ''): mixed
    {
        $old = $this->getFlash('_old_input', []);
        return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
    }

    public function ageFlashData(): void
    {
        $this->start();
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }

    public function token(): string
    {
        $this->start();
        if (empty($_SESSION['_token'])) {
            $this->regenerateToken();
        }
        return (string) $_SESSION['_token'];
    }

    public function regenerateToken(): string
    {
        $this->start();
        $_SESSION['_token'] = bin2hex(random_bytes(32));
        return (string) $_SESSION['_token'];
    }
}
