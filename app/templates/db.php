<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/db.php
 */

$db_string = getenv("DATABASE_URL");
preg_match("/mysql2:\/\/([a-z]*):(\w*)@([\d|\.|:]*)\/(\w*)/", $db_string, $db_array);

return array(

    // The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
    'server' => $db_array[3],

    // The database username to connect with.
    'user' => $db_array[1],

    // The database password to connect with.
    'password' => $db_array[2],

    // The name of the database to select.
    'database' => $db_array[4],

    // The prefix to use when naming tables. This can be no more than 5 characters.
    'tablePrefix' => '',

);