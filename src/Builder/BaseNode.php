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
use Inspirum\XML\Formatter\DefaultConfig;
use Inspirum\XML\Formatter\Formatter;
use Inspirum\XML\Parser\Parser;
use Throwable;
use function is_array;
use function is_string;
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

        $element = $this->createDOMElementNS($name, null, $withNamespaces);

        $this->setDOMElementValue($element, $value, $forcedEscape);

        foreach ($attributes as $attributeName => $attributeValue) {
            $this->setDOMAttributeNS($element, $attributeName, $attributeValue, $withNamespaces);
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
     *
     * @throws \DOMException
     */
    private function createDOMElementNS(string $name, ?string $value, bool $withNamespaces): DOMElement
    {
        $prefix = Parser::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

        if ($withNamespaces && $prefix !== null && $this->namespaceRegistry->hasNamespace($prefix)) {
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
            if (str_contains($value, '&') || $forcedEscape) {
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
    private function setDOMAttributeNS(DOMElement $element, string $name, mixed $value, bool $withNamespaces): void
    {
        $prefix = Parser::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

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
        $node = $this->node ?? $this->document;
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
        $node = $this->node ?? $this->document;

        return $node->textContent;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(bool $autoCast = false): array
    {
        $node       = $this->node ?? $this->document;
        $attributes = [];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes ?? [] as $attribute) {
                $value                            = $attribute->nodeValue;
                $attributes[$attribute->nodeName] = $autoCast ? Formatter::decodeValue($value) : $value;
            }
        }

        return $attributes;
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

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toArray(?Config $config = null): array
    {
        $result = Formatter::nodeToArray($this->node ?? $this->document, $config ?? new DefaultConfig());

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
