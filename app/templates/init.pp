class app {
  # -----------------------------------------------------------------
  # CONFIGURABLE PROPERTIES
  # -----------------------------------------------------------------
  $root_password = 'root'
  $db_name       = '<%= _.slugify(slug) %>'
  $db_username   = 'root'
  $db_password   = 'root'
  # -----------------------------------------------------------------

  $bin_path = '/usr/bin:/usr/sbin'

  # -----------------------------------------------------------------
  # Ensure everything is up-to-date before we begin installing stuff
  # -----------------------------------------------------------------
  exec { 'app::update_system':
    command => 'apt-get update',
    path    => $app::bin_path,
  }

  File    { require => Exec['app::update_system'] }
  Package { require => Exec['app::update_system'] }
  Service { require => Exec['app::update_system'] }

  # -----------------------------------------------------------------
  # Apache
  # -----------------------------------------------------------------
  include 'apache'

  apache::module { 'auth-mysql': }

  apache::module { 'php5':
    enable_module => 'true',
  }


  # -----------------------------------------------------------------
  # MySQL
  # -----------------------------------------------------------------
  class { 'mysql':
    root_password => $root_password,
  }

  mysql::db::create { $db_name: }

  mysql::user::grant {$db_username:
    host     => 'localhost',
    password => $db_password,
    database => $db_name,
  }


  # -----------------------------------------------------------------
  # PHP
  # -----------------------------------------------------------------
  php::extension { [
    'php-pear',
    'php5-curl',
    'php5-gd',
    'php5-mcrypt',
    'php5-mysql',
    'php5-sqlite',
    'php5-xdebug',
  ]: }


  # -----------------------------------------------------------------
  # Oddments
  # -----------------------------------------------------------------
  include 'curl'
  include 'composer'
}