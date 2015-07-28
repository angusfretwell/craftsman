// Generated on <%= (new Date).toISOString().split('T')[0] %> using <%= pkg.name %> <%= pkg.version %>
'use strict';

import gulp from 'gulp';
import gulpLoadPlugins from 'gulp-load-plugins';
import browserSync from 'browser-sync';
import del from 'del';
import minimist from 'minimist';

const $ = gulpLoadPlugins();
const reload = browserSync.reload;

const knownOptions = {
  string: 'env',
  default: { env: process.env.NODE_ENV || 'dev' }
};

const options = minimist(process.argv.slice(2), knownOptions);
const remotes = require('./env.json').remotes;

const env = {
  slug: '<%= slug %>',
  branch: `dokku-${options.env}`,
  sqlFile: `remote--${options.env}.sql`
}

if (remotes[options.env]) {
  env.server = remotes[options.env]
} else {
  $.util.log(`You must provide a valid remote name.`).beep();

  process.exit(1);
}

gulp.task('deploy-init', () => {
  return gulp.src('')
    .pipe($.shell([
      `git remote add ${env.branch} dokku@${env.server}:${env.slug}`,
      `git push ${env.branch} master`,
      `ssh dokku@${env.server} mariadb:create ${env.slug}`,
      `ssh dokku@${env.server} mariadb:link ${env.slug} ${env.slug}`
    ]));
});

gulp.task('deploy', () => {
  return gulp.src('')
    .pipe($.shell([
      'git push origin master',
      `git push ${env.branch} master`
    ]));
});

gulp.task('db-dump-local', ['clean:tmp'], () => {
  return gulp.src('')
    .pipe($.shell([
      '[ -d ".tmp" ] || mkdir .tmp',
      `vagrant ssh --command "mysqldump -uroot -proot ${env.slug} > /vagrant/.tmp/local.sql"`
    ]));
});

gulp.task('db-dump-remote', ['clean:tmp'], () => {
  return gulp.src('')
    .pipe($.shell([
      '[ -d ".tmp" ] || mkdir .tmp',
      `ssh dokku@${env.server} mariadb:dumpraw ${env.slug} | tee .tmp/${env.sqlFile} > /dev/null`
    ]));
});

gulp.task('db-push', ['db-dump-local'], () => {
  return gulp.src('')
    .pipe($.shell([
      `ssh dokku@${env.server} mariadb:console ${env.slug} < .tmp/local.sql`
    ]));
});

gulp.task('db-pull', ['db-dump-remote'], () => {
  return gulp.src('')
    .pipe($.shell([
      `vagrant ssh --command "mysql -uroot -proot ${env.slug} < /vagrant/.tmp/${sqlFile}"`
    ]));
});

gulp.task('db-backup', [
    'clean',
    'db-dump-local',
    'db-dump-remote'
  ], () => {
    return gulp.src([
      '.tmp/local.sql',
      `.tmp/${env.sqlFile}`
    ])
    .pipe($.rename({ prefix: `${new Date().toLocaleString()} ` }))
    .pipe(gulp.dest('databases'))
});

gulp.task('styles', () => {
  return gulp.src('app/styles/**/*.scss')
    .pipe($.plumber())
    .pipe($.sourcemaps.init())
    .pipe($.sass.sync({
      outputStyle: 'expanded',
      precision: 10,
      includePaths: ['.', 'bower_components']
    }).on('error', $.sass.logError))
    .pipe($.autoprefixer({browsers: ['last 1 version']}))
    .pipe($.sourcemaps.write())
    .pipe(gulp.dest('.tmp/styles'));
});

function lint(files, options) {
  return () => {
    return gulp.src(files)
      .pipe($.eslint(options))
      .pipe($.eslint.format())
      .pipe($.if(!browserSync.active, $.eslint.failAfterError()));
  };
}

gulp.task('lint', lint('app/scripts/**/*.js'));

gulp.task('images', () => {
  return gulp.src('app/images/**/*.{gif,jpg,png,svg,webp}')
    .pipe($.if($.if.isFile, $.cache($.imagemin({
      progressive: true,
      interlaced: true,
      svgoPlugins: [{ cleanupIDs: false }]
    }))
    .on('error', (err) => {
      console.log(err);
      this.end();
    })))
    .pipe(gulp.dest('public/images'));
});

gulp.task('fonts', () => {
  return gulp.src(require('main-bower-files')({
    filter: '**/*.{eot,svg,ttf,woff,woff2}'
  }).concat('app/fonts/**/*'))
    .pipe(gulp.dest('.tmp/fonts'))
    .pipe(gulp.dest('public/fonts'));
});

gulp.task('extras', () => {
  return gulp.src('app/*.*', {
    dot: true
  }).pipe(gulp.dest('public'));
});

gulp.task('clean', del.bind(null, [
  '.tmp/*',
  'public/**/*',
  '!public/assets',
  '!public/assets/**/*',
  '!public/index.php',
  '!public/.htaccess'
]));

gulp.task('clean:tmp', del.bind(null, [
  '.tmp/*'
]));

gulp.task('html', ['styles'], () => {
  const assets = $.useref.assets({searchPath: ['.tmp', 'app', '.']});

  return gulp.src('app/templates/**/*.html')
    .pipe(assets)
    .pipe($.if('*.js', $.uglify()))
    .pipe($.if('*.css', $.minifyCss({compatibility: '*'})))
    .pipe(assets.restore())
    .pipe($.useref())
    .pipe($.if('*.html', $.minifyHtml({conditionals: true, loose: true})))
    .pipe(gulp.dest('public/templates'))
    .pipe(reload({stream: true}));
});

gulp.task('build', ['lint', 'html', 'images', 'fonts', 'extras'], () => {
  return gulp.src('public/**/*').pipe($.size({title: 'build', gzip: true}));
});

gulp.task('watch', ['styles', 'fonts'], () => {
  browserSync({
    proxy: `${env.slug}.craft.dev`,
    port: 8080,
    open: true,
    notify: false
  });

  gulp.watch('app/*.*', ['extras']);
  gulp.watch('app/fonts/**/*', ['fonts']);
  gulp.watch('app/images/**/*', ['images']);
  gulp.watch('app/scripts/**/*.js', ['html']);
  gulp.watch('app/styles/**/*.scss', ['styles', 'html']);
  gulp.watch('app/templates/**/*.html', ['html']);
});

gulp.task('default', ['clean'], () => {
  gulp.start('build');
});
