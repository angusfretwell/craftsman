'use strict';
var fs = require('fs');
var util = require('util');
var path = require('path');
var spawn = require('child_process').spawn;
var yeoman = require('yeoman-generator');
var chalk = require('chalk');
var wiredep = require('wiredep');

var FrancisCraftGenerator = yeoman.generators.Base.extend({
    init: function() {
        this.pkg = require('../package.json');
    },

    askFor: function() {
        var done = this.async();

        this.log(this.yeoman);
        this.log(chalk.magenta('You\'re using Francis Bond\'s fantastic Craft generator.'));

        var prompts = [{
            name: 'slug',
            message: 'Please enter a unique slug for this project',
        }, {
            type: 'checkbox',
            name: 'features',
            message: 'What more would you like?',
            choices: [{
                name: 'inuit.css',
                value: 'includeInuit',
                checked: true
            }, {
                name: 'jQuery',
                value: 'includejQuery',
                checked: true
            }]
        }];

        this.prompt(prompts, function(props) {
            this.slug = props.slug;

            var features = props.features;

            var hasFeature = function(feat) {
                return features.indexOf(feat) !== -1;
            }

            this.includeInuit = hasFeature('includeInuit');
            this.includejQuery = hasFeature('includejQuery');

            done();
        }.bind(this));
    },

    app: function() {
        this.mkdir('app');
        this.mkdir('app/styles');
        this.mkdir('app/scripts');
        this.mkdir('app/images');
        this.mkdir('app/webfonts');
        this.mkdir('app/templates');

        this.mkdir('public');
        this.mkdir('public/assets');
    },

    projectfiles: function() {
        this.copy('_package.json', 'package.json');
        this.copy('_composer.json', 'composer.json');

        this.copy('bowerrc', '.bowerrc');
        this.copy('_bower.json', 'bower.json');

        this.copy('editorconfig', '.editorconfig');
        this.copy('jshintrc', '.jshintrc');

        this.copy('gitignore', '.gitignore');
        this.copy('gitattributes', '.gitattributes');

        this.copy('favicon.ico', 'app/favicon.ico');
        this.copy('robots.txt', 'app/robots.txt');
        this.copy('humans.txt', 'app/humans.txt');

        this.copy('htaccess', 'app/.htaccess');
    },

    vagrant: function() {
        this.copy('Vagrantfile', 'Vagrantfile');
    },

    puppet: function() {
        this.mkdir('puppet');
        this.mkdir('puppet/manifests');
        this.mkdir('puppet/modules');
        this.mkdir('puppet/modules/app');
        this.mkdir('puppet/modules/app/manifests');

        this.write('puppet/manifests/init.pp', 'include \'app\'');
        this.copy('init.pp', 'puppet/modules/app/manifests/init.pp');

        this.directory('bootstrap', 'puppet/boostrap');

        this.directory('apache', 'puppet/modules/apache');
        this.directory('mysql', 'puppet/modules/mysql');
        this.directory('php', 'puppet/modules/php');
        this.directory('git', 'puppet/modules/git');
        this.directory('curl', 'puppet/modules/curl');
        this.directory('composer', 'puppet/modules/composer');
    },

    craft: function() {
        this.mkdir('craft');
        this.mkdir('craft/config');
        this.mkdir('craft/storage');

        this.copy('index.php', 'public/index.php')

        this.copy('general.php', 'craft/config/general.php');
        this.copy('db.php', 'craft/config/db.php');
    },

    install: function() {
        var done = this.async();

        this.installDependencies({
            callback: function() {
                var bowerJson = JSON.parse(fs.readFileSync('./bower.json'));

                wiredep({
                    bowerJson: bowerJson,
                    directory: 'app/bower_components',
                    src: 'app/index.html'
                });

                done();
            }.bind(this)
        });
    }
});

module.exports = FrancisCraftGenerator;