<?php

use Brickhouse\View\Compiler;
use Brickhouse\View\Node;

describe('CompileInterpolation', function () {
    it('skips attributes without value', function () {
        $compiled = new Compiler()->compile(new Node("div", ['{{ $key }}']));

        expect($compiled)->toBe('<div {{ $key }}="" />');
    });
});
