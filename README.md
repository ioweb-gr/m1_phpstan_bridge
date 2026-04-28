# M1 PHPStan Bridge

Generate a PHPStan bridge from OpenMage / Magento 1 PhpStorm metadata produced by n98-magerun.

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
php bin/generate-stubs "/path/to/magento/root" --validate
```

The generator searches for `.phpstorm.meta.php` in common locations. You can also pass it explicitly:

```sh
php bin/generate-stubs "/path/to/magento/root" --meta-path="/path/to/.phpstorm.meta.php"
```

If metadata is missing, run:

```sh
n98-magerun dev:ide:phpstorm:meta
```

Generated files are written under:

```text
.m1_phpstan_bridge/
```

The old giant `mage.stub.php` strategy is not used. Factory/helper aliases are written as PHP array maps and resolved by PHPStan dynamic return type extensions.

Include the generated config in your PHPStan config:

```neon
includes:
    - .m1_phpstan_bridge/phpstan-magento.neon
```

## Behavior

- Parses `.php` files from n98-magerun PhpStorm metadata.
- Uses `nikic/php-parser` AST parsing only.
- Extracts `override(target, map([...]))` calls.
- Supports Magento factory maps for `Mage::getModel`, `Mage::helper`, `Mage::getResourceModel`, and `Mage::getSingleton`.
- Generates PHPStan map files under `.m1_phpstan_bridge/generated/`.
- Registers PHPStan dynamic return type extensions for Magento factory methods.
- Generates small structural stubs for common Magento and Varien base classes.
- Writes diagnostics under `.m1_phpstan_bridge/generated/diagnostics.json`.
- Ignores unsupported metadata constructs such as `type(...)`.
- Does not bootstrap Magento, parse XML, or evaluate runtime code.
