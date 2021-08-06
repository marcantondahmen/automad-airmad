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

use Automad\Core\Debug;
use Automad\Core\FileSystem;

defined('AUTOMAD') or die('Direct access not permitted!');

class ModelCache {
	/**
	 *	The cache directory.
	 */
	private $cacheDir = AM_BASE_DIR . AM_DIR_CACHE . '/airmad/models';

	/**
	 *	The cache file for the table records.
	 */
	private $cacheFile = false;

	/**
	 *	Cache is outdated or not.
	 */
	private $isOutdated = true;

	/**
	 *	The cache lifetime.
	 */
	private $lifeTime = 7200;

	/**
	 *	The model cache constructor.
	 *
	 *	@param array $options
	 */
	public function __construct($options) {
		if (defined('AIRMAD_CACHE_LIFETIME')) {
			$this->lifeTime = AIRMAD_CACHE_LIFETIME;
		}

		if (!empty($_GET['airmad_force_sync'])) {
			$this->lifeTime = 0;
		}

		Debug::log($this->lifeTime, 'Airmad model cache lifetime');
		Debug::log($options, 'New Airmad model cache instance for');

		$filtersJson = json_encode($options->filters);
		$hash = sha1("{$options->base}/{$options->table}/{$options->view}/{$options->linked}/{$filtersJson}/{$options->formula}");
		$this->cacheFile = $this->cacheDir . '/' . $hash;
		FileSystem::makeDir($this->cacheDir);

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
	 *	Loads a the cached data from the cache.
	 *
	 *	@return array The unserialized data array
	 */
	public function load() {
		if ($this->isOutdated) {
			return false;
		}

		Debug::log('Loading model data from cache');

		return unserialize(file_get_contents($this->cacheFile));
	}

	/**
	 *	Saves the serialized data array to the cache.
	 *
	 *	@param array $data
	 */
	public function save($data) {
		Debug::log('Saving model data to cache');
		FileSystem::write($this->cacheFile, serialize($data));
	}
}