<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Exception;
use Inspirum\XML\Builder\DocumentFactory;
use Inspirum\XML\Exception\Handler;

final class DefaultReaderFactory implements ReaderFactory
{
    public function __construct(
        private XMLReaderFactory $readerFactory,
        private DocumentFactory $documentFactory,
    ) {
    }

    public function create(string $filepath, ?string $version = null, ?string $encoding = null, ?int $flags = null): Reader
    {
        $xmlReader = $this->readerFactory->create();
        $document  = $this->documentFactory->create($version, $encoding);

        Handler::withErrorHandlerForXMLReader(static function () use ($xmlReader, $filepath, $encoding, $flags): void {
            $opened = $xmlReader->open($filepath, $encoding, $flags ?? 0);
            // @codeCoverageIgnoreStart
            if ($opened === false) {
                throw new Exception('\XMLReader::open() method failed');
            }
            // @codeCoverageIgnoreEnd
        });

        return new DefaultReader($xmlReader, $document);
    }
}
