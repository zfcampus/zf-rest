# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.4.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.3 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.2 - 2016-10-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#103](https://github.com/zfcampus/zf-rest/pull/103) and
  [#105](https://github.com/zfcampus/zf-rest/pull/105) fix an issue with
  providing a `Throwable` in order to create an `ApiProblem` from within a
  `RestController`.

## 1.3.1 - 2016-07-12

### Added

- [#100](https://github.com/zfcampus/zf-rest/pull/100) adds configuration to the
  `composer.json` to allow zend-component-installer to auto-inject the
  `ZF\Rest` module into application configuration during installation.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.3.0 - 2016-07-12

### Added

- [#99](https://github.com/zfcampus/zf-rest/pull/99) adds support for v3
  releases of Zend Framework components, while retaining compatibility for v2
  releases.
- [#96](https://github.com/zfcampus/zf-rest/pull/96) adds a `Content-Location`
  header to responses returned from `RestController::create()`, per
  [RFC 7231](https://tools.ietf.org/html/rfc7231#section-3.1.4.2).

### Deprecated

- Nothing.

### Removed

- [#99](https://github.com/zfcampus/zf-rest/pull/99) removes support for PHP 5.5.

### Fixed

- [#70](https://github.com/zfcampus/zf-rest/pull/70) updates how the
  `RestController` retrieves the identifier from `ZF\Hal\Entity` instances to
  use the new `getId()` method introduced in zf-hal 1.4.
- [#94](https://github.com/zfcampus/zf-rest/pull/94) updates the
  `RestController` to return Problem Details with a status of 400 if the
  page size requested by the client is below zero.

## 1.2.1 - 2016-07-12

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#97](https://github.com/zfcampus/zf-rest/pull/97) fixes `Location` header
  generation in the `RestController::create()` method to only use the `href`
  property of the relational link; previously, if you'd defined additional
  properties, these were also incorrectly serialized in the generated link.
