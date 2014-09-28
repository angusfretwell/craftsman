class php::pear {
  if ! defined(Package['php-pear']) {
    package { 'php-pear':
      ensure => 'present',
    }
  }

  exec { 'php::pear::upgrade':
    command => '/usr/bin/pear upgrade',
    require => Package['php-pear'],

    # @see http://blog.code4hire.com/2013/01/pear-packages-installation-under-vagrant-with-puppet/
    returns => [0, '', ' '],
  }

  exec { 'php::pear::set_autodiscover':
    command => '/usr/bin/pear config-set auto_discover 1',
    require => Exec['php::pear::upgrade'],
  }

  exec { 'php::pear::update_channels':
    command => '/usr/bin/pear update-channels',
    require => Exec['php::pear::set_autodiscover'],
  }
}

