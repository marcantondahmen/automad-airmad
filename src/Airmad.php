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
		
		$this->link();
		$this->filter();

		$count = count($this->tables[$this->options->table]);

		$this->slice();
		$this->findActiveRecords();

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
	 *	Finds records that appear in the query string as table filters and adds an "active" field.
	 */

	private function findActiveRecords() {

		foreach (array_keys($_GET) as $filter) {
			
			if (array_key_exists($filter, $this->tables)) {
				
				$id = Core\Request::query($filter);
				$key = array_search($id, array_column($this->tables[$filter], 'id'));

				if ($key !== false) {
					$this->tables[$filter][$key]->active = true;
				}
			
			}

		}

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
	 *	Links records of linked tables.
	 */

	private function link() {
	
		array_walk($this->tables[$this->options->table], function(&$record) {

			$linked = array();

			foreach ($record->fields as $fieldName => $ids) {

				if (in_array($fieldName, array_keys($this->tableMap))) {

					$fields = array();
					$tableName = $this->tableMap[$fieldName];

					foreach ($ids as $id) {
						
						$key = array_search($id, array_column($this->tables[$tableName], 'id'));

						if ($key !== false) {
							$fields[] = $this->tables[$tableName][$key]->fields;
						}

					}

					$linked[$fieldName] = $fields;
					$fields = NULL;

				}

			}
			
			$record->fields->{'@'} = (object) $linked;
			$linked = NULL;

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

				$fieldStr = '';

				// Search in record ID array.
				if (!empty($record->fields->$filter)) {
					$fieldStr = json_encode($record->fields->$filter);
				}

				// Also search in linked table values.
				if (!empty($record->fields->{'@'}->$filter)) {
					$fieldStr .= json_encode($record->fields->{'@'}->$filter);
				}

				if ($fieldStr) {
					$match = preg_match("/{$value}/is", $fieldStr);
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
		$handlebars = new Handlebars();
		$handlebars->addHelper('slider', function($template, $context, $args, $source) {

			preg_match('/^([\s\w\-\.\_]+?)(\s+\d{1,3}%)?$/i', $args, $argsArray);
			$slider = '';

			print_r($argsArray);

			if (!empty($argsArray[1])) {

				$images = $context->get(trim($argsArray[1]));
				$padding = '';

				if (!empty($argsArray[2])) {
					$padding = ' style="--airmad-slider-padding-top:' . trim($argsArray[2]) . ';"';
				}

				$slider = '<div class="airmad-slider"' . $padding . ' data-airmad-slider>';

				if (!empty($images) && is_array($images)) {
					
					foreach ($images as $image) {
						$slider .= <<< HTML
									<div class="airmad-slider-item">
										<img src="{$image->thumbnails->large->url}">
									</div>	
	HTML;
					}

				}

				$slider .= '</div>';

			}			

			return $slider;

		});

		foreach ($this->tables[$this->options->table] as $record) {
			$output .= $handlebars->render($this->options->template, $record);
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