<?php

namespace Inspirum\XML\Services;

use Exception;
use XMLReader as BaseXMLReader;

class XMLReader
{
    /**
     * XML Reader
     *
     * @var \XMLReader
     */
    private $reader;

    /**
     * XML document
     *
     * @var \Inspirum\XML\Services\XML
     */
    private $xml;

    /**
     * XMLReader constructor
     *
     * @param string $filepath
     */
    public function __construct(string $filepath)
    {
        $this->reader = $this->open($filepath);
        $this->xml    = new XML();
    }

    /**
     * XMLReader destructor
     */
    public function __destruct()
    {
        $this->reader->close();
    }

    /**
     * Open file
     *
     * @param string $filepath
     *
     * @return \XMLReader
     *
     * @throws \Exception
     */
    private function open(string $filepath): BaseXMLReader
    {
        $xmlReader = new BaseXMLReader();

        $opened = $this->withErrorHandler(function () use ($xmlReader, $filepath) {
            return $xmlReader->open($filepath);
        });

        if ($opened == false) {
            throw new Exception('\XMLReader::open() method failed');
        }

        return $xmlReader;
    }

    /**
     * Parse file and yield next node
     *
     * @param string $nodeName
     *
     * @return \Generator|\Inspirum\XML\Services\XMLNode[]
     */
    public function iterateNode(string $nodeName): iterable
    {
        // find first element with name $element
        $found = $this->moveToNode($nodeName);

        if ($found === false) {
            return yield from [];
        }

        do {
            // read new element
            $item = $this->readNode();

            // yield element data
            if ($item !== null) {
                yield $item;
            }
        } while ($this->moveToNextNode($nodeName));
    }

    /**
     * Get next node
     *
     * @param string $nodeName
     *
     * @return \Inspirum\XML\Services\XMLNode|null
     */
    public function nextNode(string $nodeName): ?XMLNode
    {
        $found = $this->moveToNode($nodeName);

        if ($found === false) {
            return null;
        }

        return $this->readNode();
    }

    /**
     * Move to first element by tag name
     *
     * @param string $nodeName
     *
     * @return bool
     */
    private function moveToNode(string $nodeName): bool
    {
        while ($this->reader->read()) {
            if ($this->isNodeElementType() && $this->getNodeName() === $nodeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Move to next sibling element by tag name
     *
     * @param string $element
     *
     * @return bool
     */
    private function moveToNextNode(string $element): bool
    {
        return $this->reader->next($this->getLocalName($element));
    }

    /**
     * Return associative array of element by name
     *
     * @param array $alwayArray
     *
     * @return \Inspirum\XML\Services\XMLNode|null
     */
    private function readNode(): ?XMLNode
    {
        $nodeName = $this->getNodeName();

        $text     = null;
        $elements = [];

        $attributes = $this->getNodeAttributes();

        if ($this->isNodeEmptyElementType()) {
            return $this->xml->createElement($nodeName, $attributes);
        }

        while ($this->reader->read()) {
            if ($this->isNodeElementEndType() && $this->getNodeName() == $nodeName) {
                if (count($elements) > 0) {
                    $node = $this->xml->createElement($nodeName, $attributes);
                    foreach ($elements as $element) {
                        $node->append($element);
                    }
                    return $node;
                }

                return $this->xml->createTextElement($nodeName, $text, $attributes);
            } elseif ($this->isNodeTextType()) {
                $text = $this->getNodeValue();
            } elseif ($this->isNodeElementType()) {
                if ($this->isNodeEmptyElementType()) {
                    $elements[$this->getNodeName()] = [$this->xml->createElement($this->getNodeName())];
                    continue;
                }

                $element = $this->readNode();

                if ($element instanceof XMLNode) {
                    $elements[] = $element;
                }
            }
        }

        return null;
    }

    /**
     * Get local name from qualified name
     *
     * @param string $name
     *
     * @return string
     */
    private function getLocalName(string $name): string
    {
        $i = strpos($name, ':');
        
        if ($i !== false) {
            return substr($name, $i + 1);
        }

        return $name;
    }

    /**
     * Get current node name
     *
     * @return string
     */
    private function getNodeName(): string
    {
        return $this->reader->name;
    }

    /**
     * Get current node type
     *
     * @return int
     */
    private function getNodeType(): int
    {
        return $this->reader->nodeType;
    }

    /**
     * Get current node value
     *
     * @return string
     */
    private function getNodeValue(): string
    {
        return $this->reader->value;
    }

    /**
     * If current node is element open tag
     *
     * @return bool
     */
    private function isNodeElementType(): bool
    {
        return $this->isNodeType(BaseXMLReader::ELEMENT);
    }

    /**
     * If current node is element open tag
     *
     * @return bool
     */
    private function isNodeEmptyElementType(): bool
    {
        return $this->reader->isEmptyElement;
    }

    /**
     * If current node is element close tag
     *
     * @return bool
     */
    private function isNodeElementEndType(): bool
    {
        return $this->isNodeType(BaseXMLReader::END_ELEMENT);
    }

    /**
     * If current node is text content
     *
     * @return bool
     */
    private function isNodeTextType(): bool
    {
        return $this->isNodeType(BaseXMLReader::TEXT) || $this->isNodeType(BaseXMLReader::CDATA);
    }

    /**
     * If current node is given node type
     *
     * @param int $type
     *
     * @return bool
     */
    private function isNodeType(int $type): bool
    {
        return $this->getNodeType() === $type;
    }

    /**
     * Get current node attributes
     *
     * @return array
     */
    private function getNodeAttributes(): array
    {
        $attributes = [];

        if ($this->reader->hasAttributes) {
            while ($this->reader->moveToNextAttribute()) {
                $attributes[$this->getNodeName()] = $this->getNodeValue();
            }
            $this->reader->moveToElement();
        }

        return $attributes;
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
            if (strpos($message, 'XMLReader::') !== false) {
                throw new Exception($message, $code);
            }
        });

        $response = $callback();

        restore_error_handler();

        return $response;
    }
}
