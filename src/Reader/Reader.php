<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\Node;

interface Reader
{
    /**
     * Parse file by node name or node xpath and yield next node
     *
     * @return iterable<\Inspirum\XML\Builder\Node>
     *
     * @throws \Exception
     */
    public function iterateNode(string $nodeName, bool $withNamespaces = false): iterable;

    /**
     * Get next node by node name or node xpath
     *
     * @throws \Exception
     */
    public function nextNode(string $nodeName): ?Node;

    /**
     * Close the input
     */
    public function close(): void;
}
