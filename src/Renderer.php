<?php

namespace Brickhouse\View\Engine;

use Brickhouse\View\Engine\Exceptions\ViewNotFoundException;

class Renderer
{
    /**
     * Gets a stack of all the fragments in the current compile process.
     *
     * @var array<int,Fragment>
     */
    protected array $fragmentRenderStack = [];

    /**
     * Gets a stack of all the rendered templates in the current compile process.
     *
     * @var array<int,array{name:string,fragment:Fragment}>
     */
    protected array $templateRenderStack = [];

    /**
     * Gets a stack of all the rendered slots in the current compile process.
     *
     * @var array<int,array{fragment:Fragment,name:string}>
     */
    protected array $slotRenderStack = [];

    protected readonly Compiler $compiler;

    public function __construct(
        public readonly ViewResolver $viewResolver,
        null|Compiler $compiler = null,
    ) {
        $this->compiler = $compiler ?? new Compiler();
    }

    /**
     * Render the given template content into a fully-rendered HTML document.
     *
     * @param string    $template       Template content to render.
     * @param array     $data           Optional data to pass to the template.
     *
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        $compiled = $this->compiler->compile($template);
        $data = [
            ...$data,
            '__renderer' => $this,
            '__fragment' => end($this->fragmentRenderStack),
        ];

        return $this->renderCompiledTemplate($compiled, $data);
    }

    /**
     * Render the template at the given file path into a fully-rendered HTML document.
     *
     * @param string    $path       Template file path to render.
     * @param array     $data       Optional data to pass to the template.
     *
     * @return string
     */
    public function renderFile(string $path, array $data = []): string
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new ViewNotFoundException($path);
        }

        return $this->render($content, $data);
    }

    /**
     * Render the given compiled template into a fully-rendered HTML document.
     *
     * @param string        $template
     * @param array         $data
     *
     * @return string
     */
    protected function renderCompiledTemplate(string $template, array $data): string
    {
        // Create a temporary file to store the template
        $compiledFilePath = tempnam(sys_get_temp_dir(), "brickhouse-view-");

        $compiledFile = fopen($compiledFilePath, "w");
        fwrite($compiledFile, $template);
        fclose($compiledFile);

        // @codeCoverageIgnoreStart
        try {
            // Render the temporary file into HTML
            return $this->renderCompiledTemplateFile($compiledFilePath, $data);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // Finally, delete the temporary file again.
            unlink($compiledFilePath);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Render the given compiled template file into a fully-rendered HTML document.
     *
     * @param string        $path
     * @param array         $data
     *
     * @return string
     */
    protected function renderCompiledTemplateFile(string $path, array $data): string
    {
        // @codeCoverageIgnoreStart

        $obLevel = ob_get_level();

        // Start output buffering.
        // In practice, this acts as the buffer `require` will put the content into
        // as opposed to printing it to the console.
        ob_start();

        try {
            (static function (string $path, array $data = []) {
                extract($data, EXTR_OVERWRITE);
                require $path;
            })($path, $data);
        } catch (\Throwable $e) {
            // If we crash, clean all the buffers that we created.
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            throw $e;
        }

        // Get all the content written to the output buffer and turn it off again.
        return ltrim(ob_get_clean());

        // @codeCoverageIgnoreEnd
    }

    /**
     * Starts the rendering process of the given fragment alias.
     *
     * @param string                $alias
     *
     * @return Fragment
     */
    public function startFragment(string $alias, array $attributes): Fragment
    {
        $fragment = $this->fragmentRenderStack[] = new Fragment($alias, $attributes);

        return $fragment;
    }

    /**
     * Render the next fragment in the fragment stack.
     *
     * @return string
     */
    public function renderFragment(): string
    {
        $fragment = end($this->fragmentRenderStack);
        $componentPath = $this->viewResolver->resolveComponent($fragment->alias);

        $data = [
            ...$fragment->attributes,
            '__fragment' => $fragment,
            'slots' => $fragment->slots,
        ];

        $rendered = $this->renderFile($componentPath, $data);
        array_pop($this->fragmentRenderStack);

        return $rendered;
    }

    /**
     * Starts the rendering process of the given slot template.
     *
     * @param string                $name
     *
     * @return void
     */
    public function startTemplate(Fragment $fragment, string $name): void
    {
        if (ob_start()) {
            $this->templateRenderStack[] = [
                'name' => $name,
                'fragment' => $fragment,
            ];
        }
    }

    /**
     * Ends the current template and appends it the it's fragment.
     *
     * @return void
     */
    public function endTemplate(): void
    {
        [
            'name' => $name,
            'fragment' => $fragment,
        ] = array_pop($this->templateRenderStack);

        $fragment->assignSlotContent($name, trim(ob_get_clean()));
    }

    /**
     * Starts the rendering process of the given component slot.
     *
     * @param string                $name
     *
     * @return void
     */
    public function startSlot(Fragment $fragment, string $name): void
    {
        if (ob_start()) {
            $this->slotRenderStack[] = [
                'name' => $name,
                'fragment' => $fragment,
            ];
        }
    }

    /**
     * Render the next component slot in the component stack.
     *
     * @return string
     */
    public function renderSlot(): string
    {
        [
            'name' => $slot,
            'fragment' => $fragment,
        ] = array_pop($this->slotRenderStack);

        $defaultContent = trim(ob_get_clean());

        if (($content = $fragment->slots[$slot] ?? null)) {
            return $content;
        }

        return $defaultContent;
    }

    /**
     * Starts the rendering process of the given fragment alias.
     *
     * @param string                $alias
     *
     * @return Fragment
     */
    public function startLayout(string $alias, array $attributes): Fragment
    {
        $fragment = $this->fragmentRenderStack[] = new Fragment($alias, $attributes);

        return $fragment;
    }

    /**
     * Render the next fragment in the fragment stack.
     *
     * @return string
     */
    public function renderLayout(): string
    {
        $fragment = end($this->fragmentRenderStack);
        $layoutPath = $this->viewResolver->resolveLayout($fragment->alias);

        $data = [
            ...$fragment->attributes,
            'slots' => $fragment->slots,
        ];

        $rendered = $this->renderFile($layoutPath, $data);
        array_pop($this->fragmentRenderStack);

        return $rendered;
    }

    /**
     * Render the given helper into it's replacement string.
     *
     * @param string    $helper     The name of the helper
     * @param mixed     $args       Args to pass to the helper.
     *
     * @return string
     */
    public function renderHelper(string $helper, mixed ...$args): string
    {
        $helpers = $this->compiler->helpers;

        $handler = $helpers[$helper] ?? null;
        if (!$handler) {
            return "";
        }

        return $handler($this->compiler, ...$args);
    }
}
