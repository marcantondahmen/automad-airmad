/*!
 *	Airmad
 *
 *	An Airtable integration for Automad.
 *
 *	Copyright (C) 2020-2021 Marc Anton Dahmen - <https://marcdahmen.de>
 *	MIT license
 */


+function (Airmad) {

	Airmad.Slider = {

		slider: function (container, options) {

			var items = container.querySelectorAll('.airmad-slider-item');

			if (items.length > 0) {
				items[0].classList.add('active');
			}

			if (items.length > 1) {

				var prev = document.createElement('a'),
					next = document.createElement('a'),
					dotWrapper = document.createElement('div'),
					dots = [],
					activeItem = 0,
					timer,
					interval = function () {

						if (options.autoplay) {

							if (timer) {
								clearInterval(timer);
							}

							timer = setInterval(function () {
								change(activeItem + 1, null);
							}, 4000);

						}

					},
					change = function (index, event) {

						if (event) {
							event.preventDefault();
						}

						activeItem = index;

						if (activeItem < 0) {
							activeItem = items.length - 1;
						}

						if (activeItem >= items.length) {
							activeItem = 0
						}

						interval();
						fade();

					},
					fade = function () {

						var currentItem = container.querySelector('.airmad-slider-item.active');

						currentItem.classList.remove('active');
						items[activeItem].classList.add('active');

						if (options.dots) {

							var currentDot = container.querySelector('.airmad-slider-dots .active');

							currentDot.classList.remove('active');
							dots[activeItem].classList.add('active');

						}

					};

				prev.classList.add('airmad-slider-prev');
				next.classList.add('airmad-slider-next');
				container.appendChild(prev);
				container.appendChild(next);

				if (options.dots) {

					dotWrapper.classList.add('airmad-slider-dots');
					container.appendChild(dotWrapper);

					for (var i = 0; i < items.length; i++) {

						(function (index) {

							var dot = document.createElement('a');

							dot.addEventListener('click', function (event) {
								change(index, event);
							});

							if (index === 0) {
								dot.classList.add('active');
							}

							dots.push(dot);
							dotWrapper.appendChild(dot);

						}(i));

					}

				}

				prev.addEventListener('click', function (event) {
					change(activeItem - 1, event);
				});

				next.addEventListener('click', function (event) {
					change(activeItem + 1, event);
				});

				interval();

			}

		},

		init: function () {

			var dataAttr = 'data-airmad-slider',
				sliders = document.body.querySelectorAll('[' + dataAttr + ']');

			if (sliders.length) {

				sliders.forEach(function (container) {

					var options = {
						autoplay: false,
						dots: true
					};

					container.removeAttribute(dataAttr);
					Airmad.Slider.slider(container, options);

				});

			}

		}

	}

	document.addEventListener('DOMContentLoaded', Airmad.Slider.init);

}(window.Airmad = window.Airmad || {});