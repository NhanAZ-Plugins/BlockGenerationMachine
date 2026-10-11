<?php

declare(strict_types=1);

use NhanAZ\OreGenerator\data\LegacyBlockDataReader;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\Position;
use pocketmine\world\World;

$autoload = $argv[1] ?? null;
if ($autoload === null || !is_file($autoload)) {
	throw new RuntimeException('Pass the PocketMine Composer autoload path.');
}
require_once $autoload;
require_once __DIR__ . '/../src/NhanAZ/OreGenerator/data/LegacyBlockDataReader.php';

$folder = dirname(__DIR__) . '/build/legacy-storage-fixture/';
$path = $folder . 'blockdata/testworld';
if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
	throw new RuntimeException('Could not create legacy fixture database.');
}

$world = (new ReflectionClass(World::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(World::class, 'folderName'))->setValue($world, 'testworld');
$position = new Position(3, 64, -2, $world);
$other = new Position(4, 64, -2, $world);
$invalid = new Position(5, 64, -2, $world);
$missing = new Position(6, 64, -2, $world);

$data = 'isOreGenerator:true owner:Legacy Player level:2 durability:1000';
$root = CompoundTag::create()
	->setString('Type', 'ore_generator')
	->setTag('Data', CompoundTag::create()->setString('data', $data));
$otherRoot = CompoundTag::create()
	->setString('Type', 'different_plugin')
	->setTag('Data', CompoundTag::create()->setString('data', 'unrelated'));
$serializer = new BigEndianNbtSerializer();
$database = new LevelDB($path, ['create_if_missing' => true]);
$database->put('b' . World::blockHash(3, 64, -2), $serializer->write(new TreeRoot($root)));
$database->put('b' . World::blockHash(4, 64, -2), $serializer->write(new TreeRoot($otherRoot)));
$database->put('b' . World::blockHash(5, 64, -2), 'invalid nbt');
unset($database);

$reader = new LegacyBlockDataReader($folder);
if ($reader->read($position) !== $data) {
	throw new RuntimeException('Legacy generator value changed during decoding.');
}
if ($reader->read($other) !== null || $reader->read($missing) !== null) {
	throw new RuntimeException('Unrelated or missing legacy data was accepted.');
}
try {
	$reader->read($invalid);
	throw new RuntimeException('Malformed legacy NBT was silently accepted.');
} catch (RuntimeException $e) {
	if (!str_contains($e->getMessage(), 'Cannot decode legacy generator data')) {
		throw $e;
	}
}
$reader->close();

$database = new LevelDB($path, ['create_if_missing' => false]);
if ($database->get('b' . World::blockHash(3, 64, -2)) !== $serializer->write(new TreeRoot($root))) {
	throw new RuntimeException('Legacy record changed after reader access.');
}
unset($database);

$lockedPath = $folder . 'blockdata/lockedworld';
if (!is_dir($lockedPath) && !mkdir($lockedPath, 0777, true) && !is_dir($lockedPath)) {
	throw new RuntimeException('Could not create locked fixture database.');
}
$lockedDatabase = new LevelDB($lockedPath, ['create_if_missing' => true]);
$lockedWorld = (new ReflectionClass(World::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(World::class, 'folderName'))->setValue($lockedWorld, 'lockedworld');
$lockedPosition = new Position(0, 64, 0, $lockedWorld);
$lockedReader = new LegacyBlockDataReader($folder);
try {
	$lockedReader->read($lockedPosition);
	throw new RuntimeException('Locked legacy database was silently accepted.');
} catch (RuntimeException $e) {
	if (!str_contains($e->getMessage(), 'Cannot open legacy generator data')) {
		throw $e;
	}
}
unset($lockedDatabase);
if ($lockedReader->read($lockedPosition) !== null) {
	throw new RuntimeException('Legacy reader did not recover after database unlock.');
}
$lockedReader->close();

echo "PASS: exact legacy NBT, unrelated/missing/malformed data, retained record, and locked DB recovery\n";
