'use strict';

var yeoman = require('yeoman-generator'),
    yosay = require('yosay');

var FrancisCraftGenerator = yeoman.generators.Base.extend({
  init: function() {
    this.pkg = require('../package.json');
  },

  promptTask: function() {
    var done = this.async();

    this.log(yosay('You\'re using Francis Bond\'s fantastic Craft generator.'));

    this.prompt([{
      name: 'slug',
      message: 'Enter a unique slug for this project',
    }, {
      name: 'staging',
      message: 'Enter the hostname of the dokku staging server',
      default: 'staging.francisbond.com'
    },
    {
      name: 'production',
      message: 'Enter the hostname of the dokku production server',
      default: 'production.francisbond.com'
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
    this.mkdir('app/styles');

    this.copy('main.scss', 'app/styles/main.scss');

    if (this.includeInuit) {
      this.copy('vars.scss', 'app/styles/_vars.scss');
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
    this.template('gulpfile.js', 'gulpfile.js');
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
    this.write('composer.lock', '');
  },

  extras: function() {
    this.copy('favicon.ico', 'app/favicon.ico');
    this.copy('robots.txt', 'app/robots.txt');
    this.copy('humans.txt', 'app/humans.txt');

    this.copy('editorconfig', '.editorconfig');
    this.copy('jshintrc', '.jshintrc');
    this.copy('env', '.env');
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

module.exports = FrancisCraftGenerator;