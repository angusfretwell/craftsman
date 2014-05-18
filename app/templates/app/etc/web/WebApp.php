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
 * @property AssetIndexingService        $assetIndexing        The assets indexing service
 * @property AssetSourcesService         $assetSources         The assets sources service
 * @property AssetsService               $assets               The assets service
 * @property AssetTransformsService      $assetTransforms      The assets sizes service
 * @property CacheService                $cache                The cache service
 * @property CategoriesService           $categories           The categories service
 * @property ComponentsService           $components           The components service
 * @property ConfigService               $config               The config service
 * @property ContentService              $content              The content service
 * @property DashboardService            $dashboard            The dashboard service
 * @property DbConnection                $db                   The database
 * @property DeprecatorService           $deprecator           The deprecator service
 * @property ElementsService             $elements             The elements service
 * @property EmailMessagesService        $emailMessages        The email messages service
 * @property EmailService                $email                The email service
 * @property EntriesService              $entries              The entries service
 * @property EntryRevisionsService       $entryRevisions       The entry revisions service
 * @property EtService                   $et                   The E.T. service
 * @property FeedsService                $feeds                The feeds service
 * @property FieldsService               $fields               The fields service
 * @property FileCache                   $fileCache            File caching
 * @property GlobalsService              $globals              The globals service
 * @property HttpRequestService          $request              The request service
 * @property HttpSessionService          $httpSession          The HTTP session service
 * @property ImagesService               $images               The images service
 * @property InstallService              $install              The images service
 * @property LocalizationService         $localization         The localization service
 * @property MatrixService               $matrix               The matrix service
 * @property MigrationsService           $migrations           The migrations service
 * @property PathService                 $path                 The path service
 * @property PluginsService              $plugins              The plugins service
 * @property RelationsService            $relations            The relations service
 * @property ResourcesService            $resources            The resources service
 * @property RoutesService               $routes               The routes service
 * @property SearchService               $search               The search service
 * @property SectionsService             $sections             The sections service
 * @property SecurityService             $security             The security service
 * @property StructuresService           $structures           The structures service
 * @property SystemSettingsService       $systemSettings       The system settings service
 * @property TagsService                 $tags                 The tags service
 * @property TasksService                $tasks                The tasks service
 * @property TemplateCacheService        $templateCache        The template cache service
 * @property TemplatesService            $templates            The template service
 * @property UpdatesService              $updates              The updates service
 * @property UserGroupsService           $userGroups           The user groups service
 * @property UserPermissionsService      $userPermissions      The user permission service
 * @property UserSessionService          $userSession          The user session service
 * @property UsersService                $users                The users service
 */
class WebApp extends \CWebApplication
{
	/**
	 * @var string The language that the application is written in. This mainly refers to
	 * the language that the messages and view files are in.
	 *
	 * Setting it here even though CApplication already defaults to 'en_us',
	 * so it's clear and in case they change it down the road.
	 */
	public $sourceLanguage = 'en_us';

	/**
	 * @var array List of built-in component aliases to be imported.
	 */
	public $componentAliases;

	private $_language;
	private $_templatePath;
	private $_editionComponents;
	private $_pendingEvents;
	private $_gettingLanguage = false;

