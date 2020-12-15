<?php
/**
 *	Airmad
 *
 *	An Airtable integration for Automad.
 *
 *	@author Marc Anton Dahmen
 *	@copyright Copyright (C) 2020 Marc Anton Dahmen - <https://marcdahmen.de> 
 *	@license MIT license
 */

namespace Airmad;
use Automad\Core;


defined('AUTOMAD') or die('Direct access not permitted!');


class AirmadModel {


	/**
	 *	The options array.
	 */

	private $options = array();


	/**
	 *	The table map array (field => table).
	 */	

	private $tableMap = array();


	/**
	 *	An array containing all tables.
	 */

	private $tables = array();


	/**
	 *	The name of the ID field.
	 */

	private $idFieldName = '_ID';

		
	/**
	 *	The constructor.
	 *
	 *	@param object $options
	 */

	public function __construct($options) {

		$this->options = $options;
		$this->tableMap = $this->buildTableMap(Core\Parse::csv($options->linked));
		$this->tables = $this->getTables();
		$this->prepare();

	}


	/**
	 *	Builds a map of fields linked to tables by passing an array of strings like "field => table".
	 *
	 *	@param array $links
	 *	@return array The map array
	 */

	private function buildTableMap($links) {

		$tableMap = array();

		foreach ($links as $link) {

			$parts = preg_split('/\s*\=\>\s*/', $link);

			if (!empty($parts)) {

				$field = $parts[0];
				$table = $field;

				if (!empty($parts[1])) {
					$table = $parts[1];
				}

				$tableMap[$field] = $table;

			}	

		}

		return $tableMap;

	}


	/**
	 *	Get all required tables including the linked ones. 
	 *
	 *	@return array The tables array.
	 */

	private function getTables() {

		$tables = array();
		$AirmadAPI = new AirmadAPI($this->options);
		$tables[$this->options->table] = $AirmadAPI->getRecords($this->options->table, $this->options->view);
		
		foreach (array_values($this->tableMap) as $tableName) {
			$tables[$tableName] = $AirmadAPI->getRecords($tableName);
		}

		return $tables;

	}
	
	
	/**
	 *	Links records of linked tables and creates record id field.
	 */

	private function prepare() {
	
		array_walk($this->tables[$this->options->table], function(&$record) {

			foreach ($record->fields as $fieldName => $ids) {

				if (in_array($fieldName, array_keys($this->tableMap))) {

					$linkedRecords = array();
					$tableName = $this->tableMap[$fieldName];

					foreach ($ids as $id) {
						
						$key = array_search($id, array_column($this->tables[$tableName], 'id'));

						if ($key !== false) {
							$linkedRecordFields = $this->tables[$tableName][$key]->fields;
							$linkedRecordFields->{$this->idFieldName} = $id;
							$linkedRecords[] = $linkedRecordFields;
						}

					}
					
					$record->fields->{$fieldName} = $linkedRecords;
					$linkedRecords = NULL;

				}

			}
			
			// Make id accessible within fields.
			$record->fields->{$this->idFieldName} = $record->id;

		});

	}


	/**
	 *	Returns all records.
	 *
	 *	@return array The actual records array.
	 */

	public function getRecords() {
		return $this->tables[$this->options->table];
	}


	/**
	 *	Returns a unique list of filter records to be use as values for autocomplete lists.
	 *
	 *	@return array The filter data.
	 */

	public function getFilterData() {}
	

}