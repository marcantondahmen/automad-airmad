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
use Handlebars\Handlebars;
use Handlebars\Loader\FilesystemLoader;
use Automad\Core;

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

		if (AirmadRuntime::isRegistered($hash)) {
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
		$this->model = $AirmadModel->get();
		$AirmadModel = NULL;

		Core\Debug::log($this->model, 'Model');

		$count = count($this->model->records);

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
	 *	Resolves the values for a given CSV list. Values can either be double quoted strings or field names.
	 *	Field names are resolved than to a value while strings will just have their wrapping quotes removed.
	 *
	 *	@param string $csv 
	 *	@param object $context 
	 *	@return array An array with all resolved values
	 */

	private function resolveCsvArgs($csv, $context) {

		$args = array();

		foreach (Core\Parse::csv($csv) as $arg) {

			if (preg_match('/"([^"]*)"/', $arg, $matches)) {
				$args[] = $matches[1];
			} else {
				$args[] = $context->get($arg);
			}

		}

		return $args;

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

		$handlebars = new Handlebars($settings);

		$handlebars->addHelper('json', function($template, $context, $args, $source) {
			return json_encode($context->get($args));
		});

		$handlebars->addHelper('sanitize', function($template, $context, $args, $source) {
			return AirmadUtils::sanitize($context->get($args));
		});

		$handlebars->addHelper('slider', function($template, $context, $args, $source) {
			return AirmadSlider::render($template, $context, $args, 'large');
		});

		$handlebars->addHelper('sliderLarge', function($template, $context, $args, $source) {
			return AirmadSlider::render($template, $context, $args, 'full');
		});

		$handlebars->addHelper('if==', function($template, $context, $args, $source) {

			$argsArray = $this->resolveCsvArgs($args, $context);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if ($argsArray[0] == $argsArray[1]) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

		$handlebars->addHelper('ifsan==', function($template, $context, $args, $source) {

			$argsArray = $this->resolveCsvArgs($args, $context);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if (AirmadUtils::sanitize($argsArray[0], true) == AirmadUtils::sanitize($argsArray[1], true)) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

		$handlebars->addHelper('if!=', function($template, $context, $args, $source) {

			$argsArray = $this->resolveCsvArgs($args, $context);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if ($argsArray[0] != $argsArray[1]) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

		$handlebars->addHelper('ifsan!=', function($template, $context, $args, $source) {

			$argsArray = $this->resolveCsvArgs($args, $context);

			if (!empty($argsArray[0]) && !empty($argsArray[1])) {
				if (AirmadUtils::sanitize($argsArray[0], true) != AirmadUtils::sanitize($argsArray[1], true)) {
					$buffer = $template->render($context);
					$template->discard();
					return $buffer;
				}
			}

			return false;

		});	

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