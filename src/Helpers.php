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

use Automad\Core\Str;

defined('AUTOMAD') or die('Direct access not permitted!');

class Helpers {
	/**
	 * If equals condition.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
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
	 * If not equals condition.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
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
	 * If sanitized equals condition.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
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
	 * If sanitized not equals condition.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
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
	 * Encode context to JSON.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function json($template, $context, $args, $source) {
		return json_encode($context->get($args));
	}

	/**
	 * Renders Markdown content.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function markdown($template, $context, $args, $source) {
		return Str::markdown($context->get($args));
	}

	/**
	 * Regex search and replace.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function replace($template, $context, $args, $source) {
		$args = Utils::resolveCsvArgs($args, $context);

		return preg_replace($args[0], $args[1], $args[2]);
	}

	/**
	 * Sanitize content.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function sanitize($template, $context, $args, $source) {
		return Utils::sanitize($context->get($args));
	}

	/**
	 * Renders a slider component.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function slider($template, $context, $args, $source) {
		return Slider::render($template, $context, $args, 'large');
	}

	/**
	 * Renders a larg slider component.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function sliderLarge($template, $context, $args, $source) {
		return Slider::render($template, $context, $args, 'full');
	}

	/**
	 * Iterate a sorted array.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function sorted($template, $context, $args, $source) {
		$args = Utils::resolveCsvArgs($args, $context);
		$data = $args[0];

		if (is_array($data) && count($data)) {
			if (!empty($args[1])) {
				$key = $args[1];

				usort($data, function ($a, $b) use ($key) {
					return $a->$key - $b->$key;
				});
			} else {
				sort($data);
			}

			return Utils::loop($data, $context, $template);
		} else {
			$template->setStopToken('else');
			$template->discard();
			$template->setStopToken(false);

			return $template->render($context);
		}
	}

	/**
	 * Loop unique array.
	 * Same as the built-in each helper but with reducing
	 * the array to only contain unique elements before looping.
	 *
	 * @param string $template
	 * @param object $context
	 * @param string $args
	 * @param string $source
	 * @return string The rendered output
	 */
	public static function unique($template, $context, $args, $source) {
		$data = $context->get($args);

		if (is_array($data) && count($data)) {
			if (array_values($data) === $data) {
				$unique = array();

				foreach ($data as $item) {
					$unique[serialize($item)] = $item;
				}

				$data = array_values($unique);
			}

			return Utils::loop($data, $context, $template);
		} else {
			$template->setStopToken('else');
			$template->discard();
			$template->setStopToken(false);

			return $template->render($context);
		}
	}
}
