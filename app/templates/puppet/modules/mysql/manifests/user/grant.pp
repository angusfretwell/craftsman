define mysql::user::grant ($user = $title, $host, $password, $database, $table = '*', $privileges = 'ALL PRIVILEGES') {
  exec { "mysql::user::grant_${user}_${host}_${database}_${table}_${privileges}":
    command => "mysql -uroot -p${mysql::root_password} -e \"GRANT ${privileges} ON ${database}.${table} TO '${user}'@'${host}' IDENTIFIED BY '${password}'; FLUSH PRIVILEGES;\"",
    path    => $mysql::bin,
    require => [
      Exec['mysql::set_root_password'],
      Exec["mysql::db::create_${database}"]
    ],
  }
}

