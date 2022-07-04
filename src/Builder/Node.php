<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMNode;
use Inspirum\Arrayable\Arrayable;
use Inspirum\XML\Formatter\Config;
use JsonSerializable;
use Stringable;

/**
 * @extends \Inspirum\Arrayable\Arrayable<int|string,mixed>
 */
interface Node extends Arrayable, Stringable, JsonSerializable
{
    /**
     * Add element to XML node
     *
     * @param array<string,mixed> $attributes
     */
    public function addElement(string $name, array $attributes = []): Node;

    /**
     * Add text element
     *
     * @param array<string,mixed> $attributes
     */
    public function addTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false): Node;

    /**
     * Append node to parent node.
     */
    public function append(Node $element): void;

    /**
     * Create new (unconnected) element
     *
     * @param array<string,mixed> $attributes
     */
    public function createElement(string $name, array $attributes = []): Node;

    /**
     * Create new (unconnected) text element
     *
     * @param array<string,mixed> $attributes
     */
    public function createTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false): Node;

    /**
     * Add XML data
     *
     * @param string $content
     */
    public function addXMLData(string $content): ?Node;

    /**
     * Get node text content
     */
    public function getTextContent(): ?string;

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
