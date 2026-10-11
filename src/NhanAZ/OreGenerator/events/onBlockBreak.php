<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\events;

use NhanAZ\OreGenerator\OreGenerator;
use NhanAZ\OreGenerator\config\ConfigManager;
use NhanAZ\OreGenerator\utils\ParserData;
use NhanAZ\OreGenerator\utils\Sound;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use RuntimeException;
use function is_null;

class onBlockBreak implements Listener {

	public function onBreak(BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$blockPos = $block->getPosition();
		$getSideDOWN = $block->getSide(Facing::DOWN);
		$storage = OreGenerator::getInstance()->getStorage();
		try {
			$data = $storage->get($blockPos);
		} catch (RuntimeException $e) {
			$event->cancel();
			OreGenerator::getInstance()->getLogger()->error("Cannot read generator data at {$blockPos}: " . $e->getMessage());
			return;
		}
		$parsedData = ParserData::parse($data ?? "");

		if ($block->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER())) {
			if ($parsedData["isOreGenerator"]) {
				$block = VanillaBlocks::LEGACY_STONECUTTER();
				$item = $block->asItem();
				$compound = new CompoundTag();
				$nbt = $compound->setString("data", $data ?? "");
				$item->setCustomBlockData($nbt);
				$level = (int) $parsedData["level"];
				$item->setCustomName(OreGenerator::getInstance()->handleCustomName($level));
				$event->setDrops([$item]);
				OreGenerator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($event, $blockPos, $storage) : void {
					if (!$event->isCancelled() && $blockPos->getWorld()->getBlock($blockPos)->hasSameTypeId(VanillaBlocks::AIR())) {
						$storage->remove($blockPos);
					}
				}), 1);
			}
		}

		try {
			$data = $storage->get($getSideDOWN->getPosition());
		} catch (RuntimeException $e) {
			$event->cancel();
			OreGenerator::getInstance()->getLogger()->error("Cannot read generator data below {$blockPos}: " . $e->getMessage());
			return;
		}
		$parsedData = ParserData::parse($data ?? "");

		if ($getSideDOWN->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER())) {
			$level = (int) $parsedData["level"];
			if ($parsedData["isOreGenerator"]) {
				$delayTicks = ConfigManager::getDelayTicks($level);
				if ($delayTicks === null) {
					OreGenerator::getInstance()->getLogger()->warning("Invalid delayTime for ore generator level {$level}");
					return;
				}
				OreGenerator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($event, $blockPos, $level) : void {
					if ($event->isCancelled() || !$blockPos->getWorld()->getBlock($blockPos)->hasSameTypeId(VanillaBlocks::AIR())) {
						return;
					}
					$block = OreGenerator::getInstance()->randomOre($level);
					if (is_null($block)) {
						return;
					}
					$blockPos->getWorld()->setBlock($blockPos, $block);
					if (OreGenerator::getInstance()->getConfig()->getNested("sounds.newResource")) {
						Sound::playFizz($blockPos);
					}
				}), $delayTicks);
			}
		}
	}
}
