class apache {
  if ! defined(Package['apache2']) {
    package { 'apache2':
      ensure => 'present',
    }
  }

  service { 'apache2':
    enable  => 'true',
    ensure  => 'running',
    require => Package['apache2'],
  }

  exec { 'enable_mod_rewrite':
    command => 'a2enmod rewrite',
    path    => '/usr/bin:/usr/sbin',
    require => Package['apache2'],
    notify  => Service['apache2'],
  }

  file { '/etc/apache2/ports.conf':
    ensure => 'present',
    source => 'puppet:///modules/apache/ports.conf',
    owner  => 'root',
    group  => 'root',
    mode   => '0644',
    notify => Service['apache2'],
  }

  file { '/etc/apache2/sites-available/default':
    ensure  => 'present',
    content => template('apache/virtualhost.erb'),
    owner   => 'root',
    group   => 'root',
    mode    => '0644',
    notify  => Service['apache2'],
  }
}

