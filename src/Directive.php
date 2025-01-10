<?php

namespace Brickhouse\View;

interface Directive
{
    /**
     * Gets the tag of the directive.
     *
     * @var string
     */
    public string $tag { get; }

    /**
     * Handler for when the directive is found in a template.
     *
     * @param array<int,mixed>  $args   The argument(s) to the directive, if any was given.
     *
     * @return string           The content to replace the directive tag with.
     */
    public function __invoke(array $args): string;
}
