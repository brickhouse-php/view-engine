<?php

namespace Brickhouse\View\Compilers;

use Brickhouse\View\CompilerContext;
use Brickhouse\View\Node;
use Brickhouse\View\ViewResolver;

class CompileFragments
{
    public function __invoke(CompilerContext $context, callable $next): string
    {
        $alias = $context->node->type;
        $attributes = $context->node->attributes;

        if (!str_starts_with($alias, ViewResolver::ALIAS_PREFIX)) {
            return $next($context);
        }

        $data = $context->compiler->sanitizeFragmentAttributes($attributes);

        // If the fragment has child content, but it's not wrapped in a `template`-tag,
        // we should do it for the user, as a convinience.
        // The tag will be handled by the `CompileTemplates` compiler.
        if ($this->shouldWrapTemplate($context)) {
            $templateNode = new Node('template', [], $context->node->children);

            $context->node->removeAllChildren();
            $context->node->addChild($templateNode);
        }

        $body = array_map(
            function (string|Node $child) use ($context) {
                if (is_string($child)) {
                    return $child;
                }

                return $context->compiler->compileNode($child);
            },
            $context->node->children
        );

        $body = implode($body);

        return $this->emplaceFragmentStructure($alias, $data, $body);
    }

    /**
     * Replace the fragment alias with the setup and teardown of it's template.
     *
     * @param string $alias     The alias of the fragment to render.
     * @param string $data      Attributes to the fragment, which has been serialized into PHP-style array syntax.
     * @param string $body      The body content to emplace as the fragment child content.
     *
     * @return string
     */
    protected function emplaceFragmentStructure(string $alias, string $data, string $body): string
    {
        $hash = str_replace(".", "", uniqid("__fragment", more_entropy: true));

        $prologue = implode([
            '<?php if(isset($__fragment)) { $' . $hash . ' = $__fragment; } ?>',
            '<?php $__fragment = $__renderer->startFragment("' . $alias . '", ' . $data . '); ?>',
        ]);

        $epilogue = implode([
            '<?php echo $__renderer->renderFragment(); ?>',
            '<?php if(isset($' . $hash . ')) { $__fragment = $' . $hash . '; } ?>',
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
