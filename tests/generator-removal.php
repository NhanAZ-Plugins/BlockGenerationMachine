<?php

declare(strict_types=1);

namespace pocketmine\event {
    interface Listener {}
}

namespace pocketmine\math {
    final class Facing {
        public const DOWN = 0;
    }
}

namespace pocketmine\world {
    use pocketmine\block\Block;

    final class World {
        /** @var array<string, Block> */
        private array $blocks = [];

        public function getBlock(Position $position) : Block {
            return $this->blocks[$position->key()] ?? new Block('air', $position);
        }

        public function setBlock(Position $position, Block $block) : void {
            $this->blocks[$position->key()] = new Block($block->type, $position);
        }
    }

    final class Position {
        public function __construct(public int $x, public int $y, public int $z, private World $world) {}

        public function getWorld() : World {
            return $this->world;
        }

        public function key() : string {
            return "$this->x:$this->y:$this->z";
        }

        public function __toString() : string {
            return $this->key();
        }
    }
}

namespace pocketmine\block {
    use pocketmine\world\Position;
    use pocketmine\world\World;

    final class Block {
        public function __construct(public string $type, private Position $position) {}

        public function getPosition() : Position {
            return $this->position;
        }

        public function getSide(int $side) : self {
            $position = new Position($this->position->x, $this->position->y - 1, $this->position->z, $this->position->getWorld());
            return $this->position->getWorld()->getBlock($position);
        }

        public function hasSameTypeId(self $other) : bool {
            return $this->type === $other->type;
        }
    }

    final class VanillaBlocks {
        public static function AIR() : Block {
            return new Block('air', new Position(0, 0, 0, new World()));
        }

        public static function LEGACY_STONECUTTER() : Block {
            return new Block('stonecutter', new Position(0, 0, 0, new World()));
        }
    }
}

namespace pocketmine\event\block {
    use pocketmine\block\Block;
    use pocketmine\player\Player;

    final class BlockBreakEvent {
        private bool $cancelled = false;

        public function __construct(private Block $block, private ?Player $player = null) {
            $this->player ??= new Player('Tester');
        }

        public function getBlock() : Block {
            return $this->block;
        }

        public function getPlayer() : Player {
            return $this->player;
        }

        public function isCancelled() : bool {
            return $this->cancelled;
        }

