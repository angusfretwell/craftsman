# Craftsman

An opinionated Craft CMS development environment.

## Introduction

Craftsman aims to simplify and automate the development of [Craft CMS](http://craftcms.com) projects by providing an opinionated structure, Vagrantfile and provisioning scripts, and a front-end asset pipeline using Gulp.

### Features

- A Vagrant machine configured to serve the Craft site with PHP 7 and Nginx
- Easy configuration overrides with PHP dotenv
- Sass compilation, minification and sourcemaps
- ES2015 compilation using Babel and [Airbnb's preset](https://github.com/airbnb/babel-preset-airbnb), module bundling with Browserify
- JavaScript linting with ESLint, configured to use [Airbnb's style guide](https://github.com/airbnb/javascript)
- Live reloading whenever your templates or assets change using BrowserSync

## Setup

### Requirements

You'll need to have Vagrant and the Virtualbox provider installed (refer to Vagrant's [getting started](https://www.vagrantup.com/docs/getting-started/) documentation). Experimental support for the VMware provider is available by update the provider value in the Craftsman.yaml file.

### Installation

1. Download the [latest release](https://github.com/angusfretwell/craftsman/releases) manually, or using curl:

  ```shell
  $ mkdir -p craftsman && curl -L https://github.com/angusfretwell/craftsman/archive/master.tar.gz | tar xvf - -C craftsman --strip-components 1
  ```

2. You may wish to modify the default configuration in the Craftsman.yaml in order to change the default hostname, resource usage, directory names, or database name.

3. Start the Vagrant machine. This will install Composer and npm dependencies, download the latest version of Craft, create a MySQL database, and configure Nginx serve your site.

  ```shell
  $ vagrant up
  ```

4. Configure a friendly hostname on your host machine:

  ```shell
  $ echo '192.168.10.10 craftsman.app' | sudo tee -a /etc/hosts
  ```

5. Install Craft by navigating to [http://craftsman.app/admin](http://craftsman.app/admin) and following the prompts.

## Usage

### Asset pipeline

JavaScript, Sass, and other front-end assets are compiled with Gulp. The following can be run inside the Vagrant machine (`vagrant ssh`), but can also be run on the host machine if you've installed Gulp globally (`npm install -g gulp`):

```shell
$ gulp
```

#### Watch for changes

The `gulp watch` command will continue running in your terminal and watch your assets for any changes. When changes occur, new files will automatically be compiled. Additionally, BrowserSync will proxy your site on port 8080, and automatically reload your browser when changes are made:

```shell
$ gulp watch
```

#### Sass compilation

The SCSS files in app/styles will be compiled, built to CSS, and minified by the asset pipeline. From the entry-point (app.scss), you can `@import` local SCSS files and CSS or SCSS files from npm packages:

```scss
// Importing from a local file (app/styles/components/_button.scss)
@import 'components/button';

// Importing from a package
@import 'normalize.css/normalize';
```

#### ES2015 and module bundling

Craftsman will compile JavaScript in the app/scripts directory with Browserify, and transpile ES2015 syntax to ES5 using Babel (using [Airbnb's preset](https://github.com/airbnb/babel-preset-airbnb). From the entry point (app.js), you can `import` or `require` local JavaScript files or from npm packages:

```js
// Importing from a local file (app/scripts/components/button.js)
import button from './components/button';

// Importing from a package
import $ from 'jquery';
```

##### ESLint

ESLint is configured to run during `gulp watch`, and uses [Airbnb's style guide](https://github.com/airbnb/javascript).

#### Integrating front-end libraries

##### Example: Bootstrap

1. Install [bootstrap-sass](https://github.com/twbs/bootstrap-sass) and jQuery

  ```shell
  npm install --save bootstrap-sass jquery
  ```

2. Import Bootstrap styles in app.scss:

  ```scss
  @import 'bootstrap-sass';
  ```

3. Require jQuery and Bootstrap javascript in app.js:

  ```js
  require('jquery');
  require('bootstrap-sass');
  ```

### Vagrant machine

When provisioning the Vagrant machine, Craftsman runs a series of provisioning scripts that installs Craft, creates a MySQL database, and configures Nginx to serve the site. Craftsman uses the [Journeyman](https://github.com/angusfretwell/journeyman) base box.

#### Development environment configuration

The hostname, folders, databases, and system resources used by Craftsman's provisioning scripts can be configured in the Craftsman.yaml file.

#### Custom provisioning tasks

If you would like to do some extra provisioning you may add any commands you wish to the scripts/after.sh file and they will be run after the Craftsman machine is provisioned.

#### Environment variables

Environment variables includes MySQL connection details, development mode, template caching  and test email address can be set in the .env file which is loaded during runtime. These environment variables are used in Craft's configuration files in craft/config.

During provisioning, Craftsman copies the .env.example file (which is tracked in version control) to .env, so that a developer can customise configuration without affecting another developer's environment.

If you'd like to control more of Craft's configuration on a per-environment, you can add more variables to the .env file (and .env.example file), and then utilise these variables in Craft's config files:

```shell
# .env
DEV_MODE=true
```

```php
<?php
// craft/config/general.php
return array(
  'devMode' => env('DEV_MODE'),
);
```

## License

The MIT License (MIT)

Copyright (c) 2016 Angus Fretwell
