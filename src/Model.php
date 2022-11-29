<?php
/**
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (C) 2020-2021 Marc Anton Dahmen - <https://marcdahmen.de>
 * @license MIT license
 */

namespace Airmad;

use Automad\Core\Parse;
use Automad\Core\Request;

defined('AUTOMAD') or die('Direct access not permitted!');

class Model {
	/**
	 * The Automad data bridge object.
	 */
	private $AutomadDataBridge;

	/**
	 * The filter data array.
	 */
	private $filterData = array();

	/**
	 * The reduced filter data array. Only filters that matche the filtered set of records.
	 */
	private $filteredFilterData = array();

	/**
	 * The name of the ID field.
	 */
	private $idFieldName = '_ID';

	/**
	 * The options array.
	 */
	private $options = array();

	/**
	 * The actual records array.
	 */
	private $records = array();

	/**
	 * The table map array (field => table).
	 */
	private $tableMap = array();

	/**
	 * The constructor.
	 *
	 * @param object $options
	 * @param object $Automad
	 */
	public function __construct($options, $Automad) {
		$this->options = $options;

		$cache = new ModelCache($options);

		if ($data = $cache->load()) {
			$this->records = $data->records;
			$this->filterData = $data->filterData;
		} else {
			$this->tableMap = $this->buildTableMap(Parse::csv($options->linked));

			$this->records = $this->prepareRecords($this->getTables());
			$this->filterData = $this->extractFilterData($this->records);

			$data = (object) array('records' => $this->records, 'filterData' => $this->filterData);
			$cache->save($data);
		}

		$this->records = $this->filterRecords($this->records);
		$this->filteredFilterData = $this->extractFilterData($this->records);
		$this->AutomadDataBridge = new AutomadDataBridge($Automad);
	}

	/**
	 * Returns the model object.
	 *
	 * @return array The model object.
	 */
	public function get() {
		return (object) array(
			'records' => $this->records,
			'filters' => $this->filterData,
			'filteredFilters' => $this->filteredFilterData,
			'query' => $_GET,
			'count' => count($this->records),
			'pages' => ceil(count($this->records) / $this->options->limit),
			'automad' => $this->AutomadDataBridge->get()
		);
	}

	/**
	 * Builds a map of fields linked to tables by passing an array of strings like "field => table".
	 *
	 * @param array $links
	 * @return array The map array
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
	 * Returns a unique list of filter records to be use as values for autocomplete lists.
	 *
	 * @param array $records
	 * @return array The filter data.
	 */
	private function extractFilterData($records) {
		$data = array();

		foreach ($this->options->filters as $filter) {
			$filterRecords = array();

			foreach ($records as $record) {
				if (!empty($record->fields->$filter)) {
					if (is_array($record->fields->$filter)) {
						foreach ($record->fields->$filter as $item) {
							$filterRecords[md5(serialize($item))] = $item;
						}
					} else {
						$value = $record->fields->$filter;

						if (is_string($value) || is_numeric($value)) {
							$key = md5(serialize($value));
							$filterRecords[$key] = $value;
						}
					}
				}
			}

			$data[$filter] = array_values($filterRecords);
		}

		return $data;
	}

	/**
	 * Filters records for the items defined in $options->filters.
	 *
	 * @param array $records
	 * @return array The filtered records array
	 */
	private function filterRecords($records) {
		$filters = array();

		foreach ($this->options->filters as $filter) {
			$value = $_GET[str_replace(' ', '_', $filter)] ?? null;

			if ($value) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$filters[] = array($filter, $v);
					}
				} else {
					$filters[] = array($filter, $value);
				}
			}
		}

		if (empty($filters)) {
			return $records;
		}

		return array_filter($records, function ($record) use ($filters) {
			$dataCache = array();

			foreach ($filters as $keyValue) {
				[$filter, $value] = $keyValue;

				if (isset($dataCache[$filter])) {
					$data = $dataCache[$filter];
				} else {
					$data = '';

					if (!empty($record->fields->$filter)) {
						$data = $record->fields->$filter;
						$data = json_encode($data, JSON_UNESCAPED_UNICODE);
						// Remove keys from JSON.
						$data = preg_replace('/"[^"]+"\:/', '', $data);
						$data = Utils::sanitize($data);
					}

					// Cache sanitized data in case the records are filtered by multiple values
					// for the same filter.
					$dataCache[$filter] = $data;
				}

				if ($data) {
					$value = Utils::sanitize(htmlspecialchars_decode($value));
					$pattern = preg_quote($value);
					$match = preg_match("/$pattern/is", $data);
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
	 * Get all required tables including the linked ones.
	 *
	 * @return array The tables array.
	 */
	private function getTables() {
		$tables = array();
		$API = new API($this->options);
		$tables[$this->options->table] = $API->getRecords(
			$this->options->table,
			$this->options->view,
			$this->options->formula,
			$this->options->fields
		);

		foreach (array_values($this->tableMap) as $tableName) {
			$tables[$tableName] = $API->getRecords($tableName);
		}

		return $tables;
	}

	/**
	 * Links records of linked tables and creates record id field and returns the main table.
	 *
	 * @param array $tables
	 * @return array The main table records including the liked data.
	 */
	private function prepareRecords($tables) {
		array_walk($tables[$this->options->table], function (&$record) use ($tables) {
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
					$linkedRecords = null;
				}
			}

			// Make id accessible within fields.
			$record->fields->{$this->idFieldName} = $record->id;
		});

		return $tables[$this->options->table];
	}
}
