define php::pear::discover ($channel = $title) {
  exec { "php::pear::discover_${channel}":
    command => "pear channel-disover ${channel}",
    path    => '/usr/bin:/usr/sbin',
    onlyif  => "pear list-channels | /bin/grep -c ${channel}",
  }
}

