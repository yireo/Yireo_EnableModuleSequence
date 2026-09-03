# Yireo EnableModuleSequence

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-enable-module-sequence)
<!-- badges.specs.end -->


**When running the Magento 2 command `bin/magento module:enable` for a given module, the dependencies of that module (as declared in the `sequence` of its `etc/module.xml` file) is not enabled. This is a good thing, because not always does the sequence reflect all neccessary dependencies. But if so, this module gives you a command to enable all dependencies at once.**

### Installation
```bash
composer require yireo/magento2-enable-module-sequence
bin/magento module:enable Yireo_EnableModuleSequence
```

### Usage
```bash
bin/magento module:sequence Yireo_Example
```

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_EnableModuleSequence/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_EnableModuleSequence/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_EnableModuleSequence/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_EnableModuleSequence/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_EnableModuleSequence/compile.yml?label=compile)
<!-- badges.test.end -->
