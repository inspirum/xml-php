<?php

namespace Inspirum\XML\Tests\Unit;

use Exception;
use Generator;
use Inspirum\XML\Services\XMLNode;
use Inspirum\XML\Services\XMLReader;
use Inspirum\XML\Tests\AbstractTestCase;
use ValueError;

class ReaderTest extends AbstractTestCase
{
    public function testEmptyFilepath()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Empty string supplied');
        } else {
            $this->expectException(ValueError::class);
            $this->expectExceptionMessage('Argument #1 ($uri) cannot be empty');
        }

        new XMLReader('');
    }

    public function testNonExistingFilepath()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to open source data');

        new XMLReader('wrong.xml');
    }

    public function testNextInvalidNode()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('test');

        $this->assertNull($node);
    }

    public function testNextNode()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('updated');

        $this->assertInstanceOf(XMLNode::class, $node);
        $this->assertEquals('2020-08-25T13:53:38+00:00', $node->getTextContent());
    }

    public function testPreviousNode()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('title');

        $this->assertInstanceOf(XMLNode::class, $node);
        $this->assertEquals('Test feed', $node->getTextContent());

        $node = $reader->nextNode('updated');
        $this->assertNull($node);
    }

    public function testParseError()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = new XMLReader($this->getSampleFilepath('sample_06.xml'));

        $reader->nextNode('a');
    }

    public function testParseErrorWithGenerator()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('XMLReader::read()');

        $reader = new XMLReader($this->getSampleFilepath('sample_06.xml'));

        foreach ($reader->iterateNode('a') as $a) {
            $a->toString();
        }
    }

    public function testReadAllFile()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('feed');

        $this->assertInstanceOf(XMLNode::class, $node);
        $this->assertEquals(
            '<feed version="2.0"><updated>2020-08-25T13:53:38+00:00</updated><title>Test feed</title><errors id="1"/><errors2/><items><item i="0"><id uuid="12345">1</id><name price="10.1">Test 1</name></item><item i="1"><id uuid="61648">2</id><name price="5">Test 2</name></item><item i="2"><id>3</id><name price="500">Test 3</name></item><item i="3"><id uuid="894654">4</id><name>Test 4</name></item><item i="4"><id uuid="78954">5</id><name price="0.99">Test 5</name></item></items></feed>',
            $node->toString()
        );
    }

    public function testNextEmptyContentNode()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('errors');

        $this->assertInstanceOf(XMLNode::class, $node);
        $this->assertEquals('', $node->getTextContent());
        $this->assertEquals('<errors id="1"/>', $node->toString());
    }

    public function testNextEmptyNode()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('errors2');

        $this->assertInstanceOf(XMLNode::class, $node);
        $this->assertEquals('', $node->getTextContent());
        $this->assertEquals('<errors2 id="2"/>', $node->toString());
    }

    public function testIterateInvalidNodes()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $items = $reader->iterateNode('item2');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($items as $item) {
            $output[] = $item->toString();
        }

        $this->assertEquals([], $output);
    }

    public function testIterateNodes()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $items = $reader->iterateNode('item');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($items as $item) {
            $this->assertInstanceOf(XMLNode::class, $item);
            $output[] = $item->toString();
        }

        $this->assertEquals(
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

    public function testIterateNamespacedNodes()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_05.xml'));

        $items = $reader->iterateNode('g:item');

        $this->assertInstanceOf(Generator::class, $items);

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $this->assertInstanceOf(XMLNode::class, $item);
            $output[] = $item->toString();
        }

        $this->assertEquals(
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

    public function testIterateMixedNamespacedAndLocalNodes()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('g:item') as $item) {
            $this->assertInstanceOf(XMLNode::class, $item);
            $output[] = $item->toString();
        }

        $this->assertEquals(
            [
                '<g:item><g:id>1</g:id><g:name>Test 1</g:name></g:item>',
                '<g:item><g:id>3</g:id><g:name>Test 3</g:name></g:item>',
            ],
            $output
        );
    }

    public function testIterateMixedLocalAndNamespacedNodes()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_07.xml'));

        $output = [];
        foreach ($reader->iterateNode('item') as $item) {
            $this->assertInstanceOf(XMLNode::class, $item);
            $output[] = $item->toString();
        }

        $this->assertEquals(
            [
                '<item><g:id>2</g:id><g:name>Test 2</g:name></item>',
            ],
            $output
        );
    }

    public function testRealUsage()
    {
        $reader = new XMLReader($this->getSampleFilepath('sample_04.xml'));

        $node = $reader->nextNode('updated');
        $this->assertEquals('2020-08-25T13:53:38+00:00', $node->getTextContent());

        $node = $reader->nextNode('title');
        $this->assertEquals('Test feed', $node->getTextContent());

        $price = null;
        foreach ($reader->iterateNode('item') as $item) {
            $data = $item->toArray();
            if ($data['@attributes']['i'] == 4) {
                $price = (float) $data['name']['@attributes']['price'];
            }
        }
        $this->assertEquals(0.99, $price);
    }
}
