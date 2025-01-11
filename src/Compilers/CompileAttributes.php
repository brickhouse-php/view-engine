<?php

namespace Brickhouse\View\Engine\Compilers;

use Brickhouse\View\Engine\Attribute;
use Brickhouse\View\Engine\AttributeArgument;
use Brickhouse\View\Engine\CompilerContext;

class CompileAttributes
{
    public function __invoke(CompilerContext $context, callable $next): string
    {
        $attributes = $context->compiler->attributes;

        $result = Attribute::BODY_MARKER;

        foreach ($context->node->attributes as $key => $value) {
            if (!($attribute = $attributes[$key] ?? null)) {
                continue;
            }

            $argumentRequirement = $attribute->attributes()[$key];

            if ($argumentRequirement === AttributeArgument::REQUIRED && $value === null) {
                throw new \RuntimeException("Required prop not defined: {$key}");
            }

            if ($argumentRequirement === AttributeArgument::NONE && $value !== null) {
                throw new \RuntimeException("Argument passed to attribute which does not support arguments: {$key} = '{$value}'");
            }

            $replacement = $attribute($context, $key, $value);
            $result = str_replace(Attribute::BODY_MARKER, $replacement, $result);

            // Remove the attribute from the node, so it doesn't show up in the compiled HTML.
            $context->node->removeAttribute($key);
        }

        $body = $next($context);

        return str_replace(Attribute::BODY_MARKER, $body, $result);
    }
}
