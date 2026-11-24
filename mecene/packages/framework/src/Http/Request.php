<?php

declare(strict_types=1);

namespace Mecene\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use Mecene\Validation\Validator;

/**
 * Requête HTTP entrante adossée au standard PSR-7 via nyholm/psr7.
 */
class Request
{
    /** @var array<string, mixed> */
    protected array $routeParams = [];

    /** @var array<string, mixed> */
    protected array $parsedBody;

    public function __construct(
        protected ServerRequestInterface $psrRequest,
    ) {
        $body = $psrRequest->getParsedBody();

        if (!is_array($body) || $body === []) {
            $raw = (string) $psrRequest->getBody();
            $contentType = $this->header('Content-Type', '');
            if ($raw !== '' && str_contains(strtolower($contentType), 'application/json')) {
                $decoded = json_decode($raw, true);
                $body = is_array($decoded) ? $decoded : [];
            } else {
                $body = [];
            }
        }

        $this->parsedBody = $body;
    }

    public static function capture(): self
    {
        $factory = new Psr17Factory();

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        $uri = $factory->createUri("{$scheme}://{$host}{$requestUri}");
        $serverRequest = $factory->createServerRequest($method, $uri, $_SERVER);

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $serverRequest = $serverRequest->withHeader($headerName, (string) $value);
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headerName = str_replace('_', '-', $key);
                $serverRequest = $serverRequest->withHeader($headerName, (string) $value);
            }
        }

        $rawBody = file_get_contents('php://input');
        if ($rawBody !== false && $rawBody !== '') {
            $serverRequest = $serverRequest->withBody($factory->createStream($rawBody));
        }

        $serverRequest = $serverRequest
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withCookieParams($_COOKIE);

        return new self($serverRequest);
    }

    public function toPsr7(): ServerRequestInterface
    {
        return $this->psrRequest;
    }

    public function method(): string
    {
        $method = strtoupper($this->psrRequest->getMethod());

        if ($method === 'POST') {
            $spoofed = $this->parsedBody['_method'] ?? $this->header('X-HTTP-Method-Override');
            if (is_string($spoofed) && in_array(strtoupper($spoofed), ['PUT', 'PATCH', 'DELETE'], true)) {
                return strtoupper($spoofed);
            }
        }

        return $method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function path(): string
    {
        $path = $this->psrRequest->getUri()->getPath();
        $normalized = '/' . trim($path, '/');
        return $normalized === '' ? '/' : $normalized;
    }

    public function uri(): string
    {
        return (string) $this->psrRequest->getUri();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->psrRequest->getQueryParams(), $this->parsedBody);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    /**
     * @param string[] $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    public function header(string $name, string $default = ''): string
    {
        $line = $this->psrRequest->getHeaderLine($name);
        return $line !== '' ? $line : $default;
    }

    public function expectsJson(): bool
    {
        $accept = strtolower($this->header('Accept'));
        return str_contains($accept, 'application/json') || str_starts_with($this->path(), '/api/');
    }

    public function ip(): string
    {
        $params = $this->psrRequest->getServerParams();
        return (string) ($params['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * @param array<string, string|array<int, string>> $rules
     * @return array<string, mixed>
     */
    public function validate(array $rules): array
    {
        return Validator::make($this->all(), $rules)->validate();
    }
}
