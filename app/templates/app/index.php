<?php

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

// Make sure this is PHP 5.3 or later
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
{
	exit('Craft requires PHP 5.3.0 or later, but you&rsquo;re running '.PHP_VERSION.'. Please talk to your host/IT department about upgrading PHP or your server.');
}

/**
 * Quit early if this is just an omitScriptNameInUrls or usePathInfo test request
 */

// Is this a script name redirect test?
if ((isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testScriptNameRedirect')
	|| (isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'], 'testScriptNameRedirect') !== false))
{
	exit('success');
}

// Is this a PATH_INFO test?
if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/testPathInfo')
{
	exit('success');
}

/**
 * Path constants and validation
 */

// We're already in the app/ folder, so let's use that as the starting point.
// Make sure it doesn't look like we're on a network share that starts with \\
$appPath = realpath(dirname(__FILE__));
if (isset($appPath[0]) && isset($appPath[1]))
{
	if ($appPath[0] !== '\\' && $appPath[1] !== '\\')
	{
		$appPath = str_replace('\\', '/', $appPath);
	}
}

defined('CRAFT_APP_PATH') || define('CRAFT_APP_PATH', $appPath.'/');

// The app/ folder goes inside craft/ by default, so work backwards from app/
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', realpath(CRAFT_APP_PATH.'..').'/');

// Everything else should be relative from craft/ by default
defined('CRAFT_CONFIG_PATH')       || define('CRAFT_CONFIG_PATH',       CRAFT_BASE_PATH.'config/');
defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');
defined('CRAFT_STORAGE_PATH')      || define('CRAFT_STORAGE_PATH',      CRAFT_BASE_PATH.'storage/');
defined('CRAFT_TEMPLATES_PATH')    || define('CRAFT_TEMPLATES_PATH',    CRAFT_BASE_PATH.'templates/');
defined('CRAFT_TRANSLATIONS_PATH') || define('CRAFT_TRANSLATIONS_PATH', CRAFT_BASE_PATH.'translations/');

function craft_createFolder($path)
{
	// Code borrowed from IOHelper...
	if (!is_dir($path))
	{
		$oldumask = umask(0);

		if (!mkdir($path, 0755, true))
		{
			exit('Tried to create a folder at '.$path.', but could not.');
		}

		// Because setting permission with mkdir is a crapshoot.
		chmod($path, 0755);
		umask($oldumask);
	}
}

function craft_ensureFolderIsReadable($path, $writableToo = false)
{
	$realPath = realpath($path);

	// !@file_exists('/.') is a workaround for the terrible is_executable()
	if ($realPath === false || !is_dir($realPath) || !@file_exists($realPath.'/.'))
	{
		exit (($realPath !== false ? $realPath : $path).' doesn\'t exist or isn\'t writable by PHP. Please fix that.');
	}

	if ($writableToo)
	{
		if (!is_writable($realPath))
		{
			exit ($realPath.' isn\'t writable by PHP. Please fix that.');
		}
	}
}

// Validate permissions on craft/config/ and craft/storage/
craft_ensureFolderIsReadable(CRAFT_CONFIG_PATH);
craft_ensureFolderIsReadable(CRAFT_STORAGE_PATH, true);

// Create the craft/storage/runtime/ folder if it doesn't already exist
craft_createFolder(CRAFT_STORAGE_PATH.'runtime/');
craft_ensureFolderIsReadable(CRAFT_STORAGE_PATH.'runtime/', true);

/**
 * Load the config
 */

// Set the environment
defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', $_SERVER['SERVER_NAME']);

// We need to special case devMode in the config because YII_DEBUG has to be set as early as possible.
if (file_exists(CRAFT_CONFIG_PATH.'general.php'))
{
	$customConfig = require CRAFT_CONFIG_PATH.'general.php';

	// Is this a multi-environment config?
	if (array_key_exists('*', $customConfig))
	{
		foreach ($customConfig as $env => $envConfig)
		{
			if ($env == '*' || strpos(CRAFT_ENVIRONMENT, $env) !== false)
			{
				// Does this environment have devMode enabled?
				if (isset($envConfig['devMode']) && $envConfig['devMode'] === true)
				{
					error_reporting(E_ALL & ~E_STRICT);
					ini_set('display_errors', 1);
					defined('YII_DEBUG') || define('YII_DEBUG', true);
					defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);
				}
			}
		}
	}
	else
	{
		// No multi-environment config, just check to see if devMode is enabled.
		if (isset($customConfig['devMode']) && $customConfig['devMode'] === true)
		{
			error_reporting(E_ALL & ~E_STRICT);
			ini_set('display_errors', 1);
			defined('YII_DEBUG') || define('YII_DEBUG', true);
			defined('YII_TRACE_LEVEL') || define('YII_TRACE_LEVEL', 3);
		}
	}

}
else
{
	// No general.php on the front end, so devMode is off.
	error_reporting(0);
	ini_set('display_errors', 0);
	defined('YII_DEBUG') || define('YII_DEBUG', false);
}

/**
 * Load Yii, Composer dependencies, and the app
 */

// Load Yii, if it's not already
if (!class_exists('Yii', false))
{
	require CRAFT_APP_PATH.'framework/yii.php';
}

// Guzzle makes use of these PHP constants, but they aren't actually defined in some compilations of PHP
// See http://it.blog.adclick.pt/php/fixing-php-notice-use-of-undefined-constant-curlopt_timeout_ms-assumed-curlopt_timeout_ms/
defined('CURLOPT_TIMEOUT_MS')        || define('CURLOPT_TIMEOUT_MS',        155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load up Composer's files
require CRAFT_APP_PATH.'vendor/autoload.php';

// Disable the PHP include path
Yii::$enableIncludePath = false;

// Load 'em up
require CRAFT_APP_PATH.'Craft.php';
require CRAFT_APP_PATH.'etc/web/WebApp.php';
require CRAFT_APP_PATH.'Info.php';

// Set some aliases for Craft::import()
Yii::setPathOfAlias('app', CRAFT_APP_PATH);
Yii::setPathOfAlias('plugins', CRAFT_PLUGINS_PATH);

// Load the full config
$config = require CRAFT_APP_PATH.'etc/config/main.php';

// Initialize Craft\WebApp this way so it doesn't cause a syntax error on PHP < 5.3
$appClass = '\Craft\WebApp';
$app = new $appClass($config);

$app->run();
