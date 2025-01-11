<?php

namespace Brickhouse\View\Engine\Attributes;

use Brickhouse\View\Engine\Attribute;
use Brickhouse\View\Engine\AttributeArgument;
use Brickhouse\View\Engine\CompilerContext;

class LoopAttributes implements Attribute
{
    /**
     * @inheritDoc
     */
    public function attributes(): array
    {
        return [
            ':for' => AttributeArgument::REQUIRED,
            ':foreach' => AttributeArgument::REQUIRED,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CompilerContext $context, string $attribute, null|string $value): string
    {
        // @phpstan-ignore match.unhandled
        [$prologue, $epilogue] = match ($attribute) {
            ':for' => [
                "<?php for({$value}): ?>",
                "<?php endfor; ?>",
            ],
            ':foreach' => [
                "<?php foreach({$value}): ?>",
                "<?php endforeach; ?>",
            ],
        };

        return implode([
            $prologue,
            self::BODY_MARKER,
            $epilogue
        ]);
    }
}
