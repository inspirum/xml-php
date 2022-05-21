<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMDocument;
use Inspirum\XML\Builder\DefaultDocument;
use Inspirum\XML\Formatter\Config;
use Inspirum\XML\Tests\BaseTestCase;
use function json_encode;

class NodeToArrayTest extends BaseTestCase
{
    public function testSimpleValues(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $bE  = $aE->addElement('b');
        $c1E = $bE->addTextElement('c1', 0);
        $c2E = $bE->addElement('c2');

        $this->assertEquals(
            [
                null,
            ],
            $c2E->toArray()
        );

        $this->assertEquals(
            [
                '0',
            ],
            $c1E->toArray()
        );

        $this->assertEquals(
            [
                'c1' => '0',
                'c2' => null,
            ],
            $bE->toArray()
        );

        $this->assertEquals(
            [
                'a' => [
                    'b' => [
                        0 => [
                            'c1' => '1',
                            'c2' => 'true',
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => '0',
                            'c2' => null,
                        ],
                    ],
                ],
            ],
            $xml->toArray()
        );
    }

    public function testWithAttributes(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', [
            'version' => '1.0',
        ]);
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1, ['test' => true, 'a' => 1]);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 2, ['test' => 'cc', 'b' => 2]);

        $this->assertEquals(
            [
                'a' => [
                    '@attributes' => [
                        'version' => '1.0',
                    ],
                    'b'           => [
                        0 => [
                            'c1' => [
                                '@attributes' => [
                                    'a'    => '1',
                                    'test' => 'true',
                                ],
                                '@value'      => '1',
                            ],
                            'c2' => 'true',
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => 'cc',
                                    'b'    => '2',
                                ],
                                '@value'      => '2',
                            ],
                        ],
                    ],
                ],
            ],
            $xml->toArray()
        );
    }

    public function testNodeWithoutTextContent(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s'   => 'stock.xsd',
        ]);
        $aE   = $rssE->addTextElement('a', null, ['id' => 1]);

        $this->assertEquals(
            [
                '@attributes' => [
                    'id' => '1',
                ],
            ],
            $aE->toArray()
        );
    }

    public function testEmptyNode(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s'   => 'stock.xsd',
        ]);
        $aE   = $rssE->addTextElement('a', '');

        $this->assertEquals(
            [
                null,
            ],
            $aE->toArray()
        );
    }

    public function testWithNamespace(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s'   => 'stock.xsd',
        ]);
        $rssE->addTextElement('s:item', 1);
        $rssE->addTextElement('s:item', 2);
        $rssE->addTextElement('s:item', 3);

        $this->assertEquals(
            [
                'rss' => [
                    '@attributes' => [
                        's:version' => '2.0',
                    ],
                    's:item'      => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                    ],
                ],
            ],
            $xml->toArray()
        );
    }

    public function testWithNamespaces(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            'g:version' => '2.0',
            'xmlns:s'   => 'stock.xsd',
            'xmlns:g'   => 'global.xsd',
        ]);
        $rssE->addTextElement('s:item', 1);
        $rssE->addTextElement('s:item', 2);
        $rssE->addTextElement('s:item', 3);
        $rssE->addTextElement('g:item', 1);
        $rssE->addTextElement('s:item', 4);
        $rssE->addTextElement('g:item', 2);
        $rssE->addTextElement('g:item', 3);

        $this->assertEquals(
            [
                'rss' => [
                    '@attributes' => [
                        'g:version' => '2.0',
                    ],
                    's:item'      => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                        3 => '4',
                    ],
                    'g:item'      => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                    ],
                ],
            ],
            $xml->toArray(new Config())
        );
    }

    public function testWithAlwaysArrayConfig(): void
    {
        $xml = $this->newDocument();

        $xml->addTextElement('a', 1);
        $xml->addTextElement('a', 2);
        $xml->addTextElement('a', 3);
        $xml->addTextElement('b', 1);
        $xml->addTextElement('b', 2);
        $xml->addTextElement('c', 1);
        $xml->addTextElement('d', 1);

        $this->assertEquals(
            [
                'a' => [
                    0 => '1',
                    1 => '2',
                    2 => '3',
                ],
                'b' => [
                    0 => '1',
                    1 => '2',
                ],
                'c' => [
                    0 => '1',
                ],
                'd' => 1,
            ],
            $xml->toArray(new Config(['c']))
        );
    }

    public function testWithAlwaysArrayConfigWithSameNodeName(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $aE->addTextElement('d', 1);
        $aE->addTextElement('d', 2);
        $aE->addTextElement('d', 3);
        $bE = $xml->addElement('b');
        $bE->addTextElement('d', 1);
        $cE = $xml->addElement('c');
        $cE->addTextElement('d', 1);

        $this->assertEquals(
            [
                'a' => [
                    'd' => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                    ],
                ],
                'b' => [
                    'd' => [
                        0 => '1',
                    ],
                ],
                'c' => [
                    'd' => 1,
                ],
            ],
            $xml->toArray(new Config(['b.d']))
        );
    }

    public function testWithResponseNameConfig(): void
    {
        $xml = $this->newDocument();

        $xml->addTextElement('a', 1, ['test' => true]);
        $xml->addTextElement('a', 2, ['test' => true]);

        $config = new Config(attributesName: '@attr', valueName: '@val');

        $this->assertEquals(
            [
                'a' => [
                    0 => [
                        '@attr' => [
                            'test' => 'true',
                        ],
                        '@val'  => '1',
                    ],
                    1 => [
                        '@attr' => [
                            'test' => 'true',
                        ],
                        '@val'  => '2',
                    ],
                ],
            ],
            $xml->toArray($config)
        );
    }

    public function testWithFullResponseConfig(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', ['test' => true, 'a' => 'b']);
        $aE->addTextElement('b', 'test');
        $xml->addTextElement('a', 2);
        $cE = $xml->addTextElement('c', 3);

        $config = new Config(fullResponse: true);

        $this->assertEquals(
            [
                '@attributes' => [],
                '@value'      => '3',
                '@nodes'      => [],
            ],
            $cE->toArray($config)
        );

        $this->assertEquals(
            [
                '@attributes' => [
                    'test' => 'true',
                    'a'    => 'b',
                ],
                '@value'      => null,
                '@nodes'      => [
                    'b' => [
                        0 => [
                            '@attributes' => [],
                            '@value'      => 'test',
                            '@nodes'      => [],
                        ],
                    ],
                ],
            ],
            $aE->toArray($config)
        );

        $this->assertEquals(
            [
                '@attributes' => [],
                '@value'      => null,
                '@nodes'      => [
                    'a' => [
                        0 => [
                            '@attributes' => [
                                'test' => 'true',
                                'a'    => 'b',
                            ],
                            '@value'      => null,
                            '@nodes'      => [
                                'b' => [
                                    0 => [
                                        '@attributes' => [],
                                        '@value'      => 'test',
                                        '@nodes'      => [],
                                    ],
                                ],
                            ],
                        ],
                        1 => [
                            '@attributes' => [],
                            '@value'      => '2',
                            '@nodes'      => [],
                        ],
                    ],
                    'c' => [
                        0 => [
                            '@attributes' => [],
                            '@value'      => '3',
                            '@nodes'      => [],
                        ],
                    ],
                ],
            ],
            $xml->toArray($config)
        );
    }

    public function testWithAutoCastConfig(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', [
            'version' => '1.0',
        ]);
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1, ['test' => true, 'a' => 1.4]);
        $bE->addTextElement('c2', false);
        $bE->addTextElement('c3', 'test');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 0, ['test' => 'cc', 'b' => 2]);

        $config = new Config(autoCast: true);

        $this->assertEquals(
            [
                'a' => [
                    '@attributes' => [
                        'version' => 1.0,
                    ],
                    'b'           => [
                        0 => [
                            'c1' => [
                                '@attributes' => [
                                    'a'    => 1.4,
                                    'test' => true,
                                ],
                                '@value'      => 1,
                            ],
                            'c2' => false,
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => 'cc',
                                    'b'    => 2,
                                ],
                                '@value'      => 0,
                            ],
                        ],
                    ],
                ],
            ],
            $xml->toArray($config)
        );
    }

    public function testJsonSerialize(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 1);
        $bE->addTextElement('c2', true);
        $bE->addTextElement('c3', 'test');
        $bE = $aE->addElement('b');
        $bE->addTextElement('c1', 0);
        $bE->addElement('c2');

        $this->assertEquals(
            [
                'a' => [
                    'b' => [
                        0 => [
                            'c1' => '1',
                            'c2' => 'true',
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => '0',
                            'c2' => null,
                        ],
                    ],
                ],
            ],
            $xml->jsonSerialize()
        );

        $this->assertEquals(
            '{"a":{"b":[{"c1":"1","c2":"true","c3":"test"},{"c1":"0","c2":null}]}}',
            json_encode($xml)
        );
    }

    private function newDocument(?string $version = null, ?string $encoding = null): DefaultDocument
    {
        return new DefaultDocument(new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8'));
    }
}
