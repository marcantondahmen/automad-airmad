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

		if (AirtableRuntimeCache::$run) {
			return false;
		}

		AirtableRuntimeCache::$run = true;

		if (!defined('AIRTABLE_TOKEN')) {
			return 'AIRTABLE_TOKEN not defined!';
		}

		$this->token = AIRTABLE_TOKEN;

		$defaults = array(
			'base' => false,
			'table' => false,
			'template' => false
		);

		$this->options = (object) array_merge($defaults, $options);

		$records = $this->requestAllRecords($this->options->table);
		
		print_r($records);

	}


	private function requestAllRecords($table) {

		$records = array();		
		$url = "$this->apiUrl/{$this->options->base}/$table";

		$query = array(
			'view' => 'Grid view',
			'maxRecords' => 1000,
			'pageSize' => 100
		);

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

			$records = array_merge($records, $data->records);

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
			$data = json_decode($output);
		}
		
		curl_close($curl);

		return $data;

	}


}