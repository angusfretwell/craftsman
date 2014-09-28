class php {
  if ! defined(Package['php5']) {
    package { 'php5':
      ensure => 'present',
    }
  }

  file { 'php.ini':
    ensure  => 'file',
    path    => '/etc/php5/apache2/php.ini',
    source  => 'puppet:///modules/php/development.php.ini',
    require => Package['php5'],
    owner   => 'root',
    group   => 'root',
    mode    => '0644',
  }
}

