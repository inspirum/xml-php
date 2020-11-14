<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMException;
use InvalidArgumentException;

class XML extends XMLNode
{
    /**
     * Map of registered namespaces
     *
     * @var array
     */
    private static $namespaces = [];

    /**
     * XML constructor
     *
     * @param string $version
     * @param string $encoding
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8')
    {
        parent::__construct(new DOMDocument($version, $encoding), null);

        self::$namespaces = [];
    }

    /**
     * Load from file
     *
     * @param string $filepath
     *
     * @return self
     */
    public static function load(string $filepath): self
    {
        return self::create(file_get_contents($filepath));
    }

    /**
     * Load from data
     *
     * @param string $filename
     *
     * @return self
     */
    public static function create(string $content): self
    {
        $xml = new self();
        $xml->loadXML($content);

        return $xml;
    }

    /**
     * Parse file with XMLReader
     *
     * @param string $filepath
     *
     * @return \Inspirum\XML\Services\XMLReader
     */
    public static function parse(string $filepath): XMLReader
    {
        return new XMLReader($filepath);
    }

    /**
     * Load XML
     *
     * @param string $content
     *
     * @throws \DOMException
     */
    private function loadXML(string $content): void
    {
        $this->withErrorHandler(function () use ($content) {
            $this->document->preserveWhiteSpace = false;

            $xml = $this->document->loadXML($content, null);

            if ($xml === false) {
                // @codeCoverageIgnoreStart
                throw new DOMException('\DOMDocument::load() method failed');
                // @codeCoverageIgnoreEnd
            }
        });
    }

    /**
     * Validate with xml scheme file (.xsd).
     *
     * @param $filename
     *
     * @return void
     *
     * @throws \DOMException
     */
    public function validate(string $filename): void
    {
        $this->withErrorHandler(function () use ($filename) {
            $xml = $this->document->schemaValidate($filename);

            if ($xml === false) {
                // @codeCoverageIgnoreStart
                throw new DOMException('\DOMDocument::schemaValidate() method failed');
                // @codeCoverageIgnoreEnd
            }
        });
    }

    /**
     * Save to file
     *
     * @param string $filename
     * @param bool   $formatOutput
     *
     * @return void
     *
     * @throws \DOMException
     */
    public function save(string $filename, bool $formatOutput = false): void
    {
        $this->withErrorHandler(function () use ($filename, $formatOutput) {
            $this->document->formatOutput = $formatOutput;

            $xml = $this->document->save($filename, null);

            if ($xml === false) {
                // @codeCoverageIgnoreStart
                throw new DOMException('\DOMDocument::save() method failed');
                // @codeCoverageIgnoreEnd
            }
        });
    }

    /**
     * Register new namespace
     *
     * @param string $localName
     * @param string $namespaceURI
     *
     * @return void
     */
    public static function registerNamespace(string $localName, string $namespaceURI): void
    {
        static::$namespaces[$localName] = $namespaceURI;
    }

    /**
     * Determinate if namespace is registered
     *
     * @param string|null $localName
     *
     * @return bool
     */
    public static function hasNamespace(?string $localName): bool
    {
        return array_key_exists($localName, static::$namespaces);
    }

    /**
     * Get namespace URI from local name
     *
     * @param string $localName
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public static function getNamespace(string $localName): string
    {
        if (static::hasNamespace($localName) === false) {
            throw new InvalidArgumentException(sprintf('Namespace [%s] does not exists', $localName));
        }

        return static::$namespaces[$localName];
    }

    /**
     * Get all registered namespaces
     *
     * @return array
     */
    public static function getNamespaces(): array
    {
        return static::$namespaces;
    }
}
