<?php

declare(strict_types=1);

namespace NhanAZ\OreGenerator\utils;

use function filter_var;
use function preg_match;

class ParserData {

	/**
	 * Data format: "isOreGenerator:(bool) owner:(string) level:(int) durability:(int)"
	 *
	 * Example data format: "isOreGenerator:true owner:NhanAZ level:1 durability:1000"
	 *
	 * @return array{isOreGenerator: bool, owner: string, level: int, durability: int}
	 */
	public static function parse(string $data) : array {
		$invalid = ["isOreGenerator" => false, "owner" => "", "level" => 0, "durability" => 0];
		if (preg_match('/\AisOreGenerator:(true|false) owner:(.+) level:([0-9]+) durability:([0-9]+)\z/', $data, $matches) !== 1) {
			return $invalid;
		}
		$level = filter_var($matches[3], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
		$durability = filter_var($matches[4], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
		if ($level === false || $durability === false) {
			return $invalid;
		}
		return [
			"isOreGenerator" => $matches[1] === "true",
			"owner" => $matches[2],
			"level" => $level,
			"durability" => $durability,
		];
	}
}
