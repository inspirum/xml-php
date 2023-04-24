<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Reader;

use Exception;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Reader\DefaultReaderFactory;
use Inspirum\XML\Reader\DefaultXMLReaderFactory;
use Inspirum\XML\Reader\Reader;
use Inspirum\XML\Reader\XMLReaderFactory;
use Inspirum\XML\Tests\BaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SimpleXMLElement;
use Throwable;
use ValueError;
use function array_map;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_quote;
use function simplexml_load_string;
use function sprintf;

class DefaultReaderTest extends BaseTestCase
{
    public function testEmptyFilepath(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Argument #1 ($uri) cannot be empty');

        $this->newReader('');
    }

    public function testNonExistingFilepath(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Unable to open source data');

        $this->newReader('wrong.xml');
    }

    public function testNextInvalidNode(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('test');

        self::assertNull($node);
    }

    public function testNextNode(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('updated');

        self::assertSame('2020-08-25T13:53:38+00:00', $node?->getTextContent());
    }

    public function testPreviousNode(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('title');

        self::assertSame('Test feed', $node?->getTextContent());

        $node = $reader->nextNode('updated');
        self::assertNull($node);
    }

    public function testParseError(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = $this->newReader(self::getTestFilePath('sample_06.xml'));

        $reader->nextNode('a');
    }

    public function testParseErrorWithGenerator(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = $this->newReader(self::getTestFilePath('sample_06.xml'));

        foreach ($reader->iterateNode('a') as $a) {
            $a->toString();
        }
    }

    public function testReadAllFile(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('feed');

        self::assertSame(
            '<feed version="2.0"><updated>2020-08-25T13:53:38+00:00</updated><title>Test feed</title><errors id="1"/><errors2 id="2"/><items><item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item><item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item><item i="2"><id>3</id><name price="500">Test 3</name></item><item i="3"><id uuid="894654">4</id><name>Test 4</name></item><item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item></items></feed>',
            $node?->toString(),
        );
    }

    public function testNextEmptyContentNode(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors');

        self::assertSame('', $node?->getTextContent());
        self::assertSame('<errors id="1"/>', $node->toString());
    }

    public function testNextEmptyNode(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors2');

        self::assertSame('', $node?->getTextContent());
        self::assertSame('<errors2 id="2"/>', $node->toString());
    }

    public function testIterateInvalidNodes(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $items = $reader->iterateNode('item2');

        $output = [];
        foreach ($items as $item) {
            $output[] = $item->toString();
        }

        self::assertSame([], $output);
    }

    public function testIterateNodes(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $items = $reader->iterateNode('item');

        $output = [];
        foreach ($items as $item) {
            $output[] = $item->toString();
        }

        self::assertSame(
            [
                '<item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item>',
                '<item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item>',
                '<item i="2"><id>3</id><name price="500">Test 3</name></item>',
                '<item i="3"><id uuid="894654">4</id><name>Test 4</name></item>',
                '<item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item>',
            ],
            $output,
        );
    }

    public function testIterateNamespacedNodes(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_05.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $output[] = $item->toString();
        }

