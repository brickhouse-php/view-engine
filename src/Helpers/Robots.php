<?php

namespace Brickhouse\View\Engine\Helpers;

use Brickhouse\View\Engine\Compiler;
use Brickhouse\View\Engine\Helper;

class Robots implements Helper
{
    /**
     * @inheritDoc
     */
    public string $tag = "robots";

    /**
     * @inheritDoc
     */
    public function __invoke(Compiler $compiler, mixed ...$args): string
    {
        $args = array_reduce(
            $args,
            function (array $carry, mixed $value) {
                $carry += is_array($value) ? $value : [$value];
                return $carry;
            },
            []
        );

        $content = join(", ", $args);

        return "<meta name=\"robots\" content=\"{$content}\" />";
    }
}
