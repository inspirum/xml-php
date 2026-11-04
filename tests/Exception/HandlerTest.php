<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Exception;

use DOMException;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Exception\ReaderException;
use Inspirum\XML\Tests\BaseTestCase;
use function trigger_error;
use const E_USER_WARNING;

final class HandlerTest extends BaseTestCase
{
    public function testXMLReaderHandlerSkipsOtherWarnings(): void
    {
        $result = @Handler::withErrorHandlerForXMLReader(static function (): string {
            trigger_error('Other warning', E_USER_WARNING);

            return 'result';
        });

        self::assertSame('result', $result);
    }

    public function testDOMDocumentHandlerSkipsOtherWarnings(): void
    {
        $result = @Handler::withErrorHandlerForDOMDocument(static function (): string {
            trigger_error('Other warning', E_USER_WARNING);

            return 'result';
        });

        self::assertSame('result', $result);
    }

    public function testXMLReaderHandlerThrowsWithoutCode(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('XMLReader::read(): error');
        $this->expectExceptionCode(0);

        Handler::withErrorHandlerForXMLReader(static function (): void {
            trigger_error('XMLReader::read(): error', E_USER_WARNING);
        });
    }

    public function testDOMDocumentHandlerThrowsWithoutCode(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): error');
        $this->expectExceptionCode(0);

        Handler::withErrorHandlerForDOMDocument(static function (): void {
            trigger_error('DOMDocument::loadXML(): error', E_USER_WARNING);
        });
    }

    public function testCheckResultForXMLReader(): void
    {
        self::assertSame('result', Handler::checkResultForXMLReader('result', '\XMLReader::open()'));
    }

    public function testCheckFailedResultForXMLReader(): void
    {
        $this->expectException(ReaderException::class);
        $this->expectExceptionMessage('\XMLReader::open() method failed');

        Handler::checkResultForXMLReader(false, '\XMLReader::open()');
    }

    public function testCheckResultForDOMDocument(): void
    {
        self::assertSame('<a/>', Handler::checkResultForDOMDocument('<a/>', '\DOMDocument::saveXML()'));
    }

    public function testCheckFailedResultForDOMDocument(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('\DOMDocument::saveXML() method failed');

        Handler::checkResultForDOMDocument(false, '\DOMDocument::saveXML()');
    }
}
