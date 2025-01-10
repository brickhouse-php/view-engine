<?php

namespace Brickhouse\View;

final class Node
{
    /**
     * Gets the unique key for the node.
     *
     * @var string
     */
    public readonly string $key;

    /**
     * Gets the HTML tag type for the node.
     *
     * @var string
     */
    public protected(set) string $type = "";

    /**
     * Gets any attributes passed to the node.
     *
     * @var array<string,string|null>
     */
    public protected(set) array $attributes = [];

    /**
     * Gets any children of the node.
     *
     * @var array<int,Node|string>
     */
    public protected(set) array $children = [];

    /**
     * Gets the parent node of the current node, if any.
     *
     * @var null|Node
     */
    public protected(set) ?Node $parent = null;

    /**
     * Gets the previous sibling node of the current node, if any.
     *
     * @var null|Node
     */
    public protected(set) ?Node $previousSibling = null;

    /**
     * Gets the following sibling node of the current node, if any.
     *
     * @var null|Node
     */
    public protected(set) ?Node $nextSibling = null;

    public function __construct(
        string $type,
        array $attributes = [],
        array|string $children = [],
    ) {
        $this->key = $attributes['key'] ?? self::newKey();
        $this->type = $type;

        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }

            $this->attributes[$key] = $value;
        }

        if (is_string($children)) {
            $children = [$children];
        }

        /**
         * PHPStan wouldn't allow to set the parent directly.
         *
         * @var Node $newParent
         *
         * @phpstan-ignore varTag.nativeType
         */
        $newParent = $this;

        foreach ($children as $idx => $child) {
            if ($child instanceof Node) {
                $child->parent = $newParent;
            }

            $this->children[] = $child;

            if ($child instanceof Node) {
                if ($idx > 0 && ($sibling = $children[$idx - 1]) instanceof Node) {
                    $child->previousSibling = $sibling;
                }

                if ($idx < count($children) - 1 && ($sibling = $children[$idx + 1]) instanceof Node) {
                    $child->nextSibling = $sibling;
                }
            }
        }
    }

    /**
     * Update the given attribute on the node.
     *
     * @param string        $key
     * @param string|bool   $value
     *
     * @return void
     */
    public function updateAttribute(string $key, bool|string $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Remove the given attribute from the node.
     *
     * @param string        $key
     *
     * @return void
     */
    public function removeAttribute(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Gets whether the node has any children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Adds a new child to the node.
     *
     * @param   Node|string     $child
     *
     * @return void
     */
    public function addChild(Node|string $child): void
    {
        if ($child instanceof Node) {
            $child->parent = $this;
            $child->previousSibling = null;
            $child->nextSibling = null;

            if (count($this->children) > 0) {
                $child->previousSibling = $this->children[count($this->children) - 1];
            }
        }

        $this->children[] = $child;
    }

    /**
     * Update the child at the given key.
     *
     * @param   string|int      $key        Integer index of the child node, or the unique key.
     * @param   Node|string     $newChild
     *
     * @return void
     */
    public function updateChild(string|int $key, Node|string $newChild): void
    {
        if (is_string($key)) {
            $key = array_find_key(
                $this->children,
                fn(string|Node $child) => !is_string($child) && $child->key === $key
            );

            if ($key === null) {
                return;
            }
        }

        $this->children[$key] = $newChild;
    }

    /**
     * Remove all the children from the node.
     *
     * @return void
     */
    public function removeAllChildren(): void
    {
        $this->children = [];
    }

    /**
     * Gets the direct child with the given key, if any.
     *
     * @param string $key
     *
     * @return null|Node
     */
    public function child(string $key): null|Node
    {
        return array_find(
            $this->children,
            fn(string|Node $child) => !is_string($child) && $child->key === $key
        );
    }

    /**
     * Gets a new unique, random key to use for new nodes.
     */
    public static function newKey(): string
    {
        return uniqid("cmp-", more_entropy: true);
    }
}
