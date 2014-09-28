define php::pear::install (
  $package = $title,
  $creates,
  $dependencies = undef,
  $require = undef
) {

  include 'php::pear'

  if ($dependencies == 'true') {
    $deps = '--alldeps'
  } else {
    $deps = ''
  }

  $base_require = Class['php::pear']

  if $require {
    $full_require = [ $base_require, $require ]
  } else {
    $full_require = $base_require
  }

  exec { "php::pear::install_${title}":
    command => "pear install ${deps} ${package}",
    creates => $creates,
    path    => '/usr/bin:/usr/sbin',
    require => $full_require,
  }
}

