<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;

interface DOMDocumentFactory
{
    /**
     * Create new DOM document
     */
    public function create(?string $version = null, ?string $encoding = null): DOMDocument;
}
