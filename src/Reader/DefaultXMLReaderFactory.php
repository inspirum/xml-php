<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use XMLReader;

final class DefaultXMLReaderFactory implements XMLReaderFactory
{
    public function create(): XMLReader
    {
        return new XMLReader();
    }
}
