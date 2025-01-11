<?php

use Brickhouse\View\Engine\Compiler;
use Brickhouse\View\Engine\Parser;

describe('CompileAttributes', function () {
    it('throws exception given attribute without required value', function () {
        $node = new Parser()->parse("<div :if></div>");
        new Compiler()->compile($node);
    })->throws(\RuntimeException::class, "Required prop not defined: :if");

    it('throws exception given attribute with value', function () {
        $node = new Parser()->parse("<div :else='value'></div>");
        new Compiler()->compile($node);
    })->throws(\RuntimeException::class, "Argument passed to attribute which does not support arguments: :else = 'value'");
});
