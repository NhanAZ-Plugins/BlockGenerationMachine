<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/NhanAZ/OreGenerator/utils/ParserData.php';

use NhanAZ\OreGenerator\utils\ParserData;

set_error_handler(static function(int $severity, string $message) : never {
	throw new ErrorException($message, 0, $severity);
});

$cases = [
	'isOreGenerator:true owner:NhanAZ level:1 durability:1000' => [true, 'NhanAZ', 1, 1000],
	'isOreGenerator:false owner:Alice Bob level:2 durability:0' => [false, 'Alice Bob', 2, 0],
	'isOreGenerator:true owner:Alice level:0 durability:10' => [false, '', 0, 0],
	'isOreGenerator:true owner:Alice level:999999999999999999999 durability:10' => [false, '', 0, 0],
	'isOreGenerator:true owner:Alice level:1 durability:-1' => [false, '', 0, 0],
	'isOreGenerator:true owner:Alice level:1' => [false, '', 0, 0],
	'garbage' => [false, '', 0, 0],
	'' => [false, '', 0, 0],
];

foreach ($cases as $input => [$expectedFlag, $expectedOwner, $expectedLevel, $expectedDurability]) {
	$actual = ParserData::parse($input);
	$expected = [
		'isOreGenerator' => $expectedFlag,
		'owner' => $expectedOwner,
		'level' => $expectedLevel,
		'durability' => $expectedDurability,
	];
	if ($actual !== $expected) {
		throw new RuntimeException('ParserData mismatch for ' . var_export($input, true));
	}
}

echo 'PASS: ' . count($cases) . " ore generator data cases\n";
