// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>
'use strict';

var gulp = require('gulp'),
    $ = require('gulp-load-plugins')();

var paths = {
  styles: 'app/styles/**/*.scss',
  scripts: 'app/scripts/**/*.js',
  images: 'app/images/**/*.{gif,jpg,png,svg,webp}',
  extras: 'app/*.*',
  html: ['app/**/*.html'],
  index: 'app/templates/_layout.html',
  clean: ['.tmp/*', 'public/**/*',
      '!public/assets', '!public/assets/**/*',
      '!public/index.php', '!public/.htaccess']
};

/**
 * 1. Add dokku remote to git repository.
 * 2. Push to dokku to intialize app container.
 * 3. Set a custom PHP buildpack
 * 4. Create mariadb container.
 * 5. Link app container with mariadb container.
 */
gulp.task('deploy-init', $.shell.task([
  'git remote remove dokku-staging',
  'git remote add dokku-staging dokku@<%= remoteStaging %>:<%= _.slugify(slug) %>', /*[1]*/
  'git push dokku-staging master', /*[2]*/
  'ssh dokku@<%= remoteStaging %> config:set <%= _.slugify(slug) %> BUILDPACK_URL=https://github.com/CHH/heroku-buildpack-php', /*[3]*/
  'ssh dokku@<%= remoteStaging %> mariadb:create <%= _.slugify(slug) %>', /*[4]*/
  'ssh dokku@<%= remoteStaging %> mariadb:link <%= _.slugify(slug) %> <%= _.slugify(slug) %>' /*[5]*/
]));

gulp.task('deploy-init-production', $.shell.task([
  'git remote remove dokku-production',
  'git remote add dokku-production dokku@<%= remoteProduction %>:<%= _.slugify(slug) %>', /*[1]*/
  'git push dokku-production master', /*[2]*/
  'ssh dokku@<%= remoteProduction %> config:set <%= _.slugify(slug) %> BUILDPACK_URL=https://github.com/CHH/heroku-buildpack-php', /*[3]*/
  'ssh dokku@<%= remoteProduction %> mariadb:create <%= _.slugify(slug) %>', /*[4]*/
  'ssh dokku@<%= remoteProduction %> mariadb:link <%= _.slugify(slug) %> <%= _.slugify(slug) %>' /*[5]*/
]));

gulp.task('deploy', $.shell.task([
  'git push dokku-staging master'
]));

gulp.task('deploy-production', $.shell.task([
  'git push dokku-production master'
]));

/**
 * 1. Check if .tmp exists, create it if it doesn't.
 * 2. Dump the local database to .tmp/local.sql.
 */
gulp.task('db-dump-local', ['build'], $.shell.task([
  '[ -d ".tmp" ] || mkdir .tmp', /*[1]*/
  'vagrant ssh --command "mysqldump -uroot -proot <%= _.slugify(slug) %> > /vagrant/.tmp/local.sql"' /*[2]*/
]));

/**
 * 1. Check if .tmp exists, create it if it doesn't.
 * 2. Dump the remote database to .tmp/remote.sql.
 */
gulp.task('db-dump-remote', ['build'], $.shell.task([
  '[ -d ".tmp" ] || mkdir .tmp', /*[1]*/
  'ssh dokku@<%= remote-staging %> mariadb:dumpraw <%= _.slugify(slug) %> | tee .tmp/remote-staging.sql > /dev/null' /*[2]*/
]));

gulp.task('db-dump-remote-production', ['build'], $.shell.task([
  '[ -d ".tmp" ] || mkdir .tmp', /*[1]*/
  'ssh dokku@<%= remote-production %> mariadb:dumpraw <%= _.slugify(slug) %> | tee .tmp/remote-production.sql > /dev/null' /*[2]*/
]));

gulp.task('db-push', ['db-dump-local'], $.shell.task([
  'ssh dokku@<%= remote-staging %> mariadb:console <%= _.slugify(slug) %> < .tmp/local.sql'
]));

gulp.task('db-push-production', ['db-dump-local'], $.shell.task([
  'ssh dokku@<%= remote-production %> mariadb:console <%= _.slugify(rslug) %> < .tmp/local.sql'
]));

gulp.task('db-pull', ['db-dump-remote'], $.shell.task([
  'vagrant ssh --command "mysql -uroot -proot <%= _.slugify(slug) %> < /vagrant/.tmp/remote-staging.sql"'
]));

gulp.task('db-pull-production', ['db-dump-remote-production'], $.shell.task([
  'vagrant ssh --command "mysql -uroot -proot <%= _.slugify(slug) %> < /vagrant/.tmp/remote-production.sql"'
]));

 gulp.task('db-dump', ['clean', 'db-dump-local', 'db-dump-remote', 'db-dump-remote-production'], function() {     
    return gulp.src(['.tmp/local.sql', '.tmp/remote-staging.sql', '.tmp/remote-production.sql'])
      .pipe(gulp.dest('databases'));
 });

gulp.task('styles', function() {
  return gulp.src([paths.styles, 'bower_components/**/*.scss'])
    .pipe($.plumber())
    .pipe($.rubySass({
      style: 'expanded',
      precision: 10
    }))
    .pipe($.autoprefixer('last 1 version'))
    .pipe($.csso())
    .pipe(gulp.dest('public/styles'))
});

gulp.task('scripts', function() {
  return gulp.src(paths.scripts)
    .pipe($.jshint())
    .pipe($.jshint.reporter('jshint-stylish'))
    .pipe($.jshint.reporter('fail'))
    .pipe($.uglify())
    .pipe(gulp.dest('public/scripts'));
});

gulp.task('images', function () {
  return gulp.src(paths.images)
    .pipe($.cache($.imagemin({
      progressive: true,
      interlaced: true
    })))
    .pipe(gulp.dest('public/images'));
});

gulp.task('extras', function() {
  return gulp.src(paths.extras, { dot: true })
    .pipe(gulp.dest('public'));
});

gulp.task('clean', function(cb) {
  del(paths.clean, cb);
});

gulp.task('build', ['clean'], function() {
  gulp.start('build-useref');
});

gulp.task('html', function() {
  return gulp.src(paths.html)
    .pipe(gulp.dest('public/templates'));
})

gulp.task('build-useref', ['html', 'images', 'scripts', 'styles', 'extras'], function() {
  var assets = $.useref.assets({searchPath: '{public, app}'});

  return gulp.src(paths.index)
    .pipe(assets)
    .pipe(assets.restore())
    .pipe($.useref())
    .pipe(gulp.dest('public/templates'));
});

gulp.task('watch', function() {
  gulp.start('build-useref');

  livereload.listen();
  gulp.watch('public/**/*', $.livereload.changed);

  gulp.watch(paths.extras, ['extras']);
  gulp.watch(paths.html, ['html']);
  gulp.watch(paths.index, ['build-useref']);
  gulp.watch(paths.scripts, ['scripts']);
  gulp.watch(paths.styles, ['styles']);
  gulp.watch(paths.images, ['images']);
});

gulp.task('default', ['build']);