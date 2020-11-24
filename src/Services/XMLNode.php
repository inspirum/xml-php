<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMException;
use DOMNode;
use Inspirum\XML\Model\Values\Config;
use Throwable;

class XMLNode
{
    /**
     * DOM Document
     *
     * @var \DOMDocument
     */
    protected $document;

    /**
     * DOM Node
     *
     * @var \DOMNode|null
     */
    private $node;

    /**
     * XMLNode constructor
     *
     * @param \DOMDocument  $document
     * @param \DOMNode|null $element
     */
    protected function __construct(DOMDocument $document, ?DOMNode $element)
    {
        $this->document = $document;
        $this->node     = $element;
    }

    /**
     * Add element to XML node
     *
     * @param string               $name
     * @param array<string,string> $attributes
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function addElement(string $name, array $attributes = []): XMLNode
    {
        return $this->addTextElement($name, null, $attributes, false);
    }

    /**
     * Add text element
     *
     * @param string               $name
     * @param mixed                $value
     * @param array<string,string> $attributes
     * @param bool                 $forcedEscape
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function addTextElement(string $name, $value, array $attributes = [], bool $forcedEscape = false): XMLNode
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape);

        $this->appendChild($element);

        return new self($this->document, $element);
    }

    /**
     * Add XML data
     *
     * @param string $content
     *
     * @return \Inspirum\XML\Services\XMLNode|null
     */
    public function addXMLData(string $content): ?XMLNode
    {
        if ($content === '') {
            return null;
        }

        $element = $this->createDOMFragment($content);

        $this->appendChild($element);

        return new self($this->document, $element);
    }

    /**
     * Create new (unconnected) element
     *
     * @param string               $name
     * @param array<string,string> $attributes
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function createElement(string $name, array $attributes = []): XMLNode
    {
        return $this->createTextElement($name, null, $attributes, false);
    }

    /**
     * Create new (unconnected) text element
     *
     * @param string               $name
     * @param mixed                $value
     * @param array<string,string> $attributes
     * @param bool                 $forcedEscape
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function createTextElement(string $name, $value, array $attributes = [], bool $forcedEscape = false): XMLNode
    {
        $element = $this->createFullDOMElement($name, $value, $attributes, $forcedEscape);

        return new self($this->document, $element);
    }

    /**
     * Append node to parent node.
     *
     * @param \Inspirum\XML\Services\XMLNode $element
     *
     * @return void
     */
    public function append(XMLNode $element): void
    {
        if ($element->node !== null) {
            $this->appendChild($element->node);
        }
    }

