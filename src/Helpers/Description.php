<?php

namespace Brickhouse\View\Helpers;

use Brickhouse\View\Compiler;
use Brickhouse\View\Helper;

class Description implements Helper
{
    /**
     * @inheritDoc
     */
    public string $tag = "description";

    /**
     * @inheritDoc
     */
    public function __invoke(Compiler $compiler, mixed ...$args): string
    {
        $content = (string) $args[0];

        return "<meta name=\"description\" content=\"{$content}\" />";
    }
}