        public function cancel() : void {
            $this->cancelled = true;
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

namespace pocketmine\scheduler {
    final class ClosureTask {
        public function __construct(public \Closure $callback) {}
    }
}

namespace NhanAZ\OreGenerator\config {
    final class ConfigManager {
        public static bool $ownerMode = true;

        public static function getDelayTicks(int $level) : int {
            return 20;
        }

        public static function canPlayerUseGenerator(int $level, string $owner, string $playerName) : bool {
            return !self::$ownerMode || strcasecmp($owner, $playerName) === 0;
        }
    }
}

namespace NhanAZ\OreGenerator\utils {
    final class Sound {
        public static function playFizz(mixed $position) : void {}
    }
}

namespace NhanAZ\OreGenerator {
    use pocketmine\block\Block;
    use pocketmine\world\Position;

    final class TestStorage {
        /** @var array<string, string> */
        private array $values = [];

        public function set(Position $position, string $value) : void {
            $this->values[$position->key()] = $value;
        }

        public function get(Position $position) : ?string {
            return $this->values[$position->key()] ?? null;
        }

        public function remove(Position $position) : void {
            unset($this->values[$position->key()]);
        }
    }

    final class TestScheduler {
        /** @var list<\Closure> */
        private array $callbacks = [];

        public function scheduleDelayedTask(\pocketmine\scheduler\ClosureTask $task, int $ticks) : void {
            $this->callbacks[] = $task->callback;
        }

        public function run() : void {
            foreach ($this->callbacks as $callback) {
                $callback();
            }
        }
    }

    final class TestLogger {
        public function error(string $message) : void {
            throw new \RuntimeException($message);
        }

        public function warning(string $message) : void {
            throw new \RuntimeException($message);
        }
    }

    final class TestConfig {
        public function getNested(string $name) : bool {
            return false;
        }
    }

    final class OreGenerator {
        private static self $instance;
        private TestStorage $storage;
        private TestScheduler $scheduler;

        public function __construct() {
            self::$instance = $this;
            $this->storage = new TestStorage();
            $this->scheduler = new TestScheduler();
        }

        public static function getInstance() : self {
            return self::$instance;
        }

        public function getStorage() : TestStorage {
            return $this->storage;
        }

        public function getScheduler() : TestScheduler {
            return $this->scheduler;
        }

        public function getLogger() : TestLogger {
            return new TestLogger();
        }

        public function getConfig() : TestConfig {
            return new TestConfig();
        }

        public function randomOre(int $level) : Block {
            return new Block('stone', new Position(0, 0, 0, new \pocketmine\world\World()));
        }
    }
}

namespace {
    require dirname(__DIR__) . '/src/NhanAZ/OreGenerator/utils/ParserData.php';
    require dirname(__DIR__) . '/src/NhanAZ/OreGenerator/events/onBlockBreak.php';

    foreach (['intact', 'removed', 'metadata-replaced'] as $scenario) {
        $plugin = new \NhanAZ\OreGenerator\OreGenerator();
        $world = new \pocketmine\world\World();
        $generator = new \pocketmine\world\Position(1, 64, 1, $world);
        $ore = new \pocketmine\world\Position(1, 65, 1, $world);
        $world->setBlock($generator, \pocketmine\block\VanillaBlocks::LEGACY_STONECUTTER());
        $world->setBlock($ore, new \pocketmine\block\Block('cobblestone', $ore));
        $data = 'isOreGenerator:true owner:Tester level:1 durability:1000';
        $plugin->getStorage()->set($generator, $data);
        $event = new \pocketmine\event\block\BlockBreakEvent($world->getBlock($ore));
        (new \NhanAZ\OreGenerator\events\onBlockBreak())->onBreak($event);
        $world->setBlock($ore, \pocketmine\block\VanillaBlocks::AIR());
        if ($scenario === 'removed') {
            $world->setBlock($generator, \pocketmine\block\VanillaBlocks::AIR());
            $plugin->getStorage()->remove($generator);
        } elseif ($scenario === 'metadata-replaced') {
            $plugin->getStorage()->set($generator, 'isOreGenerator:true owner:Other level:2 durability:1000');
        }
        $plugin->getScheduler()->run();
        $actual = $world->getBlock($ore)->type;
        $expected = $scenario === 'intact' ? 'stone' : 'air';
        if ($actual !== $expected) {
            throw new \RuntimeException("$scenario produced $actual instead of $expected");
        }
    }

    foreach (['resource', 'machine'] as $target) {
        $plugin = new \NhanAZ\OreGenerator\OreGenerator();
        $world = new \pocketmine\world\World();
        $generator = new \pocketmine\world\Position(1, 64, 1, $world);
        $ore = new \pocketmine\world\Position(1, 65, 1, $world);
        $world->setBlock($generator, \pocketmine\block\VanillaBlocks::LEGACY_STONECUTTER());
        $world->setBlock($ore, new \pocketmine\block\Block('cobblestone', $ore));
        $data = 'isOreGenerator:true owner:Tester level:1 durability:1000';
        $plugin->getStorage()->set($generator, $data);
        $player = new \pocketmine\player\Player('Visitor');
        $event = new \pocketmine\event\block\BlockBreakEvent(
            $world->getBlock($target === 'machine' ? $generator : $ore), $player
        );
        (new \NhanAZ\OreGenerator\events\onBlockBreak())->onBreak($event);
        $plugin->getScheduler()->run();
        if (!$event->isCancelled() || $player->messages === []
            || $plugin->getStorage()->get($generator) !== $data
            || $world->getBlock($generator)->type !== 'stonecutter'
            || $world->getBlock($ore)->type !== 'cobblestone') {
            throw new \RuntimeException("Non-owner $target break was not denied without mutation");
        }
    }

    \NhanAZ\OreGenerator\config\ConfigManager::$ownerMode = false;
    $plugin = new \NhanAZ\OreGenerator\OreGenerator();
    $world = new \pocketmine\world\World();
    $generator = new \pocketmine\world\Position(1, 64, 1, $world);
    $ore = new \pocketmine\world\Position(1, 65, 1, $world);
    $world->setBlock($generator, \pocketmine\block\VanillaBlocks::LEGACY_STONECUTTER());
    $world->setBlock($ore, new \pocketmine\block\Block('cobblestone', $ore));
    $plugin->getStorage()->set($generator, 'isOreGenerator:true owner:Tester level:1 durability:1000');
    $event = new \pocketmine\event\block\BlockBreakEvent($world->getBlock($ore), new \pocketmine\player\Player('Visitor'));
    (new \NhanAZ\OreGenerator\events\onBlockBreak())->onBreak($event);
    $world->setBlock($ore, \pocketmine\block\VanillaBlocks::AIR());
    $plugin->getScheduler()->run();
    if ($event->isCancelled() || $world->getBlock($ore)->type !== 'stone') {
        throw new \RuntimeException('Shared generator did not regenerate after visitor break');
    }

    echo "Regeneration requires original metadata and enforces owner mode\n";
}
