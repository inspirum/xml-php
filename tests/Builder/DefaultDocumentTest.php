<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMDocument;
use DOMException;
use Inspirum\XML\Builder\DefaultDocument;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Tests\BaseTestCase;
use InvalidArgumentException;

final class DefaultDocumentTest extends BaseTestCase
{
    public function testToString(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertSame(
            $this->getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            $xml->toString()
        );
    }

    public function testCastToString(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertSame(
            $this->getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            (string) $xml
        );
    }

    public function testNodeTextContent(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addTextElement('a', 'Test');
        $bE = $aE->addTextElement('b', '1');

        $this->assertSame('1', $bE->getTextContent());

        $this->assertSame('Test1', $aE->getTextContent());
    }

    public function testDocumentTextContent(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addTextElement('a', 'Test');

        $this->assertSame('Test', $xml->getTextContent());

        $aE->addTextElement('b', '1');

        $this->assertSame('Test1', $xml->getTextContent());
    }

    public function testEncoding(): void
    {
        $xml = $this->newDocument('1.0', 'WINDOWS-1250');

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        $this->assertSame(
            $this->getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>', '1.0', 'WINDOWS-1250'),
            $xml->toString()
        );
    }

    public function testCDATAEscaping(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $aE->addTextElement('b', '30&nbsp;km');
        $aE->addTextElement('c', 'me & you');
        $aE->addTextElement('d', '2 > 1');
        $aE->addTextElement('e', '<3', [], true);

        $this->assertSame(
            $this->getSampleXMLString('<a><b><![CDATA[30&nbsp;km]]></b><c><![CDATA[me & you]]></c><d>2 &gt; 1</d><e><![CDATA[<3]]></e></a>'),
            $xml->toString()
        );
    }

    public function testAddXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('<c a="1">2</c><d>test2</d>');

