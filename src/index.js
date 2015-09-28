import 'core-js/shim';
import yosay from 'yosay';
import mkdirp from 'mkdirp';

import { Base } from 'yeoman-generator';

export default class CraftsmanGenerator extends Base {
  constructor(...args) {
    super(...args);

    this.pkg = require('../package.json');

    this.sourceRoot(`${__dirname}/../templates`);

    // Configure Lodash templating so it ignores interpolation markers in
    // ES6 template strings.
    this._.templateSettings.interpolate = /<%=([\s\S]+?)%>/g;

    const done = this.async();

    this.log(yosay('You\'re using the fantastic Craft generator.'));
  }

  get prompting() {
    return {
      slug() {
        let done = this.async();

        let prompt = [
          {
            type: 'input',
            name: 'slug',
            message: 'Enter example unique slug for this project',
          },
        ];

        this.prompt(prompt, ( { slug } ) => {
          this.slug = slug;
          done();
        });
      },

      remoteDev() {
        let done = this.async();

        let prompt = [
          {
            type: 'input',
            name: 'dev',
            message: 'Enter the hostname of the dokku development server',
            default: 'dev.example.com',
          },
        ];

        this.prompt(prompt, ( { dev } ) => {
          this.remoteDev = dev;
          done();
        });
      },

      remoteStaging() {
        let done = this.async();

        let prompt = [
          {
            type: 'input',
            name: 'staging',
            message: 'Enter the hostname of the dokku staging server',
            default: 'staging.example.com',
          },
        ];

        this.prompt(prompt, ( { staging } ) => {
          this.remoteStaging = staging;
          done();
        });
      },

      remoteProduction() {
        let done = this.async();

        let prompt = [
          {
            type: 'input',
            name: 'production',
            message: ' the hostname of the dokku production server',
            default: 'production.example.com',
          },
        ];

        this.prompt(prompt, ( { production } ) => {
          this.remoteProduction = production;
          done();
        });
      },

      features() {
        let done = this.async();

        let prompt = [
          {
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
            ]
          },
        ];

        this.prompt(prompt, ( { features } ) => {
          function hasFeature(feature) {
            return features.indexOf(feature) !== -1;
          }

          this.includeInuit = hasFeature('includeInuit');
          this.includejQuery = hasFeature('includejQuery');

          done();
        });
      },
    };
  }

  app() {
    mkdirp('app');
    mkdirp('app/images');
    this.write('app/images/.gitkeep', '');
  }

  styles() {
    if (!this.includeInuit) {
      mkdirp('app/styles');
      this.copy('main.scss', 'app/styles/main.scss');
    } else {
      this.directory('styles', 'app/styles');
    }
  }

  scripts() {
    mkdirp('app/scripts');
    this.write('app/scripts/main.js', 'console.log(\'\\\'Allo \\\'Allo!\');');
  }

  bower() {
    this.copy('bowerrc', '.bowerrc');
    this.copy('_bower.json', 'bower.json');
  }

  gulp() {
    this.copy('gulpfile.babel.js', 'gulpfile.babel.js');
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
    mkdirp('public');
    mkdirp('public/assets');
    this.write('public/assets/.gitkeep', '');

    this.copy('index.php', 'public/index.php');
    this.copy('htaccess', 'public/.htaccess');

    mkdirp('craft');
    this.directory('craft', 'craft');
  }

  templates() {
    this.directory('templates', 'app/templates');
  }

  install() {
    this.installDependencies();
  }
}
