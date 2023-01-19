<?php
/**
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (C) 2023 Marc Anton Dahmen - <https://marcdahmen.de>
 * @license MIT license
 */

namespace Airmad;

defined('AUTOMAD') or die('Direct access not permitted!');

class Debug {
	/**
	 * The log entries array.
	 */
	private static $entries = array();

	/**
	 * Get the log entries
	 *
	 * @return array
	 */
	public static function get(): array {
		return self::$entries;
	}

	/**
	 * Add an entry to the entries array.
	 *
	 * @param mixed $entry
	 */
	public static function log($entry) {
		$backtraceAll = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
		$ignoreFunctions = array('log', __NAMESPACE__ . '\{closure}');

		$backtrace = array_filter($backtraceAll, function ($item) use ($ignoreFunctions) {
			return (isset($item['class'], $item['type'], $item['function']) && !in_array($item['function'], $ignoreFunctions));
		});

		if (count($backtrace) > 0) {
			// When the backtrace array got reduced to the actually relevant items in the backtrace, take the first element (the one calling Debug::log()).
			$backtrace = array_shift($backtrace);
			$src = basename(str_replace('\\', '/', $backtrace['class'] ?? '')) . ($backtrace['type'] ?? '') . $backtrace['function'] . '(): ';
		} else {
			$src = basename($backtraceAll[0]['file']);
		}

		self::$entries[] = array($src, $entry);
	}
}
