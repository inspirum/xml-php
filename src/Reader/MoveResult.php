<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

/**
 * @internal
 */
final class MoveResult
{
    /**
     * @param array<string,string> $namespaces
     */
    private function __construct(
        public readonly bool $found,
        public readonly array $namespaces,
    ) {
    }

    /**
     * @param array<string,string> $namespaces
     */
    public static function found(array $namespaces = []): self
    {
        return new self(true, $namespaces);
    }

    public static function notFound(): self
    {
        return new self(false, []);
    }
}
