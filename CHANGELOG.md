# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [Unreleased](https://github.com/inspirum/xml-php/compare/v3.1.1...master)


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
