<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMException;
use DOMNode;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Formatter\Config;
use Inspirum\XML\Formatter\Formatter;
use Throwable;
use function is_array;
use function strpos;

abstract class BaseNode implements Node
{
    protected function __construct(
        private DOMDocument $document,
        private ?DOMNode $node,
        private NamespaceRegistry $namespaceRegistry,
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
    public function addElement(string $name, array $attributes = []): Node
    {
        return $this->addTextElement($name, null, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function addTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false): Node
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape);

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
    public function createElement(string $name, array $attributes = []): Node
    {
        return $this->createTextElement($name, null, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function createTextElement(string $name, mixed $value, array $attributes = [], bool $forcedEscape = false): Node
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape);

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
     * @param array<string,string> $attributes
     */
    private function createFullDOMElement(string $name, mixed $value, array $attributes, bool $forcedEscape): DOMElement
    {
        $this->registerNamespaces($attributes);

        $element = $this->createDOMElementNS($name);

        $this->setDOMElementValue($element, $value, $forcedEscape);

        foreach ($attributes as $attributeName => $attributeValue) {
            $this->setDOMAttributeNS($element, $attributeName, $attributeValue);
        }

        return $element;
    }

    /**
     * Create new DOM fragment element
     */
    private function createDOMFragment(string $content): DOMDocumentFragment
    {
        $element = $this->document->createDocumentFragment();

        $element->appendXML($content);

        return $element;
    }

    /**
     * Create new DOM element with namespace if exists
     */
    private function createDOMElementNS(string $name, ?string $value = null): DOMElement
    {
        $prefix = Formatter::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

        if ($prefix !== null && $this->namespaceRegistry->hasNamespace($prefix)) {
            return $this->document->createElementNS($this->namespaceRegistry->getNamespace($prefix), $name, (string) $value);
        }

        return $this->document->createElement($name, (string) $value);
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

        try {
            if (strpos($value, '&') !== false || $forcedEscape) {
                throw new DOMException('DOMDocument::createElement(): unterminated entity reference');
            }

            $element->nodeValue = $value;
        } catch (Throwable) {
            $cdata = $this->document->createCDATASection($value);
            $element->appendChild($cdata);
        }
    }

    /**
     * Create new DOM attribute with namespace if exists
     *
     * @return void
     */
    private function setDOMAttributeNS(DOMElement $element, string $name, mixed $value): void
    {
        $prefix = Formatter::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

        if ($prefix === 'xmlns') {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', $name, (string) $value);
        } elseif ($prefix !== null && $this->namespaceRegistry->hasNamespace($prefix)) {
            $element->setAttributeNS($this->namespaceRegistry->getNamespace($prefix), $name, (string) $value);
        } else {
            $element->setAttribute($name, (string) $value);
        }
    }

    /**
     * Append child to parent node.
     */
    private function appendChild(DOMNode $element): void
    {
        $node = $this->node ?? $this->document;
        $node->appendChild($element);
    }

    /**
     * Register xmlns namespace URLs
     *
     * @param array<string,string> $attributes
     */
    private function registerNamespaces(array $attributes): void
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            [$prefix, $namespaceLocalName] = Formatter::parseQualifiedName($attributeName);

            if ($prefix === 'xmlns' && $namespaceLocalName !== null) {
                $this->namespaceRegistry->registerNamespace($namespaceLocalName, $attributeValue);
            }
        }
    }

    public function getTextContent(): ?string
    {
        $node = $this->node ?? $this->document;

        return $node->textContent;
    }

    public function toString(bool $formatOutput = false): string
    {
        return Handler::withErrorHandlerForDOMDocument(function () use ($formatOutput): string {
            $this->document->formatOutput = $formatOutput;

            $xml = $this->document->saveXML($this->node);
            if ($xml === false) {
                throw new DOMException('\DOMDocument::saveXML() method failed');
            }

            return $xml;
        });
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toArray(?Config $config = null): array
    {
        $result = Formatter::nodeToArray($this->node ?? $this->document, $config ?? new Config());

        if (is_array($result) === false) {
            $result = [$result];
        }

        return $result;
    }

    /**
     * Convert to array
     *
     * @return array<int|string,mixed>
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
