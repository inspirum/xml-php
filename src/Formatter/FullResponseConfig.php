<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

final readonly class FullResponseConfig extends BaseConfig
{
    /**
     * @param list<string>|true $alwaysArray
     */
    public function __construct(
        array | true $alwaysArray = [],
        bool $autoCast = false,
        ?string $attributesName = null,
        ?string $valueName = null,
        private ?string $nodesName = null,
    ) {
        parent::__construct($alwaysArray, $autoCast, $attributesName, $valueName);
    }

    public function isFlatten(): bool
    {
        return false;
    }

    public function isFullResponse(): bool
    {
        return true;
    }

    public function getNodesName(): string
    {
        return $this->nodesName ?? parent::getNodesName();
    }
}
