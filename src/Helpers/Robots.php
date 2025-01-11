<?php

namespace Brickhouse\View\Helpers;

use Brickhouse\View\Compiler;
use Brickhouse\View\Helper;

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
