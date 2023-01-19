<?php
/**
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (C) 2023 Marc Anton Dahmen - <https://marcdahmen.de>
 * @license MIT license
 */

namespace Airmad;

defined('AUTOMAD') or die('Direct access not permitted!');

class Table {
	/**
	 * A list of fields that have to be included in the request.
	 */
	public array $fields;

	/**
	 * The table name to be requested.
	 */
	public string $name;

	/**
	 * The constructor.
	 *
	 * @param string $name
	 * @param array $fields
	 */
	public function __construct(string $name, array $fields) {
		$this->name = $name;
		$this->fields = $fields;
	}
}
