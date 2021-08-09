<?php
/**
 *	Airmad
 *
 *	An Airtable integration for Automad.
 *
 *	@author Marc Anton Dahmen
 *	@copyright Copyright (C) 2020-2021 Marc Anton Dahmen - <https://marcdahmen.de>
 *	@license MIT license
 */

namespace Airmad;

use Automad\Core\Debug;
use Automad\Core\Parse;
use Automad\Core\Toolbox;
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;

defined('AUTOMAD') or die('Direct access not permitted!');

class Airmad {
	/**
	 *	The data model.
	 */
	private $model;

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

		if (Runtime::isRegistered($hash)) {
			return false;
		}

		$defaults = array(
			'base' => false,
			'table' => false,
			'view' => false,
			'linked' => false,
			'template' => false,
			'partials' => false,
			'filters' => false,
			'formula' => false,
			'limit' => 20,
			'page' => 1,
			'prefix' => false
		);

		$this->options = (object) array_merge($defaults, $options);
		$this->options->filters = Parse::csv($this->options->filters);
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

		$Model = new Model($this->options, $Automad);
		$this->model = $Model->get();
		$Model = null;

		Debug::log($this->model, 'Model');

		$count = count($this->model->records);

		$this->slice();

		$output = $this->render();

		$Toolbox = new Toolbox($Automad);

		$Toolbox->set(array(
			"{$this->options->prefix}Output" => $output,
			"{$this->options->prefix}Memory" => memory_get_peak_usage(true),
			"{$this->options->prefix}Count" => $count,
			"{$this->options->prefix}Page" => $this->options->page,
			"{$this->options->prefix}Pages" => ceil($count / $this->options->limit)
		));

		Runtime::register($hash);
	}

	/**
	 *	Renders an item template.
	 *
	 *	@return string The rendered output.
	 */
	private function render() {
		$settings = array('enableDataVariables' => true);

		if (!empty($this->options->partials)) {
			$partialsDir = AM_BASE_DIR . $this->options->partials;
			$partialsLoader = new FilesystemLoader($partialsDir, array('extension' => 'handlebars'));
			$settings['partials_loader'] = $partialsLoader;
		}

		$helpers = array(
			'if==' => 'ifEqual',
			'if!=' => 'ifNotEqual',
			'ifsan==' => 'ifSanitizedEqual',
			'ifsan!=' => 'ifSanitizedNotEqual',
			'json' => 'json',
			'markdown' => 'markdown',
			'sanitize' => 'sanitize',
			'slider' => 'slider',
			'sliderLarge' => 'sliderLarge',
			'unique' => 'unique'
		);

		$handlebars = new Handlebars($settings);

		foreach ($helpers as $name => $method) {
			$handlebars->addHelper($name, "\Airmad\Helpers::$method");
		}

		return $handlebars->render(
			$this->options->template,
			$this->model
		);
	}

	/**
	 *	Slices the main records array of the main table to fit the pagination settings.
	 */
	private function slice() {
		$offset = ($this->options->page - 1) * $this->options->limit;

		$this->model->records = array_slice(
			$this->model->records,
			$offset,
			$this->options->limit
		);
	}
}
