<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMException;
use DOMNode;
use Exception;
use Inspirum\XML\Model\Values\Config;
use InvalidArgumentException;

class XML extends XMLNode
{
    /**
     * Map of registered namespaces
     *
     * @var array<string,string>
     */
    protected static $namespaces = [];

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
     *
     * @throws \Exception
     */
    public static function load(string $filepath): self
    {
        $content = @file_get_contents($filepath);

        if ($content === false) {
            throw new Exception(error_get_last()['message'] ?? sprintf('Failed to open file [%s]', $filepath));
        }

        return self::create($content);
    }

    /**
     * Load from data
     *
     * @param string $content
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

            $xml = $this->document->loadXML($content);

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
     * @param string $filename
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

            $xml = $this->document->save($filename);

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
     * @param string $localName
     *
     * @return bool
     */
    public static function hasNamespace(string $localName): bool
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
     * @return array<string,string>
     */
    public static function getNamespaces(): array
    {
        return static::$namespaces;
    }
}
