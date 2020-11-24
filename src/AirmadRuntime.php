<?php
/**
 *	Automad Airmad
 *
 *	An Airtable integration for Automad.
 *
 *	@author Marc Anton Dahmen
 *	@copyright Copyright (C) 2020 Marc Anton Dahmen - <https://marcdahmen.de> 
 *	@license MIT license
 */

namespace Automad;

defined('AUTOMAD') or die('Direct access not permitted!');


class AirmadRuntime {


	/**
	 *	The runtime hash array.
	 */
	
	private static $hashes = array();
	
	
	/**
	 *	Checks if an instance is registered
	 *
	 *	@param string $hash
	 *	@return string The cached output if existing.
	 */

	public static function isRegistered($hash) {

		if (array_key_exists($hash, self::$hashes)) {
			return true;
		}
		
	}


	/**
	 *	Registers a Airmad instance by hash to prevent multiple executions.
	 *
	 *	@param string $hash
	 */
	public static function register($hash) {

		self::$hashes[$hash] = true;

	}


}