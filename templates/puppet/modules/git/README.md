# Vagrant Puppet Git
Puppet manifest to install Git on our default Ubuntu Precise Vagrant development server.

## Usage
### Install Git
To install Git add one of the following to your manifest:

- `class { 'git': }`
- `include 'git'`
