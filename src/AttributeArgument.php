<?php

namespace Brickhouse\View;

enum AttributeArgument
{
    /**
     * The attribute does not support arguments. Supplying one will cause a warning.
     */
    case NONE;

    /**
     * The attribute does supports arguments, but they are not required.
     */
    case OPTIONAL;

    /**
     * The attribute requires arguments. Not supplying one will cause an error.
     */
    case REQUIRED;
}
