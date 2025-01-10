<?php

use Brickhouse\View\Compiler;
use Brickhouse\View\Parser;

expect()->extend('toCompileInto', function (string $expected) {
    $node = new Parser()->parse($this->value);
    $result = new Compiler()->compile($node);

    expect($result)->toBe($expected);

    return $this;
});

describe('Compiler', function () {
    it('returns node of same html type')
        ->expect("<div></div>")
        ->toCompileInto('<div />');

    it('returns node of with same attributes')
        ->expect("<div class='flex'></div>")
        ->toCompileInto('<div class="flex" />');

    it('returns node of with child content')
        ->expect("<div><span></span></div>")
        ->toCompileInto('<div><span /></div>');

    it('returns node of with interpolated text')
        ->expect('<div>{{ $item }}</div>')
        ->toCompileInto('<div><?= $item; ?></div>');

    it('returns node of with interpolated attribute')
        ->expect('<div type="{{ $type }}">Text</div>')
        ->toCompileInto('<div type="<?= $type; ?>">Text</div>');

    it('returns node of with conditional attribute')
        ->expect('<div v-if="$type === \'div\'">Text</div>')
        ->toCompileInto('<?php if(($type === \'div\') ?? false): ?><div>Text</div><?php endif; ?>');
});
