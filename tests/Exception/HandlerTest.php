<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Exception;

use DOMException;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Exception\ReaderException;
use Inspirum\XML\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function restore_error_handler;
use function set_error_handler;
use function trigger_error;
use const E_USER_WARNING;

final class HandlerTest extends BaseTestCase
{
    /**
     * @param callable(callable(): string): string $handler
     */
    #[DataProvider('provideHandler')]
    public function testHandlerPassesOtherWarningsToPreviousHandler(callable $handler): void
    {
        $messages = [];
        set_error_handler(static function (int $code, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        });

        try {
            $result = $handler(static function (): string {
                trigger_error('Other warning', E_USER_WARNING);

                return 'result';
            });
        } finally {
            restore_error_handler();
        }

        self::assertSame('result', $result);
        self::assertSame(['Other warning'], $messages);
    }

    /**
     * @param callable(callable(): string): string $handler
     */
    #[DataProvider('provideHandler')]
    public function testHandlerSkipsOtherWarningsWithoutPreviousHandler(callable $handler): void
    {
        set_error_handler(null);

        try {
            $result = @$handler(static function (): string {
                trigger_error('Other warning', E_USER_WARNING);

                return 'result';
            });
        } finally {
            restore_error_handler();
        }

        self::assertSame('result', $result);
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideHandler(): iterable
    {
        yield 'XMLReader' => ['handler' => Handler::withErrorHandlerForXMLReader(...)];
        yield 'DOMDocument' => ['handler' => Handler::withErrorHandlerForDOMDocument(...)];
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
