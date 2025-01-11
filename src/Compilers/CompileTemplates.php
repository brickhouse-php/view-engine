<?php

namespace Brickhouse\View\Engine\Compilers;

use Brickhouse\View\Engine\CompilerContext;
use Brickhouse\View\Engine\Node;

class CompileTemplates
{
    public function __invoke(CompilerContext $context, callable $next): string
    {
        if ($context->node->type !== 'template') {
            return $next($context);
        }

        $name = $this->getTemplateName($context->node);
        $content = $context->compiler->compileNodes($context->node->children);

        $prologue = implode([
            '<?php $__renderer->startTemplate($__fragment, "' . $name . '"); ?>',
        ]);

        $epilogue = implode([
            '<?php $__renderer->endTemplate(); ?>',
        ]);

        return implode([$prologue, $content, $epilogue]);
    }

    /**
     * Guesses the name of the template, if any is given. Otherwise, returns `"default"`.
     * Template names are defined as attributes, which names start with a `#` (e.g. `#name`).
     *
     * @param   Node    $node
     * @return  string
     */
    protected function getTemplateName(Node $node): string
    {
        foreach ($node->attributes as $key => $_) {
            if (str_starts_with($key, "#")) {
                return ltrim($key, "#");
            }
        }

        return "default";
    }
}
