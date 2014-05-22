'use strict';

var fs = require('fs'),
    util = require('util'),
    path = require('path'),
    spawn = require('child_process').spawn,
    yeoman = require('yeoman-generator'),
    yosay = require('yosay'),
    chalk = require('chalk'),
    wiredep = require('wiredep');

var FrancisCraftGenerator = yeoman.generators.Base.extend({
    init: function() {
        this.pkg = require('../package.json');
    },

    askFor: function() {
        var done = this.async();

        this.log(yosay('You\'re using Francis Bond\'s fantastic Craft generator.'));

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

            var features = props.features,
                hasFeature = function(feat) {
                    return features.indexOf(feat) !== -1;
                }

            this.includeInuit = hasFeature('includeInuit');
            this.includejQuery = hasFeature('includejQuery');

            done();
        }.bind(this));
    },

    app: function() {
        this.mkdir('app');
        this.mkdir('app/images');
        this.mkdir('app/webfonts');
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

    projectfiles: function() {
        this.copy('gulpfile.js', 'gulpfile.js');

        this.copy('_package.json', 'package.json');
        this.copy('_composer.json', 'composer.json');
        this.write('composer.lock', '');

        this.copy('bowerrc', '.bowerrc');
        this.copy('_bower.json', 'bower.json');

        this.copy('editorconfig', '.editorconfig');
        this.copy('jshintrc', '.jshintrc');

        this.copy('gitignore', '.gitignore');
        this.copy('gitattributes', '.gitattributes');

        this.copy('favicon.ico', 'app/favicon.ico');
        this.copy('robots.txt', 'app/robots.txt');
        this.copy('humans.txt', 'app/humans.txt');
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

        this.directory('bootstrap', 'puppet/bootstrap');

        this.directory('apache', 'puppet/modules/apache');
        this.directory('mysql', 'puppet/modules/mysql');
        this.directory('php', 'puppet/modules/php');
        this.directory('git', 'puppet/modules/git');
        this.directory('curl', 'puppet/modules/curl');
        this.directory('composer', 'puppet/modules/composer');
    },

    craft: function() {
        this.mkdir('craft');
        this.mkdir('craft/storage');
        this.write('craft/storage/.gitkeep', '');

        this.mkdir('public');
        this.mkdir('public/assets');
        this.write('public/assets/.gitkeep', '');

        this.copy('index.php', 'public/index.php')
        this.copy('htaccess', 'public/.htaccess');

        this.directory('app', 'craft/app');
        this.directory('config', 'craft/config');
        this.mkdir('craft/plugins');
        this.write('craft/plugins/.gitkeep', '');

        this.copy('general.php', 'craft/config/general.php');
        this.copy('db.php', 'craft/config/db.php');
    },

    templates: function() {
        this.mkdir('app/templates');
        this.mkdir('app/templates/news');

        this.copy('layout.html', 'app/templates/_layout.html')
        this.copy('index.html', 'app/templates/index.html')
        this.copy('404.html', 'app/templates/404.html')

        this.copy('news__index.html', 'app/templates/news/index.html')
        this.copy('news__entry.html', 'app/templates/news/_entry.html')
    },

    install: function() {
        var done = this.async();

        this.installDependencies({
            callback: function() {
                var bowerJson = JSON.parse(fs.readFileSync('./bower.json'));

                wiredep({
                    bowerJson: bowerJson,
                    directory: 'bower_components',
                    src: 'app/templates/_layout.html',
                    exclude: ['inuitcss', 'modernizr']
                });

                done();
            }.bind(this)
        });
    }
});

module.exports = FrancisCraftGenerator;