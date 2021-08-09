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

use Automad\Core\Str;
use Handlebars\Context;

defined('AUTOMAD') or die('Direct access not permitted!');

class Helpers {
	/**
	 *	If equals condition.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function ifEqual($template, $context, $args, $source) {
		$argsArray = Utils::resolveCsvArgs($args, $context);

		if (!empty($argsArray[0]) && !empty($argsArray[1])) {
			if ($argsArray[0] == $argsArray[1]) {
				$buffer = $template->render($context);
				$template->discard();

				return $buffer;
			}
		}

		return false;
	}

	/**
	 *	If not equals condition.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function ifNotEqual($template, $context, $args, $source) {
		$argsArray = Utils::resolveCsvArgs($args, $context);

		if (!empty($argsArray[0]) && !empty($argsArray[1])) {
			if ($argsArray[0] != $argsArray[1]) {
				$buffer = $template->render($context);
				$template->discard();

				return $buffer;
			}
		}

		return false;
	}

	/**
	 *	If sanitized equals condition.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function ifSanitizedEqual($template, $context, $args, $source) {
		$argsArray = Utils::resolveCsvArgs($args, $context);

		if (!empty($argsArray[0]) && !empty($argsArray[1])) {
			if (Utils::sanitize($argsArray[0], true) == Utils::sanitize($argsArray[1], true)) {
				$buffer = $template->render($context);
				$template->discard();

				return $buffer;
			}
		}

		return false;
	}

	/**
	 *	If sanitized not equals condition.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function ifSanitizedNotEqual($template, $context, $args, $source) {
		$argsArray = Utils::resolveCsvArgs($args, $context);

		if (!empty($argsArray[0]) && !empty($argsArray[1])) {
			if (Utils::sanitize($argsArray[0], true) != Utils::sanitize($argsArray[1], true)) {
				$buffer = $template->render($context);
				$template->discard();

				return $buffer;
			}
		}

		return false;
	}

	/**
	 *	Encode context to JSON.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function json($template, $context, $args, $source) {
		return json_encode($context->get($args));
	}

	/**
	 *	Renders Markdown content.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function markdown($template, $context, $args, $source) {
		return Str::markdown($context->get($args));
	}

	/**
	 *	Sanitize content.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function sanitize($template, $context, $args, $source) {
		return Utils::sanitize($context->get($args));
	}

	/**
	 *	Renders a slider component.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function slider($template, $context, $args, $source) {
		return Slider::render($template, $context, $args, 'large');
	}

	/**
	 *	Renders a larg slider component.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function sliderLarge($template, $context, $args, $source) {
		return Slider::render($template, $context, $args, 'full');
	}

	/**
	 *	Loop unique array.
	 *	Same as the built-in each helper but with reducing the array to only contain unique elements before looping.
	 *
	 *	Code partially taken from the each method of the salesforce/handlebars-php project:
	 *	https://github.com/salesforce/handlebars-php/blob/59fc47c7b2701659cb483d0f3461c4f712693b2b/src/Handlebars/Helpers.php#L253
	 *
	 *	@package Handlebars
	 *	@author fzerorubigd <fzerorubigd@gmail.com>
	 *	@author Behrooz Shabani <everplays@gmail.com>
	 *	@author Mardix <https://github.com/mardix>
	 *	@copyright 2012 (c) ParsPooyesh Co
	 *	@copyright 2013 (c) Behrooz Shabani
	 *	@copyright 2014 (c) Mardix
	 *	@license MIT
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $source
	 *	@return string The rendered output
	 */
	public static function unique($template, $context, $args, $source) {
		$data = $context->get($args);

		if (is_array($data) && count($data)) {
			$buffer = '';
			$islist = array_values($data) === $data;

			$itemCount = -1;

			if ($islist) {
				$unique = array();

				foreach ($data as $item) {
					$unique[serialize($item)] = $item;
				}

				$data = array_values($unique);
				$itemCount = count($data);
			}

			foreach ($data as $key => $var) {
				$tpl = clone $template;

				if ($islist) {
					$context->pushIndex($key);

					// If data variables are enabled, push the data related to this #each context
					if ($template->getEngine()->isDataVariablesEnabled()) {
						$context->pushData(array(
							Context::DATA_KEY => $key,
							Context::DATA_INDEX => $key,
							Context::DATA_LAST => $key == ($itemCount - 1),
							Context::DATA_FIRST => $key == 0,
						));
					}
				} else {
					$context->pushKey($key);

					// If data variables are enabled, push the data related to this #each context
					if ($template->getEngine()->isDataVariablesEnabled()) {
						$context->pushData(array(
							Context::DATA_KEY => $key,
						));
					}
				}

				$context->push($var);
				$tpl->setStopToken('else');
				$buffer .= $tpl->render($context);
				$context->pop();

				if ($islist) {
					$context->popIndex();
				} else {
					$context->popKey();
				}

				if ($template->getEngine()->isDataVariablesEnabled()) {
					$context->popData();
				}
			}

			return $buffer;
		} else {
			$template->setStopToken('else');
			$template->discard();
			$template->setStopToken(false);

			return $template->render($context);
		}
	}
}
