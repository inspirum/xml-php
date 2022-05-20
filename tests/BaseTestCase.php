<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function realpath;
use function sprintf;

abstract class BaseTestCase extends TestCase
{
    protected function getTestFilePath(string $filename): string
    {
        $filepath = realpath(__DIR__ . '/data/' . $filename);
        if ($filepath === false) {
            throw new LogicException('Wrong path');
        }

        return $filepath;
    }

    protected function getSampleXMLString(string $xml, string $version = '1.0', string $encoding = 'UTF-8'): string
    {
        return sprintf("<?xml version=\"%s\" encoding=\"%s\"?>\n%s\n", $version, $encoding, $xml);
    }

    protected function loadSampleFilepath(string $filename): string
    {
        return (string) file_get_contents($this->getTestFilePath($filename));
    }
}
