'use strict';
var util = require('util');
var path = require('path');
var spawn = require('child_process').spawn;
var yeoman = require('yeoman-generator');
var chalk = require('chalk');

var FrancisCraftGenerator = yeoman.generators.Base.extend({
  init: function () {
    this.pkg = require('../package.json');

    this.on('end', function () {
      if (!this.options['skip-install']) {
        this.installDependencies();
      }
    });
  },

  askFor: function () {
    var done = this.async();

    // have Yeoman greet the user
    this.log(this.yeoman);

    // replace it with a short and sweet description of your generator
    this.log(chalk.magenta('You\'re using Francis Bond\'s fantastic Craft generator.'));

    var prompts = [{
      name: 'slug',
      message: 'Please enter a unique slug for this project',
    }];

    this.prompt(prompts, function (props) {
      this.slug = props.slug;

      done();
    }.bind(this));
  },

  app: function () {
    this.mkdir('craft');
    this.mkdir('craft/config');
    this.mkdir('craft/storage');

    this.mkdir('puppet');
    this.mkdir('puppet/manifests');
    this.mkdir('puppet/modules');
    this.mkdir('puppet/modules/app');
    this.mkdir('puppet/modules/app/manifests');

    this.mkdir('app');
    this.mkdir('app/styles');
    this.mkdir('app/scripts');
    this.mkdir('app/images');
    this.mkdir('app/webfonts');

    this.mkdir('public');
  },

  projectfiles: function () {
    this.copy('_package.json', 'package.json');
    this.copy('_composer.json', 'composer.json');

    this.copy('bowerrc', '.bowerrc');
    this.copy('_bower.json', 'bower.json');

    this.copy('editorconfig', '.editorconfig');
    this.copy('jshint', '.jshint');

    this.copy('gitignore', '.gitignore');
    this.copy('gitattributes', '.gitattributes');

    this.copy('favicon.ico', 'app/favicon.ico');
    this.copy('robots.txt', 'app/robots.txt');
    this.copy('humans.txt', 'app/humans.txt');

    this.copy('htaccess', 'app/.htaccess');
  }
});

module.exports = FrancisCraftGenerator;