        $this->assertSame(
            $this->getSampleXMLString('<a><b><c a="1">2</c><d>test2</d></b></a>'),
            $xml->toString()
        );
    }

    public function testAddEmptyStringXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('');

        $this->assertSame(
            $this->getSampleXMLString('<a><b/></a>'),
            $xml->toString()
        );
    }

    public function testAddEmptyXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('0');

        $this->assertSame(
            $this->getSampleXMLString('<a><b>0</b></a>'),
            $xml->toString()
        );
    }

    public function testAddWhitespaceXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('  ');

        $this->assertSame(
            $this->getSampleXMLString('<a><b>  </b></a>'),
            $xml->toString()
        );
    }

    public function testAttributes(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', ['price' => 23.4, 'domain' => 2]);
        $aE->addTextElement('b', 'Nazev', ['test' => true, 'locale' => 'cs']);

        $this->assertSame(
            $this->getSampleXMLString('<a price="23.4" domain="2"><b test="true" locale="cs">Nazev</b></a>'),
            $xml->toString()
        );
    }

    public function testGetNamespaceURIs(): void
    {
        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
            'xmlns:b' => 'b.xsd',
            'xmlnse'  => 'e.xsd',
            'a:test'  => '1',
        ]);

        $this->assertSame(
            [
                'a' => 'a.xsd',
                'b' => 'b.xsd',
            ],
            $xml->getNamespaces()
        );
    }

    public function testGetNamespaceURI(): void
    {
        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
        ]);

        $this->assertSame('a.xsd', $xml->getNamespace('a'));
    }

    public function testGetUnregisteredNamespaceURI(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Namespace [b] does not exists');

        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
        ]);

        $xml->getNamespace('b');
    }

    public function testMultipleDocumentsNamespaces(): void
    {
        $xml1 = $this->newDocument();
        $xml2 = $this->newDocument();

        $xml1->addElement('rss', [
            'xmlns:a' => 'a.xsd',
        ]);

        $xml2->addElement('rss', [
            'xmlns:b' => 'b.xsd',
        ]);

        $this->assertSame(['a' => 'a.xsd'], $xml1->getNamespaces());
        $this->assertSame(['b' => 'b.xsd'], $xml2->getNamespaces());
    }

    public function testAttributeNumericName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [a:1] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'a:a' => '1',
            'a:1' => '2',
        ]);
    }

    public function testAttributeEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [a:] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'a:a' => '1',
            'a:'  => '2',
        ]);
    }

    public function testAttributeEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [:a] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'a:a' => '1',
            ':a'  => '2',
        ]);
    }

    public function testElementNumericName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [e:1] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('e:1');
    }

    public function testElementEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [e:] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('e:');
    }

    public function testElementEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [:rss] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement(':rss');
    }

    public function testXmlnsAttributes(): void
    {
        $xml = $this->newDocument();

        $rss = $xml->addElement('rss', [
            'version' => '2.0',
            'xmlns:a' => 'http://base.google.com/ns/1.0',
        ]);

        $channel = $rss->addElement('channel');
        $channel->addTextElement('title', 'Feed');

        $item = $rss->addElement('item');
        $item->addTextElement('a:id', '8765');
        $item->addTextElement('a:price', 100.1);

        $this->assertSame(
            $this->getSampleXMLString('<rss xmlns:a="http://base.google.com/ns/1.0" version="2.0"><channel><title>Feed</title></channel><item><a:id>8765</a:id><a:price>100.1</a:price></item></rss>'),
            $xml->toString()
        );

        $this->assertSame(
            ['a' => 'http://base.google.com/ns/1.0'],
            $xml->getNamespaces()
        );
    }

    public function testValidateXsd(): void
    {
        $xml  = $this->newDocument();
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

        $xml->validate($this->getTestFilePath('example_03.xsd'));
        $this->assertSame(['g' => 'stock.xsd'], $xml->getNamespaces());
        $this->assertSame(
            $this->getSampleXMLString('<g:feed xmlns:g="stock.xsd" g:version="2.0"><g:updated>2020-08-25T13:53:38+00:00</g:updated><g:items><g:item><g:id>8765</g:id><g:name>Test</g:name><g:price>100.1</g:price></g:item></g:items></g:feed>'),
            $xml->toString()
        );
    }

    public function testValidateXsdFromOutput(): void
    {
        $xml  = $this->newDocument();
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

        $xml = $this->newDocumentFactory()->createForContent($xml->toString());
        $xml->validate($this->getTestFilePath('example_03.xsd'));
        $this->assertTrue(true);
    }

    public function testValidateXsdFromFile(): void
    {
        $xml = $this->newDocumentFactory()->createForFile($this->getTestFilePath('sample_03.xml'));
        $xml->validate($this->getTestFilePath('example_03.xsd'));
        $this->assertTrue(true);
    }

    public function testValidateWithoutXmlns(): void
    {
        $this->expectException(DOMException::class);

        $xml  = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getTestFilePath('example_03.xsd'));
    }

    public function testValidateWithoutAttributePrefix(): void
    {
        $this->expectException(DOMException::class);

        $xml  = $this->newDocument();
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

        $xml->validate($this->getTestFilePath('example_03.xsd'));
    }

    public function testValidateWithoutElementPrefix(): void
    {
        $this->expectException(DOMException::class);

        $xml  = $this->newDocument();
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

        $xml->validate($this->getTestFilePath('example_03.xsd'));
    }

    public function testValidateInvalidXml(): void
    {
        $this->expectException(DOMException::class);

        $xml  = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g'   => 'stock.xsd',
        ]);

        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate($this->getTestFilePath('example_03.xsd'));
    }

    private function newDocument(?string $version = null, ?string $encoding = null): DefaultDocument
    {
        return new DefaultDocument(new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8'));
    }

    private function newDocumentFactory(): DefaultDocumentFactory
    {
        return new DefaultDocumentFactory();
    }
}
