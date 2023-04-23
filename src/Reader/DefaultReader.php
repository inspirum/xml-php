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
use function array_map;
use function array_merge;
use function ksort;

final class DefaultReader implements Reader
{
    public function __construct(
        private readonly XMLReader $reader,
        private readonly Document $document,
    ) {
    }

    public function __destruct()
    {
        $this->reader->close();
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

        do {
            yield $this->readNode($withNamespaces ? $found->namespaces : null)->node;
        } while ($this->moveToNextNode($nodeName));
    }

    public function nextNode(string $nodeName, bool $withNamespaces = false): ?Node
    {
        $found = $this->moveToNode($nodeName);

        if ($found->found === false) {
            return null;
        }

        return $this->readNode($withNamespaces ? $found->namespaces : null)->node;
    }

    /**
     * @throws \Exception
     */
    private function moveToNode(string $nodeName): MoveResult
    {
        $namespaces = [];

        while ($this->read()) {
            if ($this->isNodeElementType() && $this->getNodeName() === $nodeName) {
                return MoveResult::found($namespaces);
            }

            $namespaces = array_merge($namespaces, $this->getNodeNamespaces());
        }

        return MoveResult::notFound();
    }

    private function moveToNextNode(string $nodeName): bool
    {
        $localName = Parser::getLocalName($nodeName);

        while ($this->reader->next($localName)) {
            if ($this->getNodeName() === $nodeName) {
                return true;
            }
        }

        return false;
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

        /** @var array<\Inspirum\XML\Reader\ReadResult> $elements */
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
     * @param array<\Inspirum\XML\Reader\ReadResult> $elements
     *
     * @throws \DOMException
     */
    private function createNode(string $name, mixed $text, array $attributes, array $namespaces, ?array $rootNamespaces, array $elements): ReadResult
    {
        $namespaces    = array_merge($namespaces, ...array_map(static fn(ReadResult $element) => $element->namespaces, $elements));
        $withNamespace = $rootNamespaces !== null;

        if ($withNamespace) {
            $attributes = array_merge($this->namespacesToAttributes($namespaces, $rootNamespaces), $attributes);
        }

        $node = $this->document->createTextElement($name, $text, $attributes, withNamespaces: $withNamespace);

        foreach ($elements as $element) {
            $node->append($element->node);
        }

        return ReadResult::create($node, $namespaces);
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
