<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\events;

use NhanAZ\OreGenerator\data\OreGeneratorData;
use NhanAZ\OreGenerator\OreGenerator;
use NhanAZ\OreGenerator\utils\ParserData;
use NhanAZ\OreGenerator\utils\Sound;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Facing;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use function intval;
use function is_null;

class onBlockBreak implements Listener {

	public function onBreak(BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$blockPos = $block->getPosition();
		$getSideDOWN = $block->getSide(Facing::DOWN);
		$world = OreGenerator::getInstance()->getManager()->get($blockPos->getWorld());

		$data = $world->getBlockDataAt((int) $blockPos->x, (int) $blockPos->y, (int) $blockPos->z);
		if (!($data instanceof OreGeneratorData)) {
			$data = new OreGeneratorData();
		}
		$parsedData = ParserData::parse($data->getData());
		

		if ($block->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER())) {
			if ($parsedData["isOreGenerator"]) {
				$block = VanillaBlocks::LEGACY_STONECUTTER();
				$item = $block->asItem();
				$compound = new CompoundTag();
				$nbt = $compound->setString("data", $data->getData());
				$item->setCustomBlockData($nbt);
				$level = (int) $parsedData["level"];
				$item->setCustomName(OreGenerator::getInstance()->handleCustomName($level));
				$event->setDrops([$item]);
				$data->setData();
			}
		}

		$data = $world->getBlockDataAt((int) $blockPos->x, (int) $blockPos->y - 1, (int) $blockPos->z);
		if (!($data instanceof OreGeneratorData)) {
			$data = new OreGeneratorData();
		}
		$parsedData = ParserData::parse($data->getData());

		if ($getSideDOWN->hasSameTypeId(VanillaBlocks::LEGACY_STONECUTTER())) {
			$level = (int) $parsedData["level"];
			if ($parsedData["isOreGenerator"]) {
				OreGenerator::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($blockPos, $level) : void {
					$block = OreGenerator::getInstance()->randomOre($level);
					if (is_null($block)) {
						return;
					}
					$blockPos->getWorld()->setBlock($blockPos, $block);
					if (OreGenerator::getInstance()->getConfig()->getNested("sounds.newResource")) {
						Sound::playFizz($blockPos);
					}
				}), intval(OreGenerator::getInstance()->getConfig()->getNested("oreGenerators.level_{$level}.delayTime")));
			}
		}
	}
}
