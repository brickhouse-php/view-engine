<?php

namespace Brickhouse\View\Engine\Compilers;

use Brickhouse\View\Engine\CompilerContext;
use Brickhouse\View\Engine\Node;
use Brickhouse\View\Engine\ViewResolver;

class CompileLayouts
{
    public function __invoke(CompilerContext $context, callable $next): string
    {
        $alias = $context->node->type;

        if (!str_starts_with($alias, ViewResolver::LAYOUT_PREFIX)) {
            return $next($context);
        }

        // If the fragment has child content, but it's not wrapped in a `template`-tag,
        // we should do it for the user, as a convinience.
        // The tag will be handled by the `CompileTemplates` compiler.
        if ($this->shouldWrapTemplate($context)) {
            $templateNode = new Node('template', [], $context->node->children);

            $context->node->removeAllChildren();
            $context->node->addChild($templateNode);
        }

        $body = $context->compiler->compileNodes($context->node->children);
        $data = $context->compiler->sanitizeFragmentAttributes($context->node->attributes);

        return $this->emplaceLayoutStructure($alias, $data, $body);
    }

    /**
     * Replace the layout alias with the setup and teardown of it's template.
     *
     * @param string $alias     The alias of the layout to render.
     * @param string $data      Attributes to the layout, which has been serialized into PHP-style array syntax.
     * @param string $body      The body content to emplace as the layout child content.
     *
     * @return string
     */
    protected function emplaceLayoutStructure(string $alias, string $data, string $body): string
    {
        $prologue = implode([
            '<?php $__fragment = $__renderer->startLayout("' . $alias . '", ' . $data . '); ?>',
        ]);

        $epilogue = implode([
            '<?php echo $__renderer->renderLayout(); ?>',
        ]);

        return implode([$prologue, $body, $epilogue]);
    }

    /**
     * Determine whether the fragment child content should be wrapped in a `template`-tag.
     *
     * @param CompilerContext   $context
     *
     * @return bool
     */
    protected function shouldWrapTemplate(CompilerContext $context): bool
    {
        foreach ($context->node->children as $child) {
            if (is_string($child)) {
                return true;
            }

            if ($child->type === 'template') {
                return false;
            }
        }

        return true;
    }
}
