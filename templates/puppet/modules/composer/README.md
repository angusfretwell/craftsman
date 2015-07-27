# Vagrant Puppet Composer
Puppet manifest to install Composer on our default Ubuntu Precise Vagrant development server.

## Usage
### Install Composer
To install Composer add one of the following to your manifest:

- `class { 'composer': }`
- `include 'composer'`

This will [install Composer globally][composer_global].

[composer_global]: http://getcomposer.org/doc/00-intro.md#globally

## Requirements
The Composer manifest requires that cURL be installed. [The cURL Puppet manifest][curl_manifest] can take care of that.

[curl_manifest]: https://github.com/experience/vagrant-puppet-curl