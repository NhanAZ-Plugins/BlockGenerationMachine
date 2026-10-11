<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\events;

use NhanAZ\OreGenerator\config\ConfigManager;
use NhanAZ\OreGenerator\OreGenerator;
use NhanAZ\OreGenerator\utils\ParserData;
use NhanAZ\OreGenerator\utils\Sound;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\math\Facing;
use pocketmine\scheduler\ClosureTask;
use function is_null;

class onBlockPlace implements Listener {

	/** Y coordinate can build the highest OreGenerator of the world */
	const HIGHEST_BUILDABLE_Y = 319;

	public function onBlockPlace(BlockPlaceEvent $event) : void {
		$item = $event->getItem();
		$block = null;
		foreach ($event->getTransaction()->getBlocks() as [, , , $candidate]) {
			if ($candidate->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER())) {
				$block = $candidate;
				break;
			}
		}
		if ($block === null) {
			return;
		}
		$blockPos = $block->getPosition();
		$getSideUP = $block->getSide(Facing::UP);
		if (!$item->hasCustomBlockData()) {
			return;
		}
		if (is_null($item->getCustomBlockData())) {
			return;
		}
		$tempData = $item->getCustomBlockData()->getString("data");
		$data = ParserData::parse($tempData);
		if ($data["isOreGenerator"]) {
			if ($blockPos->y == self::HIGHEST_BUILDABLE_Y) {
				OreGenerator::getInstance()->onInvalidPosition($event, $blockPos);
				return;
			}
			if ($getSideUP->hasSameTypeId(VanillaBlocks::AIR())) {
				OreGenerator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($event, $blockPos, $tempData) : void {
					if ($event->isCancelled()) {
						return;
					}
					$world = $blockPos->getWorld();
					if (!$world->getBlock($blockPos)->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER()) ||
						!$world->getBlock($blockPos->add(0, 1, 0))->hasSameTypeId(VanillaBlocks::AIR())) {
						return;
					}
					OreGenerator::getInstance()->getStorage()->set($blockPos, $tempData);
					if (OreGenerator::getInstance()->getConfig()->getNested("sounds.invalidPosition")) {
						Sound::playFizz($blockPos);
					}
					$world->setBlock($blockPos->add(0, 1, 0), ConfigManager::getDefaultBlock());
				}), 1);
			} else {
				OreGenerator::getInstance()->onInvalidPosition($event, $blockPos);
				return;
			}
		}
	}
}
