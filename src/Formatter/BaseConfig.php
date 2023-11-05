<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

abstract readonly class BaseConfig implements Config
{
    private const ATTRIBUTES         = '@attributes';
    private const NODES              = '@nodes';
    private const VALUE              = '@value';
    private const FLATTEN_NODES      = '/';
    private const FLATTEN_ATTRIBUTES = '@';

    /**
     * @param list<string>|true $alwaysArray
     */
    public function __construct(
        private array | true $alwaysArray = [],
        private bool $autoCast = false,
        private ?string $attributesName = null,
        private ?string $valueName = null,
    ) {
    }

    public function isAlwaysArray(): bool
    {
        return $this->alwaysArray === true;
    }

    /**
     * @inheritDoc
     */
    public function getAlwaysArrayNodeNames(): array
    {
        if ($this->alwaysArray === true) {
            return [];
        }

        return $this->alwaysArray;
    }

    public function isAutoCast(): bool
    {
        return $this->autoCast;
    }

    public function isFullResponse(): bool
    {
        return false;
    }

    public function getAttributesName(): string
    {
        return $this->attributesName ?? self::ATTRIBUTES;
    }

    public function getValueName(): string
    {
        return $this->valueName ?? self::VALUE;
    }

    public function getNodesName(): string
    {
        return self::NODES;
    }

    public function isFlatten(): bool
    {
        return false;
    }

    public function getFlattenNodes(): string
    {
        return self::FLATTEN_NODES;
    }

    public function getFlattenAttributes(): string
    {
        return self::FLATTEN_ATTRIBUTES;
    }

    public function isWithoutRoot(): bool
    {
        return false;
    }
}
