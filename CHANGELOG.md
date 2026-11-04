# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [Unreleased](https://github.com/inspirum/xml-php/compare/v3.2.0...main)
### Changed
- Threw `\DOMException` instead of emitting warnings for invalid `Node::addXMLData` content
- Cast only lossless numbers with `autoCast`, keeping values like `0123`, `1e3` or integers over `PHP_INT_MAX` as strings
- Copied `Node::addElementFromNode` and `Node::createElementFromNode` with `\DOMDocument::importNode`, converting text to CDATA only with `forcedEscape`
- Switched CI to the shared [inspirum/.github](https://github.com/inspirum/.github) workflow
- Renamed the default branch from `master` to `main`

### Removed
- Removed local issue and pull request templates, code of conduct and contributing guide in favour of the shared [inspirum/.github](https://github.com/inspirum/.github) defaults

### Fixed
- Fixed element name validation for non-ASCII names
- Fixed `FlattenConfig` with `withoutRoot` on a node
- Fixed `DocumentFactory::createForFile` and `DocumentFactory::createForContent` ignoring version and encoding
- Fixed `Node::addElementFromNode` and `Node::createElementFromNode` losing comments, processing instructions, mixed content order and namespace declarations
- Fixed `Node::toArray` including comments and processing instructions as keys
- Fixed `Reader` keeping only the last text of an element with mixed content
- Fixed `Node::toString` and `Document::save` leaving `formatOutput` changed on the document


## [v3.2.0 (2026-11-04)](https://github.com/inspirum/xml-php/compare/v3.1.2...v3.2.0)
### Changed
- Skip `\DOMNameSpaceNode` in `Node::xpath` method
- Threw `\DOMException` instead of `\ValueError` for empty `Document::validate` and `Document::save` filename and `DocumentFactory::createForContent` content
- Queried `Document::xpath` directly instead of a serialized copy of the document
- Copied `Node::xpath` results as DOM nodes, so they keep CDATA sections, mixed content and attribute or text node types

### Fixed
- Fixed `Node::xpath` on a node returned by `Node::xpath` of a namespaced document
- Fixed `Node::xpath` for `text()` expressions
- Fixed `.gitattributes` export-ignore paths so `phpcs.xml` and `phpstan.neon` are not shipped in the package


## [v3.1.2 (2025-03-24)](https://github.com/inspirum/xml-php/compare/v3.1.1...v3.1.2)
### Fixed
- Fix `Reader::nextNode` method


## [v3.1.1 (2025-03-21)](https://github.com/inspirum/xml-php/compare/v3.1.0...v3.1.1)
### Fixed
- Fixed regex for element name validation


## [v3.1.0 (2023-11-05)](https://github.com/inspirum/xml-php/compare/v3.0.0...v3.1.0)
### Fixed
- Fixed [`Node::xpath`](./src/Builder/Node.php) method for node with child nodes

### Added
- Added optional parameter **withoutRoot** to [`FlattenConfig`](./src/Formatter/FlattenConfig.php) to cast node to flatten (one-dimensional) array without root element node name


## [v3.0.0 (2023-10-17)](https://github.com/inspirum/xml-php/compare/v2.3.1...v3.0.0)
### Changed
- Support only **PHP 8.2+**
- Make [`Config`](./src/Formatter/Config.php) interface instead of readonly class

### Added
- Added [`Node::xpath`](./src/Builder/Node.php) method using internally [`\DOMXPath`](https://www.php.net/manual/en/class.domxpath.php)
- Added option to cast node to flatten (one-dimensional) array
  - Added [`DefaultConfig`](./src/Formatter/DefaultConfig.php) config class
  - Added [`FullResponseConfig`](./src/Formatter/FullResponseConfig.php) config class
  - Added [`FlattenConfig`](./src/Formatter/FlattenConfig.php) config class
- Added option to create or add node from `\DOMNode`


## [v2.3.1 (2023-08-08)](https://github.com/inspirum/xml-php/compare/v2.3.0...v2.3.1)
### Fixed
- Update readme


## [v2.3.0 (2023-04-27)](https://github.com/inspirum/xml-php/compare/v2.2.0...v2.3.0)
### Added
- Added option for [`Reader`](./src/Reader/Reader.php) to get/iterate nodes by its xpath.


## [v2.2.0 (2023-04-24)](https://github.com/inspirum/xml-php/compare/v2.1.0...v2.2.0)
### Added
- Added support for **PHP 8.2**
- Added optional parameter **withNamespaces** to [`Reader::iterateNode()`](./src/Reader/Reader.php) to split into XML fragments with valid namespaces


## [v2.1.0 (2022-07-04)](https://github.com/inspirum/xml-php/compare/v2.0.0...v2.1.0)
### Added
- Implement `\Arrayable` interface


## [v2.0.0 (2022-05-21)](https://github.com/inspirum/xml-php/compare/v1.0.1...v2.0.0)
### Changed
- Support only **PHP 8.1+**
- Major refactor of all services
- Readonly [`Config`](./src/Formatter/Config.php) without getters and setters

### Added
- Interfaces for most classes
  - [`Inspirum\XML\Builder\DocumentFactory`](./src/Builder/DocumentFactory.php)
  - [`Inspirum\XML\Builder\Document`](./src/Builder/Document.php)
  - [`Inspirum\XML\Builder\Node`](./src/Builder/Node.php)
  - [`Inspirum\XML\Reader\ReaderFactory`](./src/Reader/ReaderFactory.php)
  - [`Inspirum\XML\Reader\Reader`](./src/Reader/Reader.php)
- Factories for [**XML builder**](./src/Builder/Document.php) and [**XML Reader**](./src/Reader/Reader.php)
- Publicly available [`Formatter::nodeToArray`](./src/Formatter/Formatter.php) method


## [v1.0.1 (2020-01-18)](https://github.com/inspirum/xml-php/compare/v1.0.0...v1.0.1)
### Fixed
- Support "_" in elements name


## v1.0.0 (2021-01-13) 
### Added
- Added XML builder
  - Fluent builder support
  - Automatic (or forced) CDATA escaping
  - Option to add XML fragments
  - Automatic namespace usage  
  - XML validation with XSD schema
- Added memory efficient XML reader 
  - Reading XML files into [**Node**](./src/Builder/DefaultNode.php) instances
  - Powerful cast to array method
  - Iterate all nodes with given name
