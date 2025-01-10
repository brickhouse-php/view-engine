<?php

namespace Brickhouse\View\Compilers;

use Brickhouse\View\CompilerContext;

class CompileInterpolation
{
    private const string INTERPOLATION_PATTERN = "/(?<!@)?{{\s*(.+?)\s*}}(\r?\n)?/";

    public function __invoke(CompilerContext $context, callable $next): string
    {
        foreach ($context->node->children as $idx => $child) {
            // We only handle string-based children, as nodes would be handled recursively anyway.
            if (!is_string($child)) {
                continue;
            }

            $replacement = $this->matchInterpolation($child);
            $context->node->updateChild($idx, $replacement);
        }

        foreach ($context->node->attributes as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            $replacement = $this->matchInterpolation($value);
            $context->node->updateAttribute($key, $replacement);
        }

        return $next($context);
    }

    protected function matchInterpolation(string $value): string
    {
        return preg_replace(
            self::INTERPOLATION_PATTERN,
            "<?= \\1; ?>",
            $value,
        );
    }
}
