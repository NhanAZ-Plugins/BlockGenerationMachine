# Block Generation Machine

Block Generation Machine is an experimental ore generator plugin for Axolotl-PM. Its plugin manifest names the runtime plugin `OreGenerator`.

## Current status

The plugin now packages the released [NhanAZ BlockData library](https://github.com/NhanAZ-Libraries/BlockData) through DevTools. The source passes PHPStan at maximum level against both the declared API minimum and the newer revision pinned in [CI](.github/workflows/validate.yml). A local PHAR built with DevTools loaded and stopped cleanly on an isolated server. These checks do not establish compatibility with every 5.x release or in-game behavior with a connected player. No stable release has been published from this migration.

The manifest declares plugin API `5.0.0`. That declaration does not establish support for every Axolotl-PM 5.x release. Keep production worlds and plugin data separate from migration tests.

## Intended behavior

- Give a configured generator item only when a player joins for the first time.
- Store generator metadata in the stonecutter item's custom block data and at its placed block position.
- Choose an ore from the configured weights and regenerate it after the configured delay when a generated block is broken.
- Play optional sounds for invalid placement and new resources.

The data migration has been checked with a synthetic legacy LevelDB database and an isolated server restart. Synthetic server events also covered placement, breaking, and regeneration. These flows still need testing with a connected player. The `ownerMode` and `durability` configuration values are present, but the source does not enforce ownership or wear. Do not treat them as active protection.

## Configuration

The defaults in [`resources/config.yml`](resources/config.yml) define `firstJoin.amount`, `firstJoin.level`, `customName`, `defaultBlock`, sound switches, and `oreGenerators.level_*` ore weights and delays. Setting `firstJoin.amount` to `0` disables the first-join grant. `delayTime` is a positive whole number of seconds. Invalid values skip regeneration and produce a warning. There are no commands or permissions in `plugin.yml`.

Ore weights must be positive whole numbers. Decimal integer strings within range are accepted. Noninteger and nonpositive entries are ignored. If a level has no valid weights, or a weight or their sum exceeds the integer range, no ore is selected. Check each configured level before enabling the experimental generator.

Generator item metadata uses a legacy string such as `isOreGenerator:true owner:NhanAZ level:1 durability:1000`. The parser treats malformed values and an explicit `false` flag as non-generators. It also accepts spaces in a player's name.

## Migration and build gate

The original [Cosmoverse BlockData virion](https://github.com/Cosmoverse/BlockData) stores NBT values. The NhanAZ BlockData library stores JSON values. Both use the same per-world LevelDB path and block key pattern, so opening the old database directly with the new library could overwrite records.

The plugin keeps the old `blockdata/<world>` database and writes new records to `blockdata-json/<world>`. When a generator position is first read, it decodes the old NBT record and saves its metadata in the new database. A deletion marker in the new database prevents an old record from reappearing after the generator is broken. The plugin code never calls write or delete on the old database. Keep a backup of plugin data before upgrading a production world. The tested migration used only a synthetic database, not a copy of a production world.

The local DevTools PHAR contains the plugin GPL text and the BlockData virion LGPL notice. The old Cosmoverse virion is not bundled.

The focused PHP fixtures can run without a server.

```sh
php tests/parser-data.php
php tests/first-join.php
php tests/weighted-ore-selector.php
php tests/default-block.php
php tests/legacy-storage.php virions/BlockData/vendor/autoload.php
```

The legacy storage fixture needs the released BlockData checkout and its Composer dependencies. The workflow checks these fixtures, PHPStan max, and a DevTools PHAR build. The isolated server probe also verified exact NBT decoding, JSON persistence, deletion markers, restart behavior, retention of the old record, and synthetic placement and break events. It did not use a connected player.

## License and support

This repository contains the [GNU GPL version 3 license](LICENSE). Preserve notices for any dependency used in a future build. For project support, visit [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
