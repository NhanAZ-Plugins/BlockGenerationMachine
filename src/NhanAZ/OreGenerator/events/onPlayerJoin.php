<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\events;

use NhanAZ\OreGenerator\OreGenerator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use function filter_var;
use function is_int;

class onPlayerJoin implements Listener {

	public function onPlayerJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		if ($player->hasPlayedBefore()) {
			return;
		}
		$getConfig = OreGenerator::getInstance()->getConfig();
		$firstJoin_amount = filter_var($getConfig->getNested("firstJoin.amount"), FILTER_VALIDATE_INT);
		$firstJoin_level = filter_var($getConfig->getNested("firstJoin.level"), FILTER_VALIDATE_INT);
		if (is_int($firstJoin_amount) && $firstJoin_amount > 0 && is_int($firstJoin_level) && $firstJoin_level > 0) {
			OreGenerator::getInstance()->giveOreGenerator($player, $firstJoin_amount, $firstJoin_level);
		}
	}
}
