Generator Craftsman
===================

A [Yeoman](http://yeoman.io) generator for scaffolding a [Craft](http://buildwithcraft.com) website with [Gulp](http://gulpjs.com/), Bower, Sass, Babel, inuit.css, and more.

Features
--------

* Create a fresh installation of craft
* Generate a Vagrantfile and Puppet provisioning scripts
* Deploy to a Dokku-powered hosting environment
* Copy databases to and from from Dokku hosts
* Automatically compile Sass with Autoprefixing
* Automatically lint your Javascript
* Image optimization (png, jpg, gif)
* Optionally include inuit.css and jQuery

Installation
------------

* Install: `npm install -g generator-craftsman`

### Requirements

* For using the provided development environment, VirtualBox, Vagrant, and the [Vagrant Host Manager](https://github.com/smdahlen/vagrant-hostmanager) plugin must be installed.
* Yeoman, Bower, and gulp.js should be installed globally via npm: `npm i -g yo bower gulp`


Project Setup
-------------

* Create a new directory with `mkdir my-craft-project`
* Change to the project directory with `cd my-craft-project`
* Run `yo craft` and follow the prompts

### Development

* Run `gulp build` to build to `/public`
* Run `gulp watch` to watch for changes and live-reload via BrowserSync

### Deployment

Hint: Use the `--env` flag to specify an environment listend in `env.json`, e.g., `gulp deploy --env production`.

* Use `gulp deploy-init` to initalise a deployment environment
* Run `gulp deploy` after committing changes to deploy them
* Use `gulp db-push` and `gulp db-pull` to copy databases to and from an environment

Available Commands
------------------

### Deployment

* `gulp deploy-init`

  Initialize a Dokku container for use in the project's deployment.

  1. Adds a git remote corresponding with the Dokku server.
  2. Pushes the repository to the Dokku remote.
  3. Defines a Buildpack for Dokku to use in the project's deployment.
  4. Sets up and links a new MariaDB container.

  You should follow this command with gulp db-push.

* `gulp deploy`

  Pushes the repository to the Dokku remote.

* `gulp db-push`

  Dumps the local database to `/.tmp`, and imports it to the linked Dokku MariaDB container.

* `gulp db-pull`

  Dumps the remote Dokku MariaDB database to `/.tmp`, and imports it to the local environment.

* `gulp db-backup`

  Dumps the local and remote databases, and saves them to `/databases`.

### Development

* `gulp watch`

  Watches the project for changes in images, styles, javascript, HTML, etc. and performs appropriate actions.

* `gulp build`

  Build the project for deployment. Performs all tasks including minification and image optimization.

### Miscellaneous

* `bower install`

  Install project-specific Bower packages defined in bower.json. You should run this command when cloning an already initialized repository.

* `npm install`

  Install project-specific npm packages defined in the package.json. You should run this command when cloning an already initialized repository.

## Known Issues

* Rerunning `gulp deploy-init` will fail, since a Dokku remote has already been created. Running `git remote remove dokku` will resolve this.
* `gulp deploy` will sometimes fail if a newer commit has been deployed but not pushed to the repository. You can override this by running `git push origin dokku --force`.
