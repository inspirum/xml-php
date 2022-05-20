<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Exception;
use Inspirum\XML\Builder\DocumentFactory;
use Inspirum\XML\Exception\Handler;
use XMLReader;

final class DefaultReaderFactory implements ReaderFactory
{
    public function __construct(private DocumentFactory $documentFactory)
    {
    }

    public function create(string $filepath, ?string $version = null, ?string $encoding = null): Reader
    {
        $xmlReader = new XMLReader();
        $document  = $this->documentFactory->create($version, $encoding);

        $opened = Handler::withErrorHandlerForXMLReader(static function () use ($xmlReader, $filepath) {
            return $xmlReader->open($filepath);
        });

        if ($opened === false) {
            throw new Exception('\XMLReader::open() method failed');
        }

        return new DefaultReader($xmlReader, $document);
    }
}
