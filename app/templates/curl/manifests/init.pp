class curl {
  if ! defined(Package['curl']) {
    package { 'curl':
      ensure => 'present',
    }
  }
}

