'use strict';

Object.defineProperty(exports, '__esModule', {
  value: true
});

var _createClass = (function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ('value' in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

var _get = function get(_x, _x2, _x3) { var _again = true; _function: while (_again) { var object = _x, property = _x2, receiver = _x3; desc = parent = getter = undefined; _again = false; if (object === null) object = Function.prototype; var desc = Object.getOwnPropertyDescriptor(object, property); if (desc === undefined) { var parent = Object.getPrototypeOf(object); if (parent === null) { return undefined; } else { _x = parent; _x2 = property; _x3 = receiver; _again = true; continue _function; } } else if ('value' in desc) { return desc.value; } else { var getter = desc.get; if (getter === undefined) { return undefined; } return getter.call(receiver); } } };

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { 'default': obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError('Cannot call a class as a function'); } }

function _inherits(subClass, superClass) { if (typeof superClass !== 'function' && superClass !== null) { throw new TypeError('Super expression must either be null or a function, not ' + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) subClass.__proto__ = superClass; }

require('core-js/shim');

var _yosay = require('yosay');

var _yosay2 = _interopRequireDefault(_yosay);

var _mkdirp = require('mkdirp');

var _mkdirp2 = _interopRequireDefault(_mkdirp);

var _yeomanGenerator = require('yeoman-generator');

var CraftsmanGenerator = (function (_Base) {
  function CraftsmanGenerator() {
    _classCallCheck(this, CraftsmanGenerator);

    for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _get(Object.getPrototypeOf(CraftsmanGenerator.prototype), 'constructor', this).apply(this, args);

    this.pkg = require('../package.json');

    this.sourceRoot(__dirname + '/../templates');

    // Configure Lodash templating so it ignores interpolation markers in
    // ES6 template strings.
    this._.templateSettings.interpolate = /<%=([\s\S]+?)%>/g;

    var done = this.async();

    this.log((0, _yosay2['default'])('You\'re using the fantastic Craft generator.'));
  }

  _inherits(CraftsmanGenerator, _Base);

  _createClass(CraftsmanGenerator, [{
    key: 'app',
    value: function app() {
      (0, _mkdirp2['default'])('app');
      (0, _mkdirp2['default'])('app/images');
      this.write('app/images/.gitkeep', '');
    }
  }, {
    key: 'styles',
    value: function styles() {
      if (!this.includeInuit) {
        (0, _mkdirp2['default'])('app/styles');
        this.copy('main.scss', 'app/styles/main.scss');
      } else {
        this.directory('styles', 'app/styles');
      }
    }
  }, {
    key: 'scripts',
    value: function scripts() {
      (0, _mkdirp2['default'])('app/scripts');
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
      this.copy('gulpfile.babel.js', 'gulpfile.babel.js');
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
      (0, _mkdirp2['default'])('public');
      (0, _mkdirp2['default'])('public/assets');
      this.write('public/assets/.gitkeep', '');

      this.copy('index.php', 'public/index.php');
      this.copy('htaccess', 'public/.htaccess');

      (0, _mkdirp2['default'])('craft');
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
  }, {
    key: 'prompting',
    get: function get() {
      return {
        slug: function slug() {
          var _this = this;

          var done = this.async();

          var prompt = [{
            type: 'input',
            name: 'slug',
            messag: 'Enter example unique slug for this project'
          }];

          this.prompt(prompt, function (_ref) {
            var slug = _ref.slug;

            _this.slug = slug;
            done();
          });
        },

        remoteDev: function remoteDev() {
          var _this2 = this;

          var done = this.async();

          var prompt = [{
            type: 'input',
            name: 'dev',
            message: 'Enter the hostname of the dokku development server',
            'default': 'dev.example.com'
          }];

          this.prompt(prompt, function (_ref2) {
            var dev = _ref2.dev;

            _this2.remoteDev = dev;
            done();
          });
        },

        remoteStaging: function remoteStaging() {
          var _this3 = this;

          var done = this.async();

          var prompt = [{
            type: 'input',
            name: 'staging',
            message: 'Enter the hostname of the dokku staging server',
            'default': 'staging.example.com'
          }];

          this.prompt(prompt, function (_ref3) {
            var staging = _ref3.staging;

            _this3.remoteStaging = staging;
            done();
          });
        },

        remoteProduction: function remoteProduction() {
          var _this4 = this;

          var done = this.async();

          var prompt = [{
            type: 'input',
            name: 'production',
            message: ' the hostname of the dokku production server',
            defaults: 'craft.mydokkuhost.com'
          }];

          this.prompt(prompt, function (_ref4) {
            var production = _ref4.production;

            _this4.remoteProduction = production;
            done();
          });
        },

        features: function features() {
          var _this5 = this;

          var done = this.async();

          var prompt = [{
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
          }];

          this.prompt(prompt, function (_ref5) {
            var features = _ref5.features;

            function hasFeature(feature) {
              return features.indexOf(feature) !== -1;
            }

            _this5.includeInuit = hasFeature('includeInuit');
            _this5.includejQuery = hasFeature('includejQuery');

            done();
          });
        }
      };
    }
  }]);

  return CraftsmanGenerator;
})(_yeomanGenerator.Base);

exports['default'] = CraftsmanGenerator;
module.exports = exports['default'];