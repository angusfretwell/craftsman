# Vagrant Puppet cURL
Puppet manifest to install cURL on our default Ubuntu Precise Vagrant development server.

## Usage
### Install cURL
To install cURL add one of the following to your manifest:

- `class { 'curl': }`
- `include 'curl'`
