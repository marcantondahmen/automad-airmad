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

class TableLink {
	/**
	 * A field that links to the table.
	 */
	public string $field;

	/**
	 * The table name to be requested.
	 */
	public Table $Table;

	/**
	 * The constructor.
	 *
	 * @param string $name
	 * @param Table $Table
	 */
	public function __construct(string $field, string $table, array $tableFields) {
		$this->field = $field;
		$this->Table = new Table($table, $tableFields);
	}

	/**
	 * Parse a table link string.
	 *
	 * Possible link formats are:
	 * Table
	 * Table[Field1 Field2]
	 * Field => Table
	 * Field => Table[Field1 Field2]
	 *
	 * @param string $link
	 * @return TableLink|null
	 */
	public static function fromString(string $link): ?TableLink {
		$parts = preg_split('/\s*\=\>\s*/', $link);

		if (empty($parts)) {
			return null;
		}

		$field = trim(preg_replace('/\[[\w\s]+\]/is', '', $parts[0]));

		if (empty($parts[1])) {
			$tableAndFields = $parts[0];
		} else {
			$tableAndFields = $parts[1];
		}

		preg_match('/(?P<name>\w+)(?:\[(?P<fields>[\w\s]+)\])?/', $tableAndFields, $matches);

		if (!empty($matches) && !empty($matches['name'])) {
			$tableFields = array();

			if (!empty($matches['fields'])) {
				$tableFields = preg_split('/\s+/', $matches['fields']);
			}

			return new TableLink($field, $matches['name'], $tableFields);
		}

		return null;
	}
}
