# XML reader / writer

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
/** @var Inspirum\XML\Builder\DocumentFactory $factory */

$locale       = 'cs';
$currencyCode = 'CZK';

$xml = $factory->create();
$rss = $xml->addElement('rss', [
    'version' => '2.0',
    'xmlns:g' => 'http://base.google.com/ns/1.0',
]);

$channel = $rss->addElement('channel');
$channel->addTextElement('title', 'Google Merchant');
$channel->addTextElement('link', 'https://www.example.com');
$channel->addTextElement('description', 'Google Merchant products feed');
$channel->addTextElement('language', $locale);
$channel->addTextElement('lastBuildDate', (new \DateTime())->format('D, d M y H:i:s O'));
$channel->addTextElement('generator', 'Eshop');

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

/**
var_dump($xml->toString(true));

<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Google Merchant</title>
    <link>https://www.example.com</link>
    <description>Google Merchant products feed</description>
    <language>cs</language>
    <lastBuildDate>Sat, 14 Nov 20 08:00:00 +0200</lastBuildDate>
    <generator>Eshop</generator>
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
/** @var \Inspirum\XML\Reader\ReaderFactory $factory */

$reader = $factory->create('/output/feeds/google.xml');

$title = $reader->nextNode('title')->getTextContent();

/**
var_dump($title);
'Google Merchant'
*/

$lastBuildDate = $reader->nextNode('lastBuildDate')->getTextContent();

/**
var_dump($lastBuildDate);
'2020-08-25T13:53:38+00:00'
*/

$price = 0.0;
foreach ($reader->iterateNode('item') as $item) {
    $data = $item->toArray();
    $price += (float) $data['g:price'];
}

/**
var_dump($price);
501.98
*/
```

Splitting data to XML fragments by xpath (with valid namespaces)

```php
/** @var \Inspirum\XML\Reader\ReaderFactory $factory */
$reader = $factory->create('/output/feeds/google.xml');

foreach ($reader->iterateNode('/rss/channel/item', true) as $item) {
    $data = $item->toString();
    $id = ($item->xpath('/item/g:id')[0] ?? null)?->getTextContent();
    // ...
}
```


## System requirements

* [PHP 8.2+](http://php.net/releases/8_2_0.php)
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
"inspirum/xml": "^4.0"
```

## Usage

Available framework integrations:

