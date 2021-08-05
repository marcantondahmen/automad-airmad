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

defined('AUTOMAD') or die('Direct access not permitted!');

class Slider {
	/**
	 *	Renders a slider component.
	 *
	 *	@param string $template
	 *	@param object $context
	 *	@param string $args
	 *	@param string $var
	 *	@return string The rendered output
	 */
	public static function render($template, $context, $args, $var) {
		preg_match('/^([\s\w\-\.\_]+?)(\s+\d{1,3}%)?$/i', $args, $argsArray);
		$slider = '';

		if (!empty($argsArray[1])) {
			$images = $context->get(trim($argsArray[1]));
			$padding = '';

			if (!empty($argsArray[2])) {
				$padding = ' style="--airmad-slider-padding-top:' . trim($argsArray[2]) . ';"';
			}

			$slider = '<div class="airmad-slider"' . $padding . ' data-airmad-slider>';

			if (!empty($images) && is_array($images)) {
				foreach ($images as $image) {
					if (!empty($image->thumbnails) && !empty($image->thumbnails->$var) && !empty($image->thumbnails->$var->url)) {
						$url = $image->thumbnails->$var->url;

						$slider .= <<< HTML
									<div class="airmad-slider-item $var">
										<img src="$url">
									</div>	
HTML;
					}
				}
			}

			$slider .= '</div>';
		}

		return $slider;
	}
}