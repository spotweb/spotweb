# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.2.0 - 2019-01-22

### Added

- [zendframework/zendxml#6](https://github.com/zendframework/zendxml/pull/6) adds the following method:
 
  ```php
  Security::scanHtml(
      string $html,
      DOMDocument $dom = null,
      int $libXmlConstants = 0
  ) : SimpleXMLElement|DOMDocument|bool
  ```
  
  This method allows scanning markup known to be HTML, versus assuming the
  markup is generic XML.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.1.1 - 2019-01-22

### Added

- [zendframework/zendxml#16](https://github.com/zendframework/ZendXml/pull/16) adds support for PHP 7.3.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zendxml#17](https://github.com/zendframework/ZendXml/pull/17) properly enables heuristic security checks for PHP 5.6.0 - 5.6.5 when PHP
  is running as PHP-FPM.

## 1.1.0 - 2018-04-30

### Added

- [zendframework/zendxml#13](https://github.com/zendframework/ZendXml/pull/13) adds support for PHP 7.1 and 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zendxml#13](https://github.com/zendframework/ZendXml/pull/13) removes support for PHP 5.3, 5.4, and 5.5.

- [zendframework/zendxml#13](https://github.com/zendframework/ZendXml/pull/13) removes support for HHVM.

### Fixed

- Nothing.

## 1.0.2 - 2016-02-04

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zendxml#11](https://github.com/zendframework/ZendXml/pull/11) updates the
  dependencies to PHP `^5.3.3 || ^7.0` and PHPUnit `^3.7 || ^4.0`, ensuring
  better compatibility with other components, and with PHP 7. The test matrix
  was also expanded to add PHP 7 as a required platform.
