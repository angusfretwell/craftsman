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
 * Handles utility related tasks.
 */
class UtilsController extends BaseController
{
	/**
	 *
	 */
	public function init()
	{
		// Only admins.
		craft()->userSession->requireAdmin();
	}

	/**
	 * Server info
	 */
	public function actionServerInfo()
	{
		// Run the requirements checker
		$reqCheck = new RequirementsChecker();
		$reqCheck->run();

		$this->renderTemplate('utils/serverinfo', array(
			'requirements' => $reqCheck->getRequirements(),
		));
	}

	/**
	 * PHP info
	 */
	public function actionPhpInfo()
	{
		craft()->config->maxPowerCaptain();

		ob_start();
		phpinfo(-1);
		$phpInfo = ob_get_clean();

		$phpInfo = preg_replace(
			array(
				'#^.*<body>(.*)</body>.*$#ms',
				'#<h2>PHP License</h2>.*$#ms',
				'#<h1>Configuration</h1>#',
				"#\r?\n#",
				"#</(h1|h2|h3|tr)>#",
				'# +<#',
				"#[ \t]+#",
				'#&nbsp;#',
				'#  +#',
				'# class=".*?"#',
				'%&#039;%',
				'#<tr>(?:.*?)"src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
				'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
				'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
				"# +#",
				'#<tr>#',
				'#</tr>#'
			),
			array(
				'$1',
				'',
				'',
				'',
				'</$1>'."\n",
				'<',
				' ',
				' ',
				' ',
				'',
				' ',
				'<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
				'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
				'<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".'<tr><td>Zend Egg</td><td>$1</td></tr>',
				' ',
				'%S%',
				'%E%'
			),
			$phpInfo
		);

		$sections = explode('<h2>', strip_tags($phpInfo, '<h2><th><td>'));
		unset($sections[0]);

		$phpInfo = array();
		foreach($sections as $section)
		{
			$heading = substr($section, 0, strpos($section, '</h2>'));

			preg_match_all(
				'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
				$section,
				$parts,
				PREG_SET_ORDER
			);

			foreach($parts as $row)
			{
				if (!isset($row[2]))
				{
					continue;
				}
				else if ((!isset($row[3]) || $row[2] == $row[3]))
				{
					$value = $row[2];
				}
				else
				{
					$value = array_slice($row, 2);
				}

				$phpInfo[$heading][$row[1]] = $value;
			}
		}

		$this->renderTemplate('utils/phpinfo', array(
			'phpInfo' => $phpInfo
		));
	}

