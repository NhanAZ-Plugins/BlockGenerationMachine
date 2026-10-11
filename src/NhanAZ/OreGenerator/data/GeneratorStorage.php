<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\data;

use NhanAZ\BlockData\BlockData;
use NhanAZ\OreGenerator\OreGenerator;
use pocketmine\world\Position;
use function is_string;

final class GeneratorStorage {
	private BlockData $current;
	private LegacyBlockDataReader $legacy;

	public function __construct(OreGenerator $plugin) {
		$this->current = BlockData::create($plugin, false, $plugin->getDataFolder() . "blockdata-json");
		$this->legacy = new LegacyBlockDataReader($plugin->getDataFolder());
	}

	public function get(Position $position) : ?string {
		$world = $position->getWorld();
		$x = $position->getFloorX();
		$y = $position->getFloorY();
		$z = $position->getFloorZ();
		$value = $this->current->getAt($world, $x, $y, $z);
		if ($value !== null) {
			return is_string($value) ? $value : null;
		}
		$legacy = $this->legacy->read($position);
		if ($legacy !== null) {
			$this->current->setAt($world, $x, $y, $z, $legacy);
		}
		return $legacy;
	}

	public function set(Position $position, string $data) : void {
		$this->current->setAt($position->getWorld(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), $data);
	}

	public function remove(Position $position) : void {
		$this->current->setAt($position->getWorld(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ(), false);
	}

	public function close() : void {
		$this->legacy->close();
	}
}
