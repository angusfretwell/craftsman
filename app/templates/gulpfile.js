'use strict';
// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>

var gulp = require('gulp'),
    prefix = require('gulp-autoprefixer'),
    cache = require('gulp-cache'),
    csso = require('gulp-csso'),
    clean = require('gulp-clean'),
    filter = require('gulp-filter'),
    imagemin = require('gulp-imagemin'),
    jshint = require('gulp-jshint'),
    rubySass = require('gulp-ruby-sass'),
    shell = require('gulp-shell'),
    size = require('gulp-size'),
    uglify = require('gulp-uglify'),
    useref = require('gulp-useref');

var paths = {
    styles: 'app/styles/**/*.scss',
    scripts: 'app/scripts/**/*.js',
    images: 'app/images/**/*.{gif,jpg,png,svg,webp}',
    extras: ['app/*.*', 'app/webfonts/**/*.{eot,svg,ttf,woff}'],
    html: ['app/**/*.html'],
    index: 'app/templates/_layout.html',
    clean: ['.tmp', 'public/**/*', '!public/assets', '!public/index.php', '!public/.htaccess']
};

/**
 * `gulp deploy-init`
 * 1. Add dokku remote to git repository.
 * 2. Push to dokku to intialize app container.
 * 3. Create mariadb container.
 * 4. Link app container with mariadb container.
 */
gulp.task('deploy-init', shell.task([
    'git remote add dokku dokku@staging.francisbond.com:<%= _.slugify(slug) %>', /*[1]*/
    'git push dokku master', /*[2]*/
    'ssh dokku@staging.francisbond.com config:set <%= _.slugify(slug) %> BUILDPACK_URL=https://github.com/CHH/heroku-buildpack-php',
    'ssh dokku@staging.francisbond.com mariadb:create <%= _.slugify(slug) %>', /*[3]*/
    'ssh dokku@staging.francisbond.com mariadb:link <%= _.slugify(slug) %> <%= _.slugify(slug) %>' /*[4]*/
]));

gulp.task('deploy', shell.task([
    'git push dokku master'
]));

/**
 * `gulp db-dump-local`
 * 1. Check if .tmp exists, create it if it doesn't.
 * 2. Dump the local database to .tmp/local.sql.
 */
gulp.task('db-dump-local', ['build'], shell.task([
    '[ -d ".tmp" ] || mkdir .tmp', /*[1]*/
    'vagrant ssh --command "mysqldump -uroot -proot <%= _.slugify(slug) %> > /vagrant/.tmp/local.sql"' /*[2]*/
]));

/**
 * `gulp db-dump-remote`
 * 1. Check if .tmp exists, create it if it doesn't.
 * 2. Dump the remote database to .tmp/remote.sql.
 */
gulp.task('db-dump-remote', ['build'], shell.task([
    '[ -d ".tmp" ] || mkdir .tmp', /*[1]*/
    'ssh dokku@staging.francisbond.com mariadb:dumpraw <%= _.slugify(slug) %> | tee .tmp/remote.sql > /dev/null' /*[2]*/
]));

gulp.task('db-push', ['db-dump-local'], shell.task([
    'ssh dokku@staging.francisbond.com mariadb:console <%= _.slugify(slug) %> < .tmp/local.sql'
]));

gulp.task('db-pull', ['db-dump-remote'], shell.task([
    'vagrant ssh --command "mysql -uroot -proot <%= _.slugify(slug) %> < /vagrant/.tmp/remote.sql"'
]));

gulp.task('db-dump', ['clean', 'db-dump-local', 'db-dump-remote'], function() {
    return gulp.src(['.tmp/local.sql', '.tmp/remote.sql'])
        .pipe(gulp.dest('databases'));
});

gulp.task('styles', function() {
    return gulp.src(paths.styles)
        .pipe(rubySass({
            style: 'expanded'
        }))
        .pipe(prefix('last 1 version'))
        .pipe(gulp.dest('public/styles'))
        .pipe(size());
});

gulp.task('scripts', function() {
    return gulp.src(paths.scripts)
        .pipe(jshint())
        .pipe(jshint.reporter(require('jshint-stylish')))
        .pipe(gulp.dest('public/scripts'))
        .pipe(size());
});

gulp.task('html', function() {
    return gulp.src(paths.html)
        .pipe(gulp.dest('public'))
});

gulp.task('images', function() {
    return gulp.src(paths.images)
        .pipe(cache(imagemin({
            optimizationLevel: 3,
            progressive: true,
            interlaced: true
        })))
        .pipe(gulp.dest('public/images'))
        .pipe(size());
});

gulp.task('extras', function() {
    return gulp.src(paths.extras, { dot: true })
        .pipe(gulp.dest('public'));
});

gulp.task('clean', function() {
    return gulp.src(paths.clean, { read: false })
        .pipe(clean());
});

gulp.task('build', ['clean'], function() {
    // Start the real build task once clean has run.
    gulp.start('build-useref');
});

gulp.task('build-useref', ['images', 'styles', 'extras'], function() {
    var jsFilter = filter('**/*.js');
    var cssFilter = filter('**/*.css');

    return gulp.src(paths.html)
        .pipe(useref.assets({searchPath: '{public,app}'}))
        .pipe(jsFilter)
        .pipe(uglify())
        .pipe(jsFilter.restore())
        .pipe(cssFilter)
        .pipe(csso())
        .pipe(cssFilter.restore())
        .pipe(useref.restore())
        .pipe(useref())
        .pipe(gulp.dest('public'))
        .pipe(size());
});

gulp.task('watch', function() {
    // Run everything once before we start watching for changes.
    gulp.start('extras')
        .start('html')
        .start('scripts')
        .start('styles')
        .start('images')
        .start('wiredep');

    gulp.watch(paths.extras, ['extras']);
    gulp.watch(paths.html, ['html']);
    gulp.watch(paths.scripts, ['scripts']);
    gulp.watch(paths.styles, ['styles']);
    gulp.watch(paths.images, ['images']);
    gulp.watch('bower.json', ['wiredep']);
});

gulp.task('wiredep', function() {
    var wiredep = require('wiredep').stream;

    gulp.src(paths.index)
        .pipe(wiredep({
            directory: 'bower_components'<% if (includeInuit) { %>,
            exclude: ['inuitcss', 'modernizr']<% } %>
        }))
        .pipe(gulp.dest('app/templates'));
});

gulp.task('default', ['build']);