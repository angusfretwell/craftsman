import gulp from 'gulp';
import sourcemaps from 'gulp-sourcemaps';
import cssnano from 'gulp-cssnano';
import sass from 'gulp-sass';
import eslint from 'gulp-eslint';
import source from 'vinyl-source-stream';
import buffer from 'vinyl-buffer';
import browserify from 'browserify';
import babel from 'babelify';

gulp.task('styles', () => {
  return gulp.src('app/styles/main.scss')
    .pipe(sourcemaps.init())
    .pipe(sass())
    .pipe(cssnano())
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('.tmp/styles'))
});

gulp.task('scripts', ['eslint'], () => {
  return browserify({
    entries: 'app/scripts/main.js',
    debug: true,
  })
    .transform(babel)
    .bundle()
    .on('error', console.error)
    .pipe(source('bundle.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('.tmp/scripts'));
});

gulp.task('eslint', () => {
  return gulp.src('app/scripts/**/*.js')
    .pipe(eslint())
    .pipe(eslint.format());
});
