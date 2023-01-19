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

defined('AUTOMAD') or die('Direct access not permitted!');

class API {
	/**
	 * The base URL of the Airtable API.
	 */
	private $apiUrl = 'https://api.airtable.com/v0';

	/**
	 * The options array.
	 */
	private $options;

	/**
	 * The authentication token.
	 */
	private $token = false;

	/**
	 * The constructor.
	 *
	 * @param object $options
	 */
	public function __construct($options) {
		$this->options = $options;

		if (!defined('AIRMAD_TOKEN')) {
			exit('<h1>AIRMAD_TOKEN not defined!</h1>');
		}

		$this->token = AIRMAD_TOKEN;
	}

	/**
	 * Requests all records of a table.
	 *
	 * @param string $table
	 * @param string $view
	 * @param string $formula
	 * @param array $fields
	 */
	public function getRecords($table, $view = false, $formula = false, $fields = array()) {
		$cache = new Cache($this->options->base, $table, $view, $formula, $fields);

		if ($records = $cache->load()) {
			return $records;
		}

		$table = rawurlencode($table);
		$records = array();
		$url = "$this->apiUrl/{$this->options->base}/$table";

		$query = array(
			'maxRecords' => 50000,
			'pageSize' => 100,
			'view' => $view,
			'filterByFormula' => $formula
		);

		if (!empty($fields)) {
			$query['fields'] = $fields;
		}

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
	 * Makes an API curl request.
	 *
	 * @param string $url
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
}
