<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\Document;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Formatter\Formatter;
use XMLReader;

final class DefaultReader implements Reader
{
    public function __construct(
        private XMLReader $reader,
        private Document $document,
    ) {
    }

    public function __destruct()
    {
        $this->reader->close();
    }

    /**
     * @inheritDoc
     */
    public function iterateNode(string $nodeName): iterable
    {
        $found = $this->moveToNode($nodeName);

        if ($found === false) {
            return yield from [];
        }

        do {
            $item = $this->readNode();

            if ($item !== null) {
                yield $item;
            }
        } while ($this->moveToNextNode($nodeName));
    }

    public function nextNode(string $nodeName): ?Node
    {
        $found = $this->moveToNode($nodeName);

        if ($found === false) {
            return null;
        }

        return $this->readNode();
    }

    private function moveToNode(string $nodeName): bool
    {
        while ($this->read()) {
            if ($this->isNodeElementType() && $this->getNodeName() === $nodeName) {
                return true;
            }
        }

        return false;
    }

    private function moveToNextNode(string $nodeName): bool
    {
        $localName = Formatter::getLocalName($nodeName);

        while ($this->reader->next($localName)) {
            if ($this->getNodeName() === $nodeName) {
                return true;
            }
        }

        return false;
    }

    private function readNode(): ?Node
    {
        $nodeName   = $this->getNodeName();
        $attributes = $this->getNodeAttributes();

        if ($this->isNodeEmptyElementType()) {
            return $this->document->createElement($nodeName, $attributes);
        }

        $node     = null;
        $text     = null;
        $elements = [];

        while ($this->read()) {
            if ($this->isNodeElementEndType() && $this->getNodeName() === $nodeName) {
                $node = $this->document->createTextElement($nodeName, $text, $attributes);

                foreach ($elements as $element) {
                    $node->append($element);
                }

                break;
            }

            if ($this->isNodeTextType()) {
                $text = $this->getNodeValue();
            } elseif ($this->isNodeElementType()) {
                if ($this->isNodeEmptyElementType()) {
                    $elements[] = $this->document->createElement($this->getNodeName());
                    continue;
                }

                $element = $this->readNode();
                if ($element !== null) {
                    $elements[] = $element;
                }
            }
        }

        return $node;
    }

    private function read(): bool
    {
        return Handler::withErrorHandlerForXMLReader(fn(): bool => $this->reader->read());
    }

    private function getNodeName(): string
    {
        return $this->reader->name;
    }

    private function getNodeType(): int
    {
        return $this->reader->nodeType;
    }

    private function getNodeValue(): string
    {
        return $this->reader->value;
    }

    private function isNodeElementType(): bool
    {
        return $this->isNodeType(XMLReader::ELEMENT);
    }

    private function isNodeEmptyElementType(): bool
    {
        return $this->reader->isEmptyElement;
    }

    private function isNodeElementEndType(): bool
    {
        return $this->isNodeType(XMLReader::END_ELEMENT);
    }

    private function isNodeTextType(): bool
    {
        return $this->isNodeType(XMLReader::TEXT) || $this->isNodeType(XMLReader::CDATA);
    }

    private function isNodeType(int $type): bool
    {
        return $this->getNodeType() === $type;
    }

    /**
     * @return array<string,string>
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
}
