<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use DOMElement;
use DOMNode;
use Inspirum\XML\Builder\Document;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Exception\Handler;
use XMLReader;
use function count;
use function explode;
use function ksort;
use function ltrim;
use function sprintf;
use function str_starts_with;

final class DefaultReader implements Reader
{
    private const XMLNS_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    private int $depth = 0;

    private bool $next = false;

    public function __construct(
        private readonly XMLReader $reader,
        private readonly Document $document,
    ) {
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function iterateNode(string $nodeName, bool $withNamespaces = false): iterable
    {
        while ($this->moveToNode($nodeName)) {
            yield $this->readNode($withNamespaces);
        }
    }

    public function nextNode(string $nodeName, bool $withNamespaces = false): ?Node
    {
        if ($this->moveToNode($nodeName) === false) {
            return null;
        }

        return $this->readNode($withNamespaces);
    }

    public function close(): void
    {
        $this->reader->close();
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    private function moveToNode(string $nodeName): bool
    {
        $usePath = str_starts_with($nodeName, '/');
        $paths = explode('/', ltrim($nodeName, '/'));
        $maxDepth = count($paths) - 1;

        while ($this->read()) {
            if ($this->isNodeElementType()) {
                if ($usePath && $this->getNodeName() !== ($paths[$this->depth] ?? null)) {
                    $this->next();
                    continue;
                }

                if ($usePath && $this->depth === $maxDepth) {
                    return true;
                }

                if (!$usePath && $this->getNodeName() === $nodeName) {
                    return true;
                }

                if (!$this->isNodeEmptyElementType()) {
                    $this->depth++;
                }
            }

            if ($this->isNodeElementEndType()) {
                $this->depth--;
            }
        }

        return false;
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     * @throws \DOMException
     */
    private function readNode(bool $withNamespaces): Node
    {
        $node = Handler::withErrorHandlerForXMLReader(
            fn (): DOMNode => Handler::checkResultForXMLReader($this->reader->expand(), '\XMLReader::expand()'),
        );

        $this->next();

        if ($withNamespaces && $node instanceof DOMElement) {
            return $this->document->createElementFromNode($this->importWithNamespaces($node));
        }

        return $this->document->createElementFromNode($node, withNamespaces: false);
    }

    /**
     * Copy element with prefixed namespaces declared on its root, unprefixed elements stay without namespace
     *
     * @throws \DOMException
     */
    private function importWithNamespaces(DOMElement $source): DOMElement
    {
        $element = $this->createDOMElement($source);

        $namespaces = $this->getUsedNamespaces($source);
        ksort($namespaces);

        foreach ($namespaces as $prefix => $namespaceURI) {
            $namespaceURI = $source->lookupNamespaceURI($prefix) ?? $namespaceURI;
            $element->setAttributeNS(self::XMLNS_NAMESPACE, sprintf('xmlns:%s', $prefix), $namespaceURI);
        }

        return $this->importWithoutDefaultNamespace($source, $element);
    }

    /**
     * Get first binding of each namespace prefix used by element or its descendants
     *
     * @param array<string,string> $namespaces
     *
     * @return array<string,string>
     */
    private function getUsedNamespaces(DOMElement $element, array $namespaces = []): array
    {
        foreach ([$element, ...$element->attributes] as $node) {
            if ($node->prefix !== '' && $node->prefix !== 'xml') {
                $namespaces[$node->prefix] ??= (string) $node->namespaceURI;
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $namespaces = $this->getUsedNamespaces($child, $namespaces);
            }
        }

        return $namespaces;
    }

    /**
     * Copy attributes and children of source element, unprefixed elements stay without namespace
     *
     * @throws \DOMException
     */
    private function importWithoutDefaultNamespace(DOMElement $source, DOMElement $element): DOMElement
    {
        foreach ($source->attributes as $attribute) {
            if ($attribute->prefix !== '') {
                $element->setAttributeNS((string) $attribute->namespaceURI, $attribute->nodeName, $attribute->value);
            } else {
                $element->setAttribute($attribute->nodeName, $attribute->value);
            }
        }

        foreach ($source->childNodes as $sourceChild) {
            if ($sourceChild instanceof DOMElement) {
                $child = $this->createDOMElement($sourceChild);
                $element->appendChild($child);
                $this->importWithoutDefaultNamespace($sourceChild, $child);
            } else {
                $element->appendChild($this->document->getDocument()->importNode($sourceChild));
            }
        }

        return $element;
    }

    /**
     * @throws \DOMException
     */
    private function createDOMElement(DOMElement $source): DOMElement
    {
        $document = $this->document->getDocument();

        if ($source->prefix !== '') {
            return $document->createElementNS((string) $source->namespaceURI, $source->nodeName);
        }

        return $document->createElement($source->nodeName);
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    private function read(): bool
    {
        if ($this->next) {
            $this->next = false;

            return Handler::withErrorHandlerForXMLReader(fn (): bool => $this->reader->next());
        }

        return Handler::withErrorHandlerForXMLReader(fn (): bool => $this->reader->read());
    }

    private function next(): void
    {
        $this->next = true;
    }

    private function getNodeName(): string
    {
        return $this->reader->name;
    }

    private function isNodeElementType(): bool
    {
        return $this->reader->nodeType === XMLReader::ELEMENT;
    }

    private function isNodeEmptyElementType(): bool
    {
        return $this->reader->isEmptyElement;
    }

    private function isNodeElementEndType(): bool
    {
        return $this->reader->nodeType === XMLReader::END_ELEMENT;
    }
}
