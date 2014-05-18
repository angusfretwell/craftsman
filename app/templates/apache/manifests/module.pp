define apache::module ($module = $title, $prefix = 'libapache2-mod-', $ensure = 'present', $enable_module = false) {
  if ! defined(Package["${prefix}${module}"]) {
    package { "${prefix}${module}":
      ensure => $ensure,
    }
  }

  if $enable_module {
    exec { "apache::module::enable_${module}":
      command => "a2enmod ${module}",
      path    => '/usr/bin:/usr/sbin',
      require => Package['apache2'],
      notify  => Service['apache2'],
    }
  }
}

