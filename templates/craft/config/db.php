<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/db.php
 */

return array(
  '*' => array(
  // The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
  'server' => getenv('DB_HOST'),

  // The database port.
  'port' => getenv('DB_PORT'),

  // The database username to connect with.
  'user' => getenv('DB_USER'),

  // The database password to connect with.
  'password' => getenv('DB_PASSWORD'),

  // The name of the database to select.
  'database' => getenv('DB_NAME'),

  // The prefix to use when naming tables. This can be no more than 5 characters.
  'tablePrefix' => 'craft',
  ),

  '<%= _.slugify(slug) %>.craft.dev' => array(
  // The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
  'server' => 'localhost',

  // The database username to connect with.
  'user' => 'root',

  // The database password to connect with.
  'password' => 'root',

  // The name of the database to select.
  'database' => '<%= _.slugify(slug) %>',
  ),
);
