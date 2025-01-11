<?php

use Brickhouse\View\Renderer;
use Brickhouse\View\ViewResolver;

describe('Renderer', function () {
    it('throws exception given invalid file path', function () {
        new Renderer(new ViewResolver("/tmp"))->renderFile("invalid-file");
    })->throws(\RuntimeException::class);
});
