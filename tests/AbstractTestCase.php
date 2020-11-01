<?php

namespace Inspirum\XML\Tests;

use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class AbstractTestCase extends PHPUnitTestCase
{
    /**
     * Setup the test environment, before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    protected function getSampleFilepath(string $filename): string
    {
        $filepath = realpath(__DIR__ . '/examples/' . $filename);

        if ($filepath === false) {
            throw new LogicException('Wrong path');
        }

        return $filepath;
    }

    protected function getSampleXMLstring(string $xml, string $version = '1.0', string $encoding = 'UTF-8')
    {
        return sprintf("<?xml version=\"%s\" encoding=\"%s\"?>\n%s\n", $version, $encoding, $xml);
    }

    protected function loadSampleFilepath(string $filename): string
    {
        return file_get_contents($this->getSampleFilepath($filename));
    }
}
