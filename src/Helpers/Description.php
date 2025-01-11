<?php

namespace Brickhouse\View\Engine\Helpers;

use Brickhouse\View\Engine\Compiler;
use Brickhouse\View\Engine\Helper;

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
