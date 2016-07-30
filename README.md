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

## License

The MIT License (MIT)

Copyright (c) 2016 Angus Fretwell
