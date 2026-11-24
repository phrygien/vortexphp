<?php

declare(strict_types=1);

namespace Mecene\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

/**
 * Réponse HTTP sortante adossée à nyholm/psr7.
 */
class Response
{
    public function __construct(
        protected ResponseInterface $psrResponse,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function make(string $content = '', int $status = 200, array $headers = []): self
    {
        $factory = new Psr17Factory();
        $response = $factory->createResponse($status);
        $response->getBody()->write($content);

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return new self($response);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json; charset=UTF-8';

        return self::make($json, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return self::make('', $status, ['Location' => $url]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function view(string $template, array $data = [], int $status = 200): self
    {
        return view($template, $data, $status);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->psrResponse = $this->psrResponse->withHeader($name, $value);
        return $clone;
    }

    public function with(string $key, mixed $value): self
    {
        flash($key, $value);
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->psrResponse->getStatusCode();
    }

    public function getBodyContent(): string
    {
        $body = $this->psrResponse->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        return (string) $body;
    }

    public function toPsr7(): ResponseInterface
    {
        return $this->psrResponse;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->psrResponse->getStatusCode());

            foreach ($this->psrResponse->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header("{$name}: {$value}", false);
                }
            }
        }

        echo $this->getBodyContent();
    }
}
