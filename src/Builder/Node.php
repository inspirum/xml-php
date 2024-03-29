<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMNode;
use Inspirum\Arrayable\Model;
use Inspirum\XML\Formatter\Config;

/**
 * @extends \Inspirum\Arrayable\Model<int|string,mixed>
 */
interface Node extends Model
{
    /**
     * Add element to XML node
     *
     * @param array<string,mixed> $attributes
     *
     * @throws \DOMException
     */
    public function addElement(string $name, array $attributes = [], bool $withNamespaces = true): Node;

    /**
     * Add text element
     *
     * @param array<string,mixed> $attributes
     *
     * @throws \DOMException
     */
    public function addTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false, bool $withNamespaces = true): Node;

    /**
     * Add element from \DOMNode
     *
     * @throws \DOMException
     */
    public function addElementFromNode(DOMNode $node, bool $forcedEscape = false, bool $withNamespaces = true): Node;

    /**
     * Append node to parent node.
     */
    public function append(Node $element): void;

    /**
     * Create new (unconnected) element
     *
     * @param array<string,mixed> $attributes
     *
     * @throws \DOMException
     */
    public function createElement(string $name, array $attributes = [], bool $withNamespaces = true): Node;

    /**
     * Create new (unconnected) text element
     *
     * @param array<string,mixed> $attributes
     *
     * @throws \DOMException
     */
    public function createTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false, bool $withNamespaces = true): Node;

    /**
     * Create new (unconnected) element from \DOMNode
     *
     * @throws \DOMException
     */
    public function createElementFromNode(DOMNode $node, bool $forcedEscape = false, bool $withNamespaces = true): Node;

    /**
     * Add XML data
     */
    public function addXMLData(string $content): ?Node;

    /**
     * Get node text content
     */
    public function getTextContent(): ?string;

    /**
     * Get node attributes
     *
     * @return ($autoCast is true ? array<string,mixed> : array<string,string>)
     */
    public function getAttributes(bool $autoCast = false): array;

    /**
     * @return list<\Inspirum\XML\Builder\Node>|null
     */
    public function xpath(string $expression): ?array;

    /**
     * Get connected \DOMDocument
     */
    public function getDocument(): DOMDocument;

    /**
     * Get connected \DOMNode
     */
    public function getNode(): ?DOMNode;

    /**
     * Return valid XML string.
     *
     * @throws \DOMException
     */
    public function toString(bool $formatOutput = false): string;

    /**
     * Convert to array
     *
     * @return array<int|string,mixed>
     */
    public function toArray(?Config $config = null): array;
}
