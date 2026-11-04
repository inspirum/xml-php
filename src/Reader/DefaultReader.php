<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\Document;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Exception\ReaderException;
use Inspirum\XML\Formatter\Formatter;
use Inspirum\XML\Parser\Parser;
use XMLReader;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function explode;
use function in_array;
use function ksort;
use function ltrim;
use function str_starts_with;
use const ARRAY_FILTER_USE_KEY;

final class DefaultReader implements Reader
{
    private int $depth = 0;

    private bool $next = false;

    /**
     * Namespaces declared on each open element by its depth
     *
     * @var array<int,array<string,string>>
     */
    private array $namespaceScopes = [];

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
        $found = $this->moveToNode($nodeName);

        while ($found->found) {
            yield $this->readNode($withNamespaces ? $found->namespaces : null)->node;

            $found = $this->moveToNode($nodeName);
        }
    }

    public function nextNode(string $nodeName, bool $withNamespaces = false): ?Node
    {
        $found = $this->moveToNode($nodeName);

        if ($found->found === false) {
            return null;
        }

        return $this->readNode($withNamespaces ? $found->namespaces : null)->node;
    }

    public function close(): void
    {
        $this->reader->close();
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    private function moveToNode(string $nodeName): MoveResult
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
                    return MoveResult::found($this->getScopeNamespaces());
                }

                if (!$usePath && $this->getNodeName() === $nodeName) {
                    return MoveResult::found($this->getScopeNamespaces());
                }

                if (!$this->isNodeEmptyElementType()) {
                    $this->namespaceScopes[$this->depth] = $this->getNodeNamespaces();
                    $this->depth++;
                }
            }

            if ($this->isNodeElementEndType()) {
                $this->depth--;
                unset($this->namespaceScopes[$this->depth]);
            }
        }

        return MoveResult::notFound();
    }

    /**
     * @param array<string,string>|null $rootNamespaces
     *
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    private function readNode(?array $rootNamespaces = null): ReadResult
    {
        $name = $this->getNodeName();
        $attributes = $this->getNodeAttributes();
        $namespaces = Parser::parseNamespaces($attributes);

        if ($this->isNodeEmptyElementType()) {
            return $this->createEmptyNode($name, $attributes, $namespaces, $rootNamespaces);
        }

        /** @var list<\Inspirum\XML\Reader\ReadResult> $elements */
        $elements = [];
        $text = null;

        while ($this->read()) {
            if ($this->isNodeElementEndType() && $this->getNodeName() === $name) {
                return $this->createNode($name, $text, $attributes, $namespaces, $rootNamespaces, $elements);
            }

            if ($this->isNodeTextType()) {
                $text = ($text ?? '') . $this->getNodeValue();
            } elseif ($this->isNodeElementType()) {
                $elements[] = $this->readNode();
            }
        }

        throw new ReaderException('\XMLReader::read() opening and ending tag mismatch');
    }

    /**
     * @param array<string,string> $attributes
     * @param array<string,string> $namespaces
     * @param array<string,string>|null $rootNamespaces
     */
    private function createEmptyNode(string $name, array $attributes, array $namespaces, ?array $rootNamespaces): ReadResult
    {
        return $this->createNode($name, null, $attributes, $namespaces, $rootNamespaces, []);
    }

    /**
     * @param array<string,string> $attributes
     * @param array<string,string> $namespaces
     * @param array<string,string>|null $rootNamespaces
     * @param list<\Inspirum\XML\Reader\ReadResult> $elements
     *
     * @throws \DOMException
     */
    private function createNode(string $name, mixed $text, array $attributes, array $namespaces, ?array $rootNamespaces, array $elements): ReadResult
    {
        $usedNamespaces = $this->getUsedNamespaces($name, $attributes);

        $namespaces = array_merge($namespaces, ...array_map(static fn (ReadResult $element) => $element->namespaces, $elements));
        $usedNamespaces = array_merge($usedNamespaces, ...array_map(static fn (ReadResult $element) => $element->usedNamespaces, $elements));

        $withNamespace = $rootNamespaces !== null;

        if ($withNamespace) {
            $namespaceAttributes = $this->namespacesToAttributes($namespaces, $rootNamespaces);
            $namespaceAttributes = array_filter(
                $namespaceAttributes,
                static fn ($namespaceLocalName) => in_array(Parser::getLocalName($namespaceLocalName), $usedNamespaces),
                ARRAY_FILTER_USE_KEY,
            );

            $attributes = array_merge($namespaceAttributes, $attributes);
        }

        $node = $this->document->createTextElement($name, $text, $attributes, withNamespaces: $withNamespace);

        foreach ($elements as $element) {
            $node->append($element->node);
        }

        return ReadResult::create($node, $namespaces, $usedNamespaces);
    }

    /**
     * @param array<string,string> $attributes
     *
     * @return list<string>
     */
    private function getUsedNamespaces(string $name, array $attributes): array
    {
        return array_values(array_filter([
            Parser::getNamespacePrefix($name),
            ...array_map(static fn ($attributeName) => Parser::getNamespacePrefix($attributeName), array_keys($attributes)),
        ], static fn ($ns) => $ns !== null && $ns !== 'xmlns'));
    }

    /**
     * @param array<string,string> $namespaces
     * @param array<string,string> $rootNamespaces
     *
     * @return array<string,string>
     */
    private function namespacesToAttributes(array $namespaces, array $rootNamespaces): array
    {
        $mergedNamespaces = Formatter::namespacesToAttributes(array_merge($namespaces, $rootNamespaces));
        ksort($mergedNamespaces);

        return $mergedNamespaces;
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    private function read(): bool
    {
        if ($this->next) {
            $this->next = false;

            return $this->reader->next();
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

    /**
     * Get namespaces in scope of current node, inner declarations override outer ones
     *
     * @return array<string,string>
     */
    private function getScopeNamespaces(): array
    {
        return array_merge(...$this->namespaceScopes);
    }

    /**
     * @return array<string,string>
     */
    private function getNodeNamespaces(): array
    {
        return Parser::parseNamespaces($this->getNodeAttributes());
    }
}
