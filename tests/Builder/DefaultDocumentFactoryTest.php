<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMException;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Tests\BaseTestCase;
use Throwable;
use function file_get_contents;
use function sys_get_temp_dir;
use function tempnam;

class DefaultDocumentFactoryTest extends BaseTestCase
{
    public function testDefaultVersionAndEncoding(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->create();

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
            $xml->toString()
        );
    }

    public function testVersionAndEncoding(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->create('1.1', 'WINDOWS-1250');

        $this->assertSame(
            '<?xml version="1.1" encoding="WINDOWS-1250"?>' . "\n",
            $xml->toString()
        );
    }

    public function testCreateMethod(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<root><a><b1>1</b1><b2>test</b2></a></root>' . "\n",
            $xml->toString()
        );
    }

    public function testCreateMethodWithInvalidXml(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        $factory = $this->newDocumentFactory();
        $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a>A</aa></root>');
    }

    public function testCreateMethodFromFile(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForContent($this->loadSampleFilepath('sample_01.xml'));

        $this->assertSame(
            $this->getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString()
        );
    }

    public function testLoadMethodFromFile(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForFile($this->getTestFilePath('sample_01.xml'));

        $this->assertSame(
            $this->getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString()
        );
    }

    public function testLoadMethodFromWrongFile(): void
    {
        $this->expectException(Throwable::class);

        $factory = $this->newDocumentFactory();
        $factory->createForFile('wrong.xml');
    }

    public function testLoadMethodFromFileWithInvalidXml(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        $factory = $this->newDocumentFactory();
        $factory->createForFile($this->getTestFilePath('sample_02.xml'));
    }

    public function testFormattedOutput(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        $this->assertSame(
            $this->getSampleXMLString(
                <<<XML
                <root>
                  <a>
                    <b1>1</b1>
                    <b2>test</b2>
                  </a>
                </root>
                XML
            ),
            $xml->toString(true)
        );
    }

    public function testSaveMethod(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForFile($this->getTestFilePath('sample_01.xml'));

        $name = (string) tempnam(sys_get_temp_dir(), 'xml_tests_');
        $this->assertSame('', file_get_contents($name));
        $xml->save($name);

        $this->assertFileExists($name);
        $this->assertSame(
            $this->getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            file_get_contents($name)
        );
    }

    public function testSaveMethodWithFormatOutput(): void
    {
        $factory = $this->newDocumentFactory();
        $xml     = $factory->createForFile($this->getTestFilePath('sample_01.xml'));

        $name = (string) tempnam(sys_get_temp_dir(), 'xml_tests_');
        $this->assertSame('', file_get_contents($name));
        $xml->save($name, true);

        $this->assertFileExists($name);
        $this->assertSame(
            $this->getSampleXMLString(
                <<<XML
                <root>
                  <a>
                    <b1>1</b1>
                    <b2>test</b2>
                  </a>
                  <a>
                    <b1>2</b1>
                    <b2>test2</b2>
                    <b3>true</b3>
                  </a>
                  <b>
                    <a>
                      <b1>3</b1>
                      <b3>false</b3>
                    </a>
                  </b>
                </root>
                XML
            ),
            file_get_contents($name)
        );
    }

    private function newDocumentFactory(): DefaultDocumentFactory
    {
        return new DefaultDocumentFactory();
    }
}
