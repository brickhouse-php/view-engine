<?php

namespace Brickhouse\View\Engine;

final class CompilerContext
{
    public function __construct(
        public Node $node,
        public readonly Compiler $compiler,
    ) {}
}
