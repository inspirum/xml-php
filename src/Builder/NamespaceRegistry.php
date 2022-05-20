<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

interface NamespaceRegistry
{
    /**
     * Register new namespace
     */
    public function registerNamespace(string $localName, string $namespaceURI): void;

    /**
     * Determinate if namespace is registered
     */
    public function hasNamespace(string $localName): bool;

    /**
     * Get namespace URI from local name
     *
     * @throws \InvalidArgumentException
     */
    public function getNamespace(string $localName): string;

    /**
     * Get all registered namespaces
     *
     * @return array<string,string>
     */
    public function getNamespaces(): array;
}
