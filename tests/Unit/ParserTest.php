<?php

use Brickhouse\View\Engine\Parser;

describe('Parser', function () {
    it('throws exception given empty template', function () {
        new Parser()->parse("");
    })->throws(\RuntimeException::class);

    it('returns node of same html type', function () {
        $nodes = new Parser()->parse("<div></div>");

        expect($nodes)->toHaveCount(1);
        expect($nodes[0]->type)->toEqual('div');
    });

    it('returns node with text child content', function () {
        $nodes = new Parser()->parse("<span>Text</span>");

        expect($nodes[0]->type)->toEqual('span');
        expect($nodes[0]->hasChildren())->toBeTrue();
        expect($nodes[0]->children[0])->toBe('Text');
    });

    it('returns node with node child content', function () {
        $nodes = new Parser()->parse("<div><span>Text</span></div>");

        expect($nodes[0]->type)->toEqual('div');
        expect($nodes[0]->children[0]->type)->toEqual('span');
        expect($nodes[0]->children[0]->children[0])->toEqual('Text');
    });

    it('returns attributes from node', function () {
        $nodes = new Parser()->parse("<span style='red'>Text</span>");

        expect($nodes[0]->attributes)->toMatchArray(['style' => 'red']);
    });

    it('returns first attributes from node', function () {
        $nodes = new Parser()->parse("<span style='red' style='green'>Text</span>");

        expect($nodes[0]->attributes)->toMatchArray(['style' => 'red']);
    });

    it('skips comments', function () {
        $nodes = new Parser()->parse("<div><!-- Some comment --></div>");

        expect($nodes)->toHaveCount(1);
        expect($nodes[0]->type)->toEqual('div');
        expect($nodes[0]->children)->toBeEmpty();
    });

    it('skips empty text nodes', function () {
        $nodes = new Parser()->parse("<div>\t</div>");

        expect($nodes)->toHaveCount(1);
        expect($nodes[0]->type)->toEqual('div');
        expect($nodes[0]->children)->toBeEmpty();
    });
});
