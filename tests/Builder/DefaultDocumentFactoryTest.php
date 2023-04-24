<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMDocument;
use DOMException;
use Inspirum\XML\Builder\DOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Tests\BaseTestCase;
use Throwable;

class DefaultDocumentFactoryTest extends BaseTestCase
{
    public function testDefaultVersionAndEncoding(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->create();

        self::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
            $xml->toString(),
        );
    }

    public function testVersionAndEncoding(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->create('1.1', 'WINDOWS-1250');

        self::assertSame(
            '<?xml version="1.1" encoding="WINDOWS-1250"?>' . "\n",
            $xml->toString(),
        );
    }

    public function testCreateForContentMethod(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        self::assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<root><a><b1>1</b1><b2>test</b2></a></root>' . "\n",
            $xml->toString(),
        );
    }

    public function testCreateForContentMethodWithInvalidXml(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML()');

        $factory = $this->newDocumentFactory();
        $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a>A</aa></root>');
    }

    public function testCreateForContentMethodWithInvalidXml2(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML()');

        $document = $this->createMock(DOMDocument::class);
        $document->expects(self::once())->method('loadXML')->willReturn(false);

        $factory = $this->createMock(DOMDocumentFactory::class);
        $factory->expects(self::once())->method('create')->willReturn($document);

        $factory = $this->newDocumentFactory($factory);

        $factory->createForContent('err');
    }

    public function testCreateForContentMethodFromFile(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForContent(self::loadSampleFilepath('sample_01.xml'));

        self::assertSame(
            self::getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString(),
        );
    }

    public function testCreateForFileMethodFromFile(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForFile(self::getTestFilePath('sample_01.xml'));

        self::assertSame(
            self::getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString(),
        );
    }

    public function testCreateForFileMethodFromWrongFile(): void
    {
        $this->expectException(Throwable::class);

        $factory = $this->newDocumentFactory();
        $factory->createForFile('wrong.xml');
    }

    public function testCreateForFileMethodFailed(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML()');

        $factory = $this->newDocumentFactory();
        $factory->createForFile(self::getTestFilePath('sample_02.xml'));
    }

    private function newDocumentFactory(?DOMDocumentFactory $factory = null): DefaultDocumentFactory
    {
        return new DefaultDocumentFactory($factory ?? new DefaultDOMDocumentFactory());
    }
}
