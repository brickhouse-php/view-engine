<?php

use Brickhouse\View\Node;

describe('Node', function () {
    it('creates node with same type', function () {
        $node = new Node('div');

        expect($node->type)->toBe('div');
    });

    it('uses random key', function () {
        $node = new Node('div', []);

        expect($node->key)->toMatch('/^cmp-\w{14}\.\d{8}/i');
    });

    it('uses given key', function () {
        $node = new Node('div', ['key' => 'some-key']);

        expect($node->key)->toBe('some-key');
    });

    it('uses indexed attributes as keys', function () {
        $node = new Node('div', ['attr']);

        expect($node->attributes)->toMatchArray([
            'attr' => null,
        ]);
    });

    it('uses keyed attributes as values', function () {
        $node = new Node('div', ['attr' => 'value']);

        expect($node->attributes)->toMatchArray([
            'attr' => 'value',
        ]);
    });

    it('sets siblings', function () {
        $parent = new Node('div');
        $parent->addChild($child1 = new Node('p'));
        $parent->addChild($child2 = new Node('p'));

        expect($child1->nextSibling->key)->toBe($child2->key);
        expect($child2->previousSibling->key)->toBe($child1->key);
    });

    it('sets parent', function () {
        $parent = new Node('div');
        $parent->addChild($child1 = new Node('p'));
        $parent->addChild($child2 = new Node('p'));

        expect($child1->parent->key)->toBe($parent->key);
        expect($child2->parent->key)->toBe($parent->key);
    });

    it('updates child with index', function () {
        $parent = new Node('div');
        $parent->addChild(new Node('p'));
        $parent->updateChild(0, "string content");

        expect($parent->children[0])->toBeString();
        expect($parent->children[0])->toBe('string content');
    });

    it('updates child with key', function () {
        $parent = new Node('div');
        $parent->addChild($child = new Node('p'));
        $parent->updateChild($child->key, "string content");

        expect($parent->children[0])->toBeString();
        expect($parent->children[0])->toBe('string content');
    });

    it('skips child update with invalid key', function () {
        $parent = new Node('div');
        $parent->addChild($child = new Node('p'));
        $parent->updateChild('key', "string content");

        expect($parent->children[0])->toBe($child);
    });

    it('finds child with key', function () {
        $parent = new Node('div');
        $parent->addChild($child = new Node('p'));

        $found = $parent->child($child->key);

        expect($found)->not->toBeNull();
    });

    it('doesnt find child with invalid key', function () {
        $parent = new Node('div');
        $parent->addChild(new Node('p'));

        $found = $parent->child('some key');

        expect($found)->toBeNull();
    });
});
