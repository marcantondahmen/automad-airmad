<?php
/**
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * @author Marc Anton Dahmen
 * @copyright Copyright (C) 2021 Marc Anton Dahmen - <https://marcdahmen.de>
 * @license MIT license
 */

namespace Airmad;

use Automad\Core\Parse;
use URLify;
use Handlebars\Context;

defined('AUTOMAD') or die('Direct access not permitted!');

class Utils {
	/**
	 * Loop items in a template.
	 *
	 * Code partially taken from the each method of the salesforce/handlebars-php project:
	 * https://github.com/salesforce/handlebars-php/blob/59fc47c7b2701659cb483d0f3461c4f712693b2b/src/Handlebars/Helpers.php#L253
	 *
	 * @param object $data
	 * @param object $context
	 * @param string $template
	 * @return string the buffer
	 */
	public static function loop($data, $context, $template) {
		$buffer = '';
		$islist = array_values($data) === $data;
		$itemCount = count($data);

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
	}

	/**
	 * Resolves the values for a given CSV list. Values can either be double quoted strings or field names.
	 * Field names are resolved than to a value while strings will just have their wrapping quotes removed.
	 *
	 * @param string $csv
	 * @param object $context
	 * @return array An array with all resolved values
	 */
	public static function resolveCsvArgs($csv, $context) {
		$args = array();

		foreach (Parse::csv($csv) as $arg) {
			if (preg_match('/"([^"]*)"/', $arg, $matches)) {
				$args[] = $matches[1];
			} else {
				$args[] = $context->get($arg);
			}
		}

		return $args;
	}
	/**
	 * Sanitizes a string to be camparable and used as a filter.
	 *
	 * @param string $str
	 * @param boolean $stripQuotes
	 * @return string The sanitized string.
	 */
	public static function sanitize($str, $stripQuotes = false) {
		$str = str_replace('/', '-', $str);
		$str = str_replace(array('&mdash;', '&ndash;'), '-', $str);
		$str = strtolower(URLify::transliterate($str));

		if ($stripQuotes) {
			$str = str_replace('"', '', $str);
		}

		return preg_replace('/[^\w"_\[\]]+/is', '-', $str);
	}
}