        self::assertSame(
            [
                '<g:item><g:id>1</g:id><g:name g:price="10.1">Test 1</g:name></g:item>',
                '<g:item><g:id>2</g:id><g:name>Test 2</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name g:price="0.99">Test 3</g:name></g:item>',
                '<g:item><g:id>4</g:id><g:name>Test 4</g:name></g:item>',
                '<g:item><g:id>5</g:id><g:name g:price="500">Test 5</g:name></g:item>',
                '<g:item><g:id>6</g:id><g:name>Test 6</g:name></g:item>',
            ],
            $output,
        );
    }

    public function testIterateMixedNamespacedAndLocalNodes(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $output[] = $item->toString();
        }

        self::assertSame(
            [
                '<g:item><g:id>1</g:id><g:name>Test 1</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name>Test 3</g:name></g:item>',
            ],
            $output,
        );
    }

    public function testIterateMixedLocalAndNamespacedNodes(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('item') as $item) {
            $output[] = $item->toString();
        }

        self::assertSame(
            [
                '<item><g:id>2</g:id><g:name>Test 2</g:name></item>',
            ],
            $output,
        );
    }

    public function testIterateMultipleNamespaces(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_08.xml'));

        $output = [];
        foreach ($reader->iterateNode('item', true) as $item) {
            $output[] = $item->toString();
        }

        self::assertSame(
            [
                '<item attr="2"><id>1/L1</id><title>Title 1</title></item>',
                '<item xmlns:g="http://base.google.com/ns/1.0" xmlns:h="http://base.google.com/ns/2.0" attr="1"><data><g:id h:test="asd">1/L2</g:id><g:title test="bb">Title 2</g:title><link>https://www.example.com/v/1</link></data></item>',
                '<item xmlns:g="http://base.google.com/ns/1.0"><g:id>1/L3</g:id><title>Title 3</title></item>',
            ],
            $output,
        );
    }

    /**
     * @param array<array<string>|string> $expected
     */
    #[DataProvider('provideIterateWihSimpleLoadString')]
    public function testIterateWihSimpleLoadString(string $file, bool $withNamespaces, string $path, array $expected): void
    {
        $reader = $this->newReader(self::getTestFilePath($file));

        foreach ($reader->iterateNode('item', $withNamespaces) as $i => $item) {
            try {
                self::withErrorHandler(static function () use ($i, $item, $path, $expected): void {
                    $xml = simplexml_load_string($item->toString());
                    if ($xml === false) {
                        throw new Exception('simplexml_load_string: error');
                    }

                    $elements = $xml->xpath($path);
                    if ($elements === false || $elements === null) {
                        throw new Exception('xpath: error');
                    }

                    self::assertSame($expected[$i], array_map(static fn(SimpleXMLElement $element): string => (string) $element, $elements));
                });
            } catch (Throwable $exception) {
                $expectedMessage = $expected[$i];
                if (is_string($expectedMessage)) {
                    self::assertMatchesRegularExpression(sprintf('/%s/', preg_quote($expectedMessage)), $exception->getMessage());
                } else {
                    self::fail();
                }
            }
        }
    }

    /**
     * @return iterable<array<string,mixed>>
     */
    public static function provideIterateWihSimpleLoadString(): iterable
    {
        yield [
            'file' => 'sample_04.xml',
            'withNamespaces' => false,
            'path'           => '/item/id',
            'expected'       => [
                ['1'],
                ['2'],
                ['3'],
                ['4'],
                ['5'],
            ],
        ];

        yield [
            'file' => 'sample_08.xml',
            'withNamespaces' => false,
            'path'           => '/item/id',
            'expected'       => [
                'simplexml_load_string(): namespace error',
                'simplexml_load_string(): namespace error',
                'simplexml_load_string(): namespace error',
            ],
        ];

        yield [
            'file' => 'sample_08.xml',
            'withNamespaces' => false,
            'path'           => '/item/g:id',
            'expected'       => [
                'SimpleXMLElement::xpath(): Undefined namespace prefix',
                'simplexml_load_string(): namespace error',
                'simplexml_load_string(): namespace error',
            ],
        ];

        yield [
            'file' => 'sample_08.xml',
            'withNamespaces' => true,
            'path'           => '/item/id',
            'expected'       => [
                ['1/L1'],
                [],
                [],
            ],
        ];

        yield [
            'file' => 'sample_08.xml',
            'withNamespaces' => true,
            'path'           => '/item/g:id',
            'expected'       => [
                'SimpleXMLElement::xpath(): Undefined namespace prefix',
                [],
                ['1/L3'],
            ],
        ];

        yield [
            'file' => 'sample_08.xml',
            'withNamespaces' => true,
            'path'           => '/item/data/g:title',
            'expected'       => [
                'SimpleXMLElement::xpath(): Undefined namespace prefix',
                ['Title 2'],
                [],
            ],
        ];
    }

    public function testRealUsage(): void
    {
        $reader = $this->newReader(self::getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('updated');
        self::assertSame('2020-08-25T13:53:38+00:00', $node?->getTextContent());

        $node = $reader->nextNode('title');
        self::assertSame('Test feed', $node?->getTextContent());

        $price = null;

        foreach ($reader->iterateNode('item') as $item) {
            $data = $item->toArray();
            if (
                is_array($data['@attributes'])
                && is_numeric($data['@attributes']['i'])
                && (int) $data['@attributes']['i'] === 4
                && is_array($data['name'])
                && is_array($data['name']['@attributes'])
                && is_numeric($data['name']['@attributes']['price'])
            ) {
                $price = (float) $data['name']['@attributes']['price'];
            }
        }

        self::assertSame(0.99, $price);
    }

    private function newReader(
        string $filepath,
        ?string $version = null,
        ?string $encoding = null,
        ?XMLReaderFactory $readerFactory = null,
    ): Reader {
        $readerFactory = new DefaultReaderFactory(
            $readerFactory ?? new DefaultXMLReaderFactory(),
            new DefaultDocumentFactory(new DefaultDOMDocumentFactory()),
        );

        return $readerFactory->create($filepath, $version, $encoding);
    }
}
