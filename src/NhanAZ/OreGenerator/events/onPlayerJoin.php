<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\events;

use NhanAZ\OreGenerator\OreGenerator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use function intval;

class onPlayerJoin implements Listener {

	public function onPlayerJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if ($player->hasPlayedBefore()) {
			return;
		}
		$getConfig = OreGenerator::getInstance()->getConfig();
		$firstJoin_amount = intval($getConfig->getNested("firstJoin.amount"));
		$firstJoin_level = intval($getConfig->getNested("firstJoin.level"));
		if ($firstJoin_amount > 0) {
			OreGenerator::getInstance()->giveOreGenerator($player, $firstJoin_amount, $firstJoin_level);
		}
	}
}
