# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.1.2 - 2019-10-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-json#46](https://github.com/zendframework/zend-json/pull/46) changes
  curly braces in array and string offset access to square brackets
  in order to prevent issues under the upcoming PHP 7.4 release.

- [zendframework/zend-json#37](https://github.com/zendframework/zend-json/pull/37) fixes
  output of `\Laminas\Json::prettyPrint` to not remove spaces after
  commas in value.

## 3.1.1 - 2019-06-18

### Added

- [zendframework/zend-json#44](https://github.com/zendframework/zend-json/pull/44) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.1.0 - 2018-01-04

### Added

- [zendframework/zend-json#35](https://github.com/zendframework/zend-json/pull/35) and
  [zendframework/zend-json#39](https://github.com/zendframework/zend-json/pull/39) add support for PHP
  7.1 and PHP 7.2.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-json#35](https://github.com/zendframework/zend-json/pull/35) removes support for
  PHP 5.5.

- [zendframework/zend-json#35](https://github.com/zendframework/zend-json/pull/35) removes support for
  HHVM.

### Fixed

- [zendframework/zend-json#38](https://github.com/zendframework/zend-json/pull/38) provides a fix to
  `Json::prettyPrint()` to ensure that empty arrays and objects are printed
  without newlines.

- [zendframework/zend-json#38](https://github.com/zendframework/zend-json/pull/38) provides a fix to
  `Json::prettyPrint()` to remove additional newlines preceding a closing
  bracket.

## 3.0.0 - 2016-03-31

### Added

- [zendframework/zend-json#21](https://github.com/zendframework/zend-json/pull/21) adds documentation
  and publishes it to https://docs.laminas.dev/laminas-json/

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-json#20](https://github.com/zendframework/zend-json/pull/20) removes the
  `Laminas\Json\Server` subcomponent, which has been extracted to
  [laminas-json-server](https://docs.laminas.dev/laminas-json-server/).
  If you use that functionality, install the new component.
- [zendframework/zend-json#21](https://github.com/zendframework/zend-json/pull/21) removes the
  `Laminas\Json\Json::fromXml()` functionality, which has been extracted to
  [laminas-xml2json](https://docs.laminas.dev/laminas-xml2json/). If you used
  this functionality, you will need to install the new package, and rewrite
  calls to `Laminas\Json\Json::fromXml()` to `Laminas\Xml2Json\Xml2Json::fromXml()`.
- [zendframework/zend-json#20](https://github.com/zendframework/zend-json/pull/20) and
  [zendframework/zend-json#21](https://github.com/zendframework/zend-json/pull/21) removes dependencies
  on laminas/laminas-xml, laminas/laminas-stdlib,
  zendframework/zend-server, and zendframework-zend-http, due to the above
  listed component extractions.

### Fixed

- Nothing.

## 2.6.1 - 2016-02-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-json#18](https://github.com/zendframework/zend-json/pull/18) updates dependencies
  to allow usage on PHP 7, as well as with laminas-stdlib v3.

## 2.6.0 - 2015-11-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-json#5](https://github.com/zendframework/zend-json/pull/5) removes
  zendframework/zend-stdlib as a required dependency, marking it instead
  optional, as it is only used for the `Server` subcomponent.

### Fixed

- Nothing.

## 2.5.2 - 2015-08-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-json#3](https://github.com/zendframework/zend-json/pull/3) fixes an array key
  name from `intent` to `indent` to  ensure indentation works correctly during
  pretty printing.
