<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Exception;

use DOMException;
use Inspirum\XML\Exception\Handler;
use Inspirum\XML\Tests\BaseTestCase;
use Throwable;

final class HandlerTest extends BaseTestCase
{
    public function testCheckResultForXMLReader(): void
    {
        self::assertSame('result', Handler::checkResultForXMLReader('result', '\XMLReader::open()'));
    }

    public function testCheckFailedResultForXMLReader(): void
    {
        $this->expectException(Throwable::class);
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