	/**
	 * Logs
	 */
	public function actionLogs(array $variables = array())
	{
		craft()->config->maxPowerCaptain();

		if (IOHelper::folderExists(craft()->path->getLogPath()))
		{
			$dateTimePattern = '/^[0-9]{4}\/[0-9]{2}\/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/';

			$logFileNames = array();

			// Grab it all.
			$logFolderContents = IOHelper::getFolderContents(craft()->path->getLogPath());

			foreach ($logFolderContents as $logFolderContent)
			{
				// Make sure it's a file.`
				if (IOHelper::fileExists($logFolderContent) && (strpos($logFolderContent, 'craft.log') !== false || strpos($logFolderContent, 'phperrors') !== false))
				{
					$logFileNames[] = IOHelper::getFileName($logFolderContent);
				}
			}

			$logEntries = array();
			$currentLogFileName = isset($variables['currentLogFileName']) ? $variables['currentLogFileName'] : 'craft.log';

			$currentFullPath = craft()->path->getLogPath().$currentLogFileName;
			if (IOHelper::fileExists($currentFullPath))
			{
				// Different parsing logic for phperrors.log
				if ($currentLogFileName !== 'phperrors.log')
				{
					$contents = IOHelper::getFileContents(craft()->path->getLogPath().$currentLogFileName);

					// Split on the new log entry line.
					$contents = preg_split('/(\*){102}/', $contents);

					foreach ($contents as $rowChunk)
					{
						$logEntryModel = new LogEntryModel();

						$rowChunk = trim($rowChunk);
						// Split on the newlines
						$rowContents = preg_split("/\n/", $rowChunk);

						// Grab the date and time
						$logEntryModel->dateTime = rtrim(trim(mb_substr($rowContents[0], 0, 19)), ',');

						// Grab the level
						$rowContents[0] = mb_substr($rowContents[0], 21);
						$stop = mb_strpos($rowContents[0], ']');
						$logEntryModel->level = rtrim(trim(mb_substr($rowContents[0], 0, $stop)), ',');

						// Grab the category.
						$rowContents[0] = mb_substr($rowContents[0], $stop + 3);
						$stop = mb_strpos($rowContents[0], ']');
						$logEntryModel->category = rtrim(trim(mb_substr($rowContents[0], 0, $stop)), ',');

						// Find a few new markers
						$rowContents[0] = mb_substr($rowContents[0], $stop + 2);
						$cookieStart = array_search('$_COOKIE=array (', $rowContents);
						$sessionStart = array_search('$_SESSION=array (', $rowContents);
						$serverStart = array_search('$_SERVER=array (', $rowContents);
						$postStart = array_search('$_POST=array (', $rowContents);

						// If we found any of these, we know this is a devMode log.
						if ($cookieStart || $sessionStart || $serverStart || $postStart)
						{
							$cookieStart = $cookieStart ? $cookieStart + 1 : $cookieStart;
							$sessionStart = $sessionStart ? $sessionStart + 1 : $sessionStart;
							$serverStart = $serverStart ? $serverStart + 1 : $serverStart;
							$postStart = $postStart ? $postStart + 1 : $postStart;


							if (!$postStart)
							{
								if (!$cookieStart)
								{
									if (!$sessionStart)
									{
										$start = $serverStart;
									}
									else
									{
										$start = $sessionStart;
									}
								}
								else
								{
									$start = $cookieStart;
								}
							}
							else
							{
								$start = $postStart;
							}

							// Check to see if it's GET or POST
							if (mb_substr($rowContents[0], 0, 5) == '$_GET')
							{
								// Grab GET
								$logEntryModel->get = $this->_cleanUpArray(array_slice($rowContents, 1, $start - 4));
							}

							if (mb_substr($rowContents[0], 0, 6) == '$_POST')
							{
								// Grab POST
								$logEntryModel->post = $this->_cleanUpArray(array_slice($rowContents, 1, $start - 4));
							}

							// We need to do a little more work to find out what element profiling info starts on.
							$tempArray = array_slice($rowContents, $serverStart, null, true);

							$profileStart = false;
							foreach ($tempArray as $key => $tempArrayRow)
							{
								if (preg_match($dateTimePattern, $tempArrayRow))
								{
									$profileStart = $key;
									break;
								}
							}

							// Grab the cookie, session and server sections.
							if ($cookieStart)
							{
								if (!$sessionStart)
								{
									$start = $serverStart;
								}
								else
								{
									$start = $sessionStart;
								}

								$logEntryModel->cookie = $this->_cleanUpArray(array_slice($rowContents, $cookieStart, $start - $cookieStart - 3));
							}

							if ($sessionStart)
							{
								$logEntryModel->session = $this->_cleanUpArray(array_slice($rowContents, $sessionStart, $serverStart - $sessionStart - 3));
							}

							$logEntryModel->server = $this->_cleanUpArray(array_slice($rowContents, $serverStart, $profileStart - $serverStart - 1));

							// We can't just grab the profile info, we need to do some extra processing on it.
							$tempProfile = array_slice($rowContents, $profileStart);

							$profile = array();
							$profileArr = array();
							foreach ($tempProfile as $tempProfileRow)
							{
								if (preg_match($dateTimePattern, $tempProfileRow))
								{
									if (!empty($profileArr))
									{
										$profile[] = $profileArr;
										$profileArr = array();
									}
								}

								$profileArr[] = rtrim(trim($tempProfileRow), ',');
							}

							// Grab the last one.
							$profile[] = $profileArr;

							// Finally save the profile.
							$logEntryModel->profile = $profile;
						}
						else
						{
							// This is a non-devMode log entry.
							$logEntryModel->message = $rowContents[0];
						}

						// And save the log entry.
						$logEntries[] = $logEntryModel;
					}

					// Because I'm lazy.
					array_pop($logEntries);
				}
				else
				{
					$logEntry = new LogEntryModel();
					$contents = IOHelper::getFileContents(craft()->path->getLogPath().$currentLogFileName);
					$contents = str_replace("\n", "<br />", $contents);
					$logEntry->message = $contents;

					$logEntries[] = $logEntry;
				}
			}

			// Because ascending order is stupid.
			$logEntries = array_reverse($logEntries);

			$this->renderTemplate('utils/logs', array(
				'logEntries'         => $logEntries,
				'logFileNames'       => $logFileNames,
				'currentLogFileName' => $currentLogFileName
			));
		}
	}

