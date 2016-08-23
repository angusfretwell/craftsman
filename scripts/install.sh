cd $1 && cd ../ || exit;

# Install Composer requirements
composer install --prefer-dist --no-interaction --quiet

# Install node modules
npm install --no-progress

# Install Craft if it's not already installed; otherwise exit
/home/vagrant/.composer/vendor/bin/craft install --terms --no-interaction || exit

# Remove Craft readme and templates directory
rm readme.txt && rm -r craft/templates

# Rename htaccess file
mv public/htaccess public/.htaccess

# Generate .env file from .env.example
cat .env.example > .env

echo "<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/db.php
 */

return array(

	// The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
	'server' => env('DB_SERVER'),

	// The name of the database to select.
	'database' => env('DB_DATABASE'),

	// The database username to connect with.
	'user' => env('DB_USER'),

	// The database password to connect with.
	'password' => env('DB_PASSWORD'),

	// The prefix to use when naming tables. This can be no more than 5 characters.
	'tablePrefix' => env('DB_TABLE_PREFIX'),

);
" > craft/config/db.php

echo "<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

 // Ensure our URLs have the right scheme
 define('URI_SCHEME', (isset(\$_SERVER['HTTPS'])) ? 'https://' : 'http://');

 // The site URL
 define('SITE_URL', URI_SCHEME . \$_SERVER['SERVER_NAME'] . '/');

 // The site basepath
 define('BASEPATH', realpath(CRAFT_BASE_PATH . '/../') . '/');

return array(
  'omitScriptNameInUrls' => true,
  'allowAutoUpdates' => env('ALLOW_AUTO_UPDATES') ?? false,

  'environmentVariables' => array(
    'siteUrl'  => SITE_URL,
    'basePath' => BASEPATH
  ),

  'devMode' => env('DEV_MODE') ?? false,
  'enableTemplateCaching' => env('ENABLE_TEMPLATE_CACHING') ?? true,
  'testToEmailAddress' => env('TEST_TO_EMAIL_ADDRESS'),
);
" > craft/config/general.php

echo "<?php

require_once('../vendor/autoload.php');

try {
  (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException \$e) {
  //
}

Env::init();

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

  exit('Could not find your craft/ folder. Please ensure that <strong><code>\$craftPath</code></strong> is set correctly in ' . __FILE__);
}

require_once \$path;
" > public/index.php
