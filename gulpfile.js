var gulp = require('gulp');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var minify = require('gulp-minify');
var cleanCSS = require('gulp-clean-css');

gulp.task('sass', function () {
	gulp.src( 'assets/scss/bil-styles.scss' )
		.pipe(sourcemaps.init())
		.pipe(sass().on('error', function(error){
			console.log(error);
		}))
		.pipe(sourcemaps.write('map'))
		.pipe(cleanCSS({compatibility: 'ie8'}))
		.pipe(rename('bil-styles.css'))
		.pipe(gulp.dest('./assets/dist/css'));
});

gulp.task('js', function(){
	return gulp.src([
			'assets/js/bil-scripts.js'
		])
		.pipe(concat('bil-scripts.js'))
		.pipe(gulp.dest('./assets/dist/js/'))
		.pipe(minify({
			ext:{
				src:'-debug.js',
				min:'.js'
			}
		}))
		.pipe(gulp.dest('./assets/dist/js/'));
});

// Watch Our Files
gulp.task('watch', function() {
	gulp.watch([
		'assets/scss/*.scss',
		'assets/js/*.js',
	], ['sass', 'js']);
});

// Default
gulp.task('default', ['sass', 'js', 'watch']);
