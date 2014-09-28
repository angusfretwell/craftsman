<?php
namespace Craft;

/**
 * UserSessionService provides APIs for managing user sessions.
 *
 * An instance of UserSessionService is globally accessible in Craft via
 * {@link WebApp::userSession `craft()->userSession`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class UserSessionService extends \CWebUser
{
	// Constants
	// =========================================================================

	const FLASH_KEY_PREFIX = 'Craft.UserSessionService.flash.';
	const FLASH_COUNTERS   = 'Craft.UserSessionService.flashcounters';

	// Properties
	// =========================================================================

	/**
	 * Stores the user identity.
	 *
	 * @var UserIdentity
	 */
	private $_identity;

	/**
	 * Stores the user identity cookie.
	 *
	 * @var HttpCookie
	 */
	private $_identityCookie;

	/**
	 * Stores the current user model.
	 *
	 * @var UserModel
	 */
	private $_userModel;

	/**
	 * @var
	 */
	private $_userRow;

	/**
	 * @var
	 */
	private $_sessionRestoredFromCookie;

	/**
	 * Stores whether the request has requested to not extend the user's session.
	 *
	 * @var bool
	 */
	private $_dontExtendSession;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * This method will determine how long user sessions are configured to last, and whether the current request
	 * has requested to not extend the current user session, before calling {@link \CWebUser::init()}.
	 *
	 * @return null
	 */
	public function init()
	{
		if (!craft()->isConsole())
		{
			// Set the authTimeout based on whether the current identity was created with "Remember Me" checked.
			$data = $this->getIdentityCookieValue();
			$this->authTimeout = craft()->config->getUserSessionDuration($data ? $data[3] : false);

			// Should we skip auto login and cookie renewal?
			$this->_dontExtendSession = !$this->shouldExtendSession();

			$this->autoRenewCookie = !$this->_dontExtendSession;

			parent::init();
		}
	}

	/**
	 * Returns the currently logged-in user.
	 *
	 * @return UserModel|null The currently logged-in user, or `null`.
	 */
	public function getUser()
	{
		// Does a user appear to be logged in?
		if (craft()->isInstalled() && !$this->getIsGuest())
		{
			if (!isset($this->_userModel))
			{
				$userRow = $this->_getUserRow($this->getId());

				if ($userRow && $userRow['status'] == UserStatus::Active || $userRow['status'] == UserStatus::Pending)
				{
					$this->_userModel = UserModel::populateModel($userRow);
				}
				else
				{
					$this->_userModel = false;
				}
			}

			return $this->_userModel ? $this->_userModel : null;
		}
	}

	/**
	 * Returns the URL the user was trying to access before getting redirected to the login page via
	 * {@link requireLogin()}.
	 *
	 * @param string|null $defaultUrl The default URL that should be returned if no return URL was stored.
	 *
	 * @return string|null The return URL, or $defaultUrl.
	 */
	public function getReturnUrl($defaultUrl = null)
	{
		$returnUrl = $this->getState('__returnUrl');

		if ($returnUrl !== null)
		{
			// Strip out any tags that may have gotten in there by accident
			// i.e. if there was a {siteUrl} tag in the Site URL setting, but no matching environment variable,
			// so they ended up on something like http://example.com/%7BsiteUrl%7D/some/path
			$returnUrl = str_replace(array('{', '}'), array('', ''), $returnUrl);
		}

		if ($returnUrl === null)
		{
			$returnUrl = $defaultUrl;
		}

		if ($returnUrl !== null)
		{
			return UrlHelper::getUrl($returnUrl);
		}
	}

	/**
	 * Stores a notice in the user’s flash data.
	 *
	 * The message will be stored on the user session, and can be retrieved by calling
	 * {@link getFlash() `getFlash('notice')`} or {@link getFlashes()}.
	 *
	 * Only one flash notice can be stored at a time.
	 *
	 * @param string $message The message.
	 *
	 * @return null
	 */
	public function setNotice($message)
	{
		$this->setFlash('notice', $message);
	}

	/**
	 * Stores an error message in the user’s flash data.
	 *
	 * The message will be stored on the user session, and can be retrieved by calling
	 * {@link getFlash() `getFlash('error')`} or {@link getFlashes()}.
	 *
	 * Only one flash error message can be stored at a time.
	 *
	 * @param string $message The message.
	 *
	 * @return null
	 */
	public function setError($message)
	{
		$this->setFlash('error', $message);
	}

	/**
	 * Stores a JS file from resources/ in the user’s flash data.
	 *
	 * The file will be stored on the user session, and can be retrieved by calling {@link getJsResourceFlashes()} or
	 * {@link TemplatesService::getFootHtml()}.
	 *
	 * @param string $resource The resource path to the JS file.
	 *
	 * @return null
	 */
	public function addJsResourceFlash($resource)
	{
		$resources = $this->getJsResourceFlashes(false);

		if (!in_array($resource, $resources))
		{
			$resources[] = $resource;
			$this->setFlash('jsResources', $resources);
		}
	}

	/**
	 * Returns the stored JS resource flashes.
	 *
	 * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
	 *
	 * @return array The stored JS resource flashes.
	 */
	public function getJsResourceFlashes($delete = true)
	{
		return $this->getFlash('jsResources', array(), $delete);
	}

	/**
	 * Stores JS in the user’s flash data.
	 *
	 * The Javascript code will be stored on the user session, and can be retrieved by calling
	 * {@link getJsFlashes()} or {@link TemplatesService::getFootHtml()}.
	 *
	 * @param string $js The Javascript code.
	 *
	 * @return null
	 */
	public function addJsFlash($js)
	{
		$scripts = $this->getJsFlashes();
		$scripts[] = $js;
		$this->setFlash('js', $scripts);
	}

	/**
	 * Returns the stored JS flashes.
	 *
	 * @param bool $delete Whether to delete the stored flashes. Defaults to `true`.
	 *
	 * @return array The stored JS flashes.
	 */
	public function getJsFlashes($delete = true)
	{
		return $this->getFlash('js', array(), $delete);
	}

	/**
	 * Alias of {@link getIsGuest()}.
	 *
	 * @return bool
	 */
	public function isGuest()
	{
		$user = $this->_getUserRow($this->getId());
		return empty($user);
	}

	/**
	 * Returns whether the current user is logged in.
	 *
	 * The result will always just be the opposite of whatever {@link getIsGuest()} returns.
	 *
	 * @return bool Whether the current user is logged in.
	 */
	public function isLoggedIn()
	{
		return !$this->isGuest();
	}

	/**
	 * Returns whether the current user is an admin.
	 *
	 * @return bool Whether the current user is an admin.
	 */
	public function isAdmin()
	{
		$user = $this->getUser();
		return ($user && $user->admin);
	}

	/**
	 * Returns whether the current user has a given permission.
	 *
	 * @param string $permissionName The name of the permission.
	 *
	 * @return bool Whether the current user has the permission.
	 */
	public function checkPermission($permissionName)
	{
		$user = $this->getUser();
		return ($user && $user->can($permissionName));
	}

	/**
	 * Checks whether the current user has a given permission, and ends the request with a 403 error if they don’t.
	 *
	 * @param string $permissionName The name of the permission.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requirePermission($permissionName)
	{
		if (!$this->checkPermission($permissionName))
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Checks whether the current user is an admin, and ends the request with a 403 error if they aren’t.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAdmin()
	{
		if (!$this->isAdmin())
		{
			throw new HttpException(403);
		}
	}

	/**
	 * Checks whether the current user is logged in, and redirects them to the Login page if they aren’t.
	 *
	 * The current request’s URL will be stored on the user’s session before they get redirected, and Craft will
	 * automatically redirect them back to the original URL after they’ve logged in.
	 *
	 * @throws Exception
	 * @return null
	 */
	public function requireLogin()
	{
		if ($this->isGuest())
		{
			// Ignore if this was called from the Login page
			if (craft()->request->isSiteRequest() && craft()->config->get('loginPath') == craft()->request->getPath())
			{
				Craft::log('UserSessionService::requireLogin() was called from the Login page.', LogLevel::Warning, true);
				return;
			}

			if (!craft()->request->isAjaxRequest())
			{
				$url = craft()->request->getPath();

				if (($queryString = craft()->request->getQueryStringWithoutPath()))
				{
					if (craft()->request->getPathInfo())
					{
						$url .= '?'.$queryString;
					}
					else
					{
						$url .= '&'.$queryString;
					}
				}

				$this->setReturnUrl($url);
			}
			elseif (isset($this->loginRequiredAjaxResponse))
			{
				echo $this->loginRequiredAjaxResponse;
				craft()->end();
			}

			$url = UrlHelper::getUrl(craft()->config->getLoginPath());
			craft()->request->redirect($url);
		}
	}

	/**
	 * Alias of {@link requireLogin()}.
	 *
	 * @return null
	 */
	public function loginRequired()
	{
		$this->requireLogin();
	}

	/**
	 * Logs a user in.
	 *
	 * If $rememberMe is set to `true`, the user will be logged in for the duration specified by the
	 * [rememberedUserSessionDuration](http://buildwithcraft.com/docs/config-settings#rememberedUserSessionDuration)
	 * config setting. Otherwise it will last for the duration specified by the
	 * [userSessionDuration](http://buildwithcraft.com/docs/config-settings#userSessionDuration)
	 * config setting.
	 *
	 * @param string $username   The user’s username.
	 * @param string $password   The user’s submitted password.
	 * @param bool   $rememberMe Whether the user should be remembered.
	 *
	 * @throws Exception
	 * @return bool Whether the user was logged in successfully.
	 */
	public function login($username, $password, $rememberMe = false)
	{
		// Validate the username/password first.
		$usernameModel = new UsernameModel();
		$passwordModel = new PasswordModel();

		$usernameModel->username = $username;
		$passwordModel->password = $password;

		// Require a userAgent string and an IP address to help prevent direct socket connections from trying to login.
		if (!craft()->request->userAgent || !$_SERVER['REMOTE_ADDR'])
		{
			Craft::log('Someone tried to login with loginName: '.$username.', without presenting an IP address or userAgent string.', LogLevel::Warning);
			$this->logout(true);
			$this->requireLogin();
		}

		// Validate the model.
		if ($usernameModel->validate() && $passwordModel->validate())
		{
			// Authenticate the credentials.
			$this->_identity = new UserIdentity($username, $password);
			$this->_identity->authenticate();

			// Was the login successful?
			if ($this->_identity->errorCode == UserIdentity::ERROR_NONE)
			{
				// See if the 'rememberUsernameDuration' config item is set. If so, save the name to a cookie.
				$rememberUsernameDuration = craft()->config->get('rememberUsernameDuration');

				if ($rememberUsernameDuration)
				{
					$this->saveCookie('username', $username, DateTimeHelper::timeFormatToSeconds($rememberUsernameDuration));
				}
				else
				{
					// Just in case...
					$this->deleteStateCookie('username');
				}

				// Get how long this session is supposed to last.
				$this->authTimeout = craft()->config->getUserSessionDuration($rememberMe);

				$id = $this->_identity->getId();
				$states = $this->_identity->getPersistentStates();

				// Fire an 'onBeforeLogin' event
				$this->onBeforeLogin(new Event($this, array(
					'username'      => $usernameModel->username,
				)));

				// Run any before login logic.
				if ($this->beforeLogin($id, $states, false))
				{
					$this->changeIdentity($id, $this->_identity->getName(), $states);

					// Fire an 'onLogin' event
					$this->onLogin(new Event($this, array(
						'username'      => $usernameModel->username,
					)));

					if ($this->authTimeout)
					{
						if ($this->allowAutoLogin)
						{
							$user = craft()->users->getUserById($id);

							if ($user)
							{
								// Save the necessary info to the identity cookie.
								$sessionToken = StringHelper::UUID();
								$hashedToken = craft()->security->hashData(base64_encode(serialize($sessionToken)));
								$uid = craft()->users->handleSuccessfulLogin($user, $hashedToken);

								$data = array(
									$this->getName(),
									$sessionToken,
									$uid,
									($rememberMe ? 1 : 0),
									craft()->request->getUserAgent(),
									$this->saveIdentityStates(),
								);

								$this->_identityCookie = $this->saveCookie('', $data, $this->authTimeout);
							}
							else
							{
								throw new Exception(Craft::t('Could not find a user with Id of {userId}.', array('{userId}' => $this->getId())));
							}
						}
						else
						{
							throw new Exception(Craft::t('{class}.allowAutoLogin must be set true in order to use cookie-based authentication.', array('{class}' => get_class($this))));
						}
					}

					$this->_sessionRestoredFromCookie = false;
					$this->_userRow = null;

					// Run any after login logic.
					$this->afterLogin(false);
				}

				return !$this->getIsGuest();
			}
		}

		Craft::log($username.' tried to log in unsuccessfully.', LogLevel::Warning);
		return false;
	}

	/**
	 * Logs a user in for impersonation.
	 *
	 * This method doesn’t have any sort of credential verification, and just requires the ID of the user to
	 * impersonate, so use it at your own peril.
	 *
	 * The new user session will only last as long as the browser session remains active; no identity cookie will be
	 * created.
	 *
	 * @param int $userId The user’s ID.
	 *
	 * @throws Exception
	 * @return bool Whether the user is now being impersonated.
	 */
	public function impersonate($userId)
	{
		$userModel = craft()->users->getUserById($userId);

		if (!$userModel)
		{
			throw new Exception(Craft::t('Could not find a user with Id of {userId}.', array('{userId}' => $userId)));
		}

		$this->_identity = new UserIdentity($userModel->username, null);
		$this->_identity->logUserIn($userModel);

		$id = $this->_identity->getId();
		$states = $this->_identity->getPersistentStates();

		// Run any before login logic.
		if ($this->beforeLogin($id, $states, false))
		{
			// Fire an 'onBeforeLogin' event
			$this->onBeforeLogin(new Event($this, array(
				'username'      => $userModel->username,
			)));

			$this->changeIdentity($id, $this->_identity->getName(), $states);

			// Fire an 'onLogin' event
			$this->onLogin(new Event($this, array(
				'username'      => $userModel->username,
			)));

			$this->_sessionRestoredFromCookie = false;
			$this->_userRow = null;

			// Run any after login logic.
			$this->afterLogin(false);

			return !$this->getIsGuest();
		}

		Craft::log($userModel->username.' tried to log in unsuccessfully.', LogLevel::Warning);
		return false;
	}

	/**
	 * Returns the login error code from the user identity.
	 *
	 * @return int|null The login error code, or `null` if there isn’t one.
	 */
	public function getLoginErrorCode()
	{
		if (isset($this->_identity))
		{
			return $this->_identity->errorCode;
		}
	}

	/**
	 * Returns a login error message by a given error code.
	 *
	 * @param $errorCode The login error code.
	 * @param $loginName The user’s username or email.
	 *
	 * @return string The login error message.
	 */
	public function getLoginErrorMessage($errorCode, $loginName)
	{
		switch ($errorCode)
		{
			case UserIdentity::ERROR_PASSWORD_RESET_REQUIRED:
			{
				$error = Craft::t('You need to reset your password. Check your email for instructions.');
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_LOCKED:
			{
				$error = Craft::t('Account locked.');
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_COOLDOWN:
			{
				$user = craft()->users->getUserByUsernameOrEmail($loginName);

				if ($user)
				{
					$timeRemaining = $user->getRemainingCooldownTime();

					if ($timeRemaining)
					{
						$humanTimeRemaining = $timeRemaining->humanDuration();
						$error = Craft::t('Account locked. Try again in {time}.', array('time' => $humanTimeRemaining));
					}
					else
					{
						$error = Craft::t('Account locked.');
					}
				}
				else
				{
					$error = Craft::t('Account locked.');
				}
				break;
			}
			case UserIdentity::ERROR_ACCOUNT_SUSPENDED:
			{
				$error = Craft::t('Account suspended.');
				break;
			}
			case UserIdentity::ERROR_NO_CP_ACCESS:
			{
				$error = Craft::t('You cannot access the CP with that account.');
				break;
			}
			case UserIdentity::ERROR_NO_CP_OFFLINE_ACCESS:
			{
				$error = Craft::t('You cannot access the CP while the system is offline with that account.');
				break;
			}
			default:
			{
				$error = Craft::t('Invalid username or password.');
			}
		}

		return $error;
	}

	/**
	 * Returns the username of the account that the browser was last logged in as.
	 *
	 * @return string|null
	 */
	public function getRememberedUsername()
	{
		return $this->getStateCookieValue('username');
	}

	/**
	 * Alias of {@link isGuest()}.
	 *
	 * @return bool
	 */
	public function getIsGuest()
	{
		return $this->isGuest();
	}

	/**
	 * Sets a cookie on the browser.
	 *
	 * @param string $name     The name of the cookie.
	 * @param mixed  $data     The data that should be stored on the cookie.
	 * @param int    $duration The duration that the cookie should be stored for, in seconds.
	 *
	 * @return HttpCookie The cookie.
	 */
	public function saveCookie($name, $data, $duration = 0)
	{
		$name = $this->getStateKeyPrefix().$name;
		$cookie = new HttpCookie($name, '');
		$cookie->httpOnly = true;
		$cookie->expire = time() + $duration;

		if (craft()->request->isSecureConnection())
		{
			$cookie->secure = true;
		}

		$cookie->value = craft()->security->hashData(base64_encode(serialize($data)));
		craft()->request->getCookies()->add($cookie->name, $cookie);

		return $cookie;
	}

	/**
	 * Deletes a cookie on the browser that was stored for the current application state.
	 *
	 * @param string $name The name of the cookie.
	 *
	 * @return null
	 */
	public function deleteStateCookie($name)
	{
		$name = $this->getStateKeyPrefix().$name;
		craft()->request->deleteCookie($name);
	}

	/**
	 * Returns a cookie that was stored for the current application state.
	 *
	 * @param string $name The cookie name.
	 *
	 * @return HttpCookie|null The cookie, or `null` if it didn’t exist.
	 */
	public function getStateCookie($name)
	{
		$name = $this->getStateKeyPrefix().$name;
		return craft()->request->getCookie($name);
	}

	/**
	 * Returns the value of a cookie by its name, ensuring that the data hasn’t been tampered with.
	 *
	 * @param HttpCookie|string $cookie The cookie, or the name of the cookie.
	 *
	 * @return mixed The value of the cookie if it exists and hasn’t been tampered with, or `null`.
	 */
	public function getStateCookieValue($cookie)
	{
		if (is_string($cookie))
		{
			$cookie = $this->getStateCookie($cookie);
		}

		if ($cookie && !empty($cookie->value) && ($data = craft()->security->validateData($cookie->value)) !== false)
		{
			return @unserialize(base64_decode($data));
		}
	}

	/**
	 * Returns the current user identity cookie, if there is one.
	 *
	 * @return HttpCookie|null The user identity cookie.
	 */
	public function getIdentityCookie()
	{
		if (!isset($this->_identityCookie))
		{
			$cookie = $this->getStateCookie('');

			if ($cookie)
			{
				$this->_identityCookie = $cookie;
			}
			else
			{
				$this->_identityCookie = false;
			}
		}

		// Don't return false if that's what it is
		if ($this->_identityCookie)
		{
			return $this->_identityCookie;
		}
	}

	/**
	 * Returns the current user identity cookie’s value, if there is one.
	 *
	 * @param HttpCookie|null The user identity cookie, or `null` if you don’t have it on hand.
	 *
	 * @return array|null The user identity cookie’s data, or `null` if it didn’t exist.
	 */
	public function getIdentityCookieValue(HttpCookie $cookie = null)
	{
		if (!$cookie)
		{
			$cookie = $this->getIdentityCookie();
		}

		if (
			$cookie &&
			($data = $this->getStateCookieValue($cookie)) &&
			is_array($data) &&
			isset($data[0], $data[1], $data[2], $data[3], $data[4], $data[5])
		)
		{
			// TODO: remove this code after a while

			// If $data[3] is something besides 0 or 1, it was created before Craft 2.2, and represents the auth timeout
			// rather than whether Remember Me was checked. Let's fix that.
			if ($data[3] != 0 && $data[3] != 1)
			{
				// Delete the old rememberMe cookie(s)
				craft()->request->deleteCookie('rememberMe');
				$this->deleteStateCookie('rememberMe');

				// Replace $data[3]'s value with a 0 or 1
				$duration = craft()->config->get('rememberedUserSessionDuration');

				if (is_numeric($data[3]) && $data[3] >= DateTimeHelper::timeFormatToSeconds($duration))
				{
					$data[3] = 1;
				}
				else
				{
					$data[3] = 0;
				}
			}

			return $data;
		}
	}

	/**
	 * Returns whether the current user session was just restored from a cookie.
	 *
	 * This happens when a user with an active session closes their browser, and then re-opens it before their session
	 * is supposed to expire.
	 *
	 * @return bool Whether the current user session was just restored from a cookie.
	 */
	public function wasSessionRestoredFromCookie()
	{
		return $this->_sessionRestoredFromCookie;
	}

	/**
	 * Returns how many seconds are left in the current user session.
	 *
	 * @return int The seconds left in the session, or -1 if their session will expire when their HTTP session ends.
	 */
	public function getAuthTimeout()
	{
		// Are they logged in?
		if (!$this->getIsGuest())
		{
			// Is the site configured to have fixed user session durations?
			if ($this->authTimeout)
			{
				$expires = $this->getState(static::AUTH_TIMEOUT_VAR);

				if ($expires !== null)
				{
					$currentTime = time();

					// Shouldn't be possible for $expires to be < $currentTime because updateAuthStatus() would have
					// logged them out, but what the hell.
					if ($expires > $currentTime)
					{
						return $expires - $currentTime;
					}
				}
			}
			else
			{
				// The session duration must have been empty (expire when the HTTP session ends)
				return -1;
			}
		}

		return 0;
	}

	/**
	 * Clears all user identity information from persistent storage.
	 *
	 * This will remove the data stored via {@link setState}.
	 *
	 * @return null
	 */
	public function clearStates()
	{
		if (isset($_SESSION))
		{
			$keys = array_keys($_SESSION);
			$prefix = $this->getStateKeyPrefix();

			$n = mb_strlen($prefix);

			foreach($keys as $key)
			{
				if (!strncmp($key, $prefix, $n))
				{
					unset($_SESSION[$key]);
				}
			}
		}
	}

	/**
	 * Returns whether the request should extend the current session timeout or not.
	 *
	 * @return bool
	 */
	public function shouldExtendSession()
	{
		return !(
			craft()->request->isGetRequest() &&
			craft()->request->isCpRequest() &&
			craft()->request->getParam('dontExtendSession')
		);
	}

	/**
	 * Fires an 'onBeforeLogin' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeLogin(Event $event)
	{
		$this->raiseEvent('onBeforeLogin', $event);
	}

	/**
	 * Fires an 'onLogin' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onLogin(Event $event)
	{
		$this->raiseEvent('onLogin', $event);
	}

	/**
	 * Fires an 'onBeforeLogout' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeLogout(Event $event)
	{
		$this->raiseEvent('onBeforeLogout', $event);
	}

	/**
	 * Fires an 'onLogout' event.
	 *
	 * @param Event $event
	 */
	public function onLogout(Event $event)
	{
		$this->raiseEvent('onLogout', $event);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Changes the current user with the specified identity information.
	 *
	 * This method is called by {@link login} and {@link restoreFromCookie} when the current user needs to be populated
	 * with the corresponding identity information.
	 *
	 * Derived classes may override this method by retrieving additional user-related information. Make sure the parent
	 * implementation is called first.
	 *
	 * @param mixed  $id     A unique identifier for the user.
	 * @param string $name   The display name for the user.
	 * @param array  $states Identity states.
	 *
	 * @return null
	 */
	protected function changeIdentity($id, $name, $states)
	{
		$this->setId($id);
		$this->setName($name);
		$this->loadIdentityStates($states);
	}

	/**
	 * Updates the authentication status according to {@link authTimeout}.
	 *
	 * Based on the parts of {@link \CWebUser::updateAuthStatus()} that are relevant to Craft, but this version also
	 * enforces the [requireUserAgentAndIpForSession](http://buildwithcraft.com/docs/config-settings#requireUserAgentAndIpForSession)
	 * config setting, and it won't update the timeout state if the 'dontExtendSession' param is set.
	 *
	 * @return null
	 */
	protected function updateAuthStatus()
	{
		// Do we think that they're logged in?
		if (!$this->getIsGuest())
		{
			// Enforce the requireUserAgentAndIpForSession config setting
			// (helps prevent direct socket connections from trying to log in)
			if (craft()->config->get('requireUserAgentAndIpForSession'))
			{
				if (!craft()->request->getUserAgent() || !craft()->request->getIpAddress())
				{
					Craft::log('Request didn’t meet the user agent and IP requirement for maintaining a user session.', LogLevel::Warning);
					$this->logout(true);
					return;
				}
			}

			// Is the site configured to have fixed user session durations?
			if ($this->authTimeout)
			{
				// Has the session expired?
				$expires = $this->getState(self::AUTH_TIMEOUT_VAR);

				if ($expires === null || $expires < time())
				{
					// Log 'em out
					$this->logout(false);
				}
				else if (!$this->_dontExtendSession)
				{
					// Update our record of when the session will expire
					$this->setState(self::AUTH_TIMEOUT_VAR, time() + $this->authTimeout);
				}
			}
		}
	}

	/**
	 * Renews the user’s identity cookie.
	 *
	 * This function extends the identity cookie's expiration time based on either the
	 * [userSessionDuration](http://buildwithcraft.com/docs/config-settings#userSessionDuration) or
	 * [rememberedUserSessionDuration](http://buildwithcraft.com/docs/config-settings#rememberedUserSessionDuration)
	 * config setting, depending on whether Remember Me was checked when they logged in.
	 *
	 * @return null
	 */
	protected function renewCookie()
	{
		$cookie = $this->getIdentityCookie();

		if ($cookie)
		{
			$data = $this->getIdentityCookieValue($cookie);

			if ($data && $this->_checkUserAgentString($data[4]))
			{
				// Extend the expiration time
				$expiration = time() + $this->authTimeout;
				$cookie->expire = $expiration;
				$cookie->httpOnly = true;
				craft()->request->getCookies()->add($cookie->name, $cookie);
			}
		}
	}

	/**
	 * Restores a user session from the identity cookie.
	 *
	 * This method is used when automatic login ({@link allowAutoLogin}) is enabled. The user identity information is
	 * recovered from cookie.
	 *
	 * @todo Verify that it's totally necessary to re-save the cookie with a new user session token
	 * @return null
	 */
	protected function restoreFromCookie()
	{
		// If this request doesn't want to extend the user session, it is unfortunately not possible for us to restore
		// their user session from a cookie, because the cookie needs to be re-saved with a new user session token,
		// but we can't do that without also setting a new expiration date.
		if ($this->_dontExtendSession)
		{
			return;
		}

		// See if they have an existing identity cookie.
		$cookie = $this->getIdentityCookie();

		if ($cookie)
		{
			$data = $this->getIdentityCookieValue($cookie);

			if ($data && $this->_checkUserAgentString($data[4]))
			{
				$loginName = $data[0];
				$currentSessionToken = $data[1];
				$uid = $data[2];
				$rememberMe = $data[3];
				$states = $data[5];
				$currentUserAgent = craft()->request->userAgent;
				$this->authTimeout = craft()->config->getUserSessionDuration($rememberMe);

				// Get the hashed token from the db based on login name and uid.
				if (($sessionRow = $this->_findSessionToken($loginName, $uid)) !== false)
				{
					$dbHashedToken = $sessionRow['token'];
					$userId = $sessionRow['userId'];

					// Make sure the given session token matches what we have in the db.
					$checkHashedToken= craft()->security->hashData(base64_encode(serialize($currentSessionToken)));

					if (strcmp($checkHashedToken, $dbHashedToken) === 0)
					{
						// It's all good.
						if($this->beforeLogin($loginName, $states, true))
						{
							$this->changeIdentity($userId, $loginName, $states);

							if ($this->autoRenewCookie)
							{
								// Generate a new session token for the database and cookie.
								$newSessionToken = StringHelper::UUID();
								$hashedNewToken = craft()->security->hashData(base64_encode(serialize($newSessionToken)));
								$this->_updateSessionToken($loginName, $dbHashedToken, $hashedNewToken);

								// While we're let's clean up stale sessions.
								$this->_cleanStaleSessions();

								// Save updated info back to identity cookie.
								$data = array(
									$this->getName(),
									$newSessionToken,
									$uid,
									($rememberMe ? 1 : 0),
									$currentUserAgent,
									$states,
								);

								$this->_identityCookie = $this->saveCookie('', $data, $this->authTimeout);
								$this->_sessionRestoredFromCookie = true;
								$this->_userRow = null;
							}

							$this->afterLogin(true);
						}
					}
					else
					{
						Craft::log('Tried to restore session from a cookie, but the given hashed database token value does not appear to belong to the given login name. Hashed db value: '.$dbHashedToken.' and loginName: '.$loginName.'.', LogLevel::Warning);
						// Forcing logout here clears the identity cookie helping to prevent session fixation.
						$this->logout(true);
					}
				}
				else
				{
					Craft::log('Tried to restore session from a cookie, but the given login name does not match the given uid. UID: '.$uid.' and loginName: '.$loginName.'.', LogLevel::Warning);
					// Forcing logout here clears the identity cookie helping to prevent session fixation.
					$this->logout(true);
				}
			}
			else
			{
				Craft::log('Tried to restore session from a cookie, but it appears we the data in the cookie is invalid.', LogLevel::Warning);
				$this->logout(true);
			}
		}
	}

	/**
	 * Called after a user is logged in.
	 *
	 * @return null
	 */
	protected function afterLogin()
	{
		if ($this->authTimeout)
		{
			$this->setState(static::AUTH_TIMEOUT_VAR, time()+$this->authTimeout);
		}
	}

	/**
	 * Called before a user is logged out.
	 *
	 * @return bool So true.
	 */
	protected function beforeLogout()
	{
		// Fire an 'onBeforeLogout' event
		$this->onBeforeLogout(new Event($this));

		$cookie = $this->getIdentityCookie();

		if ($cookie)
		{
			$data = $this->getIdentityCookieValue($cookie);

			if ($data)
			{
				$loginName = $data[0];
				$uid = $data[2];

				// Clean up their row in the sessions table.
				$user = craft()->users->getUserByUsernameOrEmail($loginName);

				if ($user)
				{
					craft()->db->createCommand()->delete('sessions', 'userId=:userId AND uid=:uid', array('userId' => $user->id, 'uid' => $uid));
				}
			}
			else
			{
				Craft::log('During logout, tried to remove the row from the sessions table, but it appears the cookie data is invalid.', LogLevel::Warning);
			}
		}

		$this->_userRow = null;

		return true;
	}

	/**
	 * Fires an 'onLogout' event after a user has been logged out.
	 *
	 * @return null
	 */
	protected function afterLogout()
	{
		// Clear the stored user model
		$this->_userModel = null;

		// Delete the identity cookie, if there is one
		$this->deleteStateCookie('');

		// Fire an 'onLogout' event
		$this->onLogout(new Event($this));
	}

	// Private Methods
	// =========================================================================

	/**
	 * @param string $loginName
	 * @param string $uid
	 *
	 * @return bool
	 */
	private function _findSessionToken($loginName, $uid)
	{
		$result = craft()->db->createCommand()
		    ->select('s.token, s.userId')
		    ->from('sessions s')
		    ->join('users u', 's.userId = u.id')
		    ->where('(u.username=:username OR u.email=:email) AND s.uid=:uid', array(':username' => $loginName, ':email' => $loginName, 'uid' => $uid))
		    ->queryRow();

		if (is_array($result) && count($result) > 0)
		{
			return $result;
		}

		return false;
	}

	/**
	 * @param string $loginName
	 * @param string $currentToken
	 * @param string $newToken
	 *
	 * @return int
	 */
	private function _updateSessionToken($loginName, $currentToken, $newToken)
	{
		$user = craft()->users->getUserByUsernameOrEmail($loginName);

		craft()->db->createCommand()->update('sessions', array('token' => $newToken), 'token=:currentToken AND userId=:userId', array('currentToken' => $currentToken, 'userId' => $user->id));
	}

	/**
	 * @return null
	 */
	private function _cleanStaleSessions()
	{
		$interval = new DateInterval('P3M');
		$expire = DateTimeHelper::currentUTCDateTime();
		$pastTimeStamp = $expire->sub($interval)->getTimestamp();
		$pastTime = DateTimeHelper::formatTimeForDb($pastTimeStamp);

		craft()->db->createCommand()->delete('sessions', 'dateUpdated < :pastTime', array('pastTime' => $pastTime));
	}

	/**
	 * @param int $id
	 *
	 * @return int
	 */
	private function _getUserRow($id)
	{
		if (!isset($this->_userRow))
		{
			if ($id)
			{
				$userRow = craft()->db->createCommand()
				    ->select('*')
				    ->from('users')
				    ->where('id=:id', array(':id' => $id))
				    ->queryRow();

				if ($userRow)
				{
					$this->_userRow = $userRow;
				}
				else
				{
					$this->_userRow = false;
				}
			}
			else
			{
				$this->_userRow = false;
			}
		}

		return $this->_userRow;
	}

	/**
	 * Enforces the requireMatchingUserAgentForSession config setting by verifying that the user agent string on the
	 * identity cookie matches the current request's user agent string.
	 *
	 * If they don't match, a warning will be logged and the user will be logged out.
	 *
	 * @param string $userAgent  The user agent string stored in the cookie.
	 * @param bool   $autoLogout Whether the user should be logged out if the user agents don't match.
	 *
	 * @return bool Whether the user agent strings matched.
	 */
	private function _checkUserAgentString($userAgent, $autoLogout = true)
	{
		if (craft()->config->get('requireMatchingUserAgentForSession'))
		{
			$currentUserAgent = craft()->request->getUserAgent();

			if ($userAgent !== $currentUserAgent)
			{
				Craft::log('Tried to restore session from the the identity cookie, but the saved user agent ('.$userAgent.') does not match the current userAgent ('.$currentUserAgent.').', LogLevel::Warning);

				if ($autoLogout)
				{
					$this->logout(true);
				}

				return false;
			}
		}

		return true;
	}
}
