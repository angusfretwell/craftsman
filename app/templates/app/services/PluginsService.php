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
class PluginsService extends BaseApplicationComponent
{
	/**
	 * @var array The type of components plugins can have. Defined in app/etc/config/common.php.
	 */
	public $autoloadClasses;

	/**
	 * Stores whether plugins have been loaded yet for this request.
	 *
	 * @access private
	 * @var bool
	 */
	private $_pluginsLoaded = false;

	/**
	 * Stores whether plugins are in the middle of being loaded.
	 *
	 * @access private
	 * @var bool
	 */
	private $_loadingPlugins = false;

	/**
	 * Stores all plugins, whether installed or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_plugins = array();

	/**
	 * Stores all enabled plugins.
	 *
	 * @access private
	 * @var array
	 */
	private $_enabledPlugins = array();

	/**
	 * Stores all plugins in the system, regardless of whether they're installed/enabled or not.
	 *
	 * @access private
	 * @var array
	 */
	private $_allPlugins;

	/**
	 * Holds a list of all of the enabled plugin info indexed by the plugin class name.
	 *
	 * @access private
	 * @var array
	 */
	private $_enabledPluginInfo = array();

	/**
	 * Returns whether plugins have been loaded yet for this request.
	 *
	 * @return bool
	 */
	public function arePluginsLoaded()
	{
		return $this->_pluginsLoaded;
	}

	/**
	 * Loads the enabled plugins.
	 */
	public function loadPlugins()
	{
		if (!$this->_pluginsLoaded && !$this->_loadingPlugins)
		{
			if (craft()->isInstalled())
			{
				// Prevent this function from getting called twice.
				$this->_loadingPlugins = true;

				// Find all of the enabled plugins
				$rows = craft()->db->createCommand()
					->select('id, class, version, settings, installDate')
					->from('plugins')
					->where('enabled=1')
					->queryAll();

				$names = array();

				foreach ($rows as $row)
				{
					$plugin = $this->_getPlugin($row['class']);

					if ($plugin)
					{
						// Clean it up a bit
						$row['settings'] = JsonHelper::decode($row['settings']);
						$row['installDate'] = DateTime::createFromString($row['installDate']);

						$this->_enabledPluginInfo[$row['class']] = $row;

						$lcPluginHandle = mb_strtolower($plugin->getClassHandle());
						$this->_plugins[$lcPluginHandle] = $plugin;
						$this->_enabledPlugins[$lcPluginHandle] = $plugin;
						$names[] = $plugin->getName();

						$plugin->setSettings($row['settings']);

						$plugin->isInstalled = true;
						$plugin->isEnabled = true;

						$this->_autoloadPluginClasses($plugin);
					}
				}

				// Sort plugins by name
				array_multisort($names, $this->_enabledPlugins);

				// Now that all of the components have been imported,
				// initialize all the plugins
				foreach ($this->_enabledPlugins as $plugin)
				{
					$plugin->init();
				}

				$this->_loadingPlugins = false;
			}

			$this->_pluginsLoaded = true;

			// Fire an 'onLoadPlugins' event
			$this->onLoadPlugins(new Event($this));
		}
	}

	/**
	 * Returns a plugin.
	 *
	 * @param string $handle
	 * @param bool   $enabledOnly
	 * @return BasePlugin|null
	 */
	public function getPlugin($handle, $enabledOnly = true)
	{
		$lcPluginHandle = mb_strtolower($handle);

		if ($enabledOnly)
		{
			if (isset($this->_enabledPlugins[$lcPluginHandle]))
			{
				return $this->_enabledPlugins[$lcPluginHandle];
			}
			else
			{
				return null;
			}
		}
		else
		{
			if (!array_key_exists($lcPluginHandle, $this->_plugins))
			{
				// Make sure $handle has the right casing
				$handle = $this->_getPluginHandleFromFileSystem($handle);

				$plugin = $this->_getPlugin($handle);

				if ($plugin)
				{
					// Is it installed (but disabled)?
					$plugin->isInstalled = (bool) craft()->db->createCommand()
						->select('count(id)')
						->from('plugins')
						->where(array('class' => $plugin->getClassHandle()))
						->queryScalar();
				}

				$this->_plugins[$lcPluginHandle] = $plugin;
			}

			return $this->_plugins[$lcPluginHandle];
		}
	}

