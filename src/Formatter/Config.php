<?php

declare(strict_types=1);

namespace Inspirum\XML\Formatter;

interface Config
{
    public function isAlwaysArray(): bool;

    /**
     * @return list<string>
     */
    public function getAlwaysArrayNodeNames(): array;

    public function isAutoCast(): bool;

    public function isFullResponse(): bool;

    public function getAttributesName(): string;

    public function getValueName(): string;

    public function getNodesName(): string;

    public function isFlatten(): bool;

    public function getFlattenNodes(): string;

    public function getFlattenAttributes(): string;
}
