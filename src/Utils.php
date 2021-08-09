<?php
/**
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (C) 2021 Marc Anton Dahmen - <https://marcdahmen.de>
 * @license MIT license
 */

namespace Airmad;

use Automad\Core\Parse;
use URLify;

defined('AUTOMAD') or die('Direct access not permitted!');

class Utils {
	/**
	 * Resolves the values for a given CSV list. Values can either be double quoted strings or field names.
	 * Field names are resolved than to a value while strings will just have their wrapping quotes removed.
	 *
	 * @param string $csv
	 * @param object $context
	 * @return array An array with all resolved values
	 */
	public static function resolveCsvArgs($csv, $context) {
		$args = array();

		foreach (Parse::csv($csv) as $arg) {
			if (preg_match('/"([^"]*)"/', $arg, $matches)) {
				$args[] = $matches[1];
			} else {
				$args[] = $context->get($arg);
			}
		}

		return $args;
	}
	/**
	 * Sanitizes a string to be camparable and used as a filter.
	 *
	 * @param string $str
	 * @param boolean $stripQuotes
	 * @return string The sanitized string.
	 */
	public static function sanitize($str, $stripQuotes = false) {
		$str = str_replace('/', '-', $str);
		$str = str_replace(array('&mdash;', '&ndash;'), '-', $str);
		$str = strtolower(URLify::transliterate($str));

		if ($stripQuotes) {
			$str = str_replace('"', '', $str);
		}

		return preg_replace('/[^\w"_\[\]]+/is', '-', $str);
	}
}
