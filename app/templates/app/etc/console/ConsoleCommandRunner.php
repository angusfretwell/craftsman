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
class ConsoleCommandRunner extends \CConsoleCommandRunner
{
	/**
	 * @param string $name command name (case-insensitive)
	 * @return \CConsoleCommand the command object. Null if the name is invalid.
	 */
	public function createCommand($name)
	{
		$name = StringHelper::toLowerCase($name);

		$command = null;

		if (isset($this->commands[$name]))
		{
			$command = $this->commands[$name];
		}
		else
		{
			$commands = array_change_key_case($this->commands);

			if (isset($commands[$name]))
			{
				$command = $commands[$name];
			}
		}

		if ($command !== null)
		{
			if (is_string($command))  // class file path or alias
			{
				if (strpos($command, '/') !== false || strpos($command, '\\') !== false)
				{
					$className = IOHelper::getFileName($command, false);

					// If it's a default framework command, don't namespace it.
					if (strpos($command, 'framework') === false)
					{
						$className = __NAMESPACE__.'\\'.$className;
					}

					if (!class_exists($className, false))
					{
						require_once($command);
					}
				}
				else // an alias
				{
					$className = Craft::import($command);
				}

				return new $className($name, $this);
			}
			else // an array configuration
			{
				return Craft::createComponent($command, $name, $this);
			}
		}
		else if ($name === 'help')
		{
			return new \CHelpCommand('help', $this);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Adds commands from the specified command path.
	 * If a command already exists, the new one will overwrite it.
	 *
	 * @param string $path the alias of the folder containing the command class files.
	 */
	public function addCommands($path)
	{
		if (($commands=$this->findCommands($path))!==array())
		{
			foreach($commands as $name=>$file)
			{
				$this->commands[$name]=$file;
			}
		}
	}
}
