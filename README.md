# Block Generation Machine

Block Generation Machine is an experimental ore generator plugin source tree for Axolotl-PM. Its plugin manifest names the runtime plugin `OreGenerator`.

## Current status

The source still imports the older `cosmicpe\blockdata` API without declaring or packaging that dependency. The current [NhanAZ BlockData library](https://github.com/NhanAZ-Libraries/BlockData) has different classes and a different persistence format. It is not a drop-in replacement. PHPStan max currently fails on unresolved dependency symbols and obsolete API calls. A working Axolotl-PM build, server runtime test, and release have not been verified for this repository.

The manifest declares plugin API `5.0.0`. That declaration does not establish support for every Axolotl-PM 5.x release. Keep production worlds and plugin data separate from migration tests.

## Intended behavior

- Give a configured generator item only when a player joins for the first time.
- Store generator metadata in the stonecutter item's custom block data and at its placed block position.
- Choose an ore from the configured weights and regenerate it after the configured delay when a generated block is broken.
- Play optional sounds for invalid placement and new resources.

These paths still need integration tests with a compatible persistence implementation. The `ownerMode` and `durability` configuration values are present, but the current source does not enforce ownership or wear. Do not treat them as active protection.

## Configuration

The defaults in [`resources/config.yml`](resources/config.yml) define `firstJoin.amount`, `firstJoin.level`, `customName`, `defaultBlock`, sound switches, and `oreGenerators.level_*` ore weights and delays. Setting `firstJoin.amount` to `0` disables the first-join grant. There are no commands or permissions in `plugin.yml`.

Ore weights must be positive whole numbers. Decimal integer strings within range are accepted. Noninteger and nonpositive entries are ignored. If a level has no valid weights, or a weight or their sum exceeds the integer range, no ore is selected. Check each configured level before enabling the experimental generator.

Generator item metadata uses a legacy string such as `isOreGenerator:true owner:NhanAZ level:1 durability:1000`. The parser now treats malformed values and an explicit `false` flag as non-generators. It also accepts spaces in a player's name. This change does not migrate saved block data.

## Migration and build gate

The original [Cosmoverse BlockData virion](https://github.com/Cosmoverse/BlockData) stores custom NBT block data. The current NhanAZ BlockData library stores JSON-compatible values in a per-world LevelDB database. Before replacing the dependency, a migration must identify the old data path and key format, preserve existing generator metadata, and prove save and reload behavior using copied test worlds. No migration or compatible DevTools PHAR is available yet.

The focused PHP fixtures can run without a server.

```sh
php tests/parser-data.php
php tests/first-join.php
php tests/weighted-ore-selector.php
php tests/default-block.php
```

These fixtures cover local logic only. They do not establish full-source PHPStan max, dependency compatibility, PHAR build, or runtime behavior.

## License and support

This repository contains the [GNU GPL version 3 license](LICENSE). Preserve notices for any dependency used in a future build. For project support, visit [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
