<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMNode;

final class DefaultNode extends BaseNode
{
    protected function __construct(
        DOMDocument $document,
        DOMNode $node,
        NamespaceRegistry $namespaceRegistry,
    ) {
        parent::__construct($document, $node, $namespaceRegistry);
    }
}