	/**
	 * Deprecation Errors
	 */
	public function actionDeprecationErrors()
	{
		craft()->templates->includeCssResource('css/deprecator.css');
		craft()->templates->includeJsResource('js/deprecator.js');

		$this->renderTemplate('utils/deprecationerrors', array(
			'logs' => craft()->deprecator->getLogs()
		));
	}

	/**
	 * View stack trace for a deprecator log entry.
	 */
	public function actionGetDeprecationErrorTracesModal()
	{
		$this->requireAjaxRequest();

		$logId = craft()->request->getRequiredParam('logId');
		$log = craft()->deprecator->getLogById($logId);

		return $this->renderTemplate('utils/deprecationerrors/_tracesmodal',
			array('log' => $log)
		);
	}

	/**
	 * Deletes all deprecation errors.
	 */
	public function actionDeleteAllDeprecationErrors()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		craft()->deprecator->deleteAllLogs();
		craft()->end();
	}

	/**
	 * Deletes a deprecation error.
	 */
	public function actionDeleteDeprecationError()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$logId = craft()->request->getRequiredPost('logId');

		craft()->deprecator->deleteLogById($logId);
		craft()->end();
	}

	/**
	 * @param $arrayToClean
	 * @return array
	 */
	private function _cleanUpArray($arrayToClean)
	{
		$arrayToClean = implode(' ', $arrayToClean);
		$arrayToClean = '$arrayToClean = array('.str_replace('REDACTED', '"REDACTED"', $arrayToClean).');';
		eval($arrayToClean);

		foreach ($arrayToClean as $key => $item)
		{
			$arrayToClean[$key] = var_export($item, true);
		}

		return $arrayToClean;
	}

	/**
	 * @param  $backTrace
	 * @return string
	 */
	private function _formatStackTrace($backTrace)
	{
		$return = array();

		foreach ($backTrace as $step => $call)
		{
			$object = '';

			if (isset($call['class']))
			{
				$object = $call['class'];

				if (is_array($call['args']))
				{
					if (count($call['args']) > 0)
					{
						foreach ($call['args'] as &$arg)
						{
							$this->_getArg($arg);
						}
					}
					else
					{
						$call['args'] = array('array()');
					}
				}
			}

			$str = '<b>#'.str_pad($step, 3, ' ').'</b>';
			$str .= ($object !== '' ? $object.'->' : '');
			$str .= $call['method'].'('.implode(', ', $call['args']).') called at “'.$call['file'].'” line '.$call['line'];

			$return[] = $str;
		}

		return implode('<br /><br />', $return);
	}

	/**
	 * @param $arg
	 */
	private function _getArg(&$arg)
	{
		if (is_object($arg) || is_array($arg))
		{
			$arr = (array)$arg;
			$args = array();

			foreach ($arr as $key => $value)
			{
				if (strpos($key, chr(0)) !== false)
				{
					// Private variable found.
					$key = '';
				}

				if (is_array($value))
				{
					$args[] = '['.$key.'] => '.$this->_getArg($value);
				}
				else
				{
					$args[] = '['.$key.'] => '.(string)$value;
				}


			}

			if (is_object($arg))
			{
				$arg = get_class($arg) . ' Object ('.implode(',', $args).')';
			}
			else if (is_array($arg) && count($arg) == 0)
			{
				$arg = 'array()';
			}
			else if (is_array($arg) && count($arg) > 0)
			{
				$arg = 'array('.implode(',', $args).')';
			}
		}
		else if (is_array($arg) && count($arg) == 0)
		{
			$arg = '';
		}
	}
}
