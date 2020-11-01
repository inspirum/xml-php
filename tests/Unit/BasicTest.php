<?php

namespace Inspirum\XML\Tests\Unit;

use DOMException;
use Inspirum\XML\Services\XML;
use Inspirum\XML\Tests\AbstractTestCase;

class BasicTest extends AbstractTestCase
{
    public function testDefaultVersionAndEncoding()
    {
        $xml = new XML();

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n",
            $xml->toString()
        );
    }

    public function testVersionAndEncoding()
    {
        $xml = new XML('1.1', 'WINDOWS-1250');

        $this->assertEquals(
            "<?xml version=\"1.1\" encoding=\"WINDOWS-1250\"?>\n",
            $xml->toString()
        );
    }

    public function testCreateMethod()
    {
        $xml = XML::create("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root><a><b1>1</b1><b2>test</b2></a></root>");

        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root><a><b1>1</b1><b2>test</b2></a></root>\n",
            $xml->toString()
        );
    }

    public function testCreateMethodThrowExceptionOnInvalidXML()
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        XML::create("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root><a>A</aa></root>");
    }

    public function testCreateMethodFromFile()
    {
        $xml = XML::create($this->loadSampleFilepath('sample_01.xml'));

        $this->assertEquals(
            $this->getSampleXMLstring("<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>"),
            $xml->toString()
        );
    }

    public function testLoadMethodFromFile()
    {
        $xml = XML::load($this->getSampleFilepath('sample_01.xml'));

        $this->assertEquals(
            $this->getSampleXMLstring("<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>"),
            $xml->toString()
        );
    }

    public function testLoadMethodFromFileThrowExceptionOnInvalidXML()
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::loadXML(): expected \'>\' in Entity');

        XML::load($this->getSampleFilepath('sample_02.xml'));
    }

    public function testFormattedOutput()
    {
        $xml = XML::create("<?xml version=\"1.0\" encoding=\"UTF-8\"?><root><a><b1>1</b1><b2>test</b2></a></root>");

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

    public function testBuilder()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', "test");
        $aE->addTextElement('b', "2");

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>"),
            $xml->toString()
        );
    }

    public function testBuilderWithEncoding()
    {
        $xml = new XML('1.0', 'WINDOWS-1250');

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', "test");
        $aE->addTextElement('b', "2");

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>", '1.0', 'WINDOWS-1250'),
            $xml->toString()
        );
    }

    public function testCDATAEscaping()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $aE->addTextElement('b', "30&nbsp;km");
        $aE->addTextElement('c', "me & you");
        $aE->addTextElement('d', "2 > 1");
        $aE->addTextElement('e', "<3", [], true);

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b><![CDATA[30&nbsp;km]]></b><c><![CDATA[me & you]]></c><d>2 &gt; 1</d><e><![CDATA[<3]]></e></a>"),
            $xml->toString()
        );
    }

    public function testAddXMLData()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('<c a="1">2</c><d>test2</d>');

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b><c a=\"1\">2</c><d>test2</d></b></a>"),
            $xml->toString()
        );
    }

    public function testAddEmptyStringXMLData()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('');

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b/></a>"),
            $xml->toString()
        );
    }

    public function testAddEmptyXMLData()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('0');

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b>0</b></a>"),
            $xml->toString()
        );
    }

    public function testAddWhitespaceXMLData()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('  ');

        $this->assertEquals(
            $this->getSampleXMLstring("<a><b>  </b></a>"),
            $xml->toString()
        );
    }
}
