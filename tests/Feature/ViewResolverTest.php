<?php

use Brickhouse\View\ViewResolver;

describe('ViewResolver', function () {
    it('resolves view alias to the view path', function (string $alias, string $expected) {
        $resolver = new ViewResolver("/home/max/brickhouse");
        $path = $resolver->resolveView($alias);

        expect($path)->toBe($expected);
    })->with([
        ['index', '/home/max/brickhouse/app/views/index.view.php'],
        ['login/index', '/home/max/brickhouse/app/views/login/index.view.php'],
        ['login\\index', '/home/max/brickhouse/app/views/login/index.view.php']
    ]);

    it('resolves component name to the component path', function (string $alias, string $expected) {
        $resolver = new ViewResolver("/home/max/brickhouse");
        $path = $resolver->resolveComponent($alias);

        expect($path)->toBe($expected);
    })->with([
        ['x-shop', '/home/max/brickhouse/app/views/components/shop.view.php'],
        ['x-shop/product', '/home/max/brickhouse/app/views/components/shop/product.view.php'],
        ['x-shop.product', '/home/max/brickhouse/app/views/components/shop/product.view.php'],
        ['x-button.primary', '/home/max/brickhouse/app/views/components/button/primary.view.php'],
    ]);

    it('resolves layout alias to the layout path', function (string $alias, string $expected) {
        $resolver = new ViewResolver("/home/max/brickhouse");
        $path = $resolver->resolveLayout($alias);

        expect($path)->toBe($expected);
    })->with([
        ['x-layout::shop', '/home/max/brickhouse/app/views/layouts/shop.view.php'],
        ['x-layout::shop/product', '/home/max/brickhouse/app/views/layouts/shop/product.view.php'],
    ]);
});
