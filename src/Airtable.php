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

class Airtable {


	private $apiUrl = 'https://api.airtable.com/v0';

	private $token = false;

	private $options = array();


	/**
	 *	The main function.
	 *
	 *	@param array $options
	 *	@param object $Automad
	 *	@return string the output of the extension
	 */

	public function Airtable($options, $Automad) {

		if (AirtableRuntimeCache::$output) {
			return AirtableRuntimeCache::$output;
		}

		if (!defined('AIRTABLE_TOKEN')) {
			return 'AIRTABLE_TOKEN not defined!';
		}

		$this->token = AIRTABLE_TOKEN;

		$defaults = array(
			'base' => false,
			'tables' => false,
			'view' => false,
			'template' => false
		);

		$this->options = (object) array_merge($defaults, $options);
		$this->options->tables = Core\Parse::csv($this->options->tables);
		$this->activeTableName = $this->options->tables[0];

		$cache = new AirtableCache($this->options);

		if (!$this->tables = $cache->load()) {
			$this->tables = $this->requestAllTables();
			$cache->save($this->tables);
		}

		if (is_readable(AM_BASE_DIR . $this->options->template)) {
			$this->options->template = file_get_contents(AM_BASE_DIR . $this->options->template);
		}
		
		AirtableRuntimeCache::$output = $this->render();

		return AirtableRuntimeCache::$output;

	}


	private function render() {

		$mst = new \Mustache_Engine(array('entity_flags' => ENT_QUOTES));
		$output = '';

		
		$link = function($text, $helper) {
			
			preg_match('/(\w[\w\s\-]+\w)\s*=\>\s*(.*)/is', $text, $matches);

			$text = <<< MST
					{{# {$matches[1]} }}
						{{# with }}
							{{.}} in {$matches[1]} => {$matches[2]}
						{{/ with }}
					{{/ {$matches[1]} }}
MST;

			return $helper->render($text);

		};

		$with = function($text, $helper) use ($mst) {

			$regex = '/(\w+?)\s+in\s+(\w[\w\s\-]+\w)\s*=\>\s*(.*)/is';
			preg_match($regex, $helper->render($text), $matches);
			$record = $matches[1];
			$table = $this->tables[$matches[2]];
			$template = str_replace(array('{%', '%}'), array('{{', '}}'), $matches[3]);
			$key = array_search($record, array_column($table, 'id'));
			$data = $table[$key];
			
			return $mst->render($template, $data['fields']);

		};
	
		foreach ($this->tables[$this->activeTableName] as $record) {

			$data = $record['fields'];
			$data['link'] = $link;
			$data['with'] = $with;
			$output .= $mst->render($this->options->template, $data);

		}
		
		return $output;

	}
	

	private function requestAllTables() {

		$tables = array();
		$i = 0;
	
		foreach ($this->options->tables as $tableName) {

			$view = false;

			if ($i == 0) {
				$view = $this->options->view;
			}

			$tables[$tableName] = $this->requestAllRecords($tableName, $view);
			$i++;

		}

		return $tables;

	}



	private function requestAllRecords($table, $view) {

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

			$records = array_merge($records, $data['records']);

		}

		return $records;

	}


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


}