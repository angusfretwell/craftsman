'use strict';
// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>

var gulp = require('gulp');

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
    'ssh dokku@staging.francisbond.com mariadb:dumpraw <%= _.slugify(slug) %> > .tmp/remote.sql'
]));

gulp.task('db-push', ['clean', 'db-dump-local'] function () {
    'ssh dokku@staging.francisbond.com mariadb:console <%= _.slugify(slug) %> < .tmp/local.sql'
});

gulp.task('db-pull', ['clean', 'db-dump-remote'] function () {
    'vagrant ssh "mysql -uroot -proot <%= _.slugify(slug) %>" < .tmp/remote.sql'
});

gulp.task('db-dump', ['clean', 'db-dump-local', 'db-dump-remote'] function () {
    return gulp.src(['.tmp/local.sql', '.tmp/remote.sql'])
        .pipe(gulp.dest('databases'));
});

gulp.task('styles', function() {
    return gulp.src('app/styles/**/*.scss')
        .pipe($.rubySass({
            style: 'expanded'
        }))
        .pipe($.autoprefixer('last 1 version'))
        .pipe(gulp.dest('public/styles'))
        .pipe($.size());
});

gulp.task('scripts', function() {
    return gulp.src('app/scripts/**/*.js')
        .pipe($.jshint())
        .pipe($.jshint.reporter(require('jshint-stylish')))
        .pipe(gulp.dest('public/styles'))
        .pipe($.size());
});

gulp.task('html', function() {
    return gulp.src('app/*.html')
        .pipe(gulp.dest('public'))
});

gulp.task('images', function() {
    return gulp.src('app/images/**/*.{gif,jpg,png,svg,webp}')
        .pipe(cache(imagemin({
            optimizationLevel: 3,
            progressive: true,
            interlaced: true
        })))
        .pipe(gulp.dest('public/images'))
        .pipe(size());
});

gulp.task('extras', function() {
    return gulp.src(['app/*.*', '!app/*.html', 'app/webfonts/**/*.{eot,svg,ttf,woff}'], { dot: true })
        .pipe(gulp.dest('public'));
});

gulp.task('clean', function() {
    return gulp.src(['.tmp', 'public', '!public/assets', '!public/index.php'], { read: false })
        .pipe(clean());
});

gulp.task('build', ['clean', 'images', 'extras'], function() {
    var jsFilter = $.filter('**/*.js');
    var cssFilter = $.filter('**/*.css');

    return gulp.src('app/*.html')
        .pipe($.useref.assets({searchPath: '{public,app}'}))
        .pipe(jsFilter)
        .pipe($.uglify())
        .pipe(jsFilter.restore())
        .pipe(cssFilter)
        .pipe($.rubySass({
            style: 'expanded'
        }))
        .pipe($.autoprefixer('last 1 version'))
        .pipe($.csso())
        .pipe(cssFilter.restore())
        .pipe($.useref.restore())
        .pipe($.useref())
        .pipe(gulp.dest('public'))
        .pipe($.size());
});

gulp.task('watch', function () {
    gulp.watch(['app/*.*', '!app/*.html', 'app/webfonts/**/*.{eot,svg,ttf,woff}'], ['extras']);
    gulp.watch(['app/*.html', ['html']);
    gulp.watch('app/scripts/**/*.js', ['scripts']);
    gulp.watch('app/styles/**/*.scss', ['styles']);
    gulp.watch('app/images/**/*.{gif,jpg,png,svg,webp}', ['images']);
    gulp.watch('bower.json', ['wiredep']);
});

gulp.task('wiredep', function () {
    var wiredep = require('wiredep').stream;

    gulp.src(paths.html)
        .pipe(wiredep({
            directory: 'app/bower_components'
        }))
        .pipe(gulp.dest('app'));
});

gulp.task('default', function () {
    gulp.start('build');
});