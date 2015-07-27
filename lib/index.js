'use strict';

Object.defineProperty(exports, '__esModule', {
  value: true
});

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

var _get = function get(_x, _x2, _x3) { var _again = true; _function: while (_again) { var object = _x, property = _x2, receiver = _x3; desc = parent = getter = undefined; _again = false; if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { _x = parent; _x2 = property; _x3 = receiver; _again = true; continue _function; } } else if ('value' in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } } };

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

function _inherits(subClass, superClass) { if (typeof superClass !== 'function' && superClass !== null) { throw new TypeError('Super expression must either be null or a function, not ' + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

require('core-js/shim');

var _yeomanGenerator = require('yeoman-generator');

var _yosay = require('yosay');

var CraftGenerator = (function (_Base) {
  _inherits(CraftGenerator, _Base);

  function CraftGenerator() {
    _classCallCheck(this, CraftGenerator);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _get(Object.getPrototypeOf(CraftGenerator.prototype), 'constructor', this).apply(this, args);

    this.pkg = require('../package.json');

    this.sourceRoot('../templates');

    // Configure Lodash templating so it ignores interpolation markers in
    // ES6 template strings.
    this._.templateSettings.interpolate = /<%=([\s\S]+?)%>/g;
  }

  _createClass(CraftGenerator, [{
    key: 'prompting',
    value: function prompting() {
      var _this = this;

      var done = this.async();

      this.log((0, _yosay.yosay)('You\'re using the fantastic Craft generator.'));

      this.prompt([{
        name: 'slug',
        message: 'Enter a unique slug for this project'
      }, {
        name: 'dev',
        message: 'Enter the hostname of the dokku development server',
        defaults: 'dev.mydokkuhost.com'
      }, {
        name: 'staging',
        message: 'Enter the hostname of the dokku staging server',
        defaults: 'staging.mydokkuhost.com'
      }, {
        name: 'production',
        message: 'Enter the hostname of the dokku production server',
        defaults: 'craft.mydokkuhost.com'
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
      }], (function (props) {
        _this.slug = props.slug;
        _this.remoteDev = props.dev;
        _this.remoteStaging = props.staging;
        _this.remoteProduction = props.production;

        function hasFeature(feat) {
          return props.features.indexOf(feat) !== -1;
        }

        _this.includeInuit = hasFeature('includeInuit');
        _this.includejQuery = hasFeature('includejQuery');

        done();
      }).bind(this));
    }
  }, {
    key: 'app',
    value: function app() {
      this.mkdir('app');
      this.mkdir('app/images');
      this.write('app/images/.gitkeep', '');
    }
  }, {
    key: 'styles',
    value: function styles() {
      if (!this.includeInuit) {
        this.mkdir('app/styles');
        this.copy('main.scss', 'app/styles/main.scss');
      } else {
        this.directory('styles', 'app/styles');
      }
    }
  }, {
    key: 'scripts',
    value: function scripts() {
      this.mkdir('app/scripts');
      this.write('app/scripts/main.js', 'console.log(\'\\\'Allo \\\'Allo!\');');
    }
  }, {
    key: 'bower',
    value: function bower() {
      this.copy('bowerrc', '.bowerrc');
      this.copy('_bower.json', 'bower.json');
    }
  }, {
    key: 'gulp',
    value: function gulp() {
      this.template('gulpfile.babel.js', 'gulpfile.babel.js');
    }
  }, {
    key: 'git',
    value: function git() {
      this.copy('gitignore', '.gitignore');
      this.copy('gitattributes', '.gitattributes');
    }
  }, {
    key: 'package',
    value: function _package() {
      this.copy('_package.json', 'package.json');
    }
  }, {
    key: 'composer',
    value: function composer() {
      this.copy('_composer.json', 'composer.json');
      this.copy('_composer.lock', 'composer.lock');
    }
  }, {
    key: 'extras',
    value: function extras() {
      this.copy('favicon.ico', 'app/favicon.ico');
      this.copy('robots.txt', 'app/robots.txt');

      this.copy('editorconfig', '.editorconfig');
      this.copy('eslintrc', '.eslintrc');
      this.copy('env', '.env');
      this.copy('CHECKS', 'CHECKS');
      this.copy('_env.json', 'env.json');
      this.copy('Procfile', 'Procfile');
    }
  }, {
    key: 'vagrant',
    value: function vagrant() {
      this.copy('Vagrantfile', 'Vagrantfile');
    }
  }, {
    key: 'puppet',
    value: function puppet() {
      this.directory('puppet', 'puppet');
    }
  }, {
    key: 'craft',
    value: function craft() {
      this.mkdir('public');
      this.mkdir('public/assets');
      this.write('public/assets/.gitkeep', '');

      this.copy('index.php', 'public/index.php');
      this.copy('htaccess', 'public/.htaccess');

      this.mkdir('craft');
      this.directory('craft', 'craft');
    }
  }, {
    key: 'templates',
    value: function templates() {
      this.directory('templates', 'app/templates');
    }
  }, {
    key: 'install',
    value: function install() {
      this.installDependencies();
    }
  }]);

  return CraftGenerator;
})(_yeomanGenerator.Base);

exports['default'] = CraftGenerator;

module.exports = CraftGenerator;
module.exports = exports['default'];