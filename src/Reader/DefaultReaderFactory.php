<?php

declare(strict_types=1);

namespace Inspirum\XML\Reader;

use Inspirum\XML\Builder\DocumentFactory;
use Inspirum\XML\Exception\Handler;
use const LIBXML_NOBLANKS;

final class DefaultReaderFactory implements ReaderFactory
{
    public function __construct(
        private readonly XMLReaderFactory $readerFactory,
        private readonly DocumentFactory $documentFactory,
    ) {
    }

    /**
     * @throws \Inspirum\XML\Exception\ReaderException
     */
    public function create(string $filepath, ?string $version = null, ?string $encoding = null, ?int $flags = null): Reader
    {
        $xmlReader = $this->readerFactory->create();
        $document = $this->documentFactory->create($version, $encoding);

        Handler::withErrorHandlerForXMLReader(static function () use ($xmlReader, $filepath, $encoding, $flags): void {
            Handler::checkResultForXMLReader($xmlReader->open($filepath, $encoding, ($flags ?? 0) | LIBXML_NOBLANKS), '\XMLReader::open()');
        });

        return new DefaultReader($xmlReader, $document);
    }
}
