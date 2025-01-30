<?php

namespace Brickhouse\View\Engine;

class Compiler
{
    public readonly Parser $parser;

    /**
     * Get all the compilers in the pipeline for compiling templates.
     *
     * @return array<int,callable(CompilerContext $context): string>
     */
    public array $pipeline = [
        \Brickhouse\View\Engine\Compilers\CompileAttributes::class,
        \Brickhouse\View\Engine\Compilers\CompileLayouts::class,
        \Brickhouse\View\Engine\Compilers\CompileFragments::class,
        \Brickhouse\View\Engine\Compilers\CompileSlots::class,
        \Brickhouse\View\Engine\Compilers\CompileTemplates::class,
        \Brickhouse\View\Engine\Compilers\CompileInterpolation::class,
    ];

    /**
     * Get all the attribute handlers which are supported by the compiler.
     *
     * @return array<string,Attribute>
     */
    public protected(set) array $attributes = [];

    /**
     * Get all the helper handlers which are supported by the compiler.
     *
     * @return array<string,Helper>
     */
    public protected(set) array $helpers = [];

    public function __construct()
    {
        $this->parser = new Parser;

        $this->addAttributes([
            \Brickhouse\View\Engine\Attributes\ConditionalAttributes::class,
            \Brickhouse\View\Engine\Attributes\LoopAttributes::class,
        ]);

        $this->addHelpers([
            \Brickhouse\View\Engine\Helpers\Description::class,
            \Brickhouse\View\Engine\Helpers\Keywords::class,
            \Brickhouse\View\Engine\Helpers\Robots::class,
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
            $template = $this->compileNodes($node);
        } else {
            $template = $this->compileNode($node);
        }

        $template = $this->compileHelpers($template);

        return $template;
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
        $compilerStack = $this->pipeline;

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
     * Compile helpers from the given template into their respective replacements.
     *
     * @param string    $template       Template to compile helpers for.
     *
     * @return string
     */
    protected function compileHelpers(string $template): string
    {
        $pattern = "/\B@(@?\w+)(?:[ \t]*)(\( ( [\S\s]*? ) \))?/x";

        while (preg_match($pattern, $template, $matches, PREG_OFFSET_CAPTURE)) {
            $match = [
                'match' => $matches[0],
                'helper' => $matches[1],
                'arguments' => $matches[2] ?? null,
            ];

            $helper = $match['helper'][0];

            if (isset($match['arguments'])) {
                $arguments = $this->compileHelperArguments($template, $match['arguments'][1]);
            } else {
                $arguments = "[]";
            }

            $template = substr_replace(
                $template,
                '<?php echo $__renderer->renderHelper("' . $helper . '", ' . $arguments . ') ?>',
                $match['match'][1],
                strlen($match['match'][0])
            );
        }

        return $template;
    }

    /**
     * Compile the arguments for helpers in the given template.
     *
     * @param string    $template       Template to compile helpers for.
     * @param int       $start          Start index into `$template` where an argument list starts.
     *
     * @return string
     */
    protected function compileHelperArguments(string $template, int $start): string
    {
        // @codeCoverageIgnoreStart
        if (strpos($template, "(") === false) {
            return $template;
        }
        // @codeCoverageIgnoreEnd

        $hasEvenNumberOfParentheses = function (string $expression): bool {
            // @codeCoverageIgnoreStart
            if ($expression[strlen($expression) - 1] !== ')') {
                return false;
            }
            // @codeCoverageIgnoreEnd

            $difference = 0;

            for ($i = 0; $i < strlen($expression); $i++) {
                $token = $expression[$i];

                if ($token === ')') {
                    $difference--;
                } else if ($token === '(') {
                    $difference++;
                }
            }

            return $difference === 0;
        };

        $offset = strpos($template, ')', $start);

        // @codeCoverageIgnoreStart
        do {
            if ($offset === false) {
                return "";
            }

            $segment = substr($template, $start, $offset - $start + 1);

            if ($hasEvenNumberOfParentheses($segment)) {
                return substr($segment, 1, -1);
            }

            $offset = strpos($template, ')', $offset + 1);
        } while (true);
        // @codeCoverageIgnoreEnd
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

    /**
     * Adds a new helper to the compiler.
     *
     * @param   class-string<Helper>    $helper
     */
    public function addHelper(string $helper): void
    {
        $instance = new $helper;

        $this->helpers[$instance->tag] = $instance;
    }

    /**
     * Adds new helpers to the compiler.
     *
     * @param   array<int,class-string<Helper>>     $helpers
     */
    public function addHelpers(array $helpers): void
    {
        foreach ($helpers as $helper) {
            $this->addHelper($helper);
        }
    }
}
