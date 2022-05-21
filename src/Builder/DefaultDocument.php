<?php

declare(strict_types=1);

namespace Inspirum\XML\Builder;

use DOMDocument;
use DOMException;
use Inspirum\XML\Exception\Handler;
use InvalidArgumentException;
use function array_key_exists;
use function sprintf;

final class DefaultDocument extends BaseNode implements Document
{
    /**
     * Map of registered namespaces
     *
     * @var array<string,string>
     */
    private array $namespaces = [];

    public function __construct(DOMDocument $document)
    {
        parent::__construct($document, null, $this);
    }

    public function validate(string $filename): void
    {
        Handler::withErrorHandlerForDOMDocument(function () use ($filename): void {
            $validated = $this->getDocument()->schemaValidate($filename);
            if ($validated === false) {
                throw new DOMException('\DOMDocument::schemaValidate() method failed');
            }
        });
    }

    public function save(string $filename, bool $formatOutput = false): void
    {
        Handler::withErrorHandlerForDOMDocument(function () use ($filename, $formatOutput): void {
            $this->getDocument()->formatOutput = $formatOutput;

            $saved = $this->getDocument()->save($filename);
            if ($saved === false) {
                throw new DOMException('\DOMDocument::save() method failed');
            }
        });
    }

    public function registerNamespace(string $localName, string $namespaceURI): void
    {
        $this->namespaces[$localName] = $namespaceURI;
    }

    public function hasNamespace(string $localName): bool
    {
        return array_key_exists($localName, $this->namespaces);
    }

    public function getNamespace(string $localName): string
    {
        return $this->namespaces[$localName] ?? throw new InvalidArgumentException(sprintf('Namespace [%s] does not exists', $localName));
    }

    /**
     * @inheritDoc
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }
}
