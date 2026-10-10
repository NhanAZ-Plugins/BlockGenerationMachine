<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/NhanAZ/OreGenerator/utils/WeightedOreSelector.php';

use NhanAZ\OreGenerator\utils\WeightedOreSelector;

$cases = [
	[[], null],
	[['stone' => 0, 'diamond_block' => -1], null],
	[['stone' => 'not a weight'], null],
	[['stone' => '05'], 'stone'],
	[['stone' => PHP_INT_MAX], 'stone'],
	[['stone' => 2, 'diamond_block' => false], 'stone'],
	[[1 => 5], '1'],
	[['stone' => (string) PHP_INT_MAX . '0'], null],
	[['stone' => PHP_INT_MAX, 'diamond_block' => 1], null],
];
foreach ($cases as [$weights, $expected]) {
	$actual = WeightedOreSelector::choose($weights);
	if ($actual !== $expected) {
		throw new RuntimeException('Unexpected ore selection for ' . var_export($weights, true));
	}
}

foreach (range(1, 100) as $_) {
	$selected = WeightedOreSelector::choose(['stone' => 1, 'diamond_block' => 3]);
	if ($selected !== 'stone' && $selected !== 'diamond_block') {
		throw new RuntimeException('Selected an ore outside the configured options');
	}
}

echo 'PASS: ' . count($cases) . " deterministic weight cases and 100 bounded draws\n";
