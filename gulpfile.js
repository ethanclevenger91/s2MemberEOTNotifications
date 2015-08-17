// Gulp
var gulp = require('gulp');

// Plugins
var autoprefix = require('gulp-autoprefixer');
var bowerFiles = require('main-bower-files');
var browserSync = require('browser-sync');
var concat = require('gulp-concat');
var cache = require('gulp-cache');
var del = require('del');
var flatten = require('gulp-flatten');
var jshint = require('gulp-jshint');
var minifyCSS = require('gulp-minify-css');
var notify = require('gulp-notify');
var path = require('path');
var plumber = require('gulp-plumber');
var rename = require('gulp-rename');
var runSequence = require('run-sequence');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');

// Define our paths
var paths = {
	scripts: 'src/js/**/*.js',
	styles: 'src/scss/**/*.scss',
	bowerDir: './bower_components'
};

var destPaths = {
	scripts: 'dist/js',
	styles: 'dist/css',
};

// Error Handling
// Send error to notification center with gulp-notify
var handleErrors = function() {
	notify.onError({
		title: "Compile Error",
		message: "<%= error.message %>"
	}).apply(this, arguments);
	this.emit('end');
};

//Make any bower-installed css files scss to prevent extra requests
gulp.task('css-to-scss', function() {
	return bowerFiles('**/*.css').map(function(file) {
		gulp.src(file)
			.pipe(rename(function(path) {
				path.basename = '_'+path.basename;
				path.basename = path.basename.replace('.min', '');
				path.extname = '.scss';
			}))
			.pipe(gulp.dest(path.dirname(file)));
	});
});

// Compile our Sass
gulp.task('styles', function() {
	return gulp.src(paths.styles)
		.pipe(plumber())
    .pipe(sourcemaps.init())
		.pipe(sass({
			errLogToConsole:true,
			includePaths:bowerFiles('**/*.{scss}').map(function(file) {
				return path.dirname(file);
			})
		}))
		.pipe(autoprefix({cascade:false}))
		.pipe(minifyCSS())
    .pipe(sourcemaps.write('../maps'))
		.pipe(gulp.dest(destPaths.styles))
		.pipe(notify('Build styles task complete!'));
});


// Lint, minify, and concat our JS
gulp.task('scripts', function() {
	return gulp.src(bowerFiles(
			['**/*.js', '!**/jquery.js'],
			{
				includeSelf:true
			}
		), {base: 'bower_components'})
		.pipe(plumber())
		.pipe(jshint())
		.pipe(jshint.reporter('default'))
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(destPaths.scripts))
		.pipe(notify('Scripts tasks complete!'));
});

gulp.task('build-scripts', function() {
	return gulp.src(bowerFiles(
			['**/*.js', '!**/jquery.js'],
			{
				includeSelf:true
			}
		), {base: 'bower_components'})
		.pipe(plumber())
		.pipe(jshint())
		.pipe(jshint.reporter('default'))
		.pipe(uglify())
		.pipe(concat('scripts.js'))
		.pipe(gulp.dest(destPaths.scripts))
		.pipe(notify('Scripts tasks complete!'));
	});

// Watch for changes made to files
gulp.task('watch', function() {
	gulp.watch(paths.scripts, ['scripts']);
	gulp.watch(paths.styles, ['styles']);
});

// Browser Sync - autoreload the browser
// Additional Settings: http://www.browsersync.io/docs/options/
gulp.task('browser-sync', function () {
	var files = [
		'**/*.html',
		'**/*.php',
		'dist/css/styles.css',
		'dist/js/scripts.js',
	];
	browserSync.init(files, {
		//server: {
			//baseDir: './'
		//},
		proxy: 'http://10.10.10.126/spanish-cuentos', // Proxy for local dev sites
		// port: 5555, // Sets the port in which to serve the site
		// open: false // Stops BS from opening a new browser window
	});
});

gulp.task('clean', function(cb) {
	//return gulp.src('build').pipe(clean());
	del(['build'], cb);
});

gulp.task('clear-cache', function() {
	cache.clearAll();
});

// Default Task
gulp.task('default', function(cb) {
	runSequence('css-to-scss', 'clean', 'clear-cache', 'scripts', 'styles', 'browser-sync', 'watch', cb);
});
