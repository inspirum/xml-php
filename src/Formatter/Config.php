<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

final class Config
{
    private const ATTRIBUTES = '@attributes';
    private const NODES      = '@nodes';
    private const VALUE      = '@value';

    /**
     * @param array<int,string> $alwaysArray
     */
    public function __construct(
        public readonly array $alwaysArray = [],
        public readonly bool $fullResponse = false,
        public readonly bool $autoCast = false,
        public readonly string $attributesName = self::ATTRIBUTES,
        public readonly string $valueName = self::VALUE,
        public readonly string $nodesName = self::NODES,
    ) {
    }
}
