# M1 PHPStan Bridge

Generate PHPStan stub files from OpenMage PhpStorm meta files.

## Requirements

- PHP 8.2+
- Composer

The project includes `mise.toml` entries for PHP 8.2 on Linux and Windows.

## Install

```sh
composer install
```

## Usage

```sh
php bin/generate-stubs "/path/to/.phpstorm.meta.php"
```

By default this writes:

```text
.m1_phpstan_bridge/mage.stub.php
```

When the input directory is named `.phpstorm.meta.php`, the output is written
next to it in the target project root.

Use the generated stub in PHPStan:

```neon
parameters:
    stubFiles:
        - .m1_phpstan_bridge/mage.stub.php
```

## Behavior

- Parses `.php` files from a PhpStorm meta directory.
- Uses `nikic/php-parser` AST parsing only.
- Extracts `override(target, map([...]))` calls.
- Supports Magento factory maps for `Mage::getModel`, `Mage::helper`, `Mage::getResourceModel`, and `Mage::getSingleton`.
- Generates class method return stubs from supported method maps.
- Ignores unsupported constructs such as `type(...)`.
- Does not bootstrap Magento, parse XML, or evaluate runtime code.
