<?php

use Brickhouse\View\Parser;

describe('Parser', function () {
    it('throws exception given empty template', function () {
        new Parser()->parse("");
    })->throws(\RuntimeException::class);

    it('returns node of same html type', function () {
        $node = new Parser()->parse("<div></div>");

        expect($node->type)->toEqual('div');
    });

    it('returns node with text child content', function () {
        $node = new Parser()->parse("<span>Text</span>");

        expect($node->type)->toEqual('span');
        expect($node->hasChildren())->toBeTrue();
        expect($node->children[0])->toBe('Text');
    });

    it('returns node with node child content', function () {
        $node = new Parser()->parse("<div><span>Text</span></div>");

        expect($node->type)->toEqual('div');
        expect($node->children[0]->type)->toEqual('span');
        expect($node->children[0]->children[0])->toEqual('Text');
    });

    it('throws exception given multiple root nodes', function () {
        new Parser()->parse("<div></div><div></div>");
    })->throws(\RuntimeException::class);

    it('returns attributes from node', function () {
        $node = new Parser()->parse("<span style='red'>Text</span>");

        expect($node->attributes)->toMatchArray(['style' => 'red']);
    });

    it('returns last attributes from node', function () {
        $node = new Parser()->parse("<span style='red' style='green'>Text</span>");

        expect($node->attributes)->toMatchArray(['style' => 'green']);
    });
});
