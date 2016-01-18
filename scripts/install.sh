cd "$1/../";

# Install Craft if it's not already installed; otherwise exit
/home/vagrant/.composer/vendor/bin/craft install --terms --no-interaction || exit

# Remove Craft readme and templates directory
rm readme.txt && rm -r app/templates

# Rename htaccess file
mv public/htaccess public/.htaccess

echo "<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/db.php
 */

return array(

	// The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
	'server' => 'localhost',

	// The name of the database to select.
	'database' => 'craftsman',

	// The database username to connect with.
	'user' => 'journeyman',

	// The database password to connect with.
	'password' => 'secret',

	// The prefix to use when naming tables. This can be no more than 5 characters.
	'tablePrefix' => 'craft',

);
" > craft/config/db.php

echo "<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

return array(

);
" > craft/config/general.php

echo "<?php

// Path to your craft/ folder
\$craftPath = '../craft';

// Do not edit below this line
\$path = rtrim(\$craftPath, '/').'/app/index.php';

\$hostname = explode('.', \$_SERVER['SERVER_NAME']);
\$templatesPath = (end(\$hostname) === 'app') ? '../app/templates/' : './templates/';

// Path to the templates folder
define('CRAFT_TEMPLATES_PATH', \$templatesPath);

if (!is_file(\$path)) {
  if (function_exists('http_response_code')) {
    http_response_code(503);
  }

  exit('Could not find your craft/ folder. Please ensure that <strong><code>$craftPath</code></strong> is set correctly in ' . __FILE__);
}

require_once \$path;
" > public/index.php
