<?php

namespace Brickhouse\View\Engine;

class Parser
{
    /**
     * Parses the given HTML template and compiles them into a node tree.
     *
     * @param string    $template   HTML template to compile into a node tree.
     *
     * @return array<int,Node>
     */
    public function parse(string $template): array
    {
        if (trim($template) === '') {
            throw new \RuntimeException("Attempted to parse empty template.");
        }

        // The new HTML 5 parser doesn't handle self-closing elements very well,
        // as it just parses subsequent siblings as children instead.
        // This simply converts all self-closing HTML tags to explicitly-closing.
        $template = preg_replace_callback(
            "/<(?<element>.*?)\/>/",
            function (array $match) {
                $closingTag = $match['element'];
                if (($pos = strpos($closingTag, ' ')) !== false) {
                    $closingTag = substr($closingTag, 0, $pos);
                }

                return sprintf(
                    '<%s></%s>',
                    $match['element'],
                    $closingTag,
                );
            },
            $template
        );

        // The parser doesn't handle HTML documents with the 'DOCTYPE' gracefully, so we'll remove it.
        if (str_starts_with(trim($template), "<!DOCTYPE")) {
            $template = preg_replace("/<!DOCTYPE.*?\>/", "", $template, limit: 1);

            $root = \Dom\HTMLDocument::createFromString(
                $template,
                LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS
            );
        } else {
            $dom = \Dom\HTMLDocument::createFromString(
                "<div id='brickhouse-app'>{$template}</div>",
                LIBXML_NOERROR | \Dom\HTML_NO_DEFAULT_NS
            );

            $root = $dom->getElementById('brickhouse-app');
        }

        $children = iterator_to_array($root->childNodes->getIterator());

        return $this->parseNodes($children);
    }

    /**
     * Parses the given DOM nodes and compiles them into an array of node trees.
     *
     * @param array<int,\Dom\Node>      $nodes  DOM node to parse.
     *
     * @return array<int,Node>
     */
    protected function parseNodes(array $nodes): array
    {
        $nodes = array_map(
            fn(\Dom\Node $node) => $this->parseNode($node),
            $nodes
        );

        return array_values(array_filter($nodes));
    }

    /**
     * Parses the given DOM node and compiles it into a node tree.
     *
     * @param \Dom\Node     $node   DOM node to parse.
     *
     * @return Node
     */
    protected function parseNode(\Dom\Node $node): string|Node
    {
        // @codeCoverageIgnoreStart
        if ($node instanceof \Dom\Text) {
            if (trim($node->textContent) === '') {
                return '';
            }

            return $node->textContent;
        }
        // @codeCoverageIgnoreEnd

        if (!$node instanceof \Dom\Element) {
            return "";
        }

        $type = $node->tagName;
        $attributes = [];

        /** @var \Dom\Attr $attribute */
        foreach ($node->attributes as $attribute) {
            $value = $attribute->value;
            if (trim($value) === '') {
                $value = null;
            }

            $attributes[$attribute->name] = $value;
        }

        $children = $this->parseChildContent($node);

        return new Node($type, $attributes, $children);
    }

    /**
     * Parses the given DOM node and compiles it into a node tree.
     *
     * @param \Dom\Node $node   DOM node to parse.
     *
     * @return array<int,Node>|string
     */
    protected function parseChildContent(\Dom\Node $node): array|string
    {
        // @codeCoverageIgnoreStart
        if ($node instanceof \Dom\Text) {
            if (trim($node->textContent) === '') {
                return [];
            }

            return $node->textContent;
        }

        if ($node instanceof \Dom\Comment) {
            return [];
        }
        // @codeCoverageIgnoreEnd

        return $this->parseNodes(iterator_to_array($node->childNodes->getIterator()));
    }
}
