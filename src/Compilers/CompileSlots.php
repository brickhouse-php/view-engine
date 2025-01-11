<?php

namespace Brickhouse\View\Engine\Compilers;

use Brickhouse\View\Engine\CompilerContext;
use Brickhouse\View\Engine\Node;

class CompileSlots
{
    public function __invoke(CompilerContext $context, callable $next): string
    {
        if ($context->node->type !== 'slot') {
            return $next($context);
        }

        $name = $this->getSlotName($context->node);
        $content = $context->compiler->compileNodes($context->node->children);

        $prologue = implode([
            '<?php $__renderer->startSlot($__fragment, "' . $name . '"); ?>',
        ]);

        $epilogue = implode([
            '<?php echo $__renderer->renderSlot(); ?>',
        ]);

        return implode([$prologue, $content, $epilogue]);
    }

    /**
     * Guesses the name of the slot, if any is given. Otherwise, returns `"default"`.
     * Slot names are defined as attributes, which names start with a `#` (e.g. `#name`).
     *
     * @param   Node    $node
     * @return  string
     */
    protected function getSlotName(Node $node): string
    {
        foreach (array_keys($node->attributes) as $key) {
            if (str_starts_with($key, "#")) {
                return ltrim($key, "#");
            }
        }

        return "default";
    }
}
