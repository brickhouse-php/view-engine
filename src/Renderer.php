<?php

namespace Brickhouse\View;

class Renderer
{
    /**
     * Gets a stack of all the fragments in the current compile process.
     *
     * @var array<int,Fragment>
     */
    private array $fragmentRenderStack = [];

    /**
     * Gets a stack of all the rendered templates in the current compile process.
     *
     * @var array<int,array{name:string,fragment:Fragment}>
     */
    private array $templateRenderStack = [];

    /**
     * Gets a stack of all the rendered slots in the current compile process.
     *
     * @var array<int,array{fragment:Fragment,name:string}>
     */
    private array $slotRenderStack = [];

    protected readonly Compiler $compiler;

    public function __construct(
        public readonly ViewResolver $viewResolver,
    ) {
        $this->compiler = new Compiler();
    }

    public function render(string $template, array $data = []): string
    {
        $compiled = $this->compiler->compile($template);
        $data = [
            ...$data,
            '__renderer' => $this,
        ];

        return $this->renderCompiledTemplate($compiled, $data);
    }

    public function renderFile(string $path, array $data = []): string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Cannot read template fine: {$path}");
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

        try {
            // Render the temporary file into HTML
            return $this->renderCompiledTemplateFile($compiledFilePath, $data);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // Finally, delete the temporary file again.
            unlink($compiledFilePath);
        }
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
        $fragment = array_pop($this->fragmentRenderStack);
        $componentPath = $this->viewResolver->resolveComponent($fragment->alias);

        $data = [
            ...$fragment->attributes,
            '__fragment' => $fragment,
            'slots' => $fragment->slots,
        ];

        return $this->renderFile($componentPath, $data);
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
}
