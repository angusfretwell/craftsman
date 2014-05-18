define mysql::db::create ($dbname = $title) {
  exec { "mysql::db::create_${dbname}":
    command => "mysql -uroot -p${mysql::root_password} -e \"CREATE DATABASE IF NOT EXISTS ${dbname}\"",
    path    => $mysql::bin,
    require => Exec['mysql::set_root_password'],
  }
}

