<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

final readonly class FlattenConfig extends BaseConfig
{
    /**
     * @param list<string>|true $alwaysArray
     */
    public function __construct(
        array|true $alwaysArray = [],
        bool $autoCast = false,
        private ?string $flattenNodes = null,
        private ?string $flattenAttributes = null,
        private bool $withoutRoot = false,
    ) {
        parent::__construct($alwaysArray, $autoCast);
    }

    public function isFlatten(): bool
    {
        return true;
    }

    public function isFullResponse(): bool
    {
        return false;
    }

    public function getFlattenNodes(): string
    {
        return $this->flattenNodes ?? parent::getFlattenNodes();
    }

    public function getFlattenAttributes(): string
    {
        return $this->flattenAttributes ?? parent::getFlattenAttributes();
    }

    public function isWithoutRoot(): bool
    {
        return $this->withoutRoot;
    }
}
