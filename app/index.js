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
    this.mkdir('databases');
    this.mkdir('public');

    this.copy('_package.json', 'package.json');
  },

  projectfiles: function () {
    this.copy('editorconfig', '.editorconfig');
  }
});

module.exports = FrancisCraftGenerator;