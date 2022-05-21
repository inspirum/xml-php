<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;

final class DefaultDOMDocumentFactory implements DOMDocumentFactory
{
    public function create(?string $version = null, ?string $encoding = null): DOMDocument
    {
        return new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8');
    }
}
