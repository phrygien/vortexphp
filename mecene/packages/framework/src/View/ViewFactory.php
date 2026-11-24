<?php

declare(strict_types=1);

namespace Mecene\View;

use RuntimeException;
use Mecene\Session\SessionManager;

/**
 * Moteur de rendu des vues Mecene.
 */
class ViewFactory
{
    /** @var array<string, string> */
    protected array $sections = [];

    /** @var string[] */
    protected array $sectionStack = [];

    protected ?string $parentLayout = null;

    /**
     * @param string[] $paths
     */
    public function __construct(
        protected readonly MeceneCompiler $compiler,
        protected readonly array $paths,
        protected readonly SessionManager $session,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->parentLayout = null;

        $shared = [
            'errors' => $this->session->errors(),
            'flashSuccess' => $this->session->getFlash('success'),
            'flashError' => $this->session->getFlash('error'),
            '__env' => $this,
        ];

        $mergedData = array_merge($shared, $data);
        $output = $this->evaluateView($view, $mergedData);

        while ($this->parentLayout !== null) {
            $layout = $this->parentLayout;
            $this->parentLayout = null;
            $output = $this->evaluateView($layout, $mergedData);
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $parentVars
     */
    public function renderPartial(string $view, array $data = [], array $parentVars = []): string
    {
        $scope = array_merge($parentVars, $data, ['__env' => $this]);
        return $this->evaluateView($view, $scope);
    }

    public function extend(string $layout): void
    {
        $this->parentLayout = $layout;
    }

    public function startSection(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            throw new RuntimeException('Directive @endsection appelée sans @section correspondante.');
        }

        $content = ob_get_clean() ?: '';
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = $content;
        }
    }

    public function setSection(string $name, string $value): void
    {
        if (!isset($this->sections[$name])) {
            $this->sections[$name] = $value;
        }
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function evaluateView(string $view, array $data): string
    {
        $path = $this->resolvePath($view);
        $compiled = $this->compiler->compile($path);

        $render = static function (string $__compiledFile, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                require $__compiledFile;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return ob_get_clean() ?: '';
        };

        return $render($compiled, $data);
    }

    protected function resolvePath(string $view): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.mecene.php';

        foreach ($this->paths as $basePath) {
            $candidate = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $relative;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("Vue Mecene introuvable : [{$view}].");
    }
}
