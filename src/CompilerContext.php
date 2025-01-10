<?php

namespace Brickhouse\View;

final class CompilerContext
{
    public function __construct(
        public Node $node,
        public readonly Compiler $compiler,
    ) {}
}
