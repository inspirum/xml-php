<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

interface ReaderFactory
{
    public function create(string $filepath, ?string $version = null, ?string $encoding = null): Reader;
}
