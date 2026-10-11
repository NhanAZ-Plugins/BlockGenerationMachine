<?php

declare(strict_types=1);

namespace pocketmine\block {
	final class Block {
		public function __construct(public string $name) {}
	}

	final class VanillaBlocks {
		public static function COBBLESTONE() : Block {
			return new Block('cobblestone');
		}
	}
}

namespace pocketmine\item {
	use pocketmine\block\Block;

	final class TestItem {
		public function __construct(private string $name) {}

		public function getBlock() : Block {
			return new Block($this->name);
		}
	}

	final class StringToItemParser {
		public static function getInstance() : self {
			return new self();
		}

		public function parse(string $name) : ?TestItem {
			return $name === 'stone' ? new TestItem($name) : null;
		}
	}

	final class LegacyStringToItemParser {
		public static function getInstance() : self {
			return new self();
		}

		public function parse(string $name) : TestItem {
			if ($name !== 'legacy_stone') {
				throw new \InvalidArgumentException('Unknown legacy item');
			}
			return new TestItem($name);
		}
	}
}

namespace NhanAZ\OreGenerator {
	final class TestConfig {
		public mixed $defaultBlock = 'stone';
		public mixed $delayTime = 3;
		public mixed $ownerMode = true;

		public function get(string $key) : mixed {
			return $this->defaultBlock;
		}

		public function getNested(string $key) : mixed {
			return str_ends_with($key, '.ownerMode') ? $this->ownerMode : $this->delayTime;
		}
	}

	final class TestLogger {
		/** @var list<string> */
		public array $messages = [];

		public function critical(string $message) : void {
			$this->messages[] = $message;
		}
	}

	final class OreGenerator {
		public TestConfig $config;
		public TestLogger $logger;
		private static self $instance;

		public function __construct() {
			self::$instance = $this;
			$this->config = new TestConfig();
			$this->logger = new TestLogger();
		}

		public static function getInstance() : self {
			return self::$instance;
		}

		public function getConfig() : TestConfig {
			return $this->config;
		}

		public function getLogger() : TestLogger {
			return $this->logger;
		}
	}
}

namespace {
	use NhanAZ\OreGenerator\config\ConfigManager;
	use NhanAZ\OreGenerator\OreGenerator;
	use NhanAZ\OreGenerator\utils\StringToBlock;

	require_once __DIR__ . '/../src/NhanAZ/OreGenerator/utils/StringToBlock.php';
	require_once __DIR__ . '/../src/NhanAZ/OreGenerator/config/ConfigManager.php';

	$plugin = new OreGenerator();
	foreach (['stone' => 'stone', 'legacy_stone' => 'legacy_stone'] as $configured => $expected) {
		$plugin->config->defaultBlock = $configured;
		ConfigManager::checkConfigFileds();
		if (ConfigManager::getDefaultBlock()->name !== $expected) {
			throw new \RuntimeException('Valid block was replaced: ' . $configured);
		}
	}
	if ($plugin->logger->messages !== []) {
		throw new \RuntimeException('Valid blocks logged invalid configuration.');
	}

	foreach (['unknown_block', [], null] as $configured) {
		$plugin->config->defaultBlock = $configured;
		ConfigManager::checkConfigFileds();
		if (ConfigManager::getDefaultBlock()->name !== 'cobblestone') {
			throw new \RuntimeException('Invalid block did not use cobblestone.');
		}
	}
	if (count($plugin->logger->messages) !== 3 || StringToBlock::parse('unknown_block') !== null) {
		throw new \RuntimeException('Invalid block diagnostics or null handling failed.');
	}

	foreach ([1 => 20, 3 => 60] as $seconds => $ticks) {
		$plugin->config->delayTime = $seconds;
		if (ConfigManager::getDelayTicks(1) !== $ticks) {
			throw new \RuntimeException('Configured seconds were not converted to ticks.');
		}
	}
	foreach ([0, -1, '3', [], null, intdiv(PHP_INT_MAX, 20) + 1] as $invalid) {
		$plugin->config->delayTime = $invalid;
		if (ConfigManager::getDelayTicks(1) !== null) {
			throw new \RuntimeException('Invalid delayTime was accepted.');
		}
	}

	foreach ([true, null, 'false'] as $protected) {
		$plugin->config->ownerMode = $protected;
		if (!ConfigManager::canPlayerUseGenerator(1, 'Alice', 'alice')
			|| ConfigManager::canPlayerUseGenerator(1, 'Alice', 'Bob')) {
			throw new \RuntimeException('Owner mode did not protect a generator');
		}
	}
	$plugin->config->ownerMode = false;
	if (!ConfigManager::canPlayerUseGenerator(1, 'Alice', 'Bob')) {
		throw new \RuntimeException('Explicit shared mode did not allow a visitor');
	}

	echo "PASS: default blocks, delay seconds, and owner mode\n";
}
