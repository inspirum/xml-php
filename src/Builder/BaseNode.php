<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMCdataSection;
use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMException;
use DOMNameSpaceNode;
use DOMNode;
use DOMText;
use DOMXPath;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Formatter\Config;
use Inspirum\XML\Formatter\DefaultConfig;
use Inspirum\XML\Formatter\Formatter;
use Inspirum\XML\Parser\Parser;
use function is_array;
use function is_string;
use function iterator_to_array;
use function str_contains;

abstract class BaseNode implements Node
{
    protected function __construct(
        private readonly DOMDocument $document,
        private readonly ?DOMNode $node,
        private readonly NamespaceRegistry $namespaceRegistry,
    ) {
    }

    protected function createNode(DOMNode $element): Node
    {
        return new DefaultNode($this->document, $element, $this->namespaceRegistry);
    }

    public function getDocument(): DOMDocument
    {
        return $this->document;
    }

    public function getNode(): ?DOMNode
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    public function addElement(string $name, array $attributes = [], bool $withNamespaces = true): Node
    {
        return $this->addTextElement($name, null, $attributes, withNamespaces: $withNamespaces);
    }

    /**
     * @inheritDoc
     */
    public function addTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false, bool $withNamespaces = true): Node
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape, $withNamespaces);

        $this->appendChild($element);

        return $this->createNode($element);
    }

    public function addElementFromNode(DOMNode $node, bool $forcedEscape = false, bool $withNamespaces = true): Node
    {
        $element = $this->createDOMNodeFromNode($node, $forcedEscape, $withNamespaces);

        $this->appendChild($element);

        return $this->createNode($element);
    }

    public function append(Node $element): void
    {
        if ($element->getNode() !== null) {
            $this->appendChild($element->getNode());
        }
    }

    /**
     * @inheritDoc
     */
    public function createElement(string $name, array $attributes = [], bool $withNamespaces = true): Node
    {
        return $this->createTextElement($name, null, $attributes, withNamespaces: $withNamespaces);
    }

    /**
     * @inheritDoc
     */
    public function createTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false, bool $withNamespaces = true): Node
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape, $withNamespaces);

        return $this->createNode($element);
    }

    public function createElementFromNode(DOMNode $node, bool $forcedEscape = false, bool $withNamespaces = true): Node
    {
        $element = $this->createDOMNodeFromNode($node, $forcedEscape, $withNamespaces);

        return $this->createNode($element);
    }

    public function addXMLData(string $content): ?Node
    {
        if ($content === '') {
            return null;
        }

        $element = $this->createDOMFragment($content);

        $this->appendChild($element);

        return $this->createNode($element);
    }

    /**
     * Create new DOM element.
     *
     * @param array<string,mixed> $attributes
     *
     * @throws \DOMException
     */
    private function createFullDOMElement(string $name, mixed $value, array $attributes, bool $forcedEscape, bool $withNamespaces): DOMElement
    {
        $this->registerNamespaces($attributes);

        $element = $this->createDOMElementNS($name, $withNamespaces);

        $this->setDOMElementValue($element, $value, $forcedEscape);

        foreach ($attributes as $attributeName => $attributeValue) {
            $this->setDOMAttributeNS($element, $attributeName, $attributeValue, $withNamespaces);
        }

        return $element;
    }

    /**
     * @throws \DOMException
     */
    private function createDOMNodeFromNode(DOMNode $node, bool $forcedEscape, bool $withNamespaces): DOMNode
    {
        $imported = $this->importDOMNode($node, $withNamespaces);

        if ($forcedEscape) {
            return $this->escapeDOMNode($imported);
        }

        return $imported;
    }

    /**
     * @throws \DOMException
     */
    private function importDOMNode(DOMNode $node, bool $withNamespaces): DOMNode
    {
        if ($withNamespaces) {
            return $this->document->importNode($node, true);
        }

        return $this->importDOMNodeWithoutNamespaces($node);
    }

    /**
     * @throws \DOMException
     */
    private function importDOMNodeWithoutNamespaces(DOMNode $node): DOMNode
    {
        if ($node instanceof DOMElement) {
            $element = $this->createFullDOMElement($node->nodeName, null, $this->getAttributesFromNode($node), false, false);

            foreach ($node->childNodes as $child) {
                $element->appendChild($this->importDOMNodeWithoutNamespaces($child));
            }

            return $element;
        }

        return $this->document->importNode($node, true);
    }

    /**
     * Replace text nodes with CDATA sections
     */
    private function escapeDOMNode(DOMNode $node): DOMNode
    {
        if ($node instanceof DOMCdataSection) {
            return $node;
        }

        if ($node instanceof DOMText) {
            return $this->document->createCDATASection($node->textContent);
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $escaped = $this->escapeDOMNode($child);

            if ($escaped !== $child) {
                $node->replaceChild($escaped, $child);
            }
        }

        return $node;
    }

    /**
     * Create new DOM fragment element
     *
     * @throws \DOMException
     */
    private function createDOMFragment(string $content): DOMDocumentFragment
    {
        return Handler::withErrorHandlerForDOMDocument(function () use ($content): DOMDocumentFragment {
            $element = $this->document->createDocumentFragment();

            $appended = $element->appendXML($content);
            if ($appended === false) {
                throw new DOMException('\DOMDocumentFragment::appendXML() method failed');
            }

            return $element;
        });
    }

    /**
     * Create new DOM element with namespace if exists
     *
     * @throws \DOMException
     */
    private function createDOMElementNS(string $name, bool $withNamespaces): DOMElement
    {
        $prefix = Parser::getNamespacePrefix($name);

        if ($withNamespaces && $prefix !== null && $this->namespaceRegistry->hasNamespace($prefix)) {
            return $this->document->createElementNS($this->namespaceRegistry->getNamespace($prefix), $name);
        }

        return $this->document->createElement($name);
    }

    /**
     * Set node value to element
     */
    private function setDOMElementValue(DOMElement $element, mixed $value, bool $forcedEscape): void
    {
        $value = Formatter::encodeValue($value);

        if ($value === '' || $value === null) {
            return;
        }

        $element->appendChild($this->createDOMValueNode($value, $forcedEscape));
    }

    /**
     * Create text node, or CDATA section if value contains entity or escape is forced
     */
    private function createDOMValueNode(string $value, bool $forcedEscape): DOMText
    {
        if ($forcedEscape || str_contains($value, '&')) {
            return $this->document->createCDATASection($value);
        }

        return $this->document->createTextNode($value);
    }

    /**
     * Create new DOM attribute with namespace if exists
     */
    private function setDOMAttributeNS(DOMElement $element, string $name, mixed $value, bool $withNamespaces): void
    {
        $prefix = Parser::getNamespacePrefix($name);
        $value = Formatter::encodeValue($value);

        if ($withNamespaces && $prefix === 'xmlns') {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', $name, (string) $value);
        } elseif ($withNamespaces && $prefix !== null && $this->namespaceRegistry->hasNamespace($prefix)) {
            $element->setAttributeNS($this->namespaceRegistry->getNamespace($prefix), $name, (string) $value);
        } elseif ($prefix !== 'xmlns') {
            $element->setAttribute($name, (string) $value);
        }
    }

    /**
     * Append child to parent node.
     */
    private function appendChild(DOMNode $element): void
    {
        $node = $this->resolveNode();
        $node->appendChild($element);
    }

    /**
     * Register xmlns namespace URLs
     *
     * @param array<string,mixed> $attributes
     */
    private function registerNamespaces(array $attributes): void
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            [$prefix, $namespaceLocalName] = Parser::parseQualifiedName($attributeName);

            if ($prefix === 'xmlns' && is_string($attributeValue)) {
                $this->namespaceRegistry->registerNamespace($namespaceLocalName, $attributeValue);
            }
        }
    }

    public function getTextContent(): ?string
    {
        $node = $this->resolveNode();

        return $node->textContent;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(bool $autoCast = false): array
    {
        $node = $this->resolveNode();

        return $this->getAttributesFromNode($node, $autoCast);
    }

    /**
     * Get attributes from \DOMNode
     *
     * @return ($autoCast is true ? array<string,mixed> : array<string,string>)
     */
    private function getAttributesFromNode(DOMNode $node, bool $autoCast = false): array
    {
        $attributes = [];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes ?? [] as $attribute) {
                $value = $attribute->nodeValue;
                $attributes[$attribute->nodeName] = $autoCast ? Formatter::decodeValue($value) : $value;
            }
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function xpath(string $expression): array
    {
        return Handler::withErrorHandlerForDOMDocument(function () use ($expression): array {
            $xpath = new DOMXPath($this->toQueryDocument());

            $nodes = $xpath->query($expression);
            if ($nodes === false) {
                // @codeCoverageIgnoreStart
                throw new DOMException('\DOMXPath::query() method failed');
                // @codeCoverageIgnoreEnd
            }

            $results = [];
            foreach ($nodes as $node) {
                if ($node instanceof DOMNameSpaceNode) {
                    continue;
                }

                $results[] = $this->createNode($this->copyNode($node));
            }

            return $results;
        });
    }

    /**
     * Copy node to (unconnected) node of the current \DOMDocument with its namespaces declared
     */
    private function copyNode(DOMNode $node): DOMNode
    {
        if ($node->ownerDocument === $this->document) {
            return $node->cloneNode(true);
        }

        return $this->document->importNode($node, true);
    }

    /**
     * Get \DOMDocument with current node as its root element
     */
    private function toQueryDocument(): DOMDocument
    {
        if ($this->node === null) {
            return $this->document;
        }

        $document = new DOMDocument($this->document->xmlVersion ?? '1.0', $this->document->encoding ?? 'UTF-8');
        $content = $this->toString();
        if ($content === '') {
            return $document;
        }

        $document->loadXML($content);

        return $document;
    }

    /**
     * Resolve current node
     */
    private function resolveNode(): DOMNode
    {
        return $this->node ?? $this->document;
    }

    public function toString(bool $formatOutput = false): string
    {
        return Handler::withErrorHandlerForDOMDocument(function () use ($formatOutput): string {
            $previousFormatOutput = $this->document->formatOutput;
            $this->document->formatOutput = $formatOutput;

            try {
                $xml = $this->document->saveXML($this->node);
            } finally {
                $this->document->formatOutput = $previousFormatOutput;
            }

            if ($xml === false) {
                throw new DOMException('\DOMDocument::saveXML() method failed');
            }

            return $xml;
        });
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toArray(?Config $config = null): array
    {
        $result = Formatter::nodeToArray($this->resolveNode(), $config ?? new DefaultConfig());

        if (is_array($result) === false) {
            $result = [$result];
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function __toArray(): array
    {
        return $this->toArray();
    }

    /**
     * Convert to array
     *
     * @return array<int|string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
