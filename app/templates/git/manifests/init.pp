class git {
  if ! defined(Package['git-core']) {
    package { 'git-core':
      ensure => 'present',
    }
  }
}
