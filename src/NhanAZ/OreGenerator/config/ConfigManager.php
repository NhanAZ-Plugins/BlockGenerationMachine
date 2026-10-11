<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\config;

use NhanAZ\OreGenerator\OreGenerator;
use NhanAZ\OreGenerator\utils\StringToBlock;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use function get_debug_type;
use function intdiv;
use function is_int;
use function is_string;

class ConfigManager {

	public static function checkConfigFileds() : void {
		$configured = OreGenerator::getInstance()->getConfig()->get("defaultBlock");
		if (!is_string($configured) || StringToBlock::parse($configured) === null) {
			$shown = is_string($configured) ? $configured : get_debug_type($configured);
			OreGenerator::getInstance()->getLogger()->critical("defaultBlock: \"{$shown}\" > Invalid block! Please set a valid value for the defaultBlock field in config.yml. Cobblestone will be used instead.");
		}
	}

	public static function getDefaultBlock() : Block {
		$configured = OreGenerator::getInstance()->getConfig()->get("defaultBlock");
		if (is_string($configured)) {
			$block = StringToBlock::parse($configured);
			if ($block !== null) {
				return $block;
			}
		}
		return VanillaBlocks::COBBLESTONE();
	}

	public static function getDelayTicks(int $level) : ?int {
		$seconds = OreGenerator::getInstance()->getConfig()->getNested("oreGenerators.level_{$level}.delayTime");
		if (!is_int($seconds) || $seconds < 1 || $seconds > intdiv(PHP_INT_MAX, 20)) {
			return null;
		}
		return $seconds * 20;
	}
}
