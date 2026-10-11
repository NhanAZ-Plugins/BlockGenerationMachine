<?php

declare(strict_types=1);

namespace pocketmine\event {
    interface Listener {}
}

namespace pocketmine\math {
    final class Facing {
        public const UP = 1;
    }
}

namespace pocketmine\world {
    final class Position {
        public function __construct(public int $y) {}
    }
}

namespace pocketmine\block {
    use pocketmine\world\Position;

    final class Block {
        public function __construct(private string $type, private Position $position) {}

        public function hasSameTypeId(self $other) : bool {
            return $this->type === $other->type;
        }

        public function getPosition() : Position {
            return $this->position;
        }

        public function getSide(int $side) : self {
            return VanillaBlocks::AIR();
        }
    }

    final class VanillaBlocks {
        public static function LEGACY_STONECUTTER() : Block {
            return new Block('stonecutter', new Position(64));
        }

        public static function AIR() : Block {
            return new Block('air', new Position(65));
        }
    }
}

namespace pocketmine\player {
    final class Player {
        /** @var list<string> */
        public array $messages = [];

        public function __construct(private string $name) {}

        public function getName() : string {
            return $this->name;
        }

        public function sendMessage(string $message) : void {
            $this->messages[] = $message;
        }
    }
}

namespace pocketmine\event\block {
    use pocketmine\block\Block;
    use pocketmine\player\Player;

    final class TestTag {
        public function __construct(private string $data) {}

        public function getString(string $name) : string {
            return $this->data;
        }
    }

    final class TestItem {
        public function __construct(private TestTag $tag) {}

        public function hasCustomBlockData() : bool {
            return true;
        }

        public function getCustomBlockData() : TestTag {
            return $this->tag;
        }
    }

    final class TestTransaction {
        public function __construct(private Block $block) {}

        public function getBlocks() : iterable {
            yield [0, 64, 0, $this->block];
        }
    }

    final class BlockPlaceEvent {
        private bool $cancelled = false;

        public function __construct(private TestItem $item, private TestTransaction $transaction, private Player $player) {}

        public function getItem() : TestItem {
            return $this->item;
        }

        public function getTransaction() : TestTransaction {
            return $this->transaction;
        }

        public function getPlayer() : Player {
            return $this->player;
        }

        public function cancel() : void {
            $this->cancelled = true;
        }

        public function isCancelled() : bool {
            return $this->cancelled;
        }
    }
}

namespace pocketmine\scheduler {
    final class ClosureTask {
        public function __construct(public \Closure $callback) {}
    }
}

namespace NhanAZ\OreGenerator\utils {
    final class Sound {}
}

namespace NhanAZ\OreGenerator {
    use pocketmine\scheduler\ClosureTask;

    final class TestConfig {
        public mixed $ownerMode = true;

        public function getNested(string $path) : mixed {
            return $this->ownerMode;
        }
    }

    final class TestScheduler {
        public int $scheduled = 0;

        public function scheduleDelayedTask(ClosureTask $task, int $ticks) : void {
            $this->scheduled++;
        }
    }

    final class OreGenerator {
        private static self $instance;
        public TestConfig $config;
        public TestScheduler $scheduler;

        public function __construct() {
            self::$instance = $this;
            $this->config = new TestConfig();
            $this->scheduler = new TestScheduler();
        }

        public static function getInstance() : self {
            return self::$instance;
        }

        public function getConfig() : TestConfig {
            return $this->config;
        }

        public function getScheduler() : TestScheduler {
            return $this->scheduler;
        }
    }
}

namespace {
    require dirname(__DIR__) . '/src/NhanAZ/OreGenerator/utils/ParserData.php';
    require dirname(__DIR__) . '/src/NhanAZ/OreGenerator/config/ConfigManager.php';
    require dirname(__DIR__) . '/src/NhanAZ/OreGenerator/events/onBlockPlace.php';

    $plugin = new \NhanAZ\OreGenerator\OreGenerator();
    $block = \pocketmine\block\VanillaBlocks::LEGACY_STONECUTTER();
    $item = new \pocketmine\event\block\TestItem(new \pocketmine\event\block\TestTag(
        'isOreGenerator:true owner:Alice level:1 durability:1000'
    ));
    $transaction = new \pocketmine\event\block\TestTransaction($block);
    $listener = new \NhanAZ\OreGenerator\events\onBlockPlace();

    $visitor = new \pocketmine\player\Player('Bob');
    $denied = new \pocketmine\event\block\BlockPlaceEvent($item, $transaction, $visitor);
    $listener->onBlockPlace($denied);
    if (!$denied->isCancelled() || $visitor->messages === [] || $plugin->scheduler->scheduled !== 0) {
        throw new \RuntimeException('Protected generator item was placed by a visitor');
    }

    $owner = new \pocketmine\player\Player('alice');
    $allowed = new \pocketmine\event\block\BlockPlaceEvent($item, $transaction, $owner);
    $listener->onBlockPlace($allowed);
    if ($allowed->isCancelled() || $plugin->scheduler->scheduled !== 1) {
        throw new \RuntimeException('Owner could not place a protected generator item');
    }

    $plugin->config->ownerMode = false;
    $shared = new \pocketmine\event\block\BlockPlaceEvent($item, $transaction, $visitor);
    $listener->onBlockPlace($shared);
    if ($shared->isCancelled() || $plugin->scheduler->scheduled !== 2) {
        throw new \RuntimeException('Visitor could not place a shared generator item');
    }

    echo "Owner mode protects placement and permits explicitly shared generators\n";
}
