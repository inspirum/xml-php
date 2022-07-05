<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Reader;

use Generator;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Builder\Node;
use Inspirum\XML\Reader\DefaultReaderFactory;
use Inspirum\XML\Reader\DefaultXMLReaderFactory;
use Inspirum\XML\Reader\Reader;
use Inspirum\XML\Reader\XMLReaderFactory;
use Inspirum\XML\Tests\BaseTestCase;
use Throwable;
use ValueError;
use function is_array;
use function is_numeric;

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
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('test');

        $this->assertNull($node);
    }

    public function testNextNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('updated');

        $this->assertSame('2020-08-25T13:53:38+00:00', $node?->getTextContent());
    }

    public function testPreviousNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('title');

        $this->assertSame('Test feed', $node?->getTextContent());

        $node = $reader->nextNode('updated');
        $this->assertNull($node);
    }

    public function testParseError(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = $this->newReader($this->getTestFilePath('sample_06.xml'));

        $reader->nextNode('a');
    }

    public function testParseErrorWithGenerator(): void
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = $this->newReader($this->getTestFilePath('sample_06.xml'));

        foreach ($reader->iterateNode('a') as $a) {
            $a->toString();
        }
    }

    public function testReadAllFile(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('feed');

        $this->assertSame(
            '<feed version="2.0"><updated>2020-08-25T13:53:38+00:00</updated><title>Test feed</title><errors id="1"/><errors2/><items><item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item><item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item><item i="2"><id>3</id><name price="500">Test 3</name></item><item i="3"><id uuid="894654">4</id><name>Test 4</name></item><item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item></items></feed>',
            $node?->toString()
        );
    }

    public function testNextEmptyContentNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors');

        $this->assertSame('', $node?->getTextContent());
        $this->assertSame('<errors id="1"/>', $node?->toString());
    }

    public function testNextEmptyNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors2');

        $this->assertSame('', $node?->getTextContent());
        $this->assertSame('<errors2 id="2"/>', $node?->toString());
    }

    public function testIterateInvalidNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $items = $reader->iterateNode('item2');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($items as $item) {
            $output[] = $item->toString();
        }

        $this->assertSame([], $output);
    }

    public function testIterateNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $items = $reader->iterateNode('item');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($items as $item) {
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item>',
                '<item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item>',
                '<item i="2"><id>3</id><name price="500">Test 3</name></item>',
                '<item i="3"><id uuid="894654">4</id><name>Test 4</name></item>',
                '<item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item>',
            ],
            $output
        );
    }

    public function testIterateNamespacedNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_05.xml'));

        $items = $reader->iterateNode('g:item');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $this->assertInstanceOf(Node::class, $item);
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<g:item><g:id>1</g:id><g:name g:price="10.1">Test 1</g:name></g:item>',
                '<g:item><g:id>2</g:id><g:name>Test 2</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name g:price="0.99">Test 3</g:name></g:item>',
                '<g:item><g:id>4</g:id><g:name>Test 4</g:name></g:item>',
                '<g:item><g:id>5</g:id><g:name g:price="500">Test 5</g:name></g:item>',
                '<g:item><g:id>6</g:id><g:name>Test 6</g:name></g:item>',
            ],
            $output
        );
    }

    public function testIterateMixedNamespacedAndLocalNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $this->assertInstanceOf(Node::class, $item);
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<g:item><g:id>1</g:id><g:name>Test 1</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name>Test 3</g:name></g:item>',
            ],
            $output
        );
    }

    public function testIterateMixedLocalAndNamespacedNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('item') as $item) {
            $this->assertInstanceOf(Node::class, $item);
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<item><g:id>2</g:id><g:name>Test 2</g:name></item>',
            ],
            $output
        );
    }

    public function testRealUsage(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('updated');
        $this->assertSame('2020-08-25T13:53:38+00:00', $node?->getTextContent());

        $node = $reader->nextNode('title');
        $this->assertSame('Test feed', $node?->getTextContent());

        $price = null;
        /** @var \Inspirum\XML\Builder\Node $item */
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

        $this->assertSame(0.99, $price);
    }

    private function newReader(
        string $filepath,
        ?string $version = null,
        ?string $encoding = null,
        ?XMLReaderFactory $readerFactory = null
    ): Reader {
        $readerFactory = new DefaultReaderFactory(
            $readerFactory ?? new DefaultXMLReaderFactory(),
            new DefaultDocumentFactory(new DefaultDOMDocumentFactory()),
        );

        return $readerFactory->create($filepath, $version, $encoding);
    }
}
