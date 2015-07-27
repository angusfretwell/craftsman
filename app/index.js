'use strict';

var yeoman = require('yeoman-generator'),
    yosay = require('yosay');

var CraftGenerator = yeoman.generators.Base.extend({
  init: function() {
    this.pkg = require('../package.json');
  },

  promptTask: function() {
    var done = this.async();

    this.log(yosay('You\'re using the fantastic Craft generator.'));

    this.prompt([{
      name: 'slug',
      message: 'Enter a unique slug for this project',
    }, {
      name: 'dev',
      message: 'Enter the hostname of the dokku development server',
      default: 'dev.mydokkuhost.com'
    }, {
      name: 'staging',
      message: 'Enter the hostname of the dokku staging server',
      default: 'staging.mydokkuhost.com'
    }, {
      name: 'production',
      message: 'Enter the hostname of the dokku production server',
      default: 'craft.mydokkuhost.com'
    }, {
      type: 'checkbox',
      name: 'features',
      message: 'What else would you like?',
      choices: [{
        name: 'inuit.css',
        value: 'includeInuit',
        checked: true
      }, {
        name: 'jQuery',
        value: 'includejQuery',
        checked: true
      }]
    }], function(props) {
      this.slug = props.slug;
      this.remoteDev = props.dev;
      this.remoteStaging = props.staging;
      this.remoteProduction = props.production;

      var hasFeature = function(feat) {
        return props.features.indexOf(feat) !== -1;
      }

      this.includeInuit = hasFeature('includeInuit');
      this.includejQuery = hasFeature('includejQuery');

      done();
    }.bind(this));
  },

  app: function() {
    this.mkdir('app');
    this.mkdir('app/images');
    this.write('app/images/.gitkeep', '');
  },

  styles: function() {
    if (!this.includeInuit) {
      this.mkdir('app/styles');
      this.copy('main.scss', 'app/styles/main.scss');
    } else {
      this.directory('styles', 'app/styles');
    }
  },

  scripts: function() {
    this.mkdir('app/scripts');
    this.write('app/scripts/main.js', 'console.log(\'\\\'Allo \\\'Allo!\');');
  },

  bower: function() {
    this.copy('bowerrc', '.bowerrc');
    this.copy('_bower.json', 'bower.json');
  },

  gulp: function() {
    this.template('gulpfile.babel.js', 'gulpfile.babel.js');
  },

  git: function() {
    this.copy('gitignore', '.gitignore');
    this.copy('gitattributes', '.gitattributes');
  },

  package: function() {
    this.copy('_package.json', 'package.json');
  },

  composer: function() {
    this.copy('_composer.json', 'composer.json');
    this.copy('_composer.lock', 'composer.lock');
  },

  extras: function() {
    this.copy('favicon.ico', 'app/favicon.ico');
    this.copy('robots.txt', 'app/robots.txt');

    this.copy('editorconfig', '.editorconfig');
    this.copy('env', '.env');
    this.copy('CHECKS', 'CHECKS');
    this.copy('_env.json', 'env.json');
    this.copy('Procfile', 'Procfile');
  },

  vagrant: function() {
    this.copy('Vagrantfile', 'Vagrantfile');
  },

  puppet: function() {
    this.directory('puppet', 'puppet');
  },

  craft: function() {
    this.mkdir('public');
    this.mkdir('public/assets');
    this.write('public/assets/.gitkeep', '');

    this.copy('index.php', 'public/index.php')
    this.copy('htaccess', 'public/.htaccess');

    this.mkdir('craft');
    this.directory('craft', 'craft');
  },

  templates: function() {
    this.directory('templates', 'app/templates');
  },

  install: function() {
    this.installDependencies();
  }
});

module.exports = CraftGenerator;