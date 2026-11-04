<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMDocument;
use DOMDocumentFragment;
use DOMException;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDocument;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Tests\BaseTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_map;
use function file_get_contents;
use function sys_get_temp_dir;
use function tempnam;

final class DefaultDocumentTest extends BaseTestCase
{
    private const XPATH_CONTENT = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <item xmlns:g="http://base.google.com/ns/1.0" xmlns:h="http://base.google.com/ns/2.0">
            <g:detail><g:name>Color</g:name><g:value>white</g:value><g:value>gold</g:value></g:detail>
            <g:detail><g:name>Length</g:name><g:value>60 cm</g:value></g:detail>
            <note>a <b>bold</b> <![CDATA[x & y]]></note>
        </item>
        XML;

    public function testCreateElements(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');

        $aE->addElement('b');
        $cE = $aE->createElement('c');
        $cE->addTextElement('d', 'text');
        $eE = $cE->createTextElement('e', 'text');

        self::assertSame('<c><d>text</d></c>', $cE->toString());
        self::assertSame('<e>text</e>', $eE->toString());

        self::assertSame(
            self::getSampleXMLString('<a><b/></a>'),
            $xml->toString(),
        );
    }

    public function testToString(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $aE->addTextElement('b', '2');

        self::assertSame(
            self::getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            $xml->toString(),
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

        self::assertSame(
            self::getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>'),
            (string) $xml,
        );
    }

    public function testToStringFailed(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::saveXML() method failed');

        $document = $this->createMock(DOMDocument::class);
        $document->expects($this->once())->method('saveXML')->willReturn(false);

        $xml = new DefaultDocument($document);

        $xml->toString();
    }

    public function testNodeTextContent(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addTextElement('a', 'Test');
        $bE = $aE->addTextElement('b', '1');

        self::assertSame('1', $bE->getTextContent());

        self::assertSame('Test1', $aE->getTextContent());
    }

    public function testDocumentTextContent(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addTextElement('a', 'Test');

        self::assertSame('Test', $xml->getTextContent());

        $aE->addTextElement('b', '1');

        self::assertSame('Test1', $xml->getTextContent());
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

        self::assertSame(
            self::getSampleXMLString('<a><b><c1>1</c1><c2>true</c2><c3>test</c3></b><b>2</b></a>', '1.0', 'WINDOWS-1250'),
            $xml->toString(),
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

        self::assertSame(
            self::getSampleXMLString('<a><b><![CDATA[30&nbsp;km]]></b><c><![CDATA[me & you]]></c><d>2 &gt; 1</d><e><![CDATA[<3]]></e></a>'),
            $xml->toString(),
        );
    }

    public function testAddXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('<c a="1">2</c><d>test2</d>');

        self::assertSame(
            self::getSampleXMLString('<a><b><c a="1">2</c><d>test2</d></b></a>'),
            $xml->toString(),
        );
    }

    public function testAddEmptyStringXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('');

        self::assertSame(
            self::getSampleXMLString('<a><b/></a>'),
            $xml->toString(),
        );
    }

    public function testAddEmptyXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('0');

