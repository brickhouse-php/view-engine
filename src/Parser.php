<?php

namespace Brickhouse\View;

use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Node\AbstractNode;

class Parser
{
    /**
     * Parses the given HTML template and compiles them into a node tree.
     *
     * @param string    $template   HTML template to compile into a node tree.
     *
     * @return Node
     */
    public function parse(string $template): Node
    {
        if (trim($template) === '') {
            throw new \RuntimeException("Attempted to parse empty template.");
        }

        $dom = new Dom;
        $dom->setOptions(
            // this is set as the global option level.
            (new \PHPHtmlParser\Options())
                ->setWhitespaceTextNode(false)
                ->setRemoveScripts(false)
                ->setRemoveStyles(false)
                ->setRemoveSmartyScripts(false)
        );

        $dom->loadStr($template);

        /** @var \PHPHtmlParser\Dom\Node\HtmlNode $root */
        $root = $dom->root;

        if (!$root->hasChildren()) {
            throw new \RuntimeException("Root element has no children.");
        }

        if (count($root->getChildren()) !== 1) {
            throw new \RuntimeException("Template must only have one root element.");
        }

        return $this->parseNode($root->firstChild());
    }

    /**
     * Parses the given DOM nodes and compiles them into an array of node trees.
     *
     * @param array<int,AbstractNode>   $nodes  DOM node to parse.
     *
     * @return array<int,Node>
     */
    protected function parseNodes(array $nodes): array
    {
        return array_map(
            fn(AbstractNode $node) => $this->parseNode($node),
            $nodes
        );
    }

    /**
     * Parses the given DOM node and compiles it into a node tree.
     *
     * @param AbstractNode $node   DOM node to parse.
     *
     * @return Node
     */
    protected function parseNode(AbstractNode $node): Node
    {
        $type = $node->tag->name();
        $attributes = $node->getAttributes();
        $children = $this->parseChildContent($node);

        // Prevent the parser from wrapping text-nodes in a `text`-tag.
        if ($type === 'text' && is_string($children)) {
            $type = '';
        }

        return new Node($type, $attributes, $children);
    }

    /**
     * Parses the given DOM node and compiles it into a node tree.
     *
     * @param AbstractNode $node   DOM node to parse.
     *
     * @return array<int,Node>|string
     */
    protected function parseChildContent(AbstractNode $node): array|string
    {
        if ($node instanceof \PHPHtmlParser\Dom\Node\InnerNode) {
            if (strip_tags($node->innerHtml()) === $node->innerHtml()) {
                if ($node->innerHtml() === '') {
                    return [];
                }

                return $node->innerHtml();
            }

            return $this->parseNodes($node->getChildren());
        }

        if ($node instanceof \PHPHtmlParser\Dom\Node\TextNode) {
            return $node->innerHtml();
        }

        throw new \Exception("Unhandled node type: " . $node::class);
    }
}
