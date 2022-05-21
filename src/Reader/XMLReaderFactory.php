<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use XMLReader;

interface XMLReaderFactory
{
    /**
     * Create new XML reader
     */
    public function create(): XMLReader;
}
