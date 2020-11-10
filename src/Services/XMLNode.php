<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMDocumentFragment;
use DOMElement;
use DOMException;
use DOMNode;
use Inspirum\XML\Model\Values\Config;
use InvalidArgumentException;
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
     * @param string $name
     * @param array  $attributes
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
     * @param string $name
     * @param mixed  $value
     * @param array  $attributes
     * @param bool   $forcedEscape
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
     * @param string $name
     * @param array  $attributes
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
     * @param string $name
     * @param mixed  $value
     * @param array  $attributes
     * @param bool   $forcedEscape
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
        $this->appendChild($element->node);
    }

    /**
     * Create new DOM element.
     *
     * @param string                     $name
     * @param string|float|int|bool|null $value
     * @param array                      $attributes
     * @param bool                       $forcedEscape
     *
     * @return \DOMElement
     */
    private function createFullDOMElement(string $name, $value = null, array $attributes = [], bool $forcedEscape = false): DOMElement
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
        $prefix = $this->getNamespacePrefix($name);
        $value  = $this->normalizeValue($value);

        if (XML::hasNamespace($prefix)) {
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
        $value = $this->normalizeValue($value);

        if ($value === '' || $value === null) {
            return;
        }

        try {
            if ($value !== null && (strpos($value, '&') !== false || $forcedEscape)) {
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
        $prefix = $this->getNamespacePrefix($name);
        $value  = $this->normalizeValue($value);

        if ($prefix === 'xmlns') {
            $element->setAttributeNS('http://www.w3.org/2000/xmlns/', $name, $value);
        } elseif (XML::hasNamespace($prefix)) {
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
     * @param array $attributes
     *
     * @return void
     */
    private function registerNamespaces(array $attributes): void
    {
        foreach ($attributes as $attributeName => $attributeValue) {
            [$prefix, $namespaceLocalName] = $this->parseQualifiedName($attributeName);

            if ($prefix === 'xmlns') {
                XML::registerNamespace($namespaceLocalName, $attributeValue);
            }
        }
    }

    /**
     * Parse node name to namespace prefix and un-prefixed name
     *
     * @param string $name
     *
     * @return array
     */
    private function parseQualifiedName(string $name): array
    {
        $this->validateElementName($name);

        return array_pad(explode(':', $name, 2), -2, null);
    }

    /**
     * Get namespace prefix from node name
     *
     * @param string $name
     *
     * @return string|null
     */
    private function getNamespacePrefix(string $name): ?string
    {
        return $this->parseQualifiedName($name)[0];
    }

    /**
     * Validate element name
     *
     * @param string $value
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function validateElementName(string $value): void
    {
        $regex = '/^[a-zA-Z][a-zA-Z0-9]*(\:[a-zA-Z][a-zA-Z0-9]*)?$/';

        if (preg_match($regex, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('Element name or namespace prefix [%s] has invalid value', $value));
        }
    }

    /**
     * Normalize value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return $value;
    }

    /**
     * Return valid XML string.
     *
     * @param bool $formatOutput
     *
     * @return string
     */
    public function toString(bool $formatOutput = false): string
    {
        return $this->withErrorHandler(function () use ($formatOutput) {
            $this->document->formatOutput = $formatOutput;

            $xml = $this->document->saveXML($this->node, null);

            if ($xml === false) {
                throw new DOMException('\DOMDocument::saveXML() method failed');
            }

            return $xml;
        });
    }

    /**
     * Convert to array
     *
     * @param \Inspirum\XML\Model\Values\Config|null $options
     *
     * @return array
     */
    public function toArray(Config $options = null): array
    {
        return $this->nodeToArray($this->node ?: $this->document, $options ?: new Config());
    }

    /**
     * Convert node to array
     *
     * @param \DOMNode                          $node
     * @param \Inspirum\XML\Model\Values\Config $options
     *
     * @return array|string|null
     */
    private function nodeToArray(DOMNode $node, Config $options)
    {
        $result = [];

        if ($node->hasAttributes()) {
            /** @var \DOMAttr $attribute */
            foreach ($node->attributes as $attribute) {
                $result[$options->getAttributePrefix()][$attribute->nodeName] = $attribute->nodeValue;
            }
        }

        if ($node->hasChildNodes()) {
            $children = $node->childNodes;
            if ($children->length === 1) {
                $child = $children->item(0);
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE])) {
                    $result[$options->getTextContent()] = $child->nodeValue;
                    return count($result) === 1
                        ? $result[$options->getTextContent()]
                        : $result;
                }
            }

            /** @var \DOMNode $child */
            foreach ($children as $child) {
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE])) {
                    if (trim($child->nodeValue) === '') {
                        continue;
                    } else {
                        $result[$options->getTextContent()] = $child->nodeValue;
                    }
                }

                $castToArray = (
                    array_key_exists($child->nodeName, $result)
                    || in_array($child->nodeName, $options->getAlwaysArray())
                    || in_array($node->nodeName . '.' . $child->nodeName, $options->getAlwaysArray())
                );

                if ($castToArray) {
                    if (
                        array_key_exists($child->nodeName, $result)
                        && (
                            is_array($result[$child->nodeName]) === false
                            || array_keys($result[$child->nodeName]) !== range(0, count($result[$child->nodeName]) - 1)
                        )
                    ) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
                    } elseif (isset($result[$child->nodeName]) === false) {
                        $result[$child->nodeName] = [];
                    }
                    $result[$child->nodeName][] = $this->nodeToArray($child, $options);
                } else {
                    $result[$child->nodeName] = $this->nodeToArray($child, $options);
                }
            }
        } elseif (count($result) > 0) {
            $result[$options->getTextContent()] = null;
        }

        if (count($result) === 0) {
            return null;
        }

        return $result;
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
     * Convert to array
     *
     * @return array
     */
    public function __toArray()
    {
        return $this->toArray();
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
