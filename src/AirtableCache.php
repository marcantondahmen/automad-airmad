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

class AirtableCache {

	
	/**
	 *	The cache directory.
	 */

	private $cacheDir = AM_BASE_DIR . AM_DIR_CACHE . '/airtable';


	/**
	 *	The cache lifetime.
	 */

	private $lifeTime = 43200;


	/**
	 *	Cache is outdated or not.
	 */

	private $isOutdated = true;


	/**
	 *	The cache file for the current instance base on a configuration hash.
	 */

	private $tablesFile = false;


	/**
	 *	The cache constructor. An instance identifies a cache file by a hash of its configuration.
	 *
	 *	@param array $options
	 */

	public function __construct($options) {

		$this->cacheDir = AM_BASE_DIR . AM_DIR_CACHE . '/airtable/' . $options->base;
		$this->tablesFile = $this->cacheDir . '/' . sha1(json_encode($options));
		Core\FileSystem::makeDir($this->cacheDir);

		if (is_readable($this->tablesFile)) {
			$mTime = filemtime($this->tablesFile);
		} else {
			$mTime = 0;
		}

		if ($mTime + $this->lifeTime > time()) {
			$this->isOutdated = false;
		} else {
			$this->isOutdated = true;
		}

	}


	/**
	 *	Loads a the cached tables from the cache.
	 *
	 *	@return array The unserialized tables array
	 */

	public function load() {

		if ($this->isOutdated) {
			return false;
		}

		return unserialize(file_get_contents($this->tablesFile));

	}


	/**
	 *	Saves the serialized tables array to the cache. 
	 *
	 *	@param array $tables
	 */

	public function save($tables) {

		Core\FileSystem::write($this->tablesFile, serialize($tables));

	}


}