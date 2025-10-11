# Yireo EnableModuleSequence

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
