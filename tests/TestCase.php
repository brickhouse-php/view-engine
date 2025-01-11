<?php

namespace Brickhouse\View\Engine\Tests;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Gets the base path to the integration tests.
     *
     * @return string
     */
    function integrationBasePath(): string
    {
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $rootDirectory = dirname($reflection->getFileName(), levels: 3);

        return $rootDirectory . "/tests/Integration/";
    }
}
