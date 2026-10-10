<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\utils;

use function ctype_digit;
use function is_int;
use function is_string;
use function ltrim;
use function random_int;
use function strcmp;
use function strlen;

final class WeightedOreSelector {

	/** @param array<array-key, mixed> $weights */
	public static function choose(array $weights) : ?string {
		$valid = [];
		$total = 0;
		$limit = (string) PHP_INT_MAX;
		foreach ($weights as $ore => $weight) {
			$name = (string) $ore;
			if ($name === "") {
				continue;
			}
			if (is_string($weight)) {
				if (!ctype_digit($weight)) {
					continue;
				}
				$digits = ltrim($weight, "0");
				if ($digits === "") {
					continue;
				}
				if (strlen($digits) > strlen($limit) || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
					return null;
				}
				$weight = (int) $digits;
			}
			if (!is_int($weight) || $weight <= 0) {
				continue;
			}
			if ($weight > PHP_INT_MAX - $total) {
				return null;
			}
			$valid[] = [$name, $weight];
			$total += $weight;
		}
		if ($total === 0) {
			return null;
		}
		$draw = random_int(1, $total);
		foreach ($valid as [$name, $weight]) {
			$draw -= $weight;
			if ($draw <= 0) {
				return $name;
			}
		}
		return null;
	}
}
