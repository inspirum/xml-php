<?php

namespace Inspirum\XML\Services;

use DOMDocument;
use DOMException;

class XML extends XMLNode
{
    /**
     * XML constructor
     *
     * @param string $version
     * @param string $encoding
     */
    public function __construct(string $version = '1.0', string $encoding = 'UTF-8')
    {
        parent::__construct(new DOMDocument($version, $encoding), null);
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
                throw new DOMException('\DOMDocument::load() method failed');
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
     * @throws \Exception
     */
    public function validate(string $filename): void
    {
        $this->withErrorHandler(function () use ($filename) {
            $xml = $this->document->schemaValidate($filename);

            if ($xml === false) {
                throw new DOMException('\DOMDocument::schemaValidate() method failed');
            }
        });
    }

    /**
     * Save to file.
     *
     * @param string $filename
     *
     * @return string
     *
     * @throws \Exception
     */
    public function save(string $filename): string
    {
        $this->withErrorHandler(function () use ($filename) {
            $xml = $this->document->save($filename, null);

            if ($xml === false) {
                throw new DOMException('\DOMDocument::save() method failed');
            }

            return $filename;
        });
    }
}
