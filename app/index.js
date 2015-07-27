import 'core-js/shim';

import { Base } from 'yeoman-generator';
import { yosay } from 'yosay';

export default class CraftGenerator extends Base {
  constructor(...args) {
    super(...args);

    this.pkg = require('../package.json');

    this.sourceRoot('../templates');

    // Configure Lodash templating so it ignores interpolation markers in
    // ES6 template strings.
    this._.templateSettings.interpolate = /<%=([\s\S]+?)%>/g;
  }

  prompting() {
    const done = this.async();

    this.log(yosay('You\'re using the fantastic Craft generator.'));

    this.prompt([
      {
        name: 'slug',
        message: 'Enter a unique slug for this project',
      }, {
        name: 'dev',
        message: 'Enter the hostname of the dokku development server',
        defaults: 'dev.mydokkuhost.com',
      }, {
        name: 'staging',
        message: 'Enter the hostname of the dokku staging server',
        defaults: 'staging.mydokkuhost.com',
      }, {
        name: 'production',
        message: 'Enter the hostname of the dokku production server',
        defaults: 'craft.mydokkuhost.com',
      }, {
        type: 'checkbox',
        name: 'features',
        message: 'What else would you like?',

        choices: [
          {
            name: 'inuit.css',
            value: 'includeInuit',
            checked: true,
          }, {
            name: 'jQuery',
            value: 'includejQuery',
            checked: true,
          },
        ],
      },
    ], (props) => {
      this.slug = props.slug;
      this.remoteDev = props.dev;
      this.remoteStaging = props.staging;
      this.remoteProduction = props.production;

      function hasFeature(feat) {
        return props.features.indexOf(feat) !== -1;
      }

      this.includeInuit = hasFeature('includeInuit');
      this.includejQuery = hasFeature('includejQuery');

      done();
    }.bind(this));
  }

  app() {
    this.mkdir('app');
    this.mkdir('app/images');
    this.write('app/images/.gitkeep', '');
  }

  styles() {
    if (!this.includeInuit) {
      this.mkdir('app/styles');
      this.copy('main.scss', 'app/styles/main.scss');
    } else {
      this.directory('styles', 'app/styles');
    }
  }

  scripts() {
    this.mkdir('app/scripts');
    this.write('app/scripts/main.js', 'console.log(\'\\\'Allo \\\'Allo!\');');
  }

  bower() {
    this.copy('bowerrc', '.bowerrc');
    this.copy('_bower.json', 'bower.json');
  }

  gulp() {
    this.template('gulpfile.babel.js', 'gulpfile.babel.js');
  }

  git() {
    this.copy('gitignore', '.gitignore');
    this.copy('gitattributes', '.gitattributes');
  }

  package() {
    this.copy('_package.json', 'package.json');
  }

  composer() {
    this.copy('_composer.json', 'composer.json');
    this.copy('_composer.lock', 'composer.lock');
  }

  extras() {
    this.copy('favicon.ico', 'app/favicon.ico');
    this.copy('robots.txt', 'app/robots.txt');

    this.copy('editorconfig', '.editorconfig');
    this.copy('eslintrc', '.eslintrc');
    this.copy('env', '.env');
    this.copy('CHECKS', 'CHECKS');
    this.copy('_env.json', 'env.json');
    this.copy('Procfile', 'Procfile');
  }

  vagrant() {
    this.copy('Vagrantfile', 'Vagrantfile');
  }

  puppet() {
    this.directory('puppet', 'puppet');
  }

  craft() {
    this.mkdir('public');
    this.mkdir('public/assets');
    this.write('public/assets/.gitkeep', '');

    this.copy('index.php', 'public/index.php');
    this.copy('htaccess', 'public/.htaccess');

    this.mkdir('craft');
    this.directory('craft', 'craft');
  }

  templates() {
    this.directory('templates', 'app/templates');
  }

  install() {
    this.installDependencies();
  }
}

module.exports = CraftGenerator;