	/**
	 * Returns all plugins, whether they're installed or not.
	 *
	 * @param bool $enabledOnly
	 * @return array
	 */
	public function getPlugins($enabledOnly = true)
	{
		if ($enabledOnly)
		{
			return $this->_enabledPlugins;
		}
		else
		{
			if (!isset($this->_allPlugins))
			{
				$this->_allPlugins = array();

				// Find all of the plugins in the plugins folder
				$pluginsPath = craft()->path->getPluginsPath();
				$pluginFolderContents = IOHelper::getFolderContents($pluginsPath, false);

				if ($pluginFolderContents)
				{
					foreach ($pluginFolderContents as $pluginFolderContent)
					{
						// Make sure it's actually a folder.
						if (IOHelper::folderExists($pluginFolderContent))
						{
							$pluginFolderContent = IOHelper::normalizePathSeparators($pluginFolderContent);
							$pluginFolderName = mb_strtolower(IOHelper::getFolderName($pluginFolderContent, false));
							$pluginFilePath = IOHelper::getFolderContents($pluginFolderContent, false, ".*Plugin\.php");

							if (is_array($pluginFilePath) && count($pluginFilePath) > 0)
							{
								$pluginFileName = IOHelper::getFileName($pluginFilePath[0], false);

								// Chop off the "Plugin" suffix
								$handle = mb_substr($pluginFileName, 0, mb_strlen($pluginFileName) - 6);

								if (mb_strtolower($handle) === mb_strtolower($pluginFolderName))
								{
									$plugin = $this->getPlugin($handle, false);

									if ($plugin)
									{
										$this->_allPlugins[mb_strtolower($handle)] = $plugin;
										$names[] = $plugin->getName();
									}
								}
							}
						}
					}

					if (!empty($names))
					{
						// Sort plugins by name
						array_multisort($names, $this->_allPlugins);
					}
				}
			}

			return $this->_allPlugins;
		}
	}

