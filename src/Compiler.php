<?php

namespace Brickhouse\View;

class Compiler
{
    public readonly Parser $parser;

    /**
     * Get all the compilers in the pipeline for compiling templates.
     *
     * @return array<int,callable(CompilerContext $context): string>
     */
    private array $compilers = [
        \Brickhouse\View\Compilers\CompileAttributes::class,
        \Brickhouse\View\Compilers\CompileLayouts::class,
        \Brickhouse\View\Compilers\CompileFragments::class,
        \Brickhouse\View\Compilers\CompileSlots::class,
        \Brickhouse\View\Compilers\CompileTemplates::class,
        \Brickhouse\View\Compilers\CompileInterpolation::class,
    ];

    /**
     * Get all the attribute handlers which are supported by the compiler.
     *
     * @return array<string,Attribute>
     */
    public protected(set) array $attributes = [];

    public function __construct()
    {
        $this->parser = new Parser;

        $this->addAttributes([
            \Brickhouse\View\Attributes\ConditionalAttributes::class,
            \Brickhouse\View\Attributes\LoopAttributes::class,
        ]);
    }

    /**
     * Compiles the given node tree and returns content of the compiled view.
     *
     * @param string|Node|array<int,Node>   $node       The node tree(s) or template to compile.
     *
     * @return string
     */
    public function compile(string|Node|array $node): string
    {
        if (is_string($node)) {
            $node = $this->parser->parse($node);
        }

        if (is_array($node)) {
            return $this->compileNodes($node);
        }

        return $this->compileNode($node);
    }

    /**
     * Compiles the given node tree and returns content of the compiled view.
     *
     * @param Node      $node
     *
     * @return string
     */
    public function compileNode(Node $node): string
    {
        $callable = fn(CompilerContext $context) => $this->render($context->node);
        $compilerStack = $this->compilers;

        while ($compilerClass = array_pop($compilerStack)) {
            $callable = function (CompilerContext $context) use ($compilerClass, $callable) {
                $compiler = new $compilerClass;

                return $compiler($context, $callable);
            };
        }

        $context = new CompilerContext($node, $this);

        return $callable($context);
    }

    /**
     * Compiles all the nodes in the array into a compile template.
     *
     * @param array<int,Node|string>    $nodes
     *
     * @return string
     */
    public function compileNodes(array $nodes): string
    {
        $nodes = array_map(
            function (string|Node $child) {
                if (is_string($child)) {
                    $child = new Node("", [], $child);
                }

                return $this->compileNode($child);
            },
            $nodes
        );

        return implode($nodes);
    }

    /**
     * Recursively render the given node into an HTML template.
     *
     * @param Node      $node           The node tree to render.
     *
     * @return string
     */
    public function render(Node $node): string
    {
        $type = $node->type;
        $attributes = $this->renderAttributes($node);
        $childContent = $this->renderChildren($node);

        if (strlen($attributes) > 0) {
            $attributes = ' ' . $attributes;
        }

        if (trim($type) === '') {
            return $childContent;
        }

        if (trim($childContent) === '') {
            return "<{$type}{$attributes} />";
        }

        return "<{$type}{$attributes}>{$childContent}</{$type}>";
    }

    protected function renderAttributes(Node $node): string
    {
        $attributes = [];

        foreach ($node->attributes as $key => $value) {
            $attributes[] = "{$key}=\"{$value}\"";
        }

        return trim(implode(" ", $attributes));
    }

    protected function renderChildren(Node $node): string
    {
        $renderedChildContent = array_map(
            function (string|Node $child) {
                if (is_string($child)) {
                    return $child;
                }

                return $this->compile($child);
            },
            $node->children
        );

        return implode($renderedChildContent);
    }

    /**
     * Sanitizes the given array of attributes into a PHP-formatted array.
     *
     * @param array<string,mixed>   $attributes     Array attributes to sanitize.
     *
     * @return string
     */
    public function sanitizeFragmentAttributes(array $attributes): string
    {
        $data = [];

        foreach ($attributes as $key => $value) {
            $value = match (true) {
                is_array($value) => $this->sanitizeFragmentAttributes($value),
                is_int($value), is_bool($value) => $value,
                default => "\"{$value}\"",
            };

            $data[] = "\"{$key}\" => {$value}";
        }

        $data = '[' . join(", ", $data) . ']';

        return $data;
    }

    /**
     * Adds a new attribute to the compiler.
     *
     * @param   class-string<Attribute>     $attribute
     */
    public function addAttribute(string $attribute): void
    {
        $instance = new $attribute;

        foreach (array_keys($instance->attributes()) as $attribute) {
            $this->attributes[$attribute] = $instance;
        }
    }

    /**
     * Adds new attributes to the compiler.
     *
     * @param   array<int,class-string<Attribute>>  $attributes
     */
    public function addAttributes(array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $this->addAttribute($attribute);
        }
    }
}
