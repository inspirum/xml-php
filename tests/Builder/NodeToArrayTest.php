<?php

declare(strict_types=1);

namespace Inspirum\XML\Tests\Builder;

use DOMDocument;
use Inspirum\XML\Builder\DefaultDocument;
use Inspirum\XML\Formatter\DefaultConfig;
use Inspirum\XML\Formatter\FlattenConfig;
use Inspirum\XML\Formatter\FullResponseConfig;
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
        $bE = $aE->addElement('b');
        $c1E = $bE->addTextElement('c1', 0);
        $c2E = $bE->addElement('c2');

        self::assertSame(
            [
                null,
            ],
            $c2E->toArray(),
        );

        self::assertSame(
            [
                '0',
            ],
            $c1E->toArray(),
        );

        self::assertSame(
            [
                'c1' => '0',
                'c2' => null,
            ],
            $bE->toArray(),
        );

        self::assertSame(
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
            $xml->toArray(),
        );

        self::assertSame(
            $xml->toArray(),
            $xml->__toArray(),
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

        self::assertSame(
            [
                'a' => [
                    'b' => [
                        0 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => 'true',
                                    'a' => '1',
                                ],
                                '@value' => '1',
                            ],
                            'c2' => 'true',
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => 'cc',
                                    'b' => '2',
                                ],
                                '@value' => '2',
                            ],
                        ],
                    ],
                    '@attributes' => [
                        'version' => '1.0',
                    ],
                ],
            ],
            $xml->toArray(),
        );
    }

    public function testNodeWithoutTextContent(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s' => 'stock.xsd',
        ]);
        $aE = $rssE->addTextElement('a', null, ['id' => 1]);

        self::assertSame(
            [
                '@attributes' => [
                    'id' => '1',
                ],
            ],
            $aE->toArray(),
        );
    }

    public function testEmptyNode(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s' => 'stock.xsd',
        ]);
        $aE = $rssE->addTextElement('a', '');

        self::assertSame(
            [
                null,
            ],
            $aE->toArray(),
        );
    }

    public function testWithNamespace(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            's:version' => '2.0',
            'xmlns:s' => 'stock.xsd',
        ]);
        $rssE->addTextElement('s:item', 1);
        $rssE->addTextElement('s:item', 2);
        $rssE->addTextElement('s:item', 3);

        self::assertSame(
            [
                'rss' => [
                    's:item' => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                    ],
                    '@attributes' => [
                        's:version' => '2.0',
                    ],
                ],
            ],
            $xml->toArray(),
        );
    }

    public function testWithNamespaces(): void
    {
        $xml = $this->newDocument();

        $rssE = $xml->addElement('rss', [
            'g:version' => '2.0',
            'xmlns:s' => 'stock.xsd',
            'xmlns:g' => 'global.xsd',
        ]);
        $rssE->addTextElement('s:item', 1);
        $rssE->addTextElement('s:item', 2);
        $rssE->addTextElement('s:item', 3);
        $rssE->addTextElement('g:item', 1);
        $rssE->addTextElement('s:item', 4);
        $rssE->addTextElement('g:item', 2);
        $rssE->addTextElement('g:item', 3);

        self::assertSame(
            [
                'rss' => [
                    's:item' => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                        3 => '4',
                    ],
                    'g:item' => [
                        0 => '1',
                        1 => '2',
                        2 => '3',
                    ],
                    '@attributes' => [
                        'g:version' => '2.0',
                    ],
                ],
            ],
            $xml->toArray(new DefaultConfig()),
        );
    }

    public function testWithAlwaysArrayConfigSimple(): void
    {
        $xml = $this->newDocument();

        $xml->addTextElement('a', 1);
        $xml->addTextElement('b', 2);
        $xml->addTextElement('c', 3);
        $xml->addTextElement('d', 4);

        self::assertSame(
            [
                'a' => '1',
                'b' => '2',
                'c' => ['3'],
                'd' => '4',
            ],
            $xml->toArray(new DefaultConfig(alwaysArray: ['c'])),
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

        self::assertSame(
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
                'd' => '1',
            ],
            $xml->toArray(new DefaultConfig(alwaysArray: ['c'])),
        );
    }

    public function testWithAlwaysArrayConfigAll(): void
    {
        $xml = $this->newDocument();

        $xml->addTextElement('a', 1);
        $xml->addTextElement('a', 2);
        $xml->addTextElement('b', 2);
        $xml->addTextElement('c', 3);
        $xml->addTextElement('d', 4);

        self::assertSame(
            [
                'a' => ['1', '2'],
                'b' => ['2'],
                'c' => ['3'],
                'd' => ['4'],
            ],
            $xml->toArray(new DefaultConfig(alwaysArray: true)),
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

        self::assertSame(
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
                    'd' => '1',
                ],
            ],
            $xml->toArray(new DefaultConfig(alwaysArray: ['b.d'])),
        );
    }

    public function testWithResponseNameConfig(): void
    {
        $xml = $this->newDocument();

        $xml->addTextElement('a', 1, ['test' => true]);
        $xml->addTextElement('a', 2, ['test' => true]);

        $config = new DefaultConfig(attributesName: '@attr', valueName: '@val');

        self::assertSame(
            [
                'a' => [
                    0 => [
                        '@attr' => [
                            'test' => 'true',
                        ],
                        '@val' => '1',
                    ],
                    1 => [
                        '@attr' => [
                            'test' => 'true',
                        ],
                        '@val' => '2',
                    ],
                ],
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFullResponseConfig(): void
    {
        $xml = $this->newDocument();

        $aE = $xml->addElement('a', ['test' => true, 'a' => 'b']);
        $aE->addTextElement('b', 'test');
        $xml->addTextElement('a', 2);
        $cE = $xml->addTextElement('c', 3);

        $config = new FullResponseConfig();

        self::assertSame(
            [
                '@attributes' => [],
                '@value' => '3',
                '@nodes' => [],
            ],
            $cE->toArray($config),
        );

        self::assertSame(
            [
                '@attributes' => [
                    'test' => 'true',
                    'a' => 'b',
                ],
                '@value' => null,
                '@nodes' => [
                    'b' => [
                        0 => [
                            '@attributes' => [],
                            '@value' => 'test',
                            '@nodes' => [],
                        ],
                    ],
                ],
            ],
            $aE->toArray($config),
        );

        self::assertSame(
            [
                '@attributes' => [],
                '@value' => null,
                '@nodes' => [
                    'a' => [
                        0 => [
                            '@attributes' => [
                                'test' => 'true',
                                'a' => 'b',
                            ],
                            '@value' => null,
                            '@nodes' => [
                                'b' => [
                                    0 => [
                                        '@attributes' => [],
                                        '@value' => 'test',
                                        '@nodes' => [],
                                    ],
                                ],
                            ],
                        ],
                        1 => [
                            '@attributes' => [],
                            '@value' => '2',
                            '@nodes' => [],
                        ],
                    ],
                    'c' => [
                        0 => [
                            '@attributes' => [],
                            '@value' => '3',
                            '@nodes' => [],
                        ],
                    ],
                ],
            ],
            $xml->toArray($config),
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

        $config = new DefaultConfig(autoCast: true);

        self::assertSame(
            [
                'a' => [
                    'b' => [
                        0 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => true,
                                    'a' => 1.4,
                                ],
                                '@value' => 1,
                            ],
                            'c2' => false,
                            'c3' => 'test',
                        ],
                        1 => [
                            'c1' => [
                                '@attributes' => [
                                    'test' => 'cc',
                                    'b' => 2,
                                ],
                                '@value' => 0,
                            ],
                        ],
                    ],
                    '@attributes' => [
                        'version' => 1.0,
                    ],
                ],
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFlattenConfig(): void
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

        $config = new FlattenConfig();

        self::assertSame(
            [
                'a/b/c1@test' => ['true', 'cc'],
                'a/b/c1@a' => '1.4',
                'a/b/c1' => ['1', '0'],
                'a/b/c2' => 'false',
                'a/b/c3' => 'test',
                'a/b/c1@b' => '2',
                'a@version' => '1.0',
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFlattenWithoutRootConfig(): void
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

        $config = new FlattenConfig(withoutRoot: true);

        self::assertSame(
            [
                'b/c1@test' => ['true', 'cc'],
                'b/c1@a' => '1.4',
                'b/c1' => ['1', '0'],
                'b/c2' => 'false',
                'b/c3' => 'test',
                'b/c1@b' => '2',
                '@version' => '1.0',
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFlattenAutocastConfig(): void
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

        $config = new FlattenConfig(autoCast: true);

        self::assertSame(
            [
                'a/b/c1@test' => [true, 'cc'],
                'a/b/c1@a' => 1.4,
                'a/b/c1' => [1, 0],
                'a/b/c2' => false,
                'a/b/c3' => 'test',
                'a/b/c1@b' => 2,
                'a@version' => 1.0,
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFlattenAlwaysArrayConfig(): void
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

        $config = new FlattenConfig(alwaysArray: true);

        self::assertSame(
            [
                'a/b/c1@test' => ['true', 'cc'],
                'a/b/c1@a' => ['1.4'],
                'a/b/c1' => ['1', '0'],
                'a/b/c2' => ['false'],
                'a/b/c3' => ['test'],
                'a/b/c1@b' => ['2'],
                'a@version' => ['1.0'],
            ],
            $xml->toArray($config),
        );
    }

    public function testWithFlattenCustomConfig(): void
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

        $config = new FlattenConfig(flattenNodes: '.', flattenAttributes: '#');

        self::assertSame(
            [
                'a.b.c1#test' => ['true', 'cc'],
                'a.b.c1#a' => '1.4',
                'a.b.c1' => ['1', '0'],
                'a.b.c2' => 'false',
                'a.b.c3' => 'test',
                'a.b.c1#b' => '2',
                'a#version' => '1.0',
            ],
            $xml->toArray($config),
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

        self::assertSame(
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
            $xml->jsonSerialize(),
        );

        self::assertSame(
            '{"a":{"b":[{"c1":"1","c2":"true","c3":"test"},{"c1":"0","c2":null}]}}',
            json_encode($xml),
        );
    }

    private function newDocument(?string $version = null, ?string $encoding = null): DefaultDocument
    {
        return new DefaultDocument(new DOMDocument($version ?? '1.0', $encoding ?? 'UTF-8'));
    }
}
