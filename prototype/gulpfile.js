/* Set requirements */
var gulp = require('gulp');
var sass = require('gulp-sass');
var bs = require('browser-sync').create();
var gulpIf = require('gulp-if');
var cssnano = require('gulp-cssnano');
var del = require('del');
var runSequence = require('run-sequence');
var plumber = require('gulp-plumber');
var gutil = require('gulp-util');
var ejs = require("gulp-ejs");
var nodemon = require('gulp-nodemon');

/* helper functions */
// handling errors
var onError = function (err) {  
  gutil.log;
  console.log(err);
};

/* Define tasks */

// Cleanup dist folders
gulp.task('clean', function() {
  del.sync('css');
});

// Copy fonts
gulp.task('fonts', function() {
  return gulp.src('node_modules/font-awesome/fonts/**/*')
  .pipe(gulp.dest('fonts'))
})

// Compile scss to minifyed css
gulp.task('sass', function () {
    return gulp.src('scss/*.scss')
        .pipe(plumber({ // More graceful error handling, prevents watch from breaking.
        errorHandler: onError
        }))
        .pipe(sass()) // Converts Sass to CSS with gulp-sass
        .pipe(gulp.dest('css/')) // Destination for css
        .pipe(gulpIf('*.css', cssnano())) // minifi the css file
        .pipe(bs.reload({stream: true}));
});

// Watch for changes
gulp.task('watch', ['browser-sync'], function () {
    gulp.watch("scss/**/*.scss", ['sass']);
    gulp.watch("views/**/*.ejs").on('change', bs.reload);
});

// Reload browser with watch task
gulp.task('browser-sync', ['sass'], function() {
    bs.init({
        proxy: "localhost:8080",
        ui: false
    });
});

// Start express server
gulp.task('server',function(){  
    nodemon({
        'script': 'server.js',
        'ignore': '*.js'
    });
});

// Default task when running gulp
gulp.task('default', function (callback) {
  runSequence(['server', 'fonts', 'watch'],
    callback
  )
});

