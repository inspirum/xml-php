<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function file_get_contents;
use function realpath;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

abstract class BaseTestCase extends TestCase
{
    protected static function getTestFilePath(string $filename): string
    {
        $filepath = realpath(__DIR__ . '/data/' . $filename);
        if ($filepath === false) {
            throw new LogicException('Wrong path');
        }

        return $filepath;
    }

    protected static function getSampleXMLString(string $xml, string $version = '1.0', string $encoding = 'UTF-8'): string
    {
        return sprintf("<?xml version=\"%s\" encoding=\"%s\"?>\n%s\n", $version, $encoding, $xml);
    }

    protected static function loadSampleFilepath(string $filename): string
    {
        return (string) file_get_contents(self::getTestFilePath($filename));
    }

    /**
     * Register custom error handler to throw \Exception on warning message
     */
    protected static function withErrorHandler(callable $functionCallback): mixed
    {
        set_error_handler(static function (int $code, string $message): bool {
            throw new RuntimeException($message, $code);
        });

        try {
            return $functionCallback();
        } finally {
            restore_error_handler();
        }
    }
}
