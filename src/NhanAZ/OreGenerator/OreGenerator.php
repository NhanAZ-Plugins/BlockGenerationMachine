<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator;

use NhanAZ\OreGenerator\config\ConfigManager;
use NhanAZ\OreGenerator\data\GeneratorStorage;
use NhanAZ\OreGenerator\events\onBlockBreak;
use NhanAZ\OreGenerator\events\onBlockPlace;
use NhanAZ\OreGenerator\events\onPlayerJoin;
use NhanAZ\OreGenerator\utils\SingletonTrait;
use NhanAZ\OreGenerator\utils\Sound;
use NhanAZ\OreGenerator\utils\StringToBlock;
use NhanAZ\OreGenerator\utils\WeightedOreSelector;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\Position;
use function is_array;
use function str_replace;
use function is_int;
use function is_string;

class OreGenerator extends PluginBase {
	use SingletonTrait;

	private GeneratorStorage $storage;

	protected function onLoad() : void {
		self::setInstance($this);
	}

	protected function onEnable() : void {
		$this->saveDefaultConfig();
		$this->storage = new GeneratorStorage($this);
		$this->registerEvents();
		ConfigManager::checkConfigFileds();
	}

	protected function onDisable() : void {
		if (isset($this->storage)) {
			$this->storage->close();
		}
	}

	private function registerEvents() : void {
		$this->getServer()->getPluginManager()->registerEvents(new onPlayerJoin(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new onBlockPlace(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new onBlockBreak(), $this);
	}

	public function handleCustomName(int $level) : string {
		$configured = $this->getConfig()->get("customName");
		$customName = is_string($configured) ? $configured : "Ore Generator Level {level}";
		$customName = str_replace("{level}", "{$level}", $customName);
		return $customName;
	}

	public function onInvalidPosition(BlockPlaceEvent $event, Position $blockPos) : void {
		$event->cancel();
		Sound::playNote($blockPos);
	}

	public function giveOreGenerator(Player $player, int $count = 1, int $level = 1, ?int $durability = null) : void {
		$block = VanillaBlocks::LEGACY_STONECUTTER();
		$item = $block->asItem();
		$item->setCount($count);
		$item->setCustomName($this->handleCustomName($level));
		$compound = new CompoundTag();

		$owner = $player->getName();
		$configuredDurability = $this->getConfig()->getNested("oreGenerators.level_{$level}.durability");
		$durability = $durability ?? (is_int($configuredDurability) && $configuredDurability >= 0 ? $configuredDurability : 0);
		if ($durability < 0) {
			return;
		}

		$nbt = $compound->setString("data", "isOreGenerator:true owner:{$owner} level:{$level} durability:{$durability}");
		$item->setCustomBlockData($nbt);
		if ($player->getInventory()->canAddItem($item)) {
			$player->getInventory()->addItem($item);
		}
	}

	public function randomOre(int $level) : ?Block {
		$ores = $this->getConfig()->getNested("oreGenerators.level_{$level}.ores");
		if (!is_array($ores)) {
			return null;
		}
		$ore = WeightedOreSelector::choose($ores);
		return $ore === null ? null : StringToBlock::parse($ore);
	}

	public function getStorage() : GeneratorStorage {
		return $this->storage;
	}
}
