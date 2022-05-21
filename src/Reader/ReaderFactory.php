<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

interface ReaderFactory
{
    /**
     * Create new XML reader
     */
    public function create(string $filepath, ?string $version = null, ?string $encoding = null, ?int $flags = null): Reader;
}
