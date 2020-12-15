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
	 *	The actual records array.
	 */

	private $records = array();


	/**
	 *	The filter data array.
	 */

	private $filterData = array();


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

		$this->records = $this->filterRecords(
			$this->prepareRecords(
				$this->getTables()
			)
		);

		$this->filterData = $this->extractFilterData($this->records);

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
	 *	Filters records for the items defined in $options->filters.
	 *
	 *	@param array $records
	 *	@return array The filtered records array
	 */

	private function filterRecords($records) {

		$filters = array();

		foreach ($this->options->filters as $filter) {

			$value = Core\Request::query(str_replace(' ', '_', $filter));

			if ($value) {
				$filters[$filter] = $value;
			}
			
		}

		if (empty($filters)) {
			return $records;
		}

		return array_filter($records, function($record) use ($filters) {

			foreach ($filters as $filter => $value) {

				$data = '';

				if (!empty($record->fields->$filter)) {

					$data = $record->fields->$filter;

					if (is_array($data)) {

						$data = json_encode($data);

						// Remove linked IDs from JSON string to not confuse filters.
						$data = preg_replace('/\[("rec\w{14,20}",?)+\]/', '', $data);

						// Remove keys from JSON.
						$data = preg_replace('/"[^"]+"\:/', '', $data);

						// Remove special chars.
						$data = preg_replace('/[,"\{\}\[\]]+/', ' ', $data);

					}
					
				}

				if ($data) {
					$match = preg_match("/$value/is", $data);
				} else {
					$match = false;
				}
				
				if (!$match) {
					return false;
				}

			}

			return true;

		});

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
	 *	Links records of linked tables and creates record id field and returns the main table.
	 *
	 *	@param array $tables
	 *	@return array The main table records including the liked data.
	 */

	private function prepareRecords($tables) {
	
		array_walk($tables[$this->options->table], function(&$record) use ($tables) {

			foreach ($record->fields as $fieldName => $ids) {

				if (in_array($fieldName, array_keys($this->tableMap))) {

					$linkedRecords = array();
					$tableName = $this->tableMap[$fieldName];

					foreach ($ids as $id) {
						
						$key = array_search($id, array_column($tables[$tableName], 'id'));

						if ($key !== false) {
							$linkedRecordFields = $tables[$tableName][$key]->fields;
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

		return $tables[$this->options->table];

	}


	/**
	 *	Returns the model object.
	 *
	 *	@return array The model object.
	 */

	public function get() {

		return (object) array(
			'records' => $this->records,
			'filterData' => $this->filterData
		);

	}


	/**
	 *	Returns a unique list of filter records to be use as values for autocomplete lists.
	 *
	 *	@param array $records
	 *	@return array The filter data.
	 */

	private function extractFilterData($records) {

		$data = array();
		
		foreach ($this->options->filters as $filter) {

			$filterRecords = array();

			foreach ($records as $record) {

				if (is_array($record->fields->$filter)) {

					foreach ($record->fields->$filter as $item) {
						$filterRecords[md5(serialize($item))] = $item;
					}

				} else {

					$filterRecords[$record->fields->$filter] = $record->fields->$filter;

				}

			}

			$data[$filter] = $filterRecords;

		} 

		return $data;

	}
	

}