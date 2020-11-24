<?php

namespace Inspirum\XML\Tests\Unit;

use DOMException;
use Exception;
use Inspirum\XML\Services\XML;
use Inspirum\XML\Services\XMLReader;
use Inspirum\XML\Tests\AbstractTestCase;

class FacadeTest extends AbstractTestCase
{
    public function testDefaultVersionAndEncoding()
    {
        $xml = new XML();

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n",
            $xml->toString()
        );
    }

    public function testVersionAndEncoding()
    {
        $xml = new XML('1.1', 'WINDOWS-1250');

        $this->assertEquals(
            '<?xml version="1.1" encoding="WINDOWS-1250"?>' . "\n",
            $xml->toString()
        );
    }

    public function testCreateMethod()
    {
        $xml = XML::create('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<root><a><b1>1</b1><b2>test</b2></a></root>' . "\n",
            $xml->toString()
        );
    }

    public function testCreateMethodWithInvalidXml()
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        XML::create('<?xml version="1.0" encoding="UTF-8"?><root><a>A</aa></root>');
    }

    public function testCreateMethodFromFile()
    {
        $xml = XML::create($this->loadSampleFilepath('sample_01.xml'));

        $this->assertEquals(
            $this->getSampleXMLstring('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString()
        );
    }

    public function testLoadMethodFromFile()
    {
        $xml = XML::load($this->getSampleFilepath('sample_01.xml'));

        $this->assertEquals(
            $this->getSampleXMLstring('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            $xml->toString()
        );
    }

    public function testLoadMethodFromWrongFile()
    {
        $this->expectException(Exception::class);

        $xml = XML::load('wrong.xml');
    }

    public function testLoadMethodFromFileWithInvalidXml()
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        XML::load($this->getSampleFilepath('sample_02.xml'));
    }

    public function testFormattedOutput()
    {
        $xml = XML::create('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        $this->assertEquals(
            $this->getSampleXMLstring(
                <<<END
                <root>
                  <a>
                    <b1>1</b1>
                    <b2>test</b2>
                  </a>
                </root>
                END
            ),
            $xml->toString(true)
        );
    }

    public function testSaveMethod()
    {
        $xml = XML::load($this->getSampleFilepath('sample_01.xml'));

        $name = tempnam(sys_get_temp_dir(), 'xml_tests_');
        $this->assertEquals('', file_get_contents($name));
        $xml->save($name);

        $this->assertFileExists($name);
        $this->assertEquals(
            $this->getSampleXMLstring('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            file_get_contents($name)
        );
    }

    public function testSaveMethodWithFormatOutput()
    {
        $xml = XML::load($this->getSampleFilepath('sample_01.xml'));

        $name = tempnam(sys_get_temp_dir(), 'xml_tests_');
        $this->assertEquals('', file_get_contents($name));
        $xml->save($name, true);

        $this->assertFileExists($name);
        $this->assertEquals(
            $this->getSampleXMLstring("<root>\n  <a>\n    <b1>1</b1>\n    <b2>test</b2>\n  </a>\n  <a>\n    <b1>2</b1>\n    <b2>test2</b2>\n    <b3>true</b3>\n  </a>\n  <b>\n    <a>\n      <b1>3</b1>\n      <b3>false</b3>\n    </a>\n  </b>\n</root>"),
            file_get_contents($name)
        );
    }

    public function testParseMethod()
    {
        $xmlReader = XML::parse($this->getSampleFilepath('sample_01.xml'));

        $this->assertInstanceOf(XMLReader::class, $xmlReader);
    }
}
