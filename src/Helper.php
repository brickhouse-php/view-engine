<?php

namespace Brickhouse\View\Engine;

interface Helper
{
    /**
     * Gets the tag of the helper.
     *
     * @var string
     */
    public string $tag { get; }

    /**
     * Handler for when the helper is found in a template.
     *
     * @param Compiler          $compiler   Compiler instance which is handling the helper.
     * @param mixed             $args       The argument(s) to the helper, if any was given.
     *
     * @return string           The content to replace the helper tag with.
     */
    public function __invoke(Compiler $compiler, mixed ...$args): string;
}
