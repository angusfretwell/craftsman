define php::extension ($extension = $title, $ensure = 'present', $require = undef) {
  include 'php'

  $base_require = Class['php']

  if $require {
    $full_require = [ $base_require, $require ]
  } else {
    $full_require = $base_require
  }

  package { $extension:
    ensure  => $ensure,
    require => $full_require,
  }
}

