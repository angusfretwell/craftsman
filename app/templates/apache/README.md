# Vagrant Puppet Apache
Puppet manifests to install and configure Apache on our default Ubuntu Precise Vagrant development server.

## Usage
### Install Apache
To install Apache add one of the following to your manifest:

- `class { 'apache': }`
- `include 'apache'`

### Install an Apache module
To install an Apache module, pass the name of the module to the `apache::module` helper:

~~~~~ruby
# Installs the libapache2-mod-auth-mysql module
apache::module { 'auth-mysql': }
~~~~~

#### Specify a custom module prefix
The `apache::module` helper automatically prefixes the module name with `libapache2-mod-`. If required, you may specify an alternative prefix:

~~~~~ruby
# Installs the libapache2-modsecurity module
apache::module { 'security':
  prefix => 'libapache2-mod',
}
~~~~~

#### Enable the module
By default, the `apache::module` helper makes no attempt to "enable" the given module. You can change this behaviour by setting `enable_module` to 'true':

~~~~~ruby
# Installs and enables the php5 module
apache::module { 'php5':
  enable_module => 'true',
}
~~~~~

Enabling a module will cause the Apache service to be restarted.

#### Enable multiple modules
To enable multiple modules with a single command, just pass an array to the `apache::module` helper. If you specify a `prefix` or set `enable_module`, this will apply to all of the specified modules:

~~~~~ruby
# Installs multiple modules
apache::module { ['lisp', 'passenger', 'python']: }
~~~~~
