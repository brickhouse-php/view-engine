<?php

namespace Brickhouse\View;

class ViewResolver
{
    /**
     * Gets the extension for supported views.
     *
     * @var string
     */
    public const string EXTENSION = ".view.php";

    /**
     * Get the prefix for all aliases.
     *
     * @var string
     */
    public const string ALIAS_PREFIX = "x-";

    /**
     * Get the prefix for all layouts.
     *
     * @var string
     */
    public const string LAYOUT_PREFIX = "x-layout::";

    /**
     * Get the delimiter for namespaced components.
     *
     * @var string
     */
    public const string NAMESPACE_DELIMITER = "::";

    /**
     * Gets the base path to look for views.
     *
     * @var string
     */
    public readonly string $basePath;

    public function __construct(string $basePath)
    {
        if (!str_ends_with($basePath, '\\') && !str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        $this->basePath = str_replace('\\', '/', $basePath);
    }

    /**
     * Guesses the path of the view with the given alias.
     *
     * @param string $alias
     *
     * @return string
     */
    public function resolveView(string $alias): string
    {
        $path = ltrim($alias, '/\\');
        $path = str_replace(['.', '/', '\\'], ['/', '/', '/'], $path) . self::EXTENSION;
        $path = $this->path($this->basePath, 'app', 'views', $path);

        return $path;
    }

    /**
     * Guesses the path of the layout with the given alias.
     *
     * @param string $alias
     *
     * @return string
     */
    public function resolveLayout(string $alias): string
    {
        // Strip layout alias prefix
        $alias = substr($alias, strlen(self::LAYOUT_PREFIX));

        $path = ltrim($alias, '/\\');
        $path = str_replace(['.', '/', '\\'], ['/', '/', '/'], $path) . self::EXTENSION;
        $path = $this->path($this->basePath, 'app', 'views', 'layouts', $path);

        return $path;
    }

    /**
     * Guesses the path of the component with the given alias.
     *
     * @param string $alias
     *
     * @return string
     */
    public function resolveComponent(string $alias): string
    {
        // Strip fragment alias prefix
        $alias = substr($alias, strlen(self::ALIAS_PREFIX));

        $path = ltrim($alias, '/\\');
        $path = str_replace(['.', '/', '\\'], ['/', '/', '/'], $path) . self::EXTENSION;
        $path = $this->path($this->basePath, 'app', 'views', "components", $path);

        return $path;
    }

    /**
     * Join all the given path segments into a single path.
     *
     * @param string    $segments
     *
     * @return string
     */
    private function path(string ...$segments): string
    {
        return preg_replace('#/+#', DIRECTORY_SEPARATOR, join(DIRECTORY_SEPARATOR, $segments));
    }
}
