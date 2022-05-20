# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [Unreleased](https://github.com/inspirum/xml-php/compare/v2.0.0...master)


## [v2.0.0 (2022-05-20)](https://github.com/inspirum/xml-php/compare/v1.0.1...v2.0.0)
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
- Factories for [XML builder](./src/Builder/Document.php) and [XML Reader](./src/Reader/Reader.php)
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
