# M1 PHPStan Bridge

Generate a lightweight PHPStan bridge for Magento 1 / OpenMage from
n98-magerun PhpStorm metadata.

The package does not bootstrap Magento and does not evaluate Magento config. It
consumes already-generated `.phpstorm.meta.php` files and converts them into
PHPStan map files, dynamic return type extensions, structural stubs, and a
classmap autoloader.

## Requirements

- PHP 8.2+
- Composer
- PHPStan in the target Magento project
- n98-magerun metadata generated in the target Magento project

The repository includes `mise.toml` entries for PHP 8.2 on Linux and Windows.

## Install

```sh
composer install
```

## Prepare Magento Metadata

Run this in the Magento project first:

```sh
n98-magerun dev:ide:phpstorm:meta
```

If metadata is missing, the generator fails with:

```text
Unable to find .phpstorm.meta.php metadata. Run n98-magerun dev:ide:phpstorm:meta first.
```

## Generate

```sh
php bin/generate-stubs "/path/to/magento/root" --validate
```

The generator searches common metadata locations, including:

```text
/path/to/magento/root/.phpstorm.meta.php
```

You can also pass metadata explicitly:

```sh
php bin/generate-stubs "/path/to/magento/root" --meta-path="/path/to/.phpstorm.meta.php"
```

Generated files are written under:

```text
.m1_phpstan_bridge/
```

## PHPStan Usage

Include the generated config from your project PHPStan config:

```neon
includes:
    - .m1_phpstan_bridge/phpstan-magento.neon
```

The generator registers the generated PHPStan extension namespace in the target
project `composer.json` under `autoload-dev`:

```json
"M1PhpStanBridgeGenerated\\": ".m1_phpstan_bridge/src/"
```

Run `composer dump-autoload` after generation if you do not use `--validate`.

## Generated Files

Core bridge files:

```text
.m1_phpstan_bridge/phpstan-magento.neon
.m1_phpstan_bridge/mage-factories.stub.php
.m1_phpstan_bridge/magento-core.stub.php
.m1_phpstan_bridge/varien.stub.php
.m1_phpstan_bridge/autoload.php
.m1_phpstan_bridge/classmap-autoload.php
.m1_phpstan_bridge/src/PHPStan/*.php
```

Generated data and reports:

```text
.m1_phpstan_bridge/generated/model-map.php
.m1_phpstan_bridge/generated/singleton-map.php
.m1_phpstan_bridge/generated/resource-model-map.php
.m1_phpstan_bridge/generated/helper-map.php
.m1_phpstan_bridge/generated/block-map.php
.m1_phpstan_bridge/generated/class-map.php
.m1_phpstan_bridge/generated/diagnostics.json
.m1_phpstan_bridge/generated/classmap-report.md
.m1_phpstan_bridge/generated/phpstan-smoke.php
```

## What It Supports

Dynamic return type extensions are generated for:

- `Mage::getModel()`
- `Mage::getSingleton()`
- `Mage::getResourceModel()`
- `Mage::helper()`
- `Mage::getBlockSingleton()`
- `Mage_Core_Model_Layout::createBlock()`

Each extension reads the generated map file, inspects the first argument when it
is a constant string, and returns `ObjectType($class)` when the alias is known.
Unknown or dynamic aliases fall back gracefully.

## Classmap Discovery

The generator scans Magento-style source locations:

```text
app/code/core
app/code/community
app/code/local
lib/Varien
lib/Zend
```

Class discovery is token-based and only records declared symbols:

- class declarations
- interface declarations
- trait declarations
- enum declarations when supported by the PHP runtime

It does not map referenced symbols from static calls, `::class`, `instanceof`,
type hints, PHPDoc, setup variables, or other references.

Setup/data/test/temp/vendor/node paths are excluded from classmap scanning. Files
with executable top-level code before declarations are skipped for autoload
safety and reported in `classmap-report.md`.

## Validation

`--validate` performs:

- `composer dump-autoload` in the target Magento project
- `php -l` on generated PHP files
- expected core alias checks
- a non-`dumpType` PHPStan smoke test:

```sh
vendor/bin/phpstan analyse .m1_phpstan_bridge/generated/phpstan-smoke.php -c .m1_phpstan_bridge/phpstan-magento.neon --memory-limit=1G
```

The smoke test exits `0` when factory/helper/block aliases infer the expected
concrete types.

## Notes

- The old giant `mage.stub.php` conditional-return strategy is not used.
- No broad `ignoreErrors` rules are generated.
- The bridge is intended to reduce Magento dynamic false positives while still
  allowing PHPStan to report real module issues.
