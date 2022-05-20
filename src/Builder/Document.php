<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

interface Document extends Node, NamespaceRegistry
{
    /**
     * Validate with xml scheme file (.xsd).
     *
     * @throws \DOMException
     */
    public function validate(string $filename): void;

    /**
     * Save to file
     *
     * @throws \DOMException
     */
    public function save(string $filename, bool $formatOutput = false): void;
}
