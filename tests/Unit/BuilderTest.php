<?php

namespace Inspirum\XML\Tests\Unit;

use DOMException;
use Inspirum\XML\Services\XML;
use Inspirum\XML\Tests\AbstractTestCase;
use InvalidArgumentException;

class BuilderTest extends AbstractTestCase
{
    public function testToString()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertEquals(
            $this->getSampleXMLstring('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            $xml->toString()
        );
    }

    public function testCastToString()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertEquals(
            $this->getSampleXMLstring('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            (string) $xml
        );
    }

    public function testNodeTextContent()
    {
        $xml = new XML();

        $aE = $xml->addTextElement('a', 'Test');
        $bE = $aE->addTextElement('b', '1');

        $this->assertEquals(
            '1',
            $bE->getTextContent()
        );

        $this->assertEquals(
            'Test1',
            $aE->getTextContent()
        );
    }

    public function testDocumentTextContent()
    {
        $xml = new XML();

        $xml->addTextElement('a', 'Test');

        $this->assertNull(
            $xml->getTextContent()
        );
    }

    public function testEncoding()
    {
        $xml = new XML('1.0', 'WINDOWS-1250');

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertEquals(
            $this->getSampleXMLstring('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>', '1.0', 'WINDOWS-1250'),
            $xml->toString()
        );
    }

    public function testCDATAEscaping()
    {
        $xml = new XML();

        $aE = $xml->addElement('a');
        $aE->addTextElement('b', '30&nbsp;km');
        $aE->addTextElement('c', 'me & you');
        $aE->addTextElement('d', '2 > 1');
        $aE->addTextElement('e', '<3', [], true);

        $this->assertEquals(
            $this->getSampleXMLstring('<a><b><![CDATA[30&nbsp;km]]></b><c><![CDATA[me & you]]></c><d>2 &gt; 1</d><e><![CDATA[<3]]></e></a>'),
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
            $this->getSampleXMLstring('<a><b><c a="1">2</c><d>test2</d></b></a>'),
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
            $this->getSampleXMLstring('<a><b/></a>'),
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
            $this->getSampleXMLstring('<a><b>0</b></a>'),
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
            $this->getSampleXMLstring('<a><b>  </b></a>'),
            $xml->toString()
        );
    }

    public function testAttributes()
    {
        $xml = new XML();

        $aE = $xml->addElement('a', ['price' => 23.4, 'domain' => 2]);
        $aE->addTextElement('b', 'Nazev', ['test' => true, 'locale' => 'cs']);

        $this->assertEquals(
            $this->getSampleXMLstring('<a price="23.4" domain="2"><b test="true" locale="cs">Nazev</b></a>'),
            $xml->toString()
        );
    }

    public function testGetNamespaceURIs()
    {
        $xml = new XML();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
            'xmlns:b' => 'b.xsd',
            'xmlnse'  => 'e.xsd',
            'a:test'  => '1',
        ]);

        $this->assertEquals(
            [
                'a' => 'a.xsd',
                'b' => 'b.xsd',
            ],
            XML::getNamespaces()
        );
    }

    public function testGetNamespaceURI()
    {
        $xml = new XML();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
        ]);

        $this->assertEquals('a.xsd', XML::getNamespace('a'));
    }

    public function testGetUnregisteredNamespaceURI()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Namespace [b] does not exists');

        $xml = new XML();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
        ]);

        XML::getNamespace('b');
    }

    public function testAttributeNumericName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [a:1] has invalid value');

        $xml = new XML();

        $xml->addElement('rss', [
            'a:a' => '1',
            'a:1' => '2',
        ]);
    }

    public function testAttributeEmptyName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [a:] has invalid value');

        $xml = new XML();

        $xml->addElement('rss', [
            'a:a' => '1',
            'a:'  => '2',
        ]);
    }

    public function testAttributeEmptyPrefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [:a] has invalid value');

        $xml = new XML();

        $xml->addElement('rss', [
            'a:a' => '1',
            ':a'  => '2',
        ]);
    }

    public function testElementNumericName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [e:1] has invalid value');

        $xml = new XML();

        $xml->addElement('e:1');
    }

    public function testElementEmptyName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [e:] has invalid value');

        $xml = new XML();

        $xml->addElement('e:');
    }

    public function testElementEmptyPrefix()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [:rss] has invalid value');

        $xml = new XML();

        $xml->addElement(':rss');
    }

    public function testXmlnsAttributes()
    {
        $xml = new XML();

        $rss = $xml->addElement('rss', [
            'version' => '2.0',
            'xmlns:a' => 'http://base.google.com/ns/1.0',
        ]);

        $channel = $rss->addElement('channel');
        $channel->addTextElement('title', 'Feed');

        $item = $rss->addElement('item');
        $item->addTextElement('a:id', '8765');
        $item->addTextElement('a:price', 100.1);

        $this->assertEquals(
            $this->getSampleXMLstring('<rss xmlns:a="http://base.google.com/ns/1.0" version="2.0"><channel><title>Feed</title></channel><item><a:id>8765</a:id><a:price>100.1</a:price></item></rss>'),
            $xml->toString()
        );

        $this->assertEquals(
            ['a' => 'http://base.google.com/ns/1.0'],
            XML::getNamespaces()
        );
    }

    public function testValidateXsd()
    {
        $xml  = new XML();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g'   => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getSampleFilepath('example_03.xsd'));
        $this->assertEquals(['g' => 'stock.xsd'], XML::getNamespaces());
        $this->assertEquals(
            $this->getSampleXMLstring('<g:feed xmlns:g="stock.xsd" g:version="2.0"><g:updated>2020-08-25T13:53:38+00:00</g:updated><g:items><g:item><g:id>8765</g:id><g:name>Test</g:name><g:price>100.1</g:price></g:item></g:items></g:feed>'),
            $xml->toString()
        );
    }

    public function testValidateXsdFromOutput()
    {
        $xml  = new XML();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g'   => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml = XML::create($xml->toString());
        $xml->validate($this->getSampleFilepath('example_03.xsd'));
        $this->assertTrue(true);
    }

    public function testValidateXsdFromFile()
    {
        $xml = XML::load($this->getSampleFilepath('sample_03.xml'));
        $xml->validate($this->getSampleFilepath('example_03.xsd'));
        $this->assertTrue(true);
    }

    public function testValidateWithoutXmlns()
    {
        $this->expectException(DOMException::class);

        $xml  = new XML();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getSampleFilepath('example_03.xsd'));
    }

    public function testValidateWithoutAttributePrefix()
    {
        $this->expectException(DOMException::class);

        $xml  = new XML();
        $feed = $xml->addElement('g:feed', [
            'version' => '2.0',
            'xmlns:g' => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getSampleFilepath('example_03.xsd'));
    }

    public function testValidateWithoutElementPrefix()
    {
        $this->expectException(DOMException::class);

        $xml  = new XML();
        $feed = $xml->addElement('feed', [
            'version' => '2.0',
            'xmlns:g' => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getSampleFilepath('example_03.xsd'));
    }

    public function testValidateInvalidXml()
    {
        $this->expectException(DOMException::class);

        $xml  = new XML();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g'   => 'stock.xsd',
        ]);

        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getSampleFilepath('example_03.xsd'));
    }
}
