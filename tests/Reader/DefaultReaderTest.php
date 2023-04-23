<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Reader;

use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Builder\DefaultDocumentFactory;
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
            '<feed version="2.0"><updated>2020-08-25T13:53:38+00:00</updated><title>Test feed</title><errors id="1"/><errors2 id="2"/><items><item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item><item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item><item i="2"><id>3</id><name price="500">Test 3</name></item><item i="3"><id uuid="894654">4</id><name>Test 4</name></item><item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item></items></feed>',
            $node?->toString(),
        );
    }

    public function testNextEmptyContentNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors');

        $this->assertSame('', $node?->getTextContent());
        $this->assertSame('<errors id="1"/>', $node->toString());
    }

    public function testNextEmptyNode(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $node = $reader->nextNode('errors2');

        $this->assertSame('', $node?->getTextContent());
        $this->assertSame('<errors2 id="2"/>', $node->toString());
    }

    public function testIterateInvalidNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_04.xml'));

        $items = $reader->iterateNode('item2');

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
            $output,
        );
    }

    public function testIterateNamespacedNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_05.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
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
            $output,
        );
    }

    public function testIterateMixedNamespacedAndLocalNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<g:item><g:id>1</g:id><g:name>Test 1</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name>Test 3</g:name></g:item>',
            ],
            $output,
        );
    }

    public function testIterateMixedLocalAndNamespacedNodes(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('item') as $item) {
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<item><g:id>2</g:id><g:name>Test 2</g:name></item>',
            ],
            $output,
        );
    }

    public function testMultipleNamespaces(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_08.xml'));

        $item = $reader->nextNode('item', true);

        $this->assertSame(
            '<item xmlns="http://www.w3.org/2005/Atom" xmlns:b="http://base.google.com/ns/1.0" xmlns:g="http://base.google.com/ns/1.0" attr="2"><id>1/L1</id><title>Title 1</title></item>',
            $item?->toString(),
        );
    }

    public function testIterateMultipleNamespaces(): void
    {
        $reader = $this->newReader($this->getTestFilePath('sample_08.xml'));

        $output = [];
        foreach ($reader->iterateNode('item', true) as $item) {
            $output[] = $item->toString();
        }

        $this->assertSame(
            [
                '<item xmlns="http://www.w3.org/2005/Atom" xmlns:b="http://base.google.com/ns/1.0" xmlns:g="http://base.google.com/ns/1.0" attr="2"><id>1/L1</id><title>Title 1</title></item>',
                '<item xmlns="http://www.w3.org/2005/Atom" xmlns:b="http://base.google.com/ns/1.0" xmlns:g="http://base.google.com/ns/1.0" xmlns:h="http://base.google.com/ns/2.0" attr="1"><data><g:id h:test="asd">1/L2</g:id><g:title test="bb">Title 2</g:title><link>https://www.example.com/v/1</link></data></item>',
                '<item xmlns="http://www.w3.org/2005/Atom" xmlns:b="http://base.google.com/ns/1.0" xmlns:g="http://base.google.com/ns/1.0"><g:id>1/L3</g:id><title>Title 3</title></item>',
            ],
            $output,
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
        ?XMLReaderFactory $readerFactory = null,
    ): Reader {
        $readerFactory = new DefaultReaderFactory(
            $readerFactory ?? new DefaultXMLReaderFactory(),
            new DefaultDocumentFactory(new DefaultDOMDocumentFactory()),
        );

        return $readerFactory->create($filepath, $version, $encoding);
    }
}
