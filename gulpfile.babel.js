import autoprefixer from 'gulp-autoprefixer';
import babel from 'babelify';
import browserify from 'browserify';
import browserSync from 'browser-sync';
import buffer from 'vinyl-buffer';
import cache from 'gulp-cache';
import cssnano from 'gulp-cssnano';
import del from 'del';
import eslint from 'gulp-eslint';
import gulp from 'gulp';
import gutil from 'gulp-util';
import gulpif from 'gulp-if';
import imagemin from 'gulp-imagemin';
import plumber from 'gulp-plumber';
import sass from 'gulp-sass';
import size from 'gulp-size';
import source from 'vinyl-source-stream';
import sourcemaps from 'gulp-sourcemaps';
import uglify from 'gulp-uglify';
import useref from 'gulp-useref';

gulp.task('watch', ['styles', 'scripts', 'images'], () => {
  browserSync({
    proxy: 'http://craftsman.app',
    port: 8080,
  });

  gulp.watch('app/templates/**/*').on('change', browserSync.reload);
  gulp.watch('app/images/**/*', ['images']);
  gulp.watch('app/scripts/**/*', ['scripts']);
  gulp.watch('app/styles/**/*', ['styles']);
});

gulp.task('build', ['html', 'images'], () =>
  gulp.src('public/**/*')
    .pipe(size({ title: 'build', gzip: true }))
);

gulp.task('default', ['clean'], () => {
  gulp.start('build');
});

gulp.task('styles', () =>
  gulp.src('app/styles/app.scss')
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass({ includePaths: ['node_modules'] }))
    .pipe(autoprefixer('last 3 versions'))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('public/styles'))
    .pipe(browserSync.reload({ stream: true }))
);

gulp.task('scripts', ['lint'], () =>
  browserify({ entries: 'app/scripts/app.js', debug: true })
    .transform(babel)
    .bundle()
    .on('error', gutil.log)
    .pipe(source('app.js'))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest('public/scripts'))
    .pipe(browserSync.reload({ stream: true }))
);

gulp.task('lint', () =>
  gulp.src('app/scripts/**/*.js')
    .pipe(eslint())
    .pipe(eslint.format())
);

gulp.task('images', () =>
  gulp.src('app/images/**/*')
    .pipe(gulpif(gulpif.isFile, cache(imagemin())))
    .pipe(gulp.dest('public/images'))
    .pipe(browserSync.reload({ stream: true }))
);

gulp.task('html', ['styles', 'scripts'], () =>
  gulp.src('app/templates/**/*.twig')
    .pipe(useref({ searchPath: ['public'], base: 'public' }))
    .pipe(gulpif('*.js', uglify()))
    .pipe(gulpif('*.css', cssnano()))
    .pipe(gulp.dest('public/templates'))
);

gulp.task('clean', del.bind(null, [
  'public/styles',
  'public/scripts',
  'public/images',
  'public/templates',
]));
