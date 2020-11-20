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

	
	private $baseDir = AM_BASE_DIR . AM_DIR_CACHE . '/airtable';


	private $lifeTime = 43200;


	private $isOutdated = true;


	private $tablesFile = false;


	public function __construct($options) {

		$this->baseDir = AM_BASE_DIR . AM_DIR_CACHE . '/airtable/' . $options->base;
		$this->tablesFile = $this->baseDir . '/' . sha1(json_encode($options));
		Core\FileSystem::makeDir($this->baseDir);

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

	public function load() {

		if ($this->isOutdated) {
			return false;
		}

		return unserialize(file_get_contents($this->tablesFile));


	}


	public function save($tables) {

		Core\FileSystem::write($this->tablesFile, serialize($tables));

	}


}