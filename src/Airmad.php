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
	 *	The filter data array to be used to create autocomplete data lists for input fields or select fields.
	 */

	private $filterData = array();


	/**
	 *	The actual records array.
	 */

	private $records = array();


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

		$defaults = array(
			'base' => false,
			'table' => false,
			'view' => false,
			'linked' => false,
			'template' => false,
			'filters' => false,
			'limit' => 20,
			'page' => 1,
			'prefix' => false
		);

		$this->options = (object) array_merge($defaults, $options);
		$this->options->filters = Core\Parse::csv($this->options->filters);
		$this->options->limit = intval($this->options->limit);
		$this->options->page = intval($this->options->page);

		if (empty($this->options->template)) {
			return 'Please provide a value for the <code>template</code> parameter!';
		}

		if (is_readable(AM_BASE_DIR . $this->options->template)) {
			$this->options->template = file_get_contents(AM_BASE_DIR . $this->options->template);
		}

		if (empty($this->options->prefix)) {
			return 'Please provide a value for the <code>prefix</code> parameter!';
		}

		$AirmadModel = new AirmadModel($this->options); 
		$this->records = $AirmadModel->getRecords();
		$this->filterData = $AirmadModel->getFilterData();
		$AirmadModel = NULL;

		$this->filter();

		$count = count($this->records);

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

		$this->records = array_filter($this->records, function($record) use ($filters) {

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
	 *	Renders an item template.
	 *
	 *	@return string The rendered output.
	 */

	private function render() {

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

		return $handlebars->render(
			$this->options->template, 
			array(
				'records' => $this->records,
				'filterData' => $this->filterData
			)
		);

	}
	

	/**
	 *	Slices the main records array of the main table to fit the pagination settings.
	 */

	private function slice() {

		$offset = ($this->options->page - 1) * $this->options->limit;
		
		$this->records = array_slice(
			$this->records,
			$offset,
			$this->options->limit
		);

	}


}