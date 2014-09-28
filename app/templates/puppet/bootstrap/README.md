# Vagrant Bootstrap
Shell script to bootstrap our default Ubuntu Precise Vagrant development server, ready for provisioning with Puppet.

Installs the current version of Puppet from the official Puppet Labs package.

## Usage
Call the bootstrap script from your `Vagrantfile`, prior to provisioning the VM with Puppet:

~~~~~ruby
Vagrant.configure('2') do |config|
  # Bootstrap the VM
  config.vm.provision :shell, :path => 'path/to/bootstrap.sh'

  # Provision the VM with Puppet
  config.vm.provision :puppet
end
~~~~~

## Bugs and annoyances
At present `dpkg-preconfigure` outputs the following warning when updating Puppet:

~~~~~
dpkg-preconfigure: unable to re-open stdin: No such file or directory
~~~~~

This doesn't affect anything, and may be safely ignored.
