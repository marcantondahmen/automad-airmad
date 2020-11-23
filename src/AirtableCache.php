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
	 *	The cache file for the table records.
	 */

	private $cacheFile = false;


	/**
	 *	The cache constructor. An instance identifies a cache file by a hash of base/table/view.
	 *
	 *	@param array $base
	 *	@param array $table
	 *	@param array $view
	 */

	public function __construct($base, $table, $view) {

		$hash = sha1("{$base}/{$table}/{$view}");
		$this->cacheDir = AM_BASE_DIR . AM_DIR_CACHE . '/airtable';
		$this->cacheFile = $this->cacheDir . '/' . $hash;
		Core\FileSystem::makeDir($this->cacheDir);

		if (is_readable($this->cacheFile)) {
			$mTime = filemtime($this->cacheFile);
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
	 *	Loads a the cached table records from the cache.
	 *
	 *	@return array The unserialized tables array
	 */

	public function load() {

		if ($this->isOutdated) {
			return false;
		}

		return unserialize(file_get_contents($this->cacheFile));

	}


	/**
	 *	Saves the serialized table records array to the cache. 
	 *
	 *	@param array $records
	 */

	public function save($records) {

		Core\FileSystem::write($this->cacheFile, serialize($records));

	}


}