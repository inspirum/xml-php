<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\Node;

interface Reader
{
    /**
     * Parse file and yield next node
     *
     * @return iterable<\Inspirum\XML\Builder\Node>
     */
    public function iterateNode(string $nodeName): iterable;

    /**
     * Get next node
     */
    public function nextNode(string $nodeName): ?Node;
}
