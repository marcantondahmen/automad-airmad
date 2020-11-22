<?php
/**
 *	Automad Airtable
 *
 *	An Airtable integration for Automad.
 *
 *	@author Marc Anton Dahmen
 *	@copyright Copyright (C) 2020 Marc Anton Dahmen - <https://marcdahmen.de> 
 *	@license MIT license
 */

namespace Automad;

defined('AUTOMAD') or die('Direct access not permitted!');

class AirtableRuntimeCache {

	
	private static $cache = array();
	

	public static function load($hash) {

		if (array_key_exists($hash, self::$cache)) {
			return self::$cache[$hash];
		}
		
	}


	public static function save($hash, $output) {

		self::$cache[$hash] = $output;

	}


}