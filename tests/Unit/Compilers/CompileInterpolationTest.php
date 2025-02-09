<?php

use Brickhouse\View\Engine\Compiler;
use Brickhouse\View\Engine\Node;

describe('CompileInterpolation', function () {
    it('skips attributes without value', function () {
        $compiled = new Compiler()->compile(new Node("div", ['{{ $key }}']));

        expect($compiled)->toBe('<div {{ $key }}=""></div>');
    });
});
