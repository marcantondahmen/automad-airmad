<?php
/**
 *	Airmad
 *
 *	An Airtable integration for Automad.
 *
 *	@author Marc Anton Dahmen
 *	@copyright Copyright (C) 2021 Marc Anton Dahmen - <https://marcdahmen.de>
 *	@license MIT license
 */

namespace Airmad;

defined('AUTOMAD') or die('Direct access not permitted!');

class Utils {
	/**
	 *	Sanitizes a string to be camparable and used as a filter.
	 *
	 *	@param string $str
	 *	@param boolean $stripQuotes
	 *	@return string The sanitized string.
	 */
	public static function sanitize($str, $stripQuotes = false) {
		$str = str_replace('/', '-', $str);
		$str = str_replace(array('&mdash;', '&ndash;'), '-', $str);
		$str = strtolower(\URLify::transliterate($str));

		if ($stripQuotes) {
			$str = str_replace('"', '', $str);
		}

		return preg_replace('/[^\w"_\[\]]+/is', '-', $str);
	}
}