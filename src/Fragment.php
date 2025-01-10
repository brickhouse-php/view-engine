<?php

namespace Brickhouse\View;

final class Fragment
{
    /**
     * Gets the alias of the fragment.
     *
     * @var string
     */
    public readonly string $alias;

    /**
     * Gets the attributes of the fragment.
     *
     * @var array<string,string>
     */
    public protected(set) array $attributes = [];

    /**
     * Gets the slots of the fragment.
     *
     * @var array<string,string>
     */
    public protected(set) array $slots = [];

    public function __construct(string $alias, array $attributes)
    {
        $this->alias = $alias;
        $this->attributes = $attributes;
    }

    /**
     * Assigns the given content to the slot of the givne name onto the fragment.
     *
     * @param string $slot      Name of the slot.
     * @param string $content   Content to assign to the slot.
     *
     * @return void
     */
    public function assignSlotContent(string $slot, string $content): void
    {
        $this->slots[$slot] = $content;
    }
}
