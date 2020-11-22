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


	/**
	 *	The runtime cache array.
	 */
	
	private static $cache = array();
	
	
	/**
	 *	Load the cached output for a given configuration hash.
	 *
	 *	@param string $hash
	 *	@return string The cached output if existing.
	 */

	public static function load($hash) {

		if (array_key_exists($hash, self::$cache)) {
			return self::$cache[$hash];
		}
		
	}


	/**
	 *	Save a generated output for a given configuration to the runtimecache array.
	 *
	 *	@param string $hash
	 *	@param string $output
	 */
	public static function save($hash, $output) {

		self::$cache[$hash] = $output;

	}


}