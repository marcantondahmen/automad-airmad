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

defined('AUTOMAD') or die('Direct access not permitted!');

class AutomadDataBridge {
	/**
	 * The Automad object.
	 */
	private $Automad;

	/**
	 * The data array.
	 */
	private $data;

	/**
	 * The constructor.
	 *
	 * @param object $Automad
	 */
	public function __construct($Automad) {
		$this->data = array_merge(
			$Automad->Shared->data,
			$Automad->Context->get()->data
		);
	}

	/**
	 * Return the data array.
	 *
	 * @return array $data.
	 */
	public function get() {
		return $this->data;
	}
}
