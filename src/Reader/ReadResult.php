<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\Node;

/**
 * @internal
 */
final class ReadResult
{
    /**
     * @param array<string,string> $namespaces
     * @param list<string>        $usedNamespaces
     */
    private function __construct(
        public readonly Node $node,
        public readonly array $namespaces,
        public readonly array $usedNamespaces,
    ) {
    }

    /**
     * @param array<string,string> $namespaces
     * @param list<string>        $usedNamespaces
     */
    public static function create(Node $node, array $namespaces = [], array $usedNamespaces = []): self
    {
        return new self($node, $namespaces, $usedNamespaces);
    }
}
