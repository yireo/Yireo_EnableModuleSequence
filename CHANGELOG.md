# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Resolve the module sequence recursively, so dependencies of dependencies are enabled as well

### Fixed
- Skip modules without a readable `etc/module.xml` instead of failing
- Skip sequence modules that are not installed, instead of failing with "Unknown module(s)"

## [1.0.2] - 05 June 2026
### Fixed
- Allow running command with one or more modules

## [1.0.1] - 22 October 2025
### Fixed
- Update README
- Rename command from module:enable:sequence to module:sequence
- Return empty if no module is found

## [1.0.0] - 25 September 2025
### Added
- Initial release
