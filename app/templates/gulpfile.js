// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>
'use strict';

var gulp = require('gulp'),
    $ = require('gulp-load-plugins')(),
    minimist = require('minimist');

var paths = {
  styles: 'app/styles/**/*.scss',
  scripts: 'app/scripts/**/*.js',
  images: 'app/images/**/*.{gif,jpg,png,svg,webp}',
  extras: 'app/*.*',
  html: ['app/**/*.{html,json,csv}'],
  index: 'app/**/_layout.html',
  clean: ['.tmp/*', 'public/**/*',
      '!public/assets', '!public/assets/**/*',
      '!public/index.php', '!public/.htaccess']
};

var knownOptions = {
  string: 'env',
  default: { env: process.env.NODE_ENV || 'staging' }
};

var options = minimist(process.argv.slice(2), knownOptions);

/**
 * gulp deploy-init
 */
gulp.task('deploy-init', function() {
  var server = options.env === 'production'
    ? '<%= remoteProduction %>'
    : '<%= remoteStaging %>';

  var branch = options.env === 'production'
    ? 'dokku-production'
    : 'dokku-staging';

  var slug = '<%= _.slugify(slug) %>';

  return gulp.src('')
    .pipe($.shell([
      'git remote add ' + branch + ' dokku@' + server + ':' + slug,
      'git push ' + branch + ' master',
      'ssh dokku@' + server + ' mariadb:create ' + slug,
      'ssh dokku@' + server + ' mariadb:link ' + slug + ' ' + slug + ''
    ]));
});

/**
 * gulp deploy
 */
gulp.task('deploy', function() {
  var branch = options.env === 'production'
    ? 'dokku-production'
    : 'dokku-staging';

  return gulp.src('')
    .pipe($.shell([
      'git push ',
      'git push ' + branch + ' master'
    ]));
});

/**
 * gulp db-dump-local
 */
gulp.task('db-dump-local', ['build'], function() {
  var slug = '<%= _.slugify(slug) %>';

  return gulp.src('')
    .pipe($.shell([
      '[ -d ".tmp" ] || mkdir .tmp',
      'vagrant ssh --command "mysqldump -uroot -proot ' + slug + ' > /vagrant/.tmp/local.sql"'
    ]));
});

/**
 * gulp db-dump-remote
 */
gulp.task('db-dump-remote', ['build'], function() {
  var server = options.env === 'production'
    ? '<%= remoteProduction %>'
    : '<%= remoteStaging %>';

  var file = options.env === 'production'
    ? 'remote--production.sql'
    : 'remote--staging.sql';

  var slug = '<%= _.slugify(slug) %>';

  return gulp.src('')
    .pipe($.shell([
      '[ -d ".tmp" ] || mkdir .tmp',
      'ssh dokku@' + server + ' mariadb:dumpraw ' + slug + ' | tee .tmp/' + file + ' > /dev/null'
    ]));
});

/**
 * gulp db-push
 */
gulp.task('db-push', ['db-dump-local'], function() {
  var server = options.env === 'production'
    ? '<%= remoteProduction %>'
    : '<%= remoteStaging %>';

  var slug = '<%= _.slugify(slug) %>';

  return gulp.src('')
    .pipe($.shell([
      'ssh dokku@' + server + ' mariadb:console ' + slug + ' < .tmp/local.sql'
    ]));
});

/**
 * gulp db-pull
 */
gulp.task('db-pull', ['db-dump-remote'], function(){
  var file = options.env === 'production'
    ? 'remote--production.sql'
    : 'remote--staging.sql';

  var slug = '<%= _.slugify(slug) %>';

   return gulp.src('')
    .pipe($.shell([
      'vagrant ssh --command "mysql -uroot -proot ' + slug + ' < /vagrant/.tmp/' + file + '"'
    ]));
});

/**
 * gulp db-dump
 */
gulp.task('db-dump', [
    'clean',
    'db-dump-local',
    'db-dump-remote'
  ], function() {
    var file = options.env === 'production'
      ? 'remote--production.sql'
      : 'remote--staging.sql';

    return gulp.src([
        '.tmp/local.sql',
        '.tmp/ ' + file
      ])
      .pipe(gulp.dest('databases'));
});

/**
 * gulp styles
 */
gulp.task('styles', function() {
  return gulp.src(paths.styles)
    .pipe($.plumber())
    .pipe($.rubySass({
      loadPath: 'bower_components',
      style: 'expanded',
      precision: 10
    }))
    .pipe($.autoprefixer('last 1 version'))
    .pipe(gulp.dest('public/styles'))
});

/**
 * gulp scripts
 */
gulp.task('scripts', function() {
  return gulp.src(paths.scripts)
    .pipe($.changed('public/scripts'))
    .pipe($.jshint())
    .pipe($.jshint.reporter('jshint-stylish'))
    .pipe($.browserify({
      insertGlobals: true
    }))
    .pipe(gulp.dest('public/scripts'));
});

/**
 * gulp images
 */
gulp.task('images', function () {
  return gulp.src(paths.images)
    .pipe($.changed('public/images'))
    .pipe($.cache($.imagemin({
      progressive: true,
      interlaced: true
    })))
    .pipe(gulp.dest('public/images'));
});

/**
 * gulp extras
 */
gulp.task('extras', function() {
  return gulp.src(paths.extras, { dot: true })
    .pipe($.changed('public'))
    .pipe(gulp.dest('public'));
});

/**
 * gulp clean
 */
gulp.task('clean', function(cb) {
  require('del')(paths.clean, cb);
});

/**
 * gulp html
 */
gulp.task('html', function() {
  return gulp.src(paths.html)
    .pipe($.changed('public'))
    .pipe($.if(options.env === 'production', $.htmlmin({collapseWhitespace: true})))
    .pipe(gulp.dest('public'));
})

/**
 * gulp build
 */
gulp.task('build', ['clean'], function() {
  gulp.start('build-useref');
});

/**
 * gulp build-useref
 */
gulp.task('build-useref', [
    'html',
    'images',
    'scripts',
    'styles',
    'extras'
  ], function() {
  var assets = $.useref.assets({searchPath: '{public,app}'});

  return gulp.src(paths.index)
    .pipe($.if(options.env !== 'production',
      $.multinject(['http://localhost:35729/livereload.js?snipver=1'], 'livereload')
    ))
    .pipe(assets)
    .pipe($.if(options.env === 'production', $.if('*.js', $.uglify())))
    .pipe($.if(options.env === 'production', $.if('*.css', $.csso())))
    .pipe(assets.restore())
    .pipe($.useref())
    .pipe($.if(options.env === 'production', $.if('*.html', $.htmlmin({collapseWhitespace: true}))))
    .pipe(gulp.dest('public'));
});

/**
 * gulp watch
 */
gulp.task('watch', function() {
  gulp.start('build-useref');

  $.livereload.listen();
  gulp.watch('public/**/*', $.livereload.changed);

  gulp.watch(paths.extras,  ['extras']);
  gulp.watch(paths.html,    ['html']);
  gulp.watch(paths.index,   ['build-useref']);
  gulp.watch(paths.scripts, ['scripts']);
  gulp.watch(paths.styles,  ['styles']);
  gulp.watch(paths.images,  ['images']);
});

/**
 * gulp
 */
gulp.task('default', ['build']);
