<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 */

/**
 *
 */
class LoggingHelper
{
	/**
	 * @static
	 *
	 * @param      $level
	 * @param      $time
	 * @param      $category
	 * @param      $content
	 * @param null $groupName
	 * @param bool $forced
	 */
	public static function processFireBugLogEntry($level, $time, $category, $content, $groupName = null, $forced = false)
	{
		$time = date('H:i:s.', $time).sprintf('%03d', (int)(($time - (int)$time) * 1000));

		if ($level === LogLevel::Warning)
		{
			$func = 'warn';
		}
		else if ($level === LogLevel::Error)
		{
			$func = 'error';
		}
		else
		{
			$func = 'log';
		}

		if ($groupName !== null)
		{
			echo "\tconsole.groupCollapsed(\"{$groupName}\");\n";
		}

		$content = \CJavaScript::quote("[$time][$level][$category]".($forced ? "[Forced]" : "")."\n$content");
		echo "\tconsole.{$func}(\"{$content}\");\n";

		if ($groupName !== null)
		{
			echo "\tconsole.groupEnd();\n";
		}
	}

	/**
	 * Will attempt to remove passwords from the log file.
	 *
	 * @param $log
	 * @return mixed
	 */
	public static function redact($log)
	{
		// Will match 'password => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'password' => (')(.*)('),/uim", "'password' => REDACTED,", $log);

		// Will match 'newPassword => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'newPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

		// Will match 'smtpPassword => 'secretPassword', which gets logged in the POST params during debug mode.
		$log = preg_replace("/'smtpPassword' => (')(.*)('),/uim", "'newPassword' => REDACTED,", $log);

		return $log;
	}
}
