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

namespace Automad;

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

		if (!defined('AIRTABLE_TOKEN')) {
			return 'AIRTABLE_TOKEN not defined!';
		}

		$this->token = AIRTABLE_TOKEN;

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
		$this->options->linked = Core\Parse::csv($this->options->linked);
		$this->options->limit = intval($this->options->limit);
		$this->options->page = intval($this->options->page);

		$this->tables = $this->getTables();

		if (is_readable(AM_BASE_DIR . $this->options->template)) {
			$this->options->template = file_get_contents(AM_BASE_DIR . $this->options->template);
		}
		
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

				if (!empty($record['fields'][$filter])) {
					$match = preg_match("/{$value}/is", json_encode($record['fields'][$filter]));
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

		$mst = new \Mustache_Engine(array('entity_flags' => ENT_QUOTES));
		$output = '';

		$link = function($text, $helper) {
			
			preg_match('/(\w[\w\s\-]+\w)\s*=\>\s*(.*)/is', $text, $matches);

			$text = <<< MST
					{{# fields.{$matches[1]} }}
						{{# with }}
							{{.}} in {$matches[1]} => {$matches[2]}
						{{/ with }}
					{{/ fields.{$matches[1]} }}
MST;

			return $helper->render($text);

		};

		$equals = function($text, $helper) {

			$regex = '/\s*([\w_\-\+]+)\s*\=\s*([\{\}\w_\-\+]+)\s*\:(.*)/s';
			preg_match($regex, $helper->render($text), $matches);
			
			if (!empty($matches)) {
				
				if (trim($matches[1]) == trim($matches[2])) {
					return $matches[3];
				}
			}

		};

		$with = function($text, $helper) use ($mst) {

			$regex = '/(\w+?)\s+in\s+(\w[\w\s\-]+\w)\s*=\>\s*(.*)/is';
			preg_match($regex, $helper->render($text), $matches);
			$record = $matches[1];
			$table = $this->tables[$matches[2]];
			$template = str_replace(array('{%', '%}'), array('{{', '}}'), $matches[3]);
			$key = array_search($record, array_column($table, 'id'));
			
			return $mst->render($template, $table[$key]);

		};
	
		foreach ($this->tables[$this->options->table] as $record) {
			$record['link'] = $link;
			$record['equals'] = $equals;
			$record['with'] = $with;
			$output .= $mst->render($this->options->template, $record);
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
		
		foreach ($this->options->linked as $key) {
			$tables[$key] = $this->getRecords($key);
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

			if (isset($data['offset'])) {
				$offset = $data['offset'];
			} else {
				$offset = false;
			}

			if (!empty($data['records'])) {
				$records = array_merge($records, $data['records']);
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
			$data = json_decode($output, true);
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