	/**
	 * Processes resource requests before anything else has a chance to initialize.
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		// Import all the built-in components
		foreach ($this->componentAliases as $alias)
		{
			Craft::import($alias);
		}

		// So we can try to translate Yii framework strings
		craft()->coreMessages->attachEventHandler('onMissingTranslation', array('Craft\LocalizationHelper', 'findMissingTranslation'));

		// Initialize HttpRequestService and LogRouter right away
		$this->getComponent('request');
		$this->getComponent('log');

		// Attach our Craft app behavior.
		$this->attachBehavior('AppBehavior', new AppBehavior());

		// Set our own custom runtime path.
		$this->setRuntimePath($this->path->getRuntimePath());

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		// If we're not in devMode, we're going to remove some logging routes.
		if (!$this->config->get('devMode'))
		{
			$this->log->removeRoute('WebLogRoute');
			$this->log->removeRoute('ProfileLogRoute');
		}

		parent::init();
	}

	/**
	 * Processes the request.
	 *
	 * @throws HttpException
	 */
	public function processRequest()
	{
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// Validate some basics on the database configuration file.
		craft()->validateDbConfigFile();

		// Process install requests
		$this->_processInstallRequest();

		// If the system in is maintenance mode and it's a site request, throw a 503.
		if (craft()->isInMaintenanceMode() && $this->request->isSiteRequest())
		{
			throw new HttpException(503);
		}

		// Check if the app path has changed.  If so, run the requirements check again.
		$this->_processRequirementsCheck();

		// Now that we've ran the requirements checker, set MB to use UTF-8
		mb_internal_encoding('UTF-8');
		mb_http_input('UTF-8');
		mb_http_output('UTF-8');
		mb_detect_order('auto');

		// Makes sure that the uploaded files are compatible with the current DB schema
		if (!$this->updates->isSchemaVersionCompatible())
		{
			if ($this->request->isCpRequest())
			{
				throw new HttpException(200, Craft::t('Craft does not support backtracking to this version.'));
			}
			else
			{
				throw new HttpException(503);
			}
		}

		// Set the edition components
		$this->_setEditionComponents();

		// isCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if (
			$this->updates->isCraftDbMigrationNeeded() ||
			(craft()->isInMaintenanceMode() && $this->request->isCpRequest()) ||
			$this->request->getActionSegments() == array('update', 'cleanUp') ||
			$this->request->getActionSegments() == array('update', 'rollback')
		)
		{
			$this->_processUpdateLogic();
		}

		// If there's a new version, but the schema hasn't changed, just update the info table
		if ($this->updates->hasCraftBuildChanged())
		{
			$this->updates->updateCraftVersionInfo();
		}

