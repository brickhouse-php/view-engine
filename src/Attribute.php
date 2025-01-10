<?php

namespace Brickhouse\View;

interface Attribute
{
    /**
     * Marker for where to emplace node body content.
     */
    public const string BODY_MARKER = "<!-- [[ BODY-CONTENT ]] -->";

    /**
     * Returns a list of all supported attributes.
     *
     * @return array<string,AttributeArgument>
     */
    public function attributes(): array;

    /**
     * Handler for the supported attributes.
     *
     * @param CompilerContext   $context
     * @param string            $attribute      The name of the matched attribute.
     * @param null|string       $value          The value of the attribute, if any was given.
     *
     * @return string
     */
    public function __invoke(CompilerContext $context, string $attribute, null|string $value): string;
}
