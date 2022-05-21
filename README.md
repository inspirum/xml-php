# XML reader / writer

**Created as part of [inspishop][link-inspishop] e-commerce platform by [inspirum][link-inspirum] team.**

[![Latest Stable Version][ico-packagist-stable]][link-packagist-stable]
[![Build Status][ico-workflow]][link-workflow]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![PHPStan][ico-phpstan]][link-phpstan]
[![Total Downloads][ico-packagist-download]][link-packagist-download]
[![Software License][ico-license]][link-licence]

Simple XML fluent writer and memory efficient XML reader.

- Fluent builder build over [Document Object Model](https://www.php.net/manual/en/book.dom.php) with automatic CDATA escaping, namespace support and other features
- Utilises [XMLReader](https://www.php.net/manual/en/book.xmlreader.php) and [Generator](https://www.php.net/manual/en/language.generators.overview.php) for memory efficient reading of large files
- The entire code is covered by unit tests

## Usage example

*All the code snippets shown here are modified for clarity, so they may not be executable.*

#### XML Writer

Writing Google Merchant XML feed file

```php
use Inspirum\XML\Builder\DocumentFactory;

function generateFeed(DocumentFactory $factory): void
{
    $locale       = 'cs';
    $currencyCode = 'CZK';
    
    $xml = $factory->create();
    $rss = $xml->addElement('rss', [
        'version' => '2.0',
        'xmlns:g' => 'http://base.google.com/ns/1.0',
    ]);
    
    $channel = $rss->addElement('channel');
    $channel->addTextElement('title', 'Google Merchant');
    $channel->addTextElement('link', 'https://www.inspishop.cz');
    $channel->addTextElement('description', 'Google Merchant products feed');
    $channel->addTextElement('language', $locale);
    $channel->addTextElement('lastBuildDate', (new \DateTime())->format('D, d M y H:i:s O'));
    $channel->addTextElement('generator', 'Inspishop');
    
    foreach ($products as $product) {
        $item = $xml->createElement('item');
        $item->addTextElement('g:id', $product->getId());
        $item->addTextElement('title', $product->getName($locale));
        $item->addTextElement('link', $product->getUrl());
        $item->addTextElement('description', \strip_tags($product->getDescription($locale)));
        $item->addTextElement('g:image_link', $product->getImageUrl());
        foreach ($product->getAdditionalImageUrls() as $imageUrl) {
            $item->addTextElement('g:additional_image_link', $imageUrl);
        }
        $price = $product->getPrice($currencyCode);
        $item->addTextElement('g:price', $price->getOriginalPriceWithVat() . ' ' . $currencyCode);
        if ($price->inDiscount()) {
            $item->addTextElement('g:sale_price', $price->getPriceWithVat() . ' ' . $currencyCode);
        }
        if ($product->hasEAN()) {
            $item->addTextElement('g:gtin', $product->getEAN());
        } else {
            $item->addTextElement('g:identifier_exists', 'no');
        }
        $item->addTextElement('g:condition', 'new');
        if ($product->inStock()) {
            $item->addTextElement('g:availability', 'in stock');
        } elseif ($product->hasPreorder()) {
            $item->addTextElement('g:availability', 'preorder');
            $item->addTextElement('g:availability_date', $product->getDeliveryDate());
        } else {
            $item->addTextElement('g:availability', 'out of stock');
        }
        $item->addTextElement('g:brand', $product->getBrand());
        $item->addTextElement('g:size', $product->getParameterValue('size', $locale));
        $item->addTextElement('g:color', $product->getParameterValue('color', $locale));
        $item->addTextElement('g:material', $product->getParameterValue('material', $locale));
        if ($product->isVariant()) {
            $item->addTextElement('g:item_group_id', $product->getParentProductId()());
        }
        if ($product->getCustomAttribute('google_category') !== null) {
            $item->addTextElement('g:google_product_category', $product->getCustomAttribute('google_category'));
        } elseif ($product->getMainCategory() !== null) {
            $item->addTextElement('g:product_type', $product->getMainCategory()->getFullname($locale));
        }
    }
    
    $xml->validate('/google_feed.xsd');
    
    $xml->save('/output/feeds/google.xml');
}

/*
var_dump($xml->toString(true));

<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Google Merchant</title>
    <link>https://www.inspishop.cz</link>
    <description>Google Merchant products feed</description>
    <language>cs</language>
    <lastBuildDate>Sat, 14 Nov 20 08:00:00 +0200</lastBuildDate>
    <generator>Inspishop</generator>
    <item>
      <g:id>0001</g:id>
      <title><![CDATA[Sample products #1 A&B]]></title>
      <link>http://localhost/produkt/sample-product-1-a-b</link>
      <description>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</description>
      <g:image_link>http://localhost/images/no_image.webp</g:image_link>
      <g:price>19.99 CZK</g:price>
      <g:gtin>7220110003812</g:gtin>
      <g:condition>new</g:condition>
      <g:availability>in stock</g:availability>
      <g:brand>Co.</g:brand>
    </item>
    ...
  </channel>
</rss>
*/
```

#### XML Reader

Reading data from Google Merchant XML feed

```php
use Inspirum\XML\Reader\ReaderFactory;

function calculateTotalPrice(ReaderFactory $factory): float
{
    $reader = $factory->create('/output/feeds/google.xml');
    
    $title = $reader->nextNode('title')->getTextContent();
    
    /*
    var_dump($title);
    'Google Merchant'
    */
    
    $lastBuildDate = $reader->nextNode('lastBuildDate')->getTextContent();
    
    /*
    var_dump($lastBuildDate);
    '2020-08-25T13:53:38+00:00'
    */
    
    $price = 0.0;
    foreach ($reader->iterateNode('item') as $item) {
        $data = $item->toArray();
        $price += (float) $data['g:price'];
    }
    
    /*
    var_dump($price);
    501.98
    */
    
    return $price;
}
```


## System requirements

* [PHP 8.1+](http://php.net/releases/8_1_0.php)
* [ext-dom](http://php.net/dom)
* [ext-json](http://php.net/json)
* [ext-xmlreader](http://php.net/xmlreader)


## Installation

Run composer require command
```bash
$ composer require inspirum/xml
```
or add requirement to your `composer.json`
```json
"inspirum/xml": "^2.0"
```

## Usage

#### XML Writer

Optionally you can specify XML version and encoding (defaults to UTF-8).

```php
use Inspirum\XML\Builder\DefaultDocumentFactory;

$factory = new DefaultDocumentFactory()

$xml = $factory->create('1.0', 'WINDOWS-1250');
/*
<?xml version="1.0" encoding="WINDOWS-1250"?>
*/

$xml = $factory->create();
/*
<?xml version="1.0" encoding="UTF-8"?>
*/
```

Nesting elements
```php
$a = $xml->addElement('a');
$a->addTextElement('b', 'BB', ['id' => 1]);
$b = $a->addElement('b', ['id' => 2]);
$b->addTextElement('c', 'CC');

/*
<?xml version="1.0" encoding="UTF-8"?>
<a>
  <b id="1">BB</a>
  <b id="2">
    <c>CC</c>
  </b>
</a>
*/
```

Used as fluent builder
```php
$xml->addElement('root')->addElement('a')->addElement('b', ['id' => 1])->addTextElement('c', 'CC');

/*
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <a>
    <b id="2">
      <c>CC</c>
    </b>
  </a>
</root>
*/
```

Automatic CDATA escaping
```php
$a = $xml->addElement('a');
$a->addTextElement('b', 'me & you');
$a->addTextElement('b', '30&nbsp;km');

/*
<?xml version="1.0" encoding="UTF-8"?>
<a>
  <b>
     <![CDATA[me & you]]>
  </b>
  <b>
    <![CDATA[30&nbsp;km]]>
  </b>
</a>
*/
```

Forced CDATA escaping
```php
$a = $xml->addElement('a');
$a->addTextElement('b', 'me');
$a->addTextElement('b', 'you', forcedEscape: true);

/*
<?xml version="1.0" encoding="UTF-8"?>
<a>
  <b>me</b>
  <b>
    <![CDATA[you]]>
  </b>
</a>
*/
```

Adding XML fragments
```php
$a = $xml->addElement('a');
$a->addXMLData('<b><c>CC</c></b><b>0</b>');
$a->addTextElement('b', '1');

/*
<?xml version="1.0" encoding="UTF-8"?>
<a>
  <b>
    <c>CC</c>
  </b>
  <b>0</b>
  <b>1</b>
</a>
*/
```

To use automatic namespace usage you only have to set `xmlns:{prefix}` attribute on (usually) root element.

Elements (or/and attributes) use given prefix as `{prefix}:{localName}`, and it will be created with [`createElementNS`](https://php.net/manual/domdocument.createelementns.php) or [`createAttributeNS`](https://php.net/manual/domdocument.createattributens.php) method.
```php
$root = $xml->addElement('g:root', ['xmlns:g' =>'stock.xsd', 'g:version' => '2.0']);
$items = $root->addElement('g:items');
$items->addTextElement('g:item', 1);
$items->addTextElement('g:item', 2);
$items->addTextElement('g:item', 3);

/*
<?xml version="1.0" encoding="UTF-8"?>
<g:root xmlns:g="stock.xsd" g:version="2.0">
  <g:items>
     <g:item>1</g:item>
     <g:item>2</g:item>
     <g:item>3</g:item>
  </a>
</root>
*/
```

Namespace support its necessary for XML validation with XSD schema

```php
try {
    $xml->validate('/sample.xsd');
    // valid XML
} catch (\DOMException $exception) {
    // invalid XML
}
```

#### XML Reader

> /sample.xml
```xml
<?xml version="1.0" encoding="utf-8"?>
<g:feed xmlns:g="stock.xsd" g:version="2.0">
    <g:updated>2020-08-25T13:53:38+00:00</g:updated>
    <title></title>
    <g:items>
        <g:item active="true" price="99.9">
            <g:id>1</g:id>
            <g:name>Test 1</g:name>
        </g:item>
        <item active="true" price="19.9">
            <g:id>2</g:id>
            <g:name>Test 2</g:name>
        </item>
        <g:item active="false" price="0">
            <g:id>3</g:id>
            <g:name>Test 3</g:name>
        </g:item>
    </g:items>
</g:feed>
```
Reading XML files into [**Node**](./src/Builder/Node.php) instances

Read next node with given name
```php
$node = $reader->nextNode('g:updated');

$node->getTextContent();
/*
'2020-08-25T13:53:38+00:00'
*/

$node->toString();
/*
<g:updated>2020-08-25T13:53:38+00:00</g:updated>
*/
```

Powerful cast to array method
```php
$data = $reader->nextNode('g:items')->toArray();

/*
var_dump($ids);
[
  'g:item' => [
    0 => [
      'g:id'        => '1'
      'g:name'      => 'Test 1',
      '@attributes' => [
        'active' => 'true',
        'price'  => '99.9',
      ],
    ],
    1 => [
      'g:id'        => '3'
      'g:name'      => 'Test 3',
      '@attributes' => [
        'active' => 'false',
        'price'  => '0',
      ],
    ]
  ],
  'item' => [
    0 => [
      'g:id'        => '2'
      'g:name'      => 'Test 2',
      '@attributes' => [
        'active' => 'true',
        'price'  => '19.9',
      ],
    ],
  ],
]
*/
```


Optional config supported for `toArray` method

```php
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Formatter\Config;

$factory = new DefaultDocumentFactory()

$config = new Config(
    attributesName: '@attr', 
    valueName: '@val',
    fullResponse: true, 
    autoCast: true,
);

$data = $factory->createForFile('/sample.xml')->toArray($config);

/*
var_dump($ids);
[
  '@attr'  => [],
  '@val'   => null,
  '@nodes' => [
    'g:feed' => [
      0 => [
        '@attr'  => [
          'g:version' => 2.0,
        ],
        '@val'   => null,
        '@nodes' => [
          'g:updated' => [
            0 => [
              '@attr'  => [],
              '@val'   => '2020-08-25T13:53:38+00:00',
              '@nodes' => [],
            ],
          ],
          'title' => [
            0 => [
              '@attr'  => [],
              '@val'   => null,
              '@nodes' => [],
            ],
          ],
          'g:items' => [
            0 => [
              '@attr'  => [],
              '@val'   => null,
              '@nodes' => [
                'g:item' => [
                  0 => [
                    '@attr'  => [
                      'active' => true,
                      'price'  => 99.9,
                    ],
                    '@val'   => null,
                    '@nodes' => [
                      'g:id' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 1,
                          '@nodes' => [],
                        ],
                      ],
                      'g:name' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 'Test 1',
                          '@nodes' => [],
                        ],
                      ],
                    ],
                  ],
                  1 => [
                    '@attr'  => [
                      'active' => false,
                      'price'  => 0,
                    ],
                    '@val'   => null,
                    '@nodes' => 
                    [
                      'g:id' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 3,
                          '@nodes' => [],
                        ],
                      ],
                      'g:name' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 'Test 3',
                          '@nodes' => [],
                        ],
                      ],
                    ],
                  ],
                ],
                'item' => [
                  0 => [
                    '@attr'  => [
                      'active' => true,
                      'price'  => 19.9,
                    ],
                    '@val'   => null,
                    '@nodes' => [
                      'g:id' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 2,
                          '@nodes' => [],
                        ],
                      ],
                      'g:name' => [
                        0 => [
                          '@attr'  => [],
                          '@val'   => 'Test 2',
                          '@nodes' => [],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
]
*/
```

Iterate all nodes with given name
```php
$ids = [];
foreach ($reader->iterateNode('item') as $item) {
    $ids[] = $item->toArray()['id'];
}

/*
var_dump($ids);
[
  0 => '1',
  1 => '3',
]
*/
```


### All available methods

- [`Inspirum\XML\Builder\DocumentFactory`](./src/Builder/DocumentFactory.php)
- [`Inspirum\XML\Builder\Document`](./src/Builder/Document.php)
- [`Inspirum\XML\Builder\Node`](./src/Builder/Node.php)
- [`Inspirum\XML\Reader\ReaderFactory`](./src/Reader/ReaderFactory.php)
- [`Inspirum\XML\Reader\Reader`](./src/Reader/Reader.php)


## Testing

To run unit tests, run:

```bash
$ composer test:test
```

To show coverage, run:

```bash
$ composer test:coverage
```


## Contributing

Please see [CONTRIBUTING][link-contributing] and [CODE_OF_CONDUCT][link-code-of-conduct] for details.


## Security

If you discover any security related issues, please email tomas.novotny@inspirum.cz instead of using the issue tracker.


## Credits

- [Tomáš Novotný](https://github.com/tomas-novotny)
- [All Contributors][link-contributors]


## License

The MIT License (MIT). Please see [License File][link-licence] for more information.


[ico-license]:              https://img.shields.io/github/license/inspirum/xml-php.svg?style=flat-square&colorB=blue
[ico-workflow]:             https://img.shields.io/github/workflow/status/inspirum/xml-php/Test/master?style=flat-square
[ico-scrutinizer]:          https://img.shields.io/scrutinizer/coverage/g/inspirum/xml-php/master.svg?style=flat-square
[ico-code-quality]:         https://img.shields.io/scrutinizer/g/inspirum/xml-php.svg?style=flat-square
[ico-packagist-stable]:     https://img.shields.io/packagist/v/inspirum/xml.svg?style=flat-square&colorB=blue
[ico-packagist-download]:   https://img.shields.io/packagist/dt/inspirum/xml.svg?style=flat-square&colorB=blue
[ico-phpstan]:              https://img.shields.io/badge/style-level%208-brightgreen.svg?style=flat-square&label=phpstan

[link-author]:              https://github.com/inspirum
[link-contributors]:        https://github.com/inspirum/xml-php/contributors
[link-licence]:             ./LICENSE.md
[link-changelog]:           ./CHANGELOG.md
[link-contributing]:        ./docs/CONTRIBUTING.md
[link-code-of-conduct]:     ./docs/CODE_OF_CONDUCT.md
[link-workflow]:            https://github.com/inspirum/xml-php/actions
[link-scrutinizer]:         https://scrutinizer-ci.com/g/inspirum/xml-php/code-structure
[link-code-quality]:        https://scrutinizer-ci.com/g/inspirum/xml-php
[link-inspishop]:           https://www.inspishop.cz/
[link-inspirum]:            https://www.inspirum.cz/
[link-packagist-stable]:    https://packagist.org/packages/inspirum/xml
[link-packagist-download]:  https://packagist.org/packages/inspirum/xml
[link-phpstan]:             https://github.com/phpstan/phpstan