		// Make sure that the system is on...
		if (craft()->isSystemOn() ||
			// ...or it's a CP request...
			($this->request->isCpRequest() && (
				// ...and the user has permission to access the CP when the site is off
				$this->userSession->checkPermission('accessCpWhenSystemIsOff') ||
				// ...or this is a manual update request
				$this->request->getSegment(1) == 'manualupdate' ||
				// ...or they're accessing the Login, Forgot Password, Set Password, or Validation pages
				(($actionSegs = $this->request->getActionSegments()) && (
					$actionSegs == array('users', 'login') ||
					$actionSegs == array('users', 'forgotpassword') ||
					$actionSegs == array('users', 'setpassword') ||
					$actionSegs == array('users', 'validate') ||
					$actionSegs[0] == 'update'
				))
			)) ||
			// ...or it's a site request...
			($this->request->isSiteRequest() && (
				// ...and the user has permission to access the site when it's off
				$this->userSession->checkPermission('accessSiteWhenSystemIsOff')
			))
		)
		{
			// Load the plugins
			craft()->plugins->loadPlugins();

			// Check if a plugin needs to update the database.
			if ($this->updates->isPluginDbUpdateNeeded())
			{
				$this->_processUpdateLogic();
			}

			// If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
			if ($this->request->isCpRequest() && !($this->request->isActionRequest() && $this->_isValidActionRequest()))
			{
				// Make sure the user has access to the CP
				$this->userSession->requireLogin();
				$this->userSession->requirePermission('accessCp');

				// If they're accessing a plugin's section, make sure that they have permission to do so
				$firstSeg = $this->request->getSegment(1);
				if ($firstSeg)
				{
					$plugin = $plugin = $this->plugins->getPlugin($firstSeg);
					if ($plugin)
					{
						$this->userSession->requirePermission('accessPlugin-'.$plugin->getClassHandle());
					}
				}
			}

			// If this is an action request, call the controller
			$this->_processActionRequest();

			// If we're still here, finally let UrlManager do it's thing.
			parent::processRequest();
		}
		else
		{
			// Log out the user
			if ($this->userSession->isLoggedIn())
			{
				$this->userSession->logout(false);
			}

			if ($this->request->isCpRequest())
			{
				// Redirect them to the login screen
				$this->userSession->requireLogin();
			}
			else
			{
				// Display the offline template
				$this->runController('templates/offline');
			}
		}
	}

	/**
	 * Returns the target application language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		if (!isset($this->_language))
		{
			// Defend against an infinite getLanguage() loop
			if (!$this->_gettingLanguage)
			{
				$this->_gettingLanguage = true;
				$this->setLanguage($this->_getTargetLanguage());
			}
			else
			{
				// We tried to get the language, but something went wrong. Use fallback to prevent infinite loop.
				$this->setLanguage($this->_getFallbackLanguage());
				$this->_gettingLanguage = false;
			}
		}

		return $this->_language;
	}

	/**
	 * Sets the target application language.
	 *
	 * @param string $language
	 */
	public function setLanguage($language)
	{
		$this->_language = $language;
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param string $localeId
	 * @return LocaleData
	 */
	public function getLocale($localeId = null)
	{
		return craft()->i18n->getLocaleData($localeId);
	}

	/**
	 * Creates a controller instance based on a route.
	 *
	 * @param string $route
	 * @param mixed $owner
	 * @return array|null
	 */
	public function createController($route, $owner = null)
	{
		if (($route = trim($route, '/')) === '')
		{
			$route = $this->defaultController;
		}

		$routeParts = array_filter(explode('/', $route));

		// First check if the controller class is a combination of the first two segments.
		// That way FooController won't steal all of Foo_BarController's requests.
		if (isset($routeParts[1]))
		{
			$controllerId = ucfirst($routeParts[0]).'_'.ucfirst($routeParts[1]);
			$class = __NAMESPACE__.'\\'.$controllerId.'Controller';

			if (class_exists($class))
			{
				$action = implode('/', array_slice($routeParts, 2));
			}
		}

		// If that didn't work, now look for that FooController.
		if (!isset($action))
		{
			$controllerId = ucfirst($routeParts[0]);
			$class = __NAMESPACE__.'\\'.$controllerId.'Controller';

			if (class_exists($class))
			{
				$action = implode('/', array_slice($routeParts, 1));
			}
		}

		// Did we find a valid controller?
		if (isset($action))
		{
			return array(
				Craft::createComponent($class, $controllerId),
				$this->parseActionParams($action),
			);
		}
	}

	/**
	 * Gets the viewPath for the incoming request.
	 * We can't use setViewPath() because our view path depends on the request type, which is initialized after web application, so we override getViewPath();
	 *
	 * @return mixed
	 */
	public function getViewPath()
	{
		if (!isset($this->_templatePath))
		{
			if (mb_strpos(get_class($this->request), 'HttpRequest') !== false)
			{
				$this->_templatePath = $this->path->getTemplatesPath();
			}
			else
			{
				// in the case of an exception, our custom classes are not loaded.
				$this->_templatePath = CRAFT_TEMPLATES_PATH;
			}
		}

		return $this->_templatePath;
	}

	/**
	 * Sets the template path for the app.
	 *
	 * @param $path
	 */
	public function setViewPath($path)
	{
		$this->_templatePath = $path;
	}

	/**
	 * Returns the CP templates path.
	 *
	 * @return string
	 */
	public function getSystemViewPath()
	{
		return $this->path->getCpTemplatesPath();
	}

	/**
	 * Formats an exception into JSON before returning it to the client.
	 *
	 * @param array $data
	 */
	public function returnAjaxException($data)
	{
		$exceptionArr['error'] = $data['message'];

		if ($this->config->get('devMode'))
		{
			$exceptionArr['trace']  = $data['trace'];
			$exceptionArr['traces'] = (isset($data['traces']) ? $data['traces'] : null);
			$exceptionArr['file']   = $data['file'];
			$exceptionArr['line']   = $data['line'];
			$exceptionArr['type']   = $data['type'];
		}

		JsonHelper::sendJsonHeaders();
		echo JsonHelper::encode($exceptionArr);
		$this->end();
	}

	/**
	 * Formats a PHP error into JSON before returning it to the client.
	 *
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $file error file
	 * @param string $line error line
	 */
	public function returnAjaxError($code, $message, $file, $line)
	{
		if($this->config->get('devMode'))
		{
			$outputTrace = '';
			$trace = debug_backtrace();

			// skip the first 3 stacks as they do not tell the error position
			if(count($trace) > 3)
				$trace = array_slice($trace, 3);

			foreach($trace as $i => $t)
			{
				if (!isset($t['file']))
				{
					$t['file'] = 'unknown';
				}

				if (!isset($t['line']))
				{
					$t['line'] = 0;
				}

				if (!isset($t['function']))
				{
					$t['function'] = 'unknown';
				}

				$outputTrace .= "#$i {$t['file']}({$t['line']}): ";

				if (isset($t['object']) && is_object($t['object']))
				{
					$outputTrace .= get_class($t['object']).'->';
				}

				$outputTrace .= "{$t['function']}()\n";
			}

			$errorArr = array(
				'error' => $code.' : '.$message,
				'trace' => $outputTrace,
				'file'  => $file,
				'line'  => $line,
			);
		}
		else
		{
			$errorArr = array('error' => $message);
		}

		JsonHelper::sendJsonHeaders();
		echo JsonHelper::encode($errorArr);
		$this->end();
	}

	/**
	 * Returns whether we are executing in the context on a console app.
	 *
	 * @return bool
	 */
	public function isConsole()
	{
		return false;
	}

	// Remap $this->getSession() to $this->httpSession and $this->getUser() to craft->userSession

	/**
	 * @return HttpSessionService
	 */
	public function getSession()
	{
		return $this->getComponent('httpSession');
	}

	/**
	 * @return UserSessionService
	 */
	public function getUser()
	{
		return $this->getComponent('userSession');
	}

	/**
	 * Sets the application components.
	 *
	 * @param      $components
	 * @param bool $merge
	 */
	public function setComponents($components, $merge = true)
	{
		if (isset($components['editionComponents']))
		{
			$this->_editionComponents = $components['editionComponents'];
			unset($components['editionComponents']);
		}

		parent::setComponents($components, $merge);
	}

	/**
	 * Attaches an event listener, or remembers it for later if the component has not been initialized yet.
	 *
	 * @param string $event
	 * @param mixed  $handler
	 */
	public function on($event, $handler)
	{
		list($componentId, $eventName) = explode('.', $event, 2);

		$component = $this->getComponent($componentId, false);

		// Normalize the event name
		if (strncmp($eventName, 'on', 2) !== 0)
		{
			$eventName = 'on'.ucfirst($eventName);
		}

		if ($component)
		{
			$component->$eventName = $handler;
		}
		else
		{
			$this->_pendingEvents[$componentId][$eventName][] = $handler;
		}
	}

	/**
	 * Override getComponent() so we can attach any pending events if the component is getting initialized.
	 *
	 * @param string $id
	 * @param boolean $createIfNull
	 * @return mixed
	 */
	public function getComponent($id, $createIfNull = true)
	{
		$component = parent::getComponent($id, false);

		if (!$component && $createIfNull)
		{
			$component = parent::getComponent($id, true);
			$this->_attachEventListeners($id);
		}

		return $component;
	}

	/**
	 * Override setComponent so we can attach any pending events.
	 *
	 * @param string $id
	 * @param mixed  $component
	 * @param bool   $merge
	 */
	public function setComponent($id, $component, $merge = true)
	{
		parent::setComponent($id, $component, $merge);
		$this->_attachEventListeners($id);
	}

	/**
	 * Returns the system time zone.  Note that this method cannot be in AppBehavior, because Yii will check
	 * \CApplication->getTimeZone instead.
	 *
	 * @return string
	 */
	public function getTimeZone()
	{
		return $this->getInfo('timezone');
	}

	/**
	 * Tries to find a match between the browser's preferred locales and the locales Craft has been translated into.
	 *
	 * @return string
	 */
	public function getTranslatedBrowserLanguage()
	{
		$browserLanguages = $this->request->getBrowserLanguages();

		if ($browserLanguages)
		{
			$appLocaleIds = $this->i18n->getAppLocaleIds();

			foreach ($browserLanguages as $language)
			{
				if (in_array($language, $appLocaleIds))
				{
					return $language;
				}
			}
		}

		return false;
	}

	/**
	 * Attaches any pending event listeners to the newly-initialized component.
	 *
	 * @access private
	 * @param string $componentId
	 */
	private function _attachEventListeners($componentId)
	{
		if (isset($this->_pendingEvents[$componentId]))
		{
			$component = $this->getComponent($componentId, false);

			if ($component)
			{
				foreach ($this->_pendingEvents[$componentId] as $eventName => $handlers)
				{
					foreach ($handlers as $handler)
					{
						$component->$eventName = $handler;
					}
				}
			}
		}
	}

	/**
	 * Processes resource requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processResourceRequest()
	{
		if ($this->request->isResourceRequest())
		{
			// Don't want to log anything on a resource request.
			$this->log->removeRoute('FileLogRoute');

			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->getSegments()), 1);
			$path = implode('/', $segs);

			$this->resources->sendResource($path);
		}
	}

	/**
	 * Sets the edition components.
	 */
	private function _setEditionComponents()
	{
		// Set the appropriate edition components
		if (isset($this->_editionComponents))
		{
			foreach ($this->_editionComponents as $edition => $editionComponents)
			{
				if (craft()->getEdition() >= $edition)
				{
					$this->setComponents($editionComponents);
				}
			}

			unset($this->_editionComponents);
		}
	}

	/**
	 * Processes install requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processInstallRequest()
	{
		$isCpRequest = $this->request->isCpRequest();

		// Are they requesting an installer template/action specifically?
		if ($isCpRequest && $this->request->getSegment(1) === 'install' && !craft()->isInstalled())
		{
			$action = $this->request->getSegment(2, 'index');
			$this->runController('install/'.$action);
			$this->end();
		}
		else if ($isCpRequest && $this->request->isActionRequest() && ($this->request->getSegment(1) !== 'login'))
		{
			$actionSegs = $this->request->getActionSegments();
			if (isset($actionSegs[0]) && $actionSegs[0] == 'install')
			{
				$this->_processActionRequest();
			}
		}

		// Should they be?
		else if (!craft()->isInstalled())
		{
			// Give it to them if accessing the CP
			if ($isCpRequest)
			{
				$url = UrlHelper::getUrl('install');
				$this->request->redirect($url);
			}
			// Otherwise return a 404
			else
			{
				throw new HttpException(404);
			}
		}
	}

	/**
	 * Returns the target app language.
	 *
	 * @access private
	 * @return string
	 */
	private function _getTargetLanguage()
	{
		if (craft()->isInstalled())
		{
			// Will any locale validation be necessary here?
			if ($this->request->isCpRequest() || defined('CRAFT_LOCALE'))
			{
				if ($this->request->isCpRequest())
				{
					$locale = 'auto';
				}
				else
				{
					$locale = StringHelper::toLowerCase(CRAFT_LOCALE);
				}

				// Get the list of actual site locale IDs
				$siteLocaleIds = $this->i18n->getSiteLocaleIds();

				// Is it set to "auto"?
				if ($locale == 'auto')
				{
					// Place this within a try/catch in case userSession is being fussy.
					try
					{
						// If the user is logged in *and* has a primary language set, use that
						$user = $this->userSession->getUser();

						if ($user && $user->preferredLocale)
						{
							return $user->preferredLocale;
						}
					}
					catch (\Exception $e)
					{
						Craft::log("Tried to determine the user's preferred locale, but got this exception: ".$e->getMessage(), LogLevel::Error);
					}

					// Otherwise check if the browser's preferred language matches any of the site locales
					$browserLanguages = $this->request->getBrowserLanguages();

					if ($browserLanguages)
					{
						foreach ($browserLanguages as $language)
						{
							if (in_array($language, $siteLocaleIds))
							{
								return $language;
							}
						}
					}
				}

				// Is it set to a valid site locale?
				else if (in_array($locale, $siteLocaleIds))
				{
					return $locale;
				}
			}

			// Use the primary site locale by default
			return $this->i18n->getPrimarySiteLocaleId();
		}
		else
		{
			return $this->_getFallbackLanguage();
		}
	}

	/**
	 * Tries to find a language match with the user's browser's preferred language(s).  If not uses the app's sourceLanguage.
	 *
	 * @return string
	 */
	private function _getFallbackLanguage()
	{
		// See if we have the CP translated in one of the user's browsers preferred language(s)
		$language = $this->getTranslatedBrowserLanguage();

		// Default to the source language.
		if (!$language)
		{
			$language = $this->sourceLanguage;
		}

		return $language;
	}

	/**
	 * Processes action requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processActionRequest()
	{
		if ($this->request->isActionRequest())
		{
			$actionSegs = $this->request->getActionSegments();
			$route = implode('/', $actionSegs);
			$this->runController($route);
		}
	}

	/**
	 * @return bool
	 */
	private function _isValidActionRequest()
	{
		if (
			$this->request->getActionSegments() == array('users', 'login') ||
			$this->request->getActionSegments() == array('users', 'validate') ||
			$this->request->getActionSegments() == array('users', 'setpassword') ||
			$this->request->getActionSegments() == array('users', 'forgotpassword') ||
			$this->request->getActionSegments() == array('users', 'saveUser'))
		{
			return true;
		}

		return false;
	}

	/**
	 * If there is not cached app path or the existing cached app path does not match the current one, let’s run the requirement checker again.
	 * This should catch the case where an install is deployed to another server that doesn’t meet Craft’s minimum requirements.
	 */
	private function _processRequirementsCheck()
	{
		// See if we're in the middle of an update.
		$update = false;

		if ($this->request->getSegment(1) == 'updates' && $this->request->getSegment(2) == 'go')
		{
			$update = true;
		}

		if (($data = $this->request->getPost('data', null)) !== null && isset($data['handle']))
		{
			$update = true;
		}

		// Only run for CP requests and if we're not in the middle of an update.
		if ($this->request->isCpRequest() && !$update)
		{
			$cachedAppPath = craft()->cache->get('appPath');
			$appPath = $this->path->getAppPath();

			if ($cachedAppPath === false || $cachedAppPath !== $appPath)
			{
				$this->runController('templates/requirementscheck');
			}
		}
	}

	/**
	 * @throws HttpException
	 */
	private function _processUpdateLogic()
	{
		// Let all non-action CP requests through.
		if (
			$this->request->isCpRequest() &&
			(!$this->request->isActionRequest() || $this->request->getActionSegments() == array('users', 'login'))
		)
		{
			// If this is a request to actually manually update Craft, do it
			if ($this->request->getSegment(1) == 'manualupdate')
			{
				$this->runController('templates/manualUpdate');
				$this->end();
			}
			else
			{
				if ($this->updates->isBreakpointUpdateNeeded())
				{
					// Load the breakpoint update template
					$this->runController('templates/breakpointUpdateNotification');
				}
				else
				{
					if (!$this->request->isAjaxRequest())
					{
						if ($this->request->getPathInfo() !== '')
						{
							$this->userSession->setReturnUrl($this->request->getPath());
						}
					}

					// Show the manual update notification template
					$this->runController('templates/manualUpdateNotification');
				}
			}
		}
		// We'll also let action requests to UpdateController through as well.
		else if ($this->request->isActionRequest() && (($actionSegs = $this->request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] == 'update')
		{
			$controller = $actionSegs[0];
			$action = isset($actionSegs[1]) ? $actionSegs[1] : 'index';
			$this->runController($controller.'/'.$action);
		}
		else
		{
			// Use our own error template in case the custom 503 template comes with any SQL queries we're not ready for
			craft()->path->setTemplatesPath(craft()->path->getCpTemplatesPath());

			throw new HttpException(503);
		}

		// YOU SHALL NOT PASS
		$this->end();
	}
}
