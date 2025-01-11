<?php

use Brickhouse\View\Engine\Compiler;
use Brickhouse\View\Engine\Parser;

describe('CompileLayouts', function () {
    it('wraps layout in template given string content', function () {
        $node = new Parser()->parse("<x-layout::default>text-content</x-layout::default>");
        $compiled = new Compiler()->compile($node);

        expect($compiled)->toBe(implode([
            '<?php $__fragment = $__renderer->startLayout("x-layout::default", []); ?>',
            '<?php $__renderer->startTemplate($__fragment, "default"); ?>',
            'text-content',
            '<?php $__renderer->endTemplate(); ?>',
            '<?php echo $__renderer->renderLayout(); ?>',
        ]));
    });
});
