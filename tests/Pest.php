<?php

use Brickhouse\View\Tests;

$initialErrorLevel = 0;

pest()
    ->extend(Tests\TestCase::class)
    ->afterAll(fn() => error_reporting(error_reporting() & ~E_DEPRECATED))
    ->in('Unit', 'Feature');
