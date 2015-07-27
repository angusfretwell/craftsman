define mysql::user::drop ($user = $title, $host = '') {
  if $host == '' {
    $check_sql = "SELECT EXISTS(SELECT `user` FROM `mysql`.`user` WHERE `user` = '${user}' AND `host` = '${host}');"
    $drop_sql  = "DROP USER '${user}"
  } else {
    $check_sql = "SELECT EXISTS(SELECT `user` FROM `mysql`.`user` WHERE `user` = '${user}');"
    $drop_sql  = "DROP USER '${user}@${host}'"
  }

  exec { "mysql::user::drop_${user}_${host}":
    command => "mysql -uroot -p${mysql::root_password} -e \"${drop_sql}\"",
    returns => '1',
    unless  => "mysql -uroot -p${mysql::root_password} -e \"${check_sql}\"",
    path    => $mysql::bin,
    require => Exec['mysql::set_root_password'],
  }
}