    /**
     * Create new DOM element.
     *
     * @param string                     $name
     * @param string|float|int|bool|null $value
     * @param array<string,string>       $attributes
     * @param bool                       $forcedEscape
     *
     * @return \DOMElement
     */
    private function createFullDOMElement(
        string $name,
        $value = null,
        array $attributes = [],
        bool $forcedEscape = false
    ): DOMElement {
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
     *
     * @param string $content
     *
     * @return \DOMDocumentFragment
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
     * @param string      $name
     * @param string|null $value
     *
     * @return \DOMElement
     */
    private function createDOMElementNS(string $name, $value = null): DOMElement
    {
        $prefix = Formatter::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

        if ($prefix !== null && XML::hasNamespace($prefix)) {
            return $this->document->createElementNS(XML::getNamespace($prefix), $name, $value);
        } else {
            return $this->document->createElement($name, $value);
        }
    }

    /**
     * Set node value to element
     *
     * @param \DOMElement $element
     * @param mixed       $value
     * @param bool        $forcedEscape
     *
     * @return void
     */
    private function setDOMElementValue(DOMElement $element, $value, bool $forcedEscape = false): void
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
        } catch (Throwable $exception) {
            $cdata = $this->document->createCDATASection($value);
            $element->appendChild($cdata);
        }
    }

    /**
     * Create new DOM attribute with namespace if exists
     *
     * @param \DOMElement      $element
     * @param string           $name
     * @param string|float|int $value
     *
     * @return void
     */
    private function setDOMAttributeNS(DOMElement $element, string $name, $value): void
    {
        $prefix = Formatter::getNamespacePrefix($name);
        $value  = Formatter::encodeValue($value);

        if ($prefix === 'xmlns') {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', $name, $value);
        } elseif ($prefix !== null && XML::hasNamespace($prefix)) {
            $element->setAttributeNS(XML::getNamespace($prefix), $name, $value);
        } else {
            $element->setAttribute($name, $value);
        }
    }

    /**
     * Append child to parent node.
     *
     * @param \DOMNode $element
     *
     * @return void
     */
    private function appendChild(DOMNode $element): void
    {
        $parentNode = $this->node ?: $this->document;
        $parentNode->appendChild($element);
    }

    /**
     * Register xmlns namespace URLs
     *
     * @param array<string,string> $attributes
     *
     * @return void
     */
    private function registerNamespaces(array $attributes): void
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            [$prefix, $namespaceLocalName] = Formatter::parseQualifiedName($attributeName);

            if ($prefix === 'xmlns') {
                XML::registerNamespace($namespaceLocalName, $attributeValue);
            }
        }
    }

    /**
     * Return valid XML string.
     *
     * @param bool $formatOutput
     *
     * @return string
     *
     * @throws \DOMException
     */
    public function toString(bool $formatOutput = false): string
    {
        return $this->withErrorHandler(function () use ($formatOutput) {
            $this->document->formatOutput = $formatOutput;

            $xml = $this->node !== null
                ? $this->document->saveXML($this->node)
                : $this->document->saveXML();

            if ($xml === false) {
                // @codeCoverageIgnoreStart
                throw new DOMException('\DOMDocument::saveXML() method failed');
                // @codeCoverageIgnoreEnd
            }

            return $xml;
        });
    }

    /**
     * Convert to array
     *
     * @param \Inspirum\XML\Model\Values\Config|null $options
     *
     * @return array<int|string,mixed>
     */
    public function toArray(Config $options = null): array
    {
        $result = $this->nodeToArray($this->node ?: $this->document, $options ?: new Config());

        if (is_array($result) === false) {
            $result = [$result];
        }

        return $result;
    }

    /**
     * Get node text content
     *
     * @return string|null
     */
    public function getTextContent(): ?string
    {
        if ($this->node === null) {
            return null;
        }

        return $this->node->textContent;
    }

    /**
     * Convert node to array
     *
     * @param \DOMNode                          $node
     * @param \Inspirum\XML\Model\Values\Config $options
     *
     * @return array<int|string,mixed>|string|null
     */
    private function nodeToArray(DOMNode $node, Config $options)
    {
        $result = [
            $options->getAttributesName() => [],
            $options->getValueName()      => null,
            $options->getNodesName()      => [],
        ];

        /** @var \DOMAttr $attribute */
        foreach ($node->attributes as $attribute) {
            $result[$options->getAttributesName()][$attribute->nodeName] = $options->isAutoCast()
                ? Formatter::decodeValue($attribute->nodeValue)
                : $attribute->nodeValue;
        }

        /** @var \DOMNode $child */
        foreach ($node->childNodes as $child) {
            if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE])) {
                if (trim($child->nodeValue) !== '') {
                    $result[$options->getValueName()] = $options->isAutoCast()
                        ? Formatter::decodeValue($child->nodeValue)
                        : $child->nodeValue;
                }
                continue;
            }

            $result[$options->getNodesName()][$child->nodeName][] = $this->nodeToArray($child, $options);
        }

        if ($options->isFullResponse()) {
            return $result;
        }

        if (count($result[$options->getNodesName()]) === 0 && count($result[$options->getAttributesName()]) === 0) {
            return $result[$options->getValueName()];
        }

        return $this->simplifyArray($result, $options, $node);
    }

    /**
     * Remove unnecessary data
     *
     * @param array<int|string,mixed>           $result
     * @param \Inspirum\XML\Model\Values\Config $options
     * @param \DOMNode                          $node
     *
     * @return array<int|string,mixed>
     */
    private function simplifyArray(array $result, Config $options, DOMNode $node): array
    {
        $simpleResult = $result[$options->getNodesName()];
        foreach ($simpleResult as $nodeName => $values) {
            if (
                in_array($nodeName, $options->getAlwaysArray()) === false
                && in_array($node->nodeName . '.' . $nodeName, $options->getAlwaysArray()) === false
                && array_keys($values) === [0]
            ) {
                $simpleResult[$nodeName] = $values[0];
            }
        }

        if (count($result[$options->getAttributesName()]) > 0) {
            $simpleResult[$options->getAttributesName()] = $result[$options->getAttributesName()];
        }

        if ($result[$options->getValueName()] !== null) {
            $simpleResult[$options->getValueName()] = $result[$options->getValueName()];
        }

        return $simpleResult;
    }

    /**
     * Register custom error handler to throw Exception on warning message
     *
     * @param callable $callback
     *
     * @return mixed
     *
     * @throws \DOMException
     */
    protected function withErrorHandler(callable $callback)
    {
        set_error_handler(function (int $code, string $message) {
            if (strpos($message, 'DOMDocument::') !== false) {
                throw new DOMException($message, $code);
            }
        });

        $response = $callback();

        restore_error_handler();

        return $response;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
