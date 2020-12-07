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
use Handlebars\Handlebars;
use Automad\Core;

defined('AUTOMAD') or die('Direct access not permitted!');


class Airmad {


	/**
	 *	The base URL of the Airtable API.
	 */

	private $apiUrl = 'https://api.airtable.com/v0';


	/**
	 *	The authentication token.
	 */

	private $token = false;


	/**
	 *	The options array.
	 */

	private $options = array();


	/**
	 *	The name of the ID field.
	 */

	private $idFieldName = '_ID';


	/**
	 *	The main function.
	 *
	 *	@param array $options
	 *	@param object $Automad
	 *	@return string the output of the extension
	 */

	public function Airmad($options, $Automad) {

		$hash = sha1(json_encode($options));

		if (AirmadRuntime::isRegistered($hash)) {
			return false;
		}

		if (!defined('AIRMAD_TOKEN')) {
			return 'AIRMAD_TOKEN not defined!';
		}

		$this->token = AIRMAD_TOKEN;

		$defaults = array(
			'base' => false,
			'table' => false,
			'view' => false,
			'linked' => false,
			'template' => false,
			'filters' => false,
			'limit' => 20,
			'page' => 1,
			'prefix' => ':airmad'
		);

		$this->options = (object) array_merge($defaults, $options);
		$this->options->filters = Core\Parse::csv($this->options->filters);
		$this->options->limit = intval($this->options->limit);
		$this->options->page = intval($this->options->page);

		$this->tableMap = $this->buildTableMap(Core\Parse::csv($this->options->linked));
		$this->tables = $this->getTables();

		if (is_readable(AM_BASE_DIR . $this->options->template)) {
			$this->options->template = file_get_contents(AM_BASE_DIR . $this->options->template);
		}
		
		$this->prepareModel();
		$this->filter();

		$count = count($this->tables[$this->options->table]);

		$this->slice();

		$output = $this->render();

		$Toolbox = new Core\Toolbox($Automad);
	
		$Toolbox->set(array(
			"{$this->options->prefix}Output" => $output,
			"{$this->options->prefix}Memory" => memory_get_peak_usage(true),
			"{$this->options->prefix}Count" => $count,
			"{$this->options->prefix}Page" => $this->options->page,
			"{$this->options->prefix}Pages" => ceil($count / $this->options->limit)
		));

		AirmadRuntime::register($hash);
		$this->tables = NULL;

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
	 *	Links records of linked tables and creates record id field.
	 */

	private function prepareModel() {
	
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
	 *	Filters the main table for the items defined in $options->filters.
	 */

	private function filter() {

		$table = $this->options->table;

		$filters = array();

		foreach ($this->options->filters as $filter) {

			$value = Core\Request::query(str_replace(' ', '_', $filter));

			if ($value) {
				$filters[$filter] = $value;
			}
			
		}

		if (empty($filters)) {
			return false;
		}

		$this->tables[$table] = array_filter($this->tables[$table], function($record) use ($filters) {

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
					$match = preg_match("/{$value}/is", $data);
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
	 *	Renders an item template.
	 *
	 *	@return string The rendered output.
	 */

	private function render() {

		$output = '';
		$handlebars = new Handlebars(array('enableDataVariables' => true));

		$handlebars->addHelper('slider', function($template, $context, $args, $source) {
			return AirmadSlider::render($template, $context, $args, 'large');
		});

		$handlebars->addHelper('sliderLarge', function($template, $context, $args, $source) {
			return AirmadSlider::render($template, $context, $args, 'full');
		});

		$handlebars->addHelper('if==', function($template, $context, $args, $source) {

			$argsArray = Core\Parse::csv($args);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if ($context->get($argsArray[0]) == $argsArray[1]) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

		$handlebars->addHelper('if!=', function($template, $context, $args, $source) {

			$argsArray = Core\Parse::csv($args);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if ($context->get($argsArray[0]) != $argsArray[1]) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

		foreach ($this->tables[$this->options->table] as $record) {
			$output .= $handlebars->render($this->options->template, $record->fields);
		}
		
		return $output;

	}
	

	/**
	 *	Get all required tables including the linked ones. 
	 *
	 *	@return array The tables array.
	 */

	private function getTables() {

		$tables = array();
		$tables[$this->options->table] = $this->getRecords($this->options->table, $this->options->view);
		
		foreach (array_values($this->tableMap) as $tableName) {
			$tables[$tableName] = $this->getRecords($tableName);
		}

		return $tables;

	}


	/**
	 *	Requests all records of a table.
	 *
	 *	@param string $table
	 *	@param string $view
	 */

	private function getRecords($table, $view = false) {

		$cache = new AirmadCache($this->options->base, $table, $view);

		if ($records = $cache->load()) {
			return $records;
		}

		$table = rawurlencode($table);
		$records = array();		
		$url = "$this->apiUrl/{$this->options->base}/$table";

		$query = array(
			'maxRecords' => 100000,
			'pageSize' => 100,
			'view' => $view
		);

		$query = array_filter($query);

		$offset = true;
		
		while ($offset) {

			if (strlen($offset) > 1) {
				$query['offset'] = $offset;
			} 

			$queryString = http_build_query($query);

			$data = $this->request("$url?$queryString");

			if (isset($data->offset)) {
				$offset = $data->offset;
			} else {
				$offset = false;
			}

			if (!empty($data->records)) {
				$records = array_merge($records, $data->records);
			}

		}

		$cache->save($records);

		return $records;

	}


	/**
	 *	Makes an API curl request.
	 *
	 *	@param string $url
	 */

	private function request($url) {

		$data = array();
		$header = array();

		$header[] = 'Content-type: application/json';
		$header[] = "Authorization: Bearer $this->token";

		$options = array(
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_RETURNTRANSFER => 1, 
			CURLOPT_TIMEOUT => 300,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_URL => $url
		);
		
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$output = curl_exec($curl);
		
		if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 200 && !curl_errno($curl)) {	
			$data = json_decode($output);
		}
		
		curl_close($curl);

		return $data;

	}


	/**
	 *	Slices the main records array of the main table to fit the pagination settings.
	 */

	private function slice() {

		$offset = ($this->options->page - 1) * $this->options->limit;
		
		$this->tables[$this->options->table] = array_slice(
			$this->tables[$this->options->table],
			$offset,
			$this->options->limit
		);

	}


}