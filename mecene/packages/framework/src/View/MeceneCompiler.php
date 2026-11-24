<?php

declare(strict_types=1);

namespace Mecene\View;

/**
 * Compilateur de templates Mecene vers du PHP natif mis en cache.
 */
class MeceneCompiler
{
    public function __construct(
        protected readonly string $cachePath,
    ) {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0775, true);
        }
    }

    public function compile(string $viewPath): string
    {
        $hash = sha1($viewPath);
        $compiledPath = $this->cachePath . DIRECTORY_SEPARATOR . $hash . '.php';

        if (!file_exists($compiledPath) || filemtime($viewPath) > filemtime($compiledPath)) {
            $source = file_get_contents($viewPath);
            if ($source === false) {
                throw new \RuntimeException("Impossible de lire la vue [{$viewPath}].");
            }

            $compiled = $this->compileString($source);
            file_put_contents($compiledPath, $compiled, LOCK_EX);
        }

        return $compiledPath;
    }

    public function compileString(string $content): string
    {
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content) ?? $content;

        $extendsPattern = '/@extends\(\s*[\'"]([^\'"]+)[\'"]\s*\)/';
        $parentLayout = null;
        if (preg_match($extendsPattern, $content, $match) === 1) {
            $parentLayout = $match[1];
            $content = preg_replace($extendsPattern, '', $content, 1) ?? $content;
        }

        $content = str_replace(['@php', '@endphp'], ['<?php ', ' ?>'], $content);
        $content = str_replace('@csrf', '<?php echo csrf_field(); ?>', $content);
        $content = preg_replace(
            '/@method\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<input type="hidden" name="_method" value="$1">',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/@include\(\s*([^\)]+)\s*\)/',
            '<?php echo $__env->renderPartial($1, get_defined_vars()); ?>',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/@section\(\s*([\'"][^\'"]+[\'"])\s*,\s*([^\)]+)\)/',
            '<?php $__env->setSection($1, (string) ($2)); ?>',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/@section\(\s*([\'"][^\'"]+[\'"])\s*\)/',
            '<?php $__env->startSection($1); ?>',
            $content
        ) ?? $content;
        $content = str_replace('@endsection', '<?php $__env->endSection(); ?>', $content);

        $content = preg_replace(
            '/@yield\(\s*([\'"][^\'"]+[\'"])\s*,\s*([^\)]+)\)/',
            '<?php echo $__env->yieldSection($1, $2); ?>',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/@yield\(\s*([\'"][^\'"]+[\'"])\s*\)/',
            '<?php echo $__env->yieldSection($1); ?>',
            $content
        ) ?? $content;

        $content = preg_replace(
            '/@error\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
            '<?php if (isset($errors[\'$1\'])): $message = $errors[\'$1\'][0]; ?>',
            $content
        ) ?? $content;
        $content = str_replace('@enderror', '<?php unset($message); endif; ?>', $content);

        $content = preg_replace('/@if\s*\((.*)\)/', '<?php if ($1): ?>', $content) ?? $content;
        $content = preg_replace('/@elseif\s*\((.*)\)/', '<?php elseif ($1): ?>', $content) ?? $content;
        $content = str_replace('@else', '<?php else: ?>', $content);
        $content = str_replace('@endif', '<?php endif; ?>', $content);

        $content = preg_replace('/@foreach\s*\((.*)\)/', '<?php foreach ($1): ?>', $content) ?? $content;
        $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);

        $content = $this->compileForelseBlocks($content);

        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?php echo $1; ?>', $content) ?? $content;
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo e($1); ?>', $content) ?? $content;

        if ($parentLayout !== null) {
            $content .= "\n<?php \$__env->extend(" . var_export($parentLayout, true) . "); ?>";
        }

        return $content;
    }

    protected function compileForelseBlocks(string $content): string
    {
        $counter = 0;
        $stack = [];

        $content = preg_replace_callback(
            '/@forelse\s*\((.+?)\s+as\s+(.+?)\)/',
            function (array $m) use (&$counter, &$stack): string {
                $id = ++$counter;
                $stack[] = $id;
                return "<?php \$__empty_{$id} = true; foreach ({$m[1]} as {$m[2]}): \$__empty_{$id} = false; ?>";
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/@empty\b/',
            function () use (&$stack): string {
                $id = end($stack) ?: 0;
                return "<?php endforeach; if (\$__empty_{$id}): ?>";
            },
            $content
        ) ?? $content;

        $content = preg_replace_callback(
            '/@endforelse/',
            function () use (&$stack): string {
                array_pop($stack);
                return '<?php endif; ?>';
            },
            $content
        ) ?? $content;

        return $content;
    }
}
