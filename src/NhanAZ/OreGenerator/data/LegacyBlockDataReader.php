<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\data;

use LevelDB;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use Throwable;
use function is_dir;

final class LegacyBlockDataReader {
	/** @var array<string, LevelDB|false> */
	private array $databases = [];
	private BigEndianNbtSerializer $serializer;

	public function __construct(private string $dataFolder) {
		$this->serializer = new BigEndianNbtSerializer();
	}

	public function read(Position $position) : ?string {
		$world = $position->getWorld();
		$name = $world->getFolderName();
		if (!isset($this->databases[$name])) {
			$path = $this->dataFolder . "blockdata/" . $name;
			if (!is_dir($path)) {
				$this->databases[$name] = false;
				return null;
			}
			try {
				$this->databases[$name] = new LevelDB($path, ["create_if_missing" => false]);
			} catch (Throwable $e) {
				throw new RuntimeException("Cannot open legacy generator data in world '{$name}'", 0, $e);
			}
		}
		$database = $this->databases[$name];
		if ($database === false) {
			return null;
		}
		$key = "b" . World::blockHash($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
		try {
			$raw = $database->get($key);
		} catch (Throwable $e) {
			throw new RuntimeException("Cannot read legacy generator data in world '{$name}'", 0, $e);
		}
		if ($raw === false) {
			return null;
		}
		try {
			$root = $this->serializer->read($raw)->mustGetCompoundTag();
			if ($root->getString("Type") !== "ore_generator") {
				return null;
			}
			$data = $root->getCompoundTag("Data");
			if ($data === null) {
				throw new RuntimeException("Legacy generator record has no Data tag");
			}
			return $data->getString("data");
		} catch (Throwable $e) {
			throw new RuntimeException("Cannot decode legacy generator data in world '{$name}'", 0, $e);
		}
	}

	public function close() : void {
		$this->databases = [];
	}
}
