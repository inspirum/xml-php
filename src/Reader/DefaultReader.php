<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Exception;
use Inspirum\XML\Builder\Document;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Exception\Handler;
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

        if ($found->found === false) {
            return yield from [];
        }

        $rootNamespaces = $withNamespaces ? $found->namespaces : null;

        while ($found->found) {
            yield $this->readNode($rootNamespaces)->node;

            $found = $this->moveToNode($nodeName);
        }
    }

    public function nextNode(string $nodeName): ?Node
    {
        $found = $this->moveToNode($nodeName);

        if ($found->found === false) {
            return null;
        }

        return $this->readNode()->node;
    }

    public function close(): void
    {
        $this->reader->close();
    }

    /**
     * @throws \Exception
     */
    private function moveToNode(string $nodeName): MoveResult
    {
        $usePath  = str_starts_with($nodeName, '/');
        $paths    = explode('/', ltrim($nodeName, '/'));
        $maxDepth = count($paths) - 1;

        $namespaces = [];

        while ($this->read()) {
            if ($this->isNodeElementType()) {
                if ($usePath && $this->getNodeName() !== $paths[$this->depth]) {
                    $this->next();
                    continue;
                }

                if ($usePath && $this->depth === $maxDepth && $this->getNodeName() === $paths[$this->depth]) {
                    return MoveResult::found($namespaces);
                }

                if (!$usePath && $this->getNodeName() === $nodeName) {
                    return MoveResult::found($namespaces);
                }

                if (!$this->isNodeEmptyElementType()) {
                    $this->depth++;
                }
            }

            if ($this->isNodeElementEndType()) {
                $this->depth--;
            }

            $namespaces = array_merge($namespaces, $this->getNodeNamespaces());
        }

        return MoveResult::notFound();
    }

    /**
     * @param array<string,string>|null $rootNamespaces
     *
     * @throws \Exception
     */
    private function readNode(?array $rootNamespaces = null): ReadResult
    {
        $name       = $this->getNodeName();
        $attributes = $this->getNodeAttributes();
        $namespaces = Parser::parseNamespaces($attributes);

        if ($this->isNodeEmptyElementType()) {
            return $this->createEmptyNode($name, $attributes, $namespaces, $rootNamespaces);
        }

        /** @var list<\Inspirum\XML\Reader\ReadResult> $elements */
        $elements = [];
        $text     = null;

        while ($this->read()) {
            if ($this->isNodeElementEndType() && $this->getNodeName() === $name) {
                return $this->createNode($name, $text, $attributes, $namespaces, $rootNamespaces, $elements);
            }

            if ($this->isNodeTextType()) {
                $text = $this->getNodeValue();
            } elseif ($this->isNodeElementType()) {
                $elements[] = $this->readNode();
            }
        }

        // @codeCoverageIgnoreStart
        throw new Exception('\XMLReader::read() opening and ending tag mismatch');
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param array<string,string>      $attributes
     * @param array<string,string>      $namespaces
     * @param array<string,string>|null $rootNamespaces
     */
    private function createEmptyNode(string $name, array $attributes, array $namespaces, ?array $rootNamespaces): ReadResult
    {
        return $this->createNode($name, null, $attributes, $namespaces, $rootNamespaces, []);
    }

    /**
     * @param array<string,string>                   $attributes
     * @param array<string,string>                   $namespaces
     * @param array<string,string>|null              $rootNamespaces
     * @param list<\Inspirum\XML\Reader\ReadResult>  $elements
     *
     * @throws \DOMException
     */
    private function createNode(string $name, mixed $text, array $attributes, array $namespaces, ?array $rootNamespaces, array $elements): ReadResult
    {
        $usedNamespaces = $this->getUsedNamespaces($name, $attributes);

        $namespaces     = array_merge($namespaces, ...array_map(static fn(ReadResult $element) => $element->namespaces, $elements));
        $usedNamespaces = array_merge($usedNamespaces, ...array_map(static fn(ReadResult $element) => $element->usedNamespaces, $elements));

        $withNamespace = $rootNamespaces !== null;

        if ($withNamespace) {
            $namespaceAttributes = $this->namespacesToAttributes($namespaces, $rootNamespaces);
            $namespaceAttributes = array_filter(
                $namespaceAttributes,
                static fn($namespaceLocalName) => in_array(Parser::getLocalName($namespaceLocalName), $usedNamespaces),
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
     * @param string               $name
     * @param array<string,string> $attributes
     *
     * @return list<string>
     */
    private function getUsedNamespaces(string $name, array $attributes): array
    {
        return array_values(array_filter([
            Parser::getNamespacePrefix($name),
            ...array_map(static fn($attributeName) => Parser::getNamespacePrefix($attributeName), array_keys($attributes)),
        ], static fn($ns) => $ns !== null && $ns !== 'xmlns'));
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
     * @throws \Exception
     */
    private function read(): bool
    {
        return Handler::withErrorHandlerForXMLReader(fn(): bool => $this->reader->read());
    }

    private function next(?string $name = null): bool
    {
        return $this->reader->next($name);
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
     * @return array<string,string>
     */
    private function getNodeNamespaces(): array
    {
        return Parser::parseNamespaces($this->getNodeAttributes());
    }
}