        self::assertSame(
            self::getSampleXMLString('<a><b>0</b></a>'),
            $xml->toString(),
        );
    }

    public function testAddWhitespaceXMLData(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addXMLData('  ');

        self::assertSame(
            self::getSampleXMLString('<a><b>  </b></a>'),
            $xml->toString(),
        );
    }

    public function testAttributes(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', ['price' => 23.4, 'domain' => 2, 'wrong' => [1, 2, 3]]);

        self::assertSame('', $aE->getTextContent());
        self::assertSame(['price' => '23.4', 'domain' => '2', 'wrong' => ''], $aE->getAttributes());
        self::assertSame(['price' => 23.4, 'domain' => 2, 'wrong' => ''], $aE->getAttributes(true));

        $bE = $aE->addTextElement('b', 'Nazev', ['test' => true, 'locale' => 'cs', 'disable' => null]);

        self::assertSame('Nazev', $bE->getTextContent());
        self::assertSame(['test' => 'true', 'locale' => 'cs', 'disable' => ''], $bE->getAttributes());
        self::assertSame(['test' => true, 'locale' => 'cs', 'disable' => ''], $bE->getAttributes(true));

        self::assertSame(
            self::getSampleXMLString('<a price="23.4" domain="2" wrong=""><b test="true" locale="cs" disable="">Nazev</b></a>'),
            $xml->toString(),
        );
    }

    public function testGetNamespaceURIs(): void
    {
        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
            'xmlns:b' => 'b.xsd',
            'xmlnse' => 'e.xsd',
            'xmlns' => 'default.xsd',
            'a:test' => '1',
        ]);

        self::assertSame(
            [
                'a' => 'a.xsd',
                'b' => 'b.xsd',
                '' => 'default.xsd',
            ],
            $xml->getNamespaces(),
        );
    }

    public function testGetNamespaceURI(): void
    {
        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'xmlns:a' => 'a.xsd',
            'xmlns' => 'default.xsd',
        ]);

        self::assertSame('a.xsd', $xml->getNamespace('a'));
        self::assertSame('default.xsd', $xml->getNamespace(''));
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

        self::assertSame(['a' => 'a.xsd'], $xml1->getNamespaces());
        self::assertSame(['b' => 'b.xsd'], $xml2->getNamespaces());
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
            'a:' => '2',
        ]);
    }

    public function testAttributeEmptyPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Element name or namespace prefix [:a] has invalid value');

        $xml = $this->newDocument();

        $xml->addElement('rss', [
            'a:a' => '1',
            ':a' => '2',
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

        self::assertSame(
            self::getSampleXMLString('<rss xmlns:a="http://base.google.com/ns/1.0" version="2.0"><channel><title>Feed</title></channel><item><a:id>8765</a:id><a:price>100.1</a:price></item></rss>'),
            $xml->toString(),
        );

        self::assertSame(
            ['a' => 'http://base.google.com/ns/1.0'],
            $xml->getNamespaces(),
        );
    }

    public function testDefaultXmlnsAttributes(): void
    {
        $xml = $this->newDocument();

        $rss = $xml->addElement('rss', [
            'version' => '2.0',
            'xmlns' => 'http://www.w3.org/2005/Atom',
        ]);

        $channel = $rss->addElement('channel');
        $channel->addTextElement('title', 'Feed');

        $item = $rss->addElement('item');
        $item->addTextElement('id', '8765');
        $item->addTextElement('price', 100.1);

        self::assertSame(
            self::getSampleXMLString('<rss xmlns="http://www.w3.org/2005/Atom" version="2.0"><channel><title>Feed</title></channel><item><id>8765</id><price>100.1</price></item></rss>'),
            $xml->toString(),
        );

        self::assertSame(
            ['' => 'http://www.w3.org/2005/Atom'],
            $xml->getNamespaces(),
        );
    }

    public function testValidateXsd(): void
    {
        $xml = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g' => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate(self::getTestFilePath('example_03.xsd'));
        self::assertSame(['g' => 'stock.xsd'], $xml->getNamespaces());
        self::assertSame(
            self::getSampleXMLString('<g:feed xmlns:g="stock.xsd" g:version="2.0"><g:updated>2020-08-25T13:53:38+00:00</g:updated><g:items><g:item><g:id>8765</g:id><g:name>Test</g:name><g:price>100.1</g:price></g:item></g:items></g:feed>'),
            $xml->toString(),
        );
    }

    public function testValidateXsdFromOutput(): void
    {
        $this->expectNotToPerformAssertions();

        $xml = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g' => 'stock.xsd',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml = $this->newDocumentFactory()->createForContent($xml->toString());
        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateXsdFromFile(): void
    {
        $this->expectNotToPerformAssertions();

        $xml = $this->newDocumentFactory()->createForFile(self::getTestFilePath('sample_03.xml'));
        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateXsdFailed(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::schemaValidate() method failed');

        $document = $this->createMock(DOMDocument::class);
        $document->expects($this->once())->method('schemaValidate')->willReturn(false);

        $xml = new DefaultDocument($document);

        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateWithoutXmlns(): void
    {
        $this->expectException(DOMException::class);

        $xml = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
        ]);

        $feed->addTextElement('g:updated', '2020-08-25T13:53:38+00:00');
        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateWithoutAttributePrefix(): void
    {
        $this->expectException(DOMException::class);

        $xml = $this->newDocument();
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

        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateWithoutElementPrefix(): void
    {
        $this->expectException(DOMException::class);

        $xml = $this->newDocument();
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

        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testValidateInvalidXml(): void
    {
        $this->expectException(DOMException::class);

        $xml = $this->newDocument();
        $feed = $xml->addElement('g:feed', [
            'g:version' => '2.0',
            'xmlns:g' => 'stock.xsd',
        ]);

        $items = $feed->addElement('g:items');

        $item = $items->addElement('g:item');
        $item->addTextElement('g:id', '8765');
        $item->addTextElement('g:name', 'Test');
        $item->addTextElement('g:price', 100.1);

        $xml->validate(self::getTestFilePath('example_03.xsd'));
    }

    public function testFormattedOutput(): void
    {
        $factory = $this->newDocumentFactory();
        $xml = $factory->createForContent('<?xml version="1.0" encoding="UTF-8"?><root><a><b1>1</b1><b2>test</b2></a></root>');

        self::assertSame(
            self::getSampleXMLString(
                <<<'XML'
                <root>
                  <a>
                    <b1>1</b1>
                    <b2>test</b2>
                  </a>
                </root>
                XML,
            ),
            $xml->toString(true),
        );
    }

    public function testSaveMethod(): void
    {
        $factory = $this->newDocumentFactory();
        $xml = $factory->createForFile(self::getTestFilePath('sample_01.xml'));

        $name = (string) tempnam(sys_get_temp_dir(), 'xml_tests_');
        self::assertSame('', file_get_contents($name));
        $xml->save($name);

        self::assertFileExists($name);
        self::assertSame(
            self::getSampleXMLString('<root><a><b1>1</b1><b2>test</b2></a><a><b1>2</b1><b2>test2</b2><b3>true</b3></a><b><a><b1>3</b1><b3>false</b3></a></b></root>'),
            file_get_contents($name),
        );
    }

    public function testSaveMethodWithFormatOutput(): void
    {
        $factory = $this->newDocumentFactory();
        $xml = $factory->createForFile(self::getTestFilePath('sample_01.xml'));

        $name = (string) tempnam(sys_get_temp_dir(), 'xml_tests_');
        self::assertSame('', file_get_contents($name));
        $xml->save($name, true);

        self::assertFileExists($name);
        self::assertSame(
            self::getSampleXMLString(
                <<<'XML'
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
                XML,
            ),
            file_get_contents($name),
        );
    }

    public function testSaveMethodFailed(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocument::save() method failed');

        $document = $this->createMock(DOMDocument::class);
        $document->expects($this->once())->method('save')->willReturn(false);

        $xml = new DefaultDocument($document);

        $xml->save('test.xml', true);
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('provideXpath')]
    public function testXpath(string $path, array $expected): void
    {
        $xml = $this->newDocumentFactory()->createForContent(self::XPATH_CONTENT);

        self::assertSame($expected, array_map(static fn (Node $node): string => $node->toString(), $xml->xpath($path)));
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideXpath(): iterable
    {
        yield 'namespaced elements' => [
            'path' => '/item/g:detail/g:name',
            'expected' => [
                '<g:name xmlns:g="http://base.google.com/ns/1.0">Color</g:name>',
                '<g:name xmlns:g="http://base.google.com/ns/1.0">Length</g:name>',
            ],
        ];

        yield 'element with mixed content' => [
            'path' => '/item/note',
            'expected' => [
                '<note>a <b>bold</b> <![CDATA[x & y]]></note>',
            ],
        ];

        yield 'text nodes' => [
            'path' => '/item/g:detail/g:value/text()',
            'expected' => ['white', 'gold', '60 cm'],
        ];
    }

    /**
     * @param list<list<string>> $expected
     */
    #[DataProvider('provideXpathInsideResult')]
    public function testXpathInsideResult(string $path, string $innerPath, array $expected): void
    {
        $xml = $this->newDocumentFactory()->createForContent(self::XPATH_CONTENT);

        $results = array_map(
            static fn (Node $node): array => array_map(static fn (Node $inner): string => $inner->getTextContent(), $node->xpath($innerPath)),
            $xml->xpath($path),
        );

        self::assertSame($expected, $results);
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideXpathInsideResult(): iterable
    {
        yield 'relative path' => [
            'path' => '/item/g:detail',
            'innerPath' => 'g:value',
            'expected' => [['white', 'gold'], ['60 cm']],
        ];

        yield 'absolute path' => [
            'path' => '/item/g:detail',
            'innerPath' => '/g:detail/g:name',
            'expected' => [['Color'], ['Length']],
        ];
    }

    public function testXpathWithInvalidExpression(): void
    {
        $xml = $this->newDocumentFactory()->createForContent(self::XPATH_CONTENT);

        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMXPath::query(): Invalid expression');

        $xml->xpath('/item[');
    }

    public function testUnicodeElementName(): void
    {
        $xml = $this->newDocument();

        $xml->addElement('název', ['délka' => 1]);

        self::assertSame(
            self::getSampleXMLString('<název délka="1"/>'),
            $xml->toString(),
        );
    }

    public function testAddInvalidXMLData(): void
    {
        $xml = $this->newDocument();
        $aE = $xml->addElement('a');

        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocumentFragment::appendXML()');

        $aE->addXMLData('<b>');
    }

    public function testAddXMLDataFailed(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('DOMDocumentFragment::appendXML() method failed');

        $fragment = $this->createMock(DOMDocumentFragment::class);
        $fragment->expects($this->once())->method('appendXML')->willReturn(false);

        $document = $this->createMock(DOMDocument::class);
        $document->expects($this->once())->method('createDocumentFragment')->willReturn($fragment);

        $xml = new DefaultDocument($document);
        $xml->addXMLData('<a/>');
    }

    public function testXpathOnEmptyNode(): void
    {
        $xml = $this->newDocument();

        $node = $xml->addElement('a')->addXMLData('<b/>');

        self::assertSame([], $node?->xpath('b'));
    }

    public function testValidateWithEmptyFilename(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Schema filename must not be empty');

        $this->newDocument()->validate('');
    }

    public function testSaveWithEmptyFilename(): void
    {
        $this->expectException(DOMException::class);
        $this->expectExceptionMessage('Filename must not be empty');

        $this->newDocument()->save('');
    }

    public function testToStringKeepsFormatOutput(): void
    {
        $xml = $this->newDocument();
        $xml->addElement('a')->addElement('b');

        $xml->toString(true);

        self::assertFalse($xml->getDocument()->formatOutput);
    }

    #[DataProvider('provideCreateForContentDeclaration')]
    public function testCreateForContentDeclaration(string $content, ?string $version, ?string $encoding, string $expected): void
    {
        $xml = $this->newDocumentFactory()->createForContent($content, $version, $encoding);

        self::assertSame($expected, $xml->toString());
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideCreateForContentDeclaration(): iterable
    {
        yield 'without declaration' => [
            'content' => '<a>č</a>',
            'version' => null,
            'encoding' => null,
            'expected' => "<?xml version=\"1.0\"?>\n<a>&#x10D;</a>\n",
        ];

        yield 'with encoding' => [
            'content' => '<a>č</a>',
            'version' => null,
            'encoding' => 'UTF-8',
            'expected' => self::getSampleXMLString('<a>č</a>'),
        ];

        yield 'with declared encoding' => [
            'content' => '<?xml version="1.0" encoding="ISO-8859-2"?><a/>',
            'version' => null,
            'encoding' => null,
            'expected' => self::getSampleXMLString('<a/>', encoding: 'ISO-8859-2'),
        ];

        yield 'with overridden declaration' => [
            'content' => '<?xml version="1.0" encoding="UTF-8"?><a/>',
            'version' => '1.1',
            'encoding' => 'ISO-8859-2',
            'expected' => self::getSampleXMLString('<a/>', '1.1', 'ISO-8859-2'),
        ];
    }

    public function testCreateForFileDeclaration(): void
    {
        $xml = $this->newDocumentFactory()->createForFile(self::getTestFilePath('sample_12.xml'), encoding: 'UTF-8');

        self::assertSame(self::getSampleXMLString('<a>č</a>'), $xml->toString());
    }

    #[DataProvider('provideElementFromNode')]
    public function testAddElementFromNode(string $content, bool $forcedEscape, bool $withNamespaces, string $expected): void
    {
        $node = $this->newDocumentFactory()->createForContent($content)->getDocument()->documentElement;
        self::assertNotNull($node);

        $xml = $this->newDocument();
        $xml->addElementFromNode($node, $forcedEscape, $withNamespaces);

        self::assertSame(self::getSampleXMLString($expected), $xml->toString());
    }

    #[DataProvider('provideElementFromNode')]
    public function testCreateElementFromNode(string $content, bool $forcedEscape, bool $withNamespaces, string $expected): void
    {
        $node = $this->newDocumentFactory()->createForContent($content)->getDocument()->documentElement;
        self::assertNotNull($node);

        $xml = $this->newDocument();
        $xml->append($xml->createElementFromNode($node, $forcedEscape, $withNamespaces));

        self::assertSame(self::getSampleXMLString($expected), $xml->toString());
    }

    /**
     * @return iterable<string,array<string,mixed>>
     */
    public static function provideElementFromNode(): iterable
    {
        yield 'comment and processing instruction' => [
            'content' => '<a><!-- c --><?pi data?><b>x</b></a>',
            'forcedEscape' => false,
            'withNamespaces' => true,
            'expected' => '<a><!-- c --><?pi data?><b>x</b></a>',
        ];

        yield 'mixed content' => [
            'content' => '<a>foo<b>bar</b>baz</a>',
            'forcedEscape' => false,
            'withNamespaces' => true,
            'expected' => '<a>foo<b>bar</b>baz</a>',
        ];

        yield 'namespaces' => [
            'content' => '<a xmlns:g="urn:g"><g:b g:x="1">x</g:b></a>',
            'forcedEscape' => false,
            'withNamespaces' => true,
            'expected' => '<a xmlns:g="urn:g"><g:b g:x="1">x</g:b></a>',
        ];

        yield 'without namespaces' => [
            'content' => '<a xmlns:g="urn:g"><g:b g:x="1">x &amp; y</g:b></a>',
            'forcedEscape' => false,
            'withNamespaces' => false,
            'expected' => '<a><g:b g:x="1">x &amp; y</g:b></a>',
        ];

        yield 'forced escape' => [
            'content' => '<a>x<b>y &amp; z</b><c><![CDATA[w]]></c></a>',
            'forcedEscape' => true,
            'withNamespaces' => true,
            'expected' => '<a><![CDATA[x]]><b><![CDATA[y & z]]></b><c><![CDATA[w]]></c></a>',
        ];

        yield 'forced escape without namespaces' => [
            'content' => '<a xmlns:g="urn:g"><g:b>x</g:b></a>',
            'forcedEscape' => true,
            'withNamespaces' => false,
            'expected' => '<a><g:b><![CDATA[x]]></g:b></a>',
        ];
    }

    private function newDocument(?string $version = null, ?string $encoding = null): DefaultDocument
    {
        return new DefaultDocument(new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8'));
    }

    private function newDocumentFactory(): DefaultDocumentFactory
    {
        return new DefaultDocumentFactory(new DefaultDOMDocumentFactory());
    }
}
