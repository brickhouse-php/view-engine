<?php

use Brickhouse\View\Renderer;
use Brickhouse\View\ViewResolver;

/**
 * Renders the given template into a fully-rendered HTML document.
 *
 * @param string $template
 *
 * @return string
 */
function renderTemplate(string $template, array $data = []): string
{
    $basePath = test()->integrationBasePath();

    $resolver = new ViewResolver($basePath);
    $renderer = new Renderer($resolver);

    return $renderer->render($template, $data);
}

describe('Renderer', function () {
    it('renders static content', function () {
        $rendered = renderTemplate("<div></div>");

        expect($rendered)->toBe("<div />");
    });

    it('renders nested static content', function () {
        $rendered = renderTemplate("<div><span>Hello</span></div>");

        expect($rendered)->toBe("<div><span>Hello</span></div>");
    });

    it('renders interpolated content', function () {
        $rendered = renderTemplate("<span>{{ 'Hello' }}</span>");

        expect($rendered)->toBe("<span>Hello</span>");
    });

    it('renders interpolated attributes', function () {
        $rendered = renderTemplate("<input type=\"{{ 'button' }}\" />");

        expect($rendered)->toBe('<input type="button" />');
    });

    it('renders interpolated values from data array', function () {
        $rendered = renderTemplate('<span>{{ $message }}</span>', ['message' => 'Hello World!']);

        expect($rendered)->toBe("<span>Hello World!</span>");
    });

    it('renders conditional truthy if-statement', function () {
        $rendered = renderTemplate('<span :if="$condition">Condition met</span>', ['condition' => true]);

        expect($rendered)->toBe("<span>Condition met</span>");
    });

    it('renders conditional falsy if-statement', function () {
        $rendered = renderTemplate('<span :if="$condition">Condition met</span>', ['condition' => false]);

        expect($rendered)->toBe("");
    });

    it('renders conditional else-statements', function (bool $condition, string $expected) {
        $rendered = renderTemplate(<<<'HTML'
            <div>
                <span :if="$condition">Condition met</span>
                <span :else>Condition not met</span>
            </div>
        HTML, ['condition' => $condition]);

        expect($rendered)->toBe($expected);
    })->with([
        [true, "<div><span>Condition met</span></div>"],
        [false, "<div><span>Condition not met</span></div>"],
    ]);

    it('renders conditional else-if-statements', function (int $counter, string $expected) {
        $rendered = renderTemplate(<<<'HTML'
            <div>
                <span :if="$counter === 0">Counter is zero</span>
                <span :else-if="$counter === 1">Counter is one</span>
                <span :else>Counter is {{ $counter }}</span>
            </div>
        HTML, ['counter' => $counter]);

        expect($rendered)->toBe($expected);
    })->with([
        [0, "<div><span>Counter is zero</span></div>"],
        [1, "<div><span>Counter is one</span></div>"],
        [2, "<div><span>Counter is 2</span></div>"],
        [10, "<div><span>Counter is 10</span></div>"],
    ]);

    it('renders looped statements (for)', function () {
        $rendered = renderTemplate(<<<'HTML'
            <span :for="$i = 1; $i <= 3; $i++">Item {{ $i }}</span>
        HTML);

        expect($rendered)->toBe("<span>Item 1</span><span>Item 2</span><span>Item 3</span>");
    });

    it('renders looped statements (foreach)', function () {
        $rendered = renderTemplate(<<<'HTML'
            <span :foreach="$elements as $element">Item {{ $element }}</span>
        HTML, ['elements' => range(1, 3)]);

        expect($rendered)->toBe("<span>Item 1</span><span>Item 2</span><span>Item 3</span>");
    });

    it('renders components', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-button type="default" />
        HTML);

        expect($rendered)->toBe('<button type="default">Default Button</button>');
    });

    it('renders component attributes', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-button type='danger' />
        HTML);

        expect($rendered)->toBe('<button type="danger">Default Button</button>');
    });

    it('renders component slots', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-button type="default">Button Text</x-button>
        HTML);

        expect($rendered)->toBe('<button type="default">Button Text</button>');
    });

    it('renders component slots using explicit templates', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-button type="default"><template>Button Text</template></x-button>
        HTML);

        expect($rendered)->toBe('<button type="default">Button Text</button>');
    });

    it('renders named component slots', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-button type="default"><template #icon><p>Icon</p></template></x-button>
        HTML);

        expect($rendered)->toBe('<button type="default"><p>Icon</p>Default Button</button>');
    });

    it('renders layouts', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-layout::default>
                <h1>Title</h1>
            </x-layout::default>
        HTML);

        expect($rendered)->toBe(<<<'HTML'
        <html><head><title>Example Title</title></head><body><h1>Title</h1></body></html>
        HTML);
    });

    it('renders layouts with attributes', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-layout::titled title="Testing">
                <h1>Title</h1>
            </x-layout::titled>
        HTML);

        expect($rendered)->toBe(<<<'HTML'
        <html><head><title>Testing</title></head><body><h1>Title</h1></body></html>
        HTML);
    });

    it('renders layouts with default slot', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-layout::default>
                <template>
                    <h1>Title</h1>
                </template>
            </x-layout::default>
        HTML);

        expect($rendered)->toBe(<<<'HTML'
        <html><head><title>Example Title</title></head><body><h1>Title</h1></body></html>
        HTML);
    });

    it('renders layouts with named slot', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-layout::navbar>
                <template #navbar>
                    <h1>Navigation</h1>
                </template>
            </x-layout::navbar>
        HTML);

        expect($rendered)->toBe(<<<'HTML'
        <html><head><title>Example Title</title></head><body><h1>Navigation</h1>Default Content</body></html>
        HTML);
    });

    it('renders layouts with mulitple named slots', function () {
        $rendered = renderTemplate(<<<'HTML'
            <x-layout::navbar>
                <template #navbar>
                    <h1>Navigation</h1>
                </template>
                <template>
                    <h1>Custom Title</h1>
                </template>
            </x-layout::navbar>
        HTML);

        expect($rendered)->toBe(<<<'HTML'
        <html><head><title>Example Title</title></head><body><h1>Navigation</h1><h1>Custom Title</h1></body></html>
        HTML);
    });
});