- [Symfony](https://github.com/inspirum/xml-php-symfony)

But you can also use it without any framework implementation:

```php
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Reader\DefaultReaderFactory;
use Inspirum\XML\Reader\DefaultXMLReaderFactory;

$documentFactory = new DefaultDocumentFactory(new DefaultDOMDocumentFactory());
$document = $documentFactory->create();
// ...

$readerFactory = new DefaultReaderFactory(new DefaultXMLReaderFactory(), $documentFactory);
$reader = $readerFactory->create('/path/to/file.xml');
// ...
```


#### XML Writer

Optionally you can specify XML version and encoding (defaults to UTF-8).

```php
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;

$factory = new DefaultDocumentFactory(new DefaultDOMDocumentFactory());

$xml = $factory->create('1.0', 'WINDOWS-1250');
/**
<?xml version="1.0" encoding="WINDOWS-1250"?>
*/

$xml = $factory->create();
/**
<?xml version="1.0" encoding="UTF-8"?>
*/
```

Nesting elements
```php
$a = $xml->addElement('a');
$a->addTextElement('b', 'BB', ['id' => 1]);
$b = $a->addElement('b', ['id' => 2]);
$b->addTextElement('c', 'CC');

/**
<?xml version="1.0" encoding="UTF-8"?>
<a>
  <b id="1">BB</b>
  <b id="2">
    <c>CC</c>
  </b>
</a>
*/
```

Used as fluent builder
```php
$xml->addElement('root')->addElement('a')->addElement('b', ['id' => 1])->addTextElement('c', 'CC');

/**
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <a>
    <b id="1">
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

/**
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

/**
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

/**
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

Invalid XML fragment throws `\DOMException`
```php
try {
    $a->addXMLData('<b>');
} catch (\DOMException $exception) {
    // DOMDocumentFragment::appendXML(): Entity: line 1: parser error : Premature end of data in tag b line 1
}
```

Copying nodes from another document keeps their namespaces, comments and mixed content, `forcedEscape` converts all their text to CDATA
```php
$source = $factory->createForContent('<a xmlns:g="stock.xsd"><!-- note --><g:b>me &amp; you</g:b></a>');

$xml->addElementFromNode($source->getDocument()->documentElement);
/**
<a xmlns:g="stock.xsd"><!-- note --><g:b>me &amp; you</g:b></a>
*/

$xml->addElementFromNode($source->getDocument()->documentElement, forcedEscape: true);
/**
<a xmlns:g="stock.xsd"><!-- note --><g:b><![CDATA[me & you]]></g:b></a>
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

/**
<?xml version="1.0" encoding="UTF-8"?>
<g:root xmlns:g="stock.xsd" g:version="2.0">
  <g:items>
     <g:item>1</g:item>
     <g:item>2</g:item>
     <g:item>3</g:item>
  </g:items>
</g:root>
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
/**
'2020-08-25T13:53:38+00:00'
*/

$node->toString();
/**
<g:updated>2020-08-25T13:53:38+00:00</g:updated>
*/
```

Read next node with its namespace declarations
```php
$node = $reader->nextNode('g:item', true);

$node->toString();
/**
<g:item xmlns:g="stock.xsd" active="true" price="99.9"><g:id>1</g:id><g:name>Test 1</g:name></g:item>
*/
```

Powerful cast to array method
```php
$data = $reader->nextNode('g:items')->toArray();

/**
var_dump($data);
[
  'g:item' => [
    0 => [
      'g:id'        => '1'
      'g:name'      => 'Test 1'
      '@attributes' => [
        'active' => 'true'
        'price'  => '99.9'
      ]
    ]
    1 => [
      'g:id'        => '3'
      'g:name'      => 'Test 3'
      '@attributes' => [
        'active' => 'false'
        'price'  => '0'
      ]
    ]
  ]
  'item' => [
    0 => [
      'g:id'        => '2'
      'g:name'      => 'Test 2'
      '@attributes' => [
        'active' => 'true'
        'price'  => '19.9'
      ]
    ]
  ]
]
*/
```


Optional config supported for `toArray` method

```php
use Inspirum\XML\Builder\DefaultDocumentFactory;
use Inspirum\XML\Builder\DefaultDOMDocumentFactory;
use Inspirum\XML\Formatter\FullResponseConfig;

$factory = new DefaultDocumentFactory(new DefaultDOMDocumentFactory());

$config = new FullResponseConfig(
    attributesName: '@attr', 
    valueName: '@val',
    autoCast: true,
);

$data = $factory->createForFile('/sample.xml')->toArray($config);

/**
var_dump($data);
[
  '@attr'  => []
  '@val'   => null
  '@nodes' => [
    'g:feed' => [
      0 => [
        '@attr'  => [
          'g:version' => 2.0
        ]
        '@val'   => null
        '@nodes' => [
          'g:updated' => [
            0 => [
              '@attr'  => []
              '@val'   => '2020-08-25T13:53:38+00:00'
              '@nodes' => []
            ]
          ]
          'title' => [
            0 => [
              '@attr'  => []
              '@val'   => null
              '@nodes' => []
            ]
          ]
          'g:items' => [
            0 => [
              '@attr'  => []
              '@val'   => null
              '@nodes' => [
                'g:item' => [
                  0 => [
                    '@attr'  => [
                      'active' => true
                      'price'  => 99.9
                    ]
                    '@val'   => null
                    '@nodes' => [
                      'g:id' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 1
                          '@nodes' => []
                        ]
                      ]
                      'g:name' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 'Test 1'
                          '@nodes' => []
                        ]
                      ]
                    ]
                  ]
                  1 => [
                    '@attr'  => [
                      'active' => false
                      'price'  => 0
                    ]
                    '@val'   => null
                    '@nodes' => 
                    [
                      'g:id' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 3
                          '@nodes' => []
                        ]
                      ]
                      'g:name' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 'Test 3'
                          '@nodes' => []
                        ]
                      ]
                    ]
                  ]
                ]
                'item' => [
                  0 => [
                    '@attr'  => [
                      'active' => true
                      'price'  => 19.9
                    ]
                    '@val'   => null
                    '@nodes' => [
                      'g:id' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 2
                          '@nodes' => []
                        ]
                      ]
                      'g:name' => [
                        0 => [
                          '@attr'  => []
                          '@val'   => 'Test 2'
                          '@nodes' => []
                        ]
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ]
    ]
  ]
]
*/
```

`autoCast` casts only values that survive the cast unchanged: integers, decimal numbers and `true` / `false`
```php
use Inspirum\XML\Formatter\DefaultConfig;

$data = $factory->createForContent('<a><zip>0123</zip><id>12</id><price>1.50</price><e>1e3</e><b>true</b></a>')->toArray(new DefaultConfig(autoCast: true));

/**
var_dump($data);
[
  'a' => [
    'zip'   => '0123'
    'id'    => 12
    'price' => 1.5
    'e'     => '1e3'
    'b'     => true
  ]
]
*/
```

Iterate all nodes with given name

```php
$ids = [];
foreach ($reader->iterateNode('item') as $item) {
    $ids[] = $item->toArray()['id'];
}

/**
var_dump($ids);
[
  0 => '1'
  1 => '3'
]
*/
```

Splitting data to XML fragments (with valid namespaces)


```php
$items = [];
foreach ($reader->iterateNode('g:item', true) as $item) {
    $items[] = $item->toString();
}

/**
var_dump($items);
[
  0 => '<g:item xmlns:g="stock.xsd" active="true" price="99.9"><g:id>1</g:id><g:name>Test 1</g:name></g:item>'
  1 => '<g:item xmlns:g="stock.xsd" active="false" price="0"><g:id>3</g:id><g:name>Test 3</g:name></g:item>'
]
*/
```

#### XPath

Querying document or node returns list of copied nodes
```php
use Inspirum\XML\Builder\Node;

$ids = array_map(
    static fn (Node $node): string => $node->getTextContent(),
    $factory->createForFile('/sample.xml')->xpath('/g:feed/g:items/g:item/g:id'),
);

/**
var_dump($ids);
[
  0 => '1'
  1 => '3'
]
*/
```

#### Errors

DOM errors throw `\DOMException`, reader errors throw [`ReaderException`](./src/Exception/ReaderException.php)
```php
use Inspirum\XML\Exception\ReaderException;

try {
    $document->xpath('/g:feed[');
} catch (\DOMException $exception) {
    // DOMXPath::query(): Invalid expression
}

try {
    $reader = $readerFactory->create('/missing.xml');
} catch (ReaderException $exception) {
    // XMLReader::open(): Unable to open source data
}
```

#### Limitations

- Reader joins all text of an element with mixed content before its child elements, so `<a>foo<b/>bar</a>` is read as `<a>foobar<b/></a>`
- Copied nodes can repeat a namespace declaration on their root element, as [`DOMNode::appendChild`](https://www.php.net/manual/domnode.appendchild.php) does


### All available methods

- [`Inspirum\XML\Builder\DocumentFactory`](./src/Builder/DocumentFactory.php)
- [`Inspirum\XML\Builder\Document`](./src/Builder/Document.php)
- [`Inspirum\XML\Builder\Node`](./src/Builder/Node.php)
- [`Inspirum\XML\Reader\ReaderFactory`](./src/Reader/ReaderFactory.php)
- [`Inspirum\XML\Reader\Reader`](./src/Reader/Reader.php)


## Upgrading from 3.x

- `Node::xpath()` returns a list and throws `\DOMException` for an invalid expression instead of a warning and `null`
- `Node::addXMLData()` throws `\DOMException` for invalid XML instead of a warning
- `Node::getTextContent()` returns `string`
- `Reader` and `ReaderFactory` throw `ReaderException` instead of `\Exception`
- `Reader::nextNode()` has `bool $withNamespaces = false` parameter, update your own `Reader` implementations
- `autoCast` keeps values like `0123`, `1e3` or integers over `PHP_INT_MAX` as strings
- `FlattenConfig(withoutRoot: true)` on a node keeps its child names in keys
- `DocumentFactory::createForFile()` and `createForContent()` apply given version and encoding
- `inspirum/arrayable` 2.0 is supported next to 1.3

See [CHANGELOG][link-changelog] for all changes.


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
[ico-workflow]:             https://img.shields.io/github/actions/workflow/status/inspirum/xml-php/test.yml?branch=main&style=flat-square
[ico-scrutinizer]:          https://img.shields.io/scrutinizer/coverage/g/inspirum/xml-php/main.svg?style=flat-square
[ico-code-quality]:         https://img.shields.io/scrutinizer/g/inspirum/xml-php.svg?style=flat-square
[ico-packagist-stable]:     https://img.shields.io/packagist/v/inspirum/xml.svg?style=flat-square&colorB=blue
[ico-packagist-download]:   https://img.shields.io/packagist/dt/inspirum/xml.svg?style=flat-square&colorB=blue
[ico-phpstan]:              https://img.shields.io/badge/style-level%2010-brightgreen.svg?style=flat-square&label=phpstan

[link-author]:              https://github.com/inspirum
[link-contributors]:        https://github.com/inspirum/xml-php/contributors
[link-licence]:             ./LICENSE.md
[link-changelog]:           ./CHANGELOG.md
[link-contributing]:        https://github.com/inspirum/.github/blob/main/docs/CONTRIBUTING.md
[link-code-of-conduct]:     https://github.com/inspirum/.github/blob/main/docs/CODE_OF_CONDUCT.md
[link-workflow]:            https://github.com/inspirum/xml-php/actions
[link-scrutinizer]:         https://scrutinizer-ci.com/g/inspirum/xml-php/code-structure
[link-code-quality]:        https://scrutinizer-ci.com/g/inspirum/xml-php
[link-packagist-stable]:    https://packagist.org/packages/inspirum/xml
[link-packagist-download]:  https://packagist.org/packages/inspirum/xml/stats
[link-phpstan]:             https://github.com/phpstan/phpstan
