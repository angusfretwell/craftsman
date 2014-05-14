'use strict';
// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>

var gulp = require('gulp');

// Load plugins
var $ = require('gulp-load-plugins')();

var paths = {
    styles: 'app/styles/**/*.scss',
    scripts: 'app/scripts/**/*.js',
    html: 'app/*.html',
    images: 'app/images/**/*',
    fonts: 'app/webfonts/**/*.{eot,svg,ttf,woff}',
    extras: ['app/*.*', '!app/*.html']
}

/**
 * `gulp deploy-init`
 * 1. Create mariadb container.
 * 2. Add dokku remote to git repository.
 * 3. Push to dokku to intialize app container.
 * 4. Link app container with mariadb container.
 */
gulp.task('deploy-init', shell.task([
    'ssh dokku@staging.francisbond.com mariadb:create <%= _.slugify(slug) %>', /*[1]*/
    'git remote add dokku dokku@staging.francisbond.com:<%= _.slugify(slug) %>', /*[2]*/
    'git push dokku master', /*[3]*/
    'ssh dokku@staging.francisbond.com mariadb:link <%= _.slugify(slug) %> <%= _.slugify(slug) %>' /*[4]*/
]));

gulp.task('deploy', shell.task([
    'git push dokku master'
]));

gulp.task('db-dump-local', shell.task([
    'vagrant ssh "mysqldump -uroot -proot <%= _.slugify(slug) %>" > .tmp/local.sql'
]));

gulp.task('db-dump-remote', shell.task([
    'ssh dokku@staging.francisbond.com mariadb:dumpraw <%= _.slugify(slug) %> > .tmp/local.sql'
]));
//
//gulp.task('db-push', function () {
//
//});
//
//gulp.task('db-pull', function () {
//
//});
//
//gulp.task('db-dump', function () {
//
//});

gulp.task('styles', function () {
    return gulp.src(paths.styles)
        .pipe($.rubySass({
            style: 'expanded'
        }))
        .pipe($.autoprefixer('last 1 version'))
        .pipe(gulp.dest('.tmp/styles'))
        .pipe($.size());
});

gulp.task('scripts', function () {
    return gulp.src(paths.scripts)
        .pipe($.jshint())
        .pipe($.jshint.reporter(require('jshint-stylish')))
        .pipe($.size());
});

gulp.task('html', ['styles', 'scripts'], function () {
    var jsFilter = $.filter('**/*.js');
    var cssFilter = $.filter('**/*.css');

    return gulp.src(paths.html)
        .pipe($.useref.assets({searchPath: '{.tmp,app}'}))
        .pipe(jsFilter)
        .pipe($.uglify())
        .pipe(jsFilter.restore())
        .pipe(cssFilter)
        .pipe($.csso())
        .pipe(cssFilter.restore())
        .pipe($.useref.restore())
        .pipe($.useref())
        .pipe(gulp.dest('public'))
        .pipe($.size());
});

gulp.task('images', function () {
    return gulp.src(paths.images)
        .pipe($.cache($.imagemin({
            optimizationLevel: 3,
            progressive: true,
            interlaced: true
        })))
        .pipe(gulp.dest('public/images'))
        .pipe($.size());
});

gulp.task('fonts', function () {
    return gulp.src(paths.fonts)
        .pipe(gulp.dest('dist/webfonts'))
        .pipe($.size());
});

gulp.task('extras', function () {
    return gulp.src(paths.extras, { dot: true })
        .pipe(gulp.dest('public'));
});

gulp.task('clean', function () {
    return gulp.src(['.tmp', 'public', '!public/assets'], { read: false })
        .pipe($.clean());
});

gulp.task('build', ['html', 'images', 'fonts', 'extras']);

// Wire Bower dependencies
gulp.task('wiredep', function () {
    var wiredep = require('wiredep').stream;

    gulp.src(paths.html)
        .pipe(wiredep({
            directory: 'app/bower_components'
        }))
        .pipe(gulp.dest('app'));
});

// Watch for changes
gulp.task('watch', ['build', 'connect'], function () {
    gulp.watch('app/**/*', ['build']);
});

// Default task
gulp.task('default', ['clean'], function () {
    gulp.start('build');
});