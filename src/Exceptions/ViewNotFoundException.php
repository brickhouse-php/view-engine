<?php

namespace Brickhouse\View\Engine\Exceptions;

class ViewNotFoundException extends \RuntimeException
{
    public function __construct(
        public readonly string $view,
    ) {
        parent::__construct("Cannot read template file: {$this->view}");
    }
}
