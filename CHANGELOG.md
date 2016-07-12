# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.2.1 - TBD

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
