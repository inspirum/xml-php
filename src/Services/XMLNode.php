<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
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
     * XMLNode constructor.
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
     * Add element to XML node.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function addElement(string $name, array $attributes = []): XMLNode
    {
        return $this->addTextElement($name, null, $attributes);
    }

    /**
     * Add text element.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $attributes
     * @param bool   $forcedEscape
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function addTextElement(string $name, $value = null, array $attributes = [], bool $forcedEscape = false): XMLNode
    {
        // create element with given value
        $element = $this->createDOMElement($name, $value, $attributes, $forcedEscape);

        // attach to document
        $this->appendChild($element);

        // return new node
        return new self($this->document, $element);
    }

    /**
     * Create new (unconnected) element.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function createElement(string $name, array $attributes = []): XMLNode
    {
        return $this->createTextElement($name, null, $attributes);
    }

    /**
     * Create new (unconnected) text element.
     *
     * @param string $name
     * @param mixed  $value
     * @param array  $attributes
     * @param bool   $forcedEscape
     *
     * @return \Inspirum\XML\Services\XMLNode
     */
    public function createTextElement(string $name, $value = null, array $attributes = [], bool $forcedEscape = false): XMLNode
    {
        // create element with given value
        $element = $this->createDOMElement($name, $value, $attributes, $forcedEscape);

        // return new node
        return new self($this->document, $element);
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
    private function createDOMElement(string $name, $value = null, array $attributes = [], bool $forcedEscape = false): DOMElement
    {
        $value = $this->normalizeValue($value);

        // create element with given value
        try {
            // escape values with "&", or with forced escaping flag
            if ($value !== null && (strpos($value, '&') !== false || $forcedEscape)) {
                throw new DOMException('DOMDocument::createElement(): unterminated entity reference');
            }
            // create element with given value
            $element = $this->document->createElement($name, $value);
        } catch (Throwable $exception) {
            // encapsulate with CDATA
            $element = $this->document->createElement($name);
            $cdata   = $this->document->createCDATASection($value);
            $element->appendChild($cdata);
        }

        // set attributes
        foreach ($attributes as $attributeName => $attributeValue) {
            $element->setAttribute($attributeName, $this->normalizeValue($attributeValue));
        }

        return $element;
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
     * Convert to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