	/**
	 * Enables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function enablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” can’t be enabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 1),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = true;
		$this->_enabledPlugins[$lcPluginHandle] = $plugin;

		return true;
	}

	/**
	 * Disables a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function disablePlugin($handle)
	{
		$plugin = $this->getPlugin($handle);
		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” can’t be disabled because it isn’t installed yet.', array('plugin' => $plugin->getName())));
		}

		craft()->db->createCommand()->update('plugins',
			array('enabled' => 0),
			array('class' => $plugin->getClassHandle())
		);

		$plugin->isEnabled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);

		return true;
	}

	/**
	 * Installs a plugin.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function installPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if ($plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” is already installed.', array('plugin' => $plugin->getName())));
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Add the plugins as a record to the database.
			craft()->db->createCommand()->insert('plugins', array(
				'class'       => $plugin->getClassHandle(),
				'version'     => $plugin->version,
				'enabled'     => true,
				'installDate' => DateTimeHelper::currentTimeForDb(),
			));

			$plugin->isInstalled = true;
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;

			$this->_savePluginMigrations(craft()->db->getLastInsertID(), $plugin->getClassHandle());
			$this->_autoloadPluginClasses($plugin);
			$plugin->createTables();

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		$plugin->onAfterInstall();

		return true;
	}

	/**
	 * Uninstalls a plugin by removing it's record from the database, deleting it's tables and foreign keys and running the plugin's uninstall method if it exists.
	 *
	 * @param $handle
	 * @throws Exception
	 * @throws \Exception
	 * @return bool
	 */
	public function uninstallPlugin($handle)
	{
		$plugin = $this->getPlugin($handle, false);
		$lcPluginHandle = mb_strtolower($plugin->getClassHandle());

		if (!$plugin)
		{
			$this->_noPluginExists($handle);
		}

		if (!$plugin->isInstalled)
		{
			throw new Exception(Craft::t('“{plugin}” is already uninstalled.', array('plugin' => $plugin->getName())));
		}

		if (!$plugin->isEnabled)
		{
			// Pretend that the plugin is enabled just for this request
			$plugin->isEnabled = true;
			$this->_enabledPlugins[$lcPluginHandle] = $plugin;
			$this->_autoloadPluginClasses($plugin);

			$pluginRow = craft()->db->createCommand()
				->select('id')
				->from('plugins')
				->where('class=:class', array('class' => $plugin->getClassHandle()))
				->queryRow();

			$pluginId = $pluginRow['id'];
		}
		else
		{
			$pluginId = $this->_enabledPluginInfo[$handle]['id'];
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$plugin->onBeforeUninstall();

			// If the plugin has any element types, delete their elements
			$elementTypeInfo = craft()->components->types['element'];
			$elementTypeClasses = $this->getPluginClasses($plugin, $elementTypeInfo['subfolder'], $elementTypeInfo['suffix']);

			foreach ($elementTypeClasses as $class)
			{
				$elementType = craft()->components->initializeComponent($class, $elementTypeInfo['instanceof']);

				if ($elementType)
				{
					craft()->elements->deleteElementsByType($elementType->getClassHandle());
				}
			}

			// Drop any tables created by the plugin's records
			$plugin->dropTables();

			// Remove the row from the database.
			craft()->db->createCommand()->delete('plugins', array('class' => $handle));

			// Remove any migrations.
			craft()->db->createCommand()->delete('migrations', array('pluginId' => $pluginId));

			if ($transaction !== null)
			{
				// Let's commit to this.
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		$plugin->isEnabled = false;
		$plugin->isInstalled = false;
		unset($this->_enabledPlugins[$lcPluginHandle]);
		unset($this->_plugins[$lcPluginHandle]);
		unset($this->_enabledPluginInfo[$handle]);

		return true;
	}

	/**
	 * Saves a plugin's settings.
	 *
	 * @param BasePlugin $plugin
	 * @param mixed $settings
	 * @return bool
	 */
	public function savePluginSettings(BasePlugin $plugin, $settings)
	{
		// Give the plugin a chance to modify the settings
		$settings = $plugin->prepSettings($settings);
		$settings = JsonHelper::encode($settings);

		$affectedRows = craft()->db->createCommand()->update('plugins', array(
			'settings' => $settings
		), array(
			'class' => $plugin->getClassHandle()
		));

		return (bool) $affectedRows;
	}

	/**
	 * Calls a method on all plugins that have the method.
	 *
	 * @param string $method
	 * @param array $args
	 * @return array
	 */
	public function call($method, $args = array())
	{
		$result = array();
		$altMethod = 'hook'.ucfirst($method);

		foreach ($this->getPlugins() as $plugin)
		{
			if (method_exists($plugin, $method))
			{
				$result[$plugin->getClassHandle()] = call_user_func_array(array($plugin, $method), $args);
			}
			else if (method_exists($plugin, $altMethod))
			{
				craft()->deprecator->log('PluginsService::method_hook_prefix', 'The “hook” prefix on the '.get_class($plugin).'::'.$altMethod.'() method name has been deprecated. It should be renamed to '.$method.'().');
				$result[$plugin->getClassHandle()] = call_user_func_array(array($plugin, $altMethod), $args);
			}
		}

		return $result;
	}

	/**
	 * Provides legacy support for craft()->plugins->callHook().
	 *
	 * @param string $method
	 * @param array $args
	 * @return array
	 * @deprecated Deprecated in 1.0.
	 */
	public function callHook($method, $args = array())
	{
		craft()->deprecator->log('PluginsService::callHook()', 'PluginsService::callHook() has been deprecated. Use call() instead.');
		return $this->call($method, $args);
	}

	/**
	 * Returns whether the given plugin's local version number is greater than the record we have in the database.
	 *
	 * @param BasePlugin $plugin
	 * @return bool
	 */
	public function doesPluginRequireDatabaseUpdate(BasePlugin $plugin)
	{
		$storedPluginInfo = $this->getPluginInfo($plugin);

		if ($storedPluginInfo)
		{
			if (version_compare($plugin->getVersion(), $storedPluginInfo['version'], '>'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns an array of the stored info for a given plugin.
	 *
	 * @param BasePlugin $plugin
	 * @return array|null
	 */
	public function getPluginInfo(BasePlugin $plugin)
	{
		if (isset($this->_enabledPluginInfo[$plugin->getClassHandle()]))
		{
			return $this->_enabledPluginInfo[$plugin->getClassHandle()];
		}
	}

	// Events

	/**
	 * Fires an 'onLoadPlugins' event.
	 *
	 * @param Event $event
	 */
	public function onLoadPlugins(Event $event)
	{
		$this->raiseEvent('onLoadPlugins', $event);
	}

	/**
	 * Returns an array of class names found in a given plugin folder.
	 *
	 * @param BasePlugin $plugin
	 * @param string     $classSubfolder
	 * @param string     $classSuffix
	 * @param bool       $autoload
	 * @return array
	 */
	public function getPluginClasses(BasePlugin $plugin, $classSubfolder, $classSuffix, $autoload = true)
	{
		$classes = array();

		$pluginHandle = $plugin->getClassHandle();
		$pluginFolder = mb_strtolower($plugin->getClassHandle());
		$pluginFolderPath = craft()->path->getPluginsPath().$pluginFolder.'/';
		$classSubfolderPath = $pluginFolderPath.$classSubfolder.'/';

		if (IOHelper::folderExists($classSubfolderPath))
		{
			// See if it has any files in ClassName*Suffix.php format.
			$filter = $pluginHandle.'(_.+)?'.$classSuffix.'\.php$';
			$files = IOHelper::getFolderContents($classSubfolderPath, false, $filter);

			if ($files)
			{
				foreach ($files as $file)
				{
					$class = IOHelper::getFileName($file, false);
					$classes[] = $class;

					if ($autoload)
					{
						Craft::import("plugins.{$pluginFolder}.{$classSubfolder}.{$class}");
					}
				}
			}
		}

		return $classes;
	}

	/**
	 * Returns whether a plugin class exists.
	 *
	 * @param BasePlugin $plugin
	 * @param string     $classSubfolder
	 * @param string     $class
	 * @param bool       $autoload
	 * @return bool
	 */
	public function doesPluginClassExist(BasePlugin $plugin, $classSubfolder, $class, $autoload = true)
	{
		$pluginHandle = $plugin->getClassHandle();
		$pluginFolder = mb_strtolower($plugin->getClassHandle());
		$classPath = craft()->path->getPluginsPath().$pluginFolder.'/'.$classSubfolder.'/'.$class.'.php';

		if (IOHelper::fileExists($classPath))
		{
			if ($autoload)
			{
				Craft::import("plugins.{$pluginFolder}.{$classSubfolder}.{$class}");
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	// Private Methods

	/**
	 * Throws a "no plugin exists" exception.
	 *
	 * @access private
	 * @param string $handle
	 * @throws Exception
	 */
	private function _noPluginExists($handle)
	{
		throw new Exception(Craft::t('No plugin exists with the class “{class}”', array('class' => $handle)));
	}

	/**
	 * Finds and imports all of the autoloadable classes for a given plugin.
	 *
	 * @access private
	 * @param BasePlugin $plugin
	 */
	private function _autoloadPluginClasses(BasePlugin $plugin)
	{
		foreach ($this->autoloadClasses as $classSuffix)
		{
			// *Controller's live in controllers/, etc.
			$classSubfolder = mb_strtolower($classSuffix).'s';
			$classes = $this->getPluginClasses($plugin, $classSubfolder, $classSuffix, true);

			if ($classSuffix == 'Service')
			{
				$this->_registerPluginServices($classes);
			}
		}
	}

	/**
	 * If the plugin already had a migrations folder with migrations in it, let's save them in the db.
	 *
	 * @param $pluginId
	 * @param $pluginHandle
	 * @throws Exception
	 */
	private function _savePluginMigrations($pluginId, $pluginHandle)
	{
		$migrationsFolder = craft()->path->getPluginsPath().mb_strtolower($pluginHandle).'/migrations/';

		if (IOHelper::folderExists($migrationsFolder))
		{
			$migrations = array();
			$migrationFiles = IOHelper::getFolderContents($migrationsFolder, false, "(m(\d{6}_\d{6})_.*?)\.php");

			if ($migrationFiles)
			{
				foreach ($migrationFiles as $file)
				{
					if (IOHelper::fileExists($file))
					{
						$migration = new MigrationRecord();
						$migration->version = IOHelper::getFileName($file, false);
						$migration->applyTime = DateTimeHelper::currentUTCDateTime();
						$migration->pluginId = $pluginId;

						$migrations[] = $migration;
					}
				}

				foreach ($migrations as $migration)
				{
					if (!$migration->save())
					{
						throw new Exception(Craft::t('There was a problem saving to the migrations table: ').$this->_getFlattenedErrors($migration->getErrors()));
					}
				}
			}
		}
	}

	/**
	 * Registers any services provided by a plugin.
	 *
	 * @access private
	 * @param array $classes
	 * @throws Exception
	 * @return void
	 */
	private function _registerPluginServices($classes)
	{
		$services = array();

		foreach ($classes as $class)
		{
			$parts = explode('_', $class);

			foreach ($parts as $index => $part)
			{
				$parts[$index] = lcfirst($part);
			}

			$serviceName = implode('_', $parts);
			$serviceName = mb_substr($serviceName, 0, - mb_strlen('Service'));

			if (!craft()->getComponent($serviceName, false))
			{
				// Register the component with the handle as (className or className_*) minus the "Service" suffix
				$nsClass = __NAMESPACE__.'\\'.$class;
				$services[$serviceName] = array('class' => $nsClass);
			}
			else
			{
				throw new Exception(Craft::t('The plugin “{handle}” tried to register a service “{service}” that conflicts with a core service name.', array('handle' => $handle, 'service' => $serviceName)));
			}
		}

		craft()->setComponents($services, false);
	}

	/**
	 * Returns a new plugin instance based on its class handle.
	 *
	 * @param $handle
	 * @return BasePlugin|null
	 */
	private function _getPlugin($handle)
	{
		// Get the full class name
		$class = $handle.'Plugin';
		$nsClass = __NAMESPACE__.'\\'.$class;

		// Skip the autoloader
		if (!class_exists($nsClass, false))
		{
			$path = craft()->path->getPluginsPath().mb_strtolower($handle).'/'.$class.'.php';

			if (($path = IOHelper::fileExists($path, false)) !== false)
			{
				require_once $path;
			}
			else
			{
				return null;
			}
		}

		if (!class_exists($nsClass, false))
		{
			return null;
		}

		$plugin = new $nsClass;

		// Make sure the plugin implements the BasePlugin abstract class
		if (!$plugin instanceof BasePlugin)
		{
			return null;
		}

		return $plugin;
	}

	/**
	 * Returns the actual plugin class handle based on a case-insensitive handle.
	 *
	 * @param $iHandle
	 * @return bool|string
	 */
	private function _getPluginHandleFromFileSystem($iHandle)
	{
		$pluginsPath = craft()->path->getPluginsPath();
		$fullPath = $pluginsPath.mb_strtolower($iHandle).'/'.$iHandle.'Plugin.php';

		if (($file = IOHelper::fileExists($fullPath, true)) !== false)
		{
			$file = IOHelper::getFileName($file, false);
			return mb_substr($file, 0, mb_strlen($file) - mb_strlen('Plugin'));
		}

		return false;
	}

	/**
	 * Get a flattened list of model errors
	 *
	 * @access private
	 * @param array $errors
	 * @return string
	 */
	private function _getFlattenedErrors($errors)
	{
		$return = '';

		foreach ($errors as $attribute => $attributeErrors)
		{
			$return .= "\n - ".implode("\n - ", $attributeErrors);
		}

		return $return;
	}
}
