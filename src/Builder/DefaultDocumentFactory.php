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
    public function create(?string $version = null, ?string $encoding = null): Document
    {
        return new DefaultDocument($this->createDOMDocument($version, $encoding));
    }

    public function createForFile(string $filepath, ?string $version = null, ?string $encoding = null): Document
    {
        $content = @file_get_contents($filepath);
        if ($content === false) {
            throw new RuntimeException(error_get_last()['message'] ?? sprintf('Failed to open file [%s]', $filepath));
        }

        return $this->createForContent($content);
    }

    public function createForContent(string $content, ?string $version = null, ?string $encoding = null): Document
    {
        $document = $this->createDOMDocument($version, $encoding);
        $this->loadXML($document, $content);

        return new DefaultDocument($document);
    }

    private function createDOMDocument(?string $version = null, ?string $encoding = null): DOMDocument
    {
        return new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8');
    }

    /**
     * @throws \DOMException
     */
    private function loadXML(DOMDocument $document, string $content): void
    {
        Handler::withErrorHandlerForDOMDocument(static function () use ($document, $content): void {
            $document->preserveWhiteSpace = false;

            $xml = $document->loadXML($content);
            if ($xml === false) {
                throw new DOMException('\DOMDocument::load() method failed');
            }
        });
    }
}
