# Vagrant Puppet PHP
Puppet manifests to install and configure PHP on our default Ubuntu Precise Vagrant development server.

## Usage
### Install PHP
To install PHP add one of the following to your manifest:

- `class { 'php': }`
- `include 'php'`

This will install PHP 5.3.10 (via the Ubuntu package manager), and configure it for development. Take a look at `files/development.php.ini` for the exact configuration details.

### Install a PHP extension
To install a PHP extension, pass the extension name to the `php::extension` helper:

~~~~~ruby
# Installs the php5-curl extension
php::extension { 'php5-curl': }
~~~~~

#### Specify extension requirements
The `php::extension` helper requires that the `php5` package is installed. You may specify additional requirements using the `require` parameter:

~~~~~ruby
# Requires the 'wibble' package, in addition to the 'php5' package
php::extension { 'php5-gd':
  require => Package['wibble'],
}
~~~~~

#### Install multiple extensions
To install multiple extensions with a single command, just pass an array to the `php::extension` helper. Any parameters will apply to all of the specified extensions:

~~~~~ruby
# Installs multiple extensions
php::extension { ['php-pear', 'php5-mcrypt', 'php5-xdebug']: }
~~~~~

### Uninstall a PHP extension
By default, the `php::extension` helper will ensure that the specified extension is "present". To uninstall an extension, you can tell it to ensure that the extension is "absent":

~~~~~ruby
# Uninstalls the php5-curl extension
php::extension { 'php5-curl':
  ensure => 'absent',
}
~~~~~

### Install PEAR
To install PEAR add one of the following to your manifest:

1. `class { 'php::pear': }`
2. `include 'php::pear'`

This will:

1. Upgrade PEAR to the latest version;
2. Set `autodiscover` to `1`;
3. Update the PEAR channels.

Note that running this command **will not install the `php-pear` extension**. The previous section describes how to easily install a PHP extension.

### Install a PEAR package
To install a PEAR package, pass the package name to the `php::pear::install` helper, along with the path to the file that is created during the installation:

~~~~~ruby
# Installs the PHPUnit PEAR package
php::pear::install { 'phpunit':
  package => 'pear.phpunit.de/PHPUnit',
  creates => '/usr/bin/phpunit',
}
~~~~~

The `creates` parameter is required, to ensure that we do not attempt to install a PEAR package that is already installed.

#### Install PEAR package dependencies
To install a PEAR package's dependencies, set the `dependencies` parameter to 'true':

~~~~~ruby
# Installs the PHPUnit PEAR package, along with any dependencies
php::pear::install { 'phpunit':
  package      => 'pear.phpunit.de/PHPUnit',
  creates      => '/usr/bin/phpunit',
  dependencies => 'true',
}
~~~~~
#### Specify PEAR package requirements
The `php::pear::install` helper requires that `php` Class. You may specify additional requirements using the `require` parameter:

~~~~~ruby
# Requires the execution of 'wibble', prior to installing PHPUnit
php::pear::install { 'phpunit':
  package      => 'pear.phpunit.de/PHPUnit',
  creates      => '/usr/bin/phpunit',
  require      => Exec['wibble'],
}
~~~~~

### Discover a PEAR channel
By default, PEAR is set to auto-discover new channels. If this proves to be insufficient, you can explicitly instruct PEAR to 'discover' a channel using the `php::pear::discover` helper:

~~~~~ruby
# Discovers the PHPDoc channel
php::pear::discover { 'pear.phpdoc.org': }
~~~~~
