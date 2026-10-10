<?php

declare(strict_types=1);

namespace pocketmine\event {
	interface Listener {}
}

namespace pocketmine\player {
	final class Player {
		public function __construct(private bool $playedBefore) {}

		public function hasPlayedBefore() : bool {
			return $this->playedBefore;
		}
	}
}

namespace pocketmine\event\player {
	use pocketmine\player\Player;

	final class PlayerJoinEvent {
		public function __construct(private Player $player) {}

		public function getPlayer() : Player {
			return $this->player;
		}
	}
}

namespace NhanAZ\OreGenerator {
	use pocketmine\player\Player;

	final class TestConfig {
		public int $amount = 2;

		public function getNested(string $path) : int {
			return $path === 'firstJoin.amount' ? $this->amount : 3;
		}
	}

	final class OreGenerator {
		private static self $instance;
		public array $grants = [];
		public TestConfig $config;

		public function __construct() {
			self::$instance = $this;
			$this->config = new TestConfig();
		}

		public static function getInstance() : self {
			return self::$instance;
		}

		public function getConfig() : TestConfig {
			return $this->config;
		}

		public function giveOreGenerator(Player $player, int $count, int $level) : void {
			$this->grants[] = [$player, $count, $level];
		}
	}
}

namespace {
	use NhanAZ\OreGenerator\events\onPlayerJoin;
	use NhanAZ\OreGenerator\OreGenerator;
	use pocketmine\event\player\PlayerJoinEvent;
	use pocketmine\player\Player;

	require_once __DIR__ . '/../src/NhanAZ/OreGenerator/events/onPlayerJoin.php';

	$plugin = new OreGenerator();
	$listener = new onPlayerJoin();
	$newPlayer = new Player(false);
	$listener->onPlayerJoin(new PlayerJoinEvent($newPlayer));
	if ($plugin->grants !== [[$newPlayer, 2, 3]]) {
		throw new \RuntimeException('New player did not receive the configured item once.');
	}

	$listener->onPlayerJoin(new PlayerJoinEvent(new Player(true)));
	if (count($plugin->grants) !== 1) {
		throw new \RuntimeException('Returning player received a first-join item.');
	}

	$plugin->config->amount = 0;
	$listener->onPlayerJoin(new PlayerJoinEvent(new Player(false)));
	if (count($plugin->grants) !== 1) {
		throw new \RuntimeException('Disabled first-join grant still ran.');
	}

	echo "PASS: first join, returning player, and disabled grant\n";
}
