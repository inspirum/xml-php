<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

interface DocumentFactory
{
    /**
     * Create XML empty document
     */
    public function create(?string $version = null, ?string $encoding = null): Document;

    /**
     * Create XML document from file content
     *
     * @throws \RuntimeException
     * @throws \DOMException
     */
    public function createForFile(string $filepath, ?string $version = null, ?string $encoding = null): Document;

    /**
     * Create XML document from content
     *
     * @throws \DOMException
     */
    public function createForContent(string $content, ?string $version = null, ?string $encoding = null): Document;
}
