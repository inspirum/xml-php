<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMException;
use Inspirum\XML\Exception\Handler;
use RuntimeException;
use function error_get_last;
use function file_get_contents;
use function sprintf;

final class DefaultDocumentFactory implements DocumentFactory
{
    public function __construct(
        private readonly DOMDocumentFactory $factory,
    ) {
    }

    public function create(?string $version = null, ?string $encoding = null): Document
    {
        return new DefaultDocument($this->factory->create($version, $encoding));
    }

    public function createForFile(string $filepath, ?string $version = null, ?string $encoding = null): Document
    {
        $content = @file_get_contents($filepath);
        if ($content === false) {
            throw new RuntimeException(error_get_last()['message'] ?? sprintf('Failed to open file [%s]', $filepath));
        }

        return $this->createForContent($content, $version, $encoding);
    }

    public function createForContent(string $content, ?string $version = null, ?string $encoding = null): Document
    {
        $document = $this->factory->create($version, $encoding);
        $this->loadXML($document, $content);
        $this->overrideDeclaration($document, $version, $encoding);

        return new DefaultDocument($document);
    }

    /**
     * Restore version and encoding replaced by the declaration of loaded content
     */
    private function overrideDeclaration(DOMDocument $document, ?string $version, ?string $encoding): void
    {
        if ($version !== null) {
            $document->xmlVersion = $version;
        }

        if ($encoding !== null) {
            $document->encoding = $encoding;
        }
    }

    /**
     * @throws \DOMException
     */
    private function loadXML(DOMDocument $document, string $content): void
    {
        Handler::withErrorHandlerForDOMDocument(static function () use ($document, $content): void {
            if ($content === '') {
                throw new DOMException('Content must not be empty');
            }

            $document->preserveWhiteSpace = false;

            Handler::checkResultForDOMDocument($document->loadXML($content), '\DOMDocument::loadXML()');
        });
    }
}
