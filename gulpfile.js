/*!
 * Airmad
 *
 * An Airtable integration for Automad.
 *
 * Copyright (C) 2020-2021 Marc Anton Dahmen - <https://marcdahmen.de>
 * MIT license
 */

var gulp = require('gulp'),
	autoprefixer = require('gulp-autoprefixer'),
	cleanCSS = require('gulp-clean-css'),
	concat = require('gulp-concat'),
	favicons = require('favicons').stream,
	less = require('gulp-less'),
	rename = require('gulp-rename'),
	uglify = require('gulp-uglify-es').default,
	gutil = require('gulp-util');

// Error handling to prevent watch task to fail silently without restarting.
var onError = function (err) {
	gutil.log(gutil.colors.red('ERROR', err.plugin), err.message);
	gutil.beep();
	new gutil.PluginError(err.plugin, err, { showStack: true });
	this.emit('end');
};

// Concat minify and prefix all required js files.
gulp.task('airmad-js', function () {
	var uglifyOptions = {
		compress: {
			hoist_funs: false,
			hoist_vars: false,
		},
		output: {
			comments: /(license|copyright)/i,
			max_line_len: 500,
		},
	};

	return gulp
		.src('js/*.js')
		.pipe(uglify(uglifyOptions))
		.pipe(concat('airmad.min.js', { newLine: '\r\n\r\n' }))
		.pipe(gulp.dest('src'));
});

// Compile, minify and prefix alpha.less.
gulp.task('airmad-less', function () {
	var cleanCSSOptions = {
		format: { wrapAt: 500 },
		rebase: false,
	};

	return gulp
		.src('less/airmad.less')
		.pipe(less())
		.on('error', onError)
		.pipe(autoprefixer({ grid: false }))
		.pipe(cleanCSS(cleanCSSOptions))
		.pipe(rename({ suffix: '.min' }))
		.pipe(gulp.dest('src'));
});

// Watch task.
gulp.task('watch', function () {
	gulp.watch('less/*.less', gulp.series('airmad-less'));
	gulp.watch('js/*.js', gulp.series('airmad-js'));
});

// Favicons.
gulp.task('favicons', function () {
	return gulp
		.src('docs/source/_static/favicon.png')
		.pipe(
			favicons({
				appName: 'Airmad',
				appDescription: '',
				developerName: 'Marc Anton Dahmen',
				developerURL: 'https://marcdahmen.de',
				background: '',
				path: 'docs/source/_static/',
				url: '',
				display: 'browser',
				orientation: 'any',
				start_url: '',
				version: 1.0,
				logging: false,
				html: false,
				pipeHTML: false,
				replace: true,
				icons: {
					android: false, // Create Android homescreen icon. `boolean` or `{ offset, background }`
					appleIcon: false, // Create Apple touch icons. `boolean` or `{ offset, background }`
					appleStartup: false, // Create Apple startup images. `boolean` or `{ offset, background }`
					coast: false, // Create Opera Coast icon. `boolean` or `{ offset, background }`
					favicons: true, // Create regular favicons. `boolean`
					firefox: false, // Create Firefox OS icons. `boolean` or `{ offset, background }`
					windows: false, // Create Windows 8 tile icons. `boolean` or `{ background }`
					yandex: false,
				},
			})
		)
		.pipe(gulp.dest('docs/source/_static'));
});

// The default task.
gulp.task('default', gulp.series('airmad-js', 'airmad-less'));
