<?php
namespace Craft;

/**
 * BaseController is a base class that all controllers in Craft extend.
 *
 * It extend's Yii's {@link \CController} overwriting specific methods as required.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.controllers
 * @since     1.0
 */
abstract class BaseController extends \CController
{
	// Properties
	// =========================================================================

	/**
	 * If set to false, you are required to be logged in to execute any of the given controller's actions.
	 *
	 * If set to true, anonymous access is allowed for all of the given controller's actions.
	 *
	 * If the value is an array of action names, then you must be logged in for any action method except for the ones in
	 * the array list.
	 *
	 * If you have a controller that where the majority of action methods will be anonymous, but you only want require
	 * login on a few, it's best to use {@link UserSessionService::requireLogin() craft()->userSession->requireLogin()}
	 * in the individual methods.
	 *
	 * @var bool
	 */
	protected $allowAnonymous = false;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the controller.  This method is called by the Craft before the controller starts to execute.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{

	}

	/**
	 * Include any route params gathered by {@link UrlManager} as controller action params.
	 *
	 * @return array
	 */
	public function getActionParams()
	{
		$params = parent::getActionParams();
		$routeParams = craft()->urlManager->getRouteParams();

		if (is_array($routeParams))
		{
			$params = array_merge($params, $routeParams);
		}

		return $params;
	}

	/**
	 * Returns the folder containing view files for this controller. Craft overrides this since {@link \CController}'s
	 * version defaults $module to craft().
	 *
	 * @return string The folder containing the view files for this controller.
	 */
	public function getViewPath()
	{
		if (($module = $this->getModule()) === null)
		{
			$module = craft();
		}

		return $module->getViewPath().'/';
	}

	/**
	 * Renders a template, and either outputs or returns it.
	 *
	 * @param mixed $template      The name of the template to load, or a {@link StringTemplate} object.
	 * @param array $variables     The variables that should be available to the template.
	 * @param bool  $return        Whether to return the results, rather than output them.
	 * @param bool  $processOutput
	 *
	 * @throws HttpException
	 * @return mixed
	 */
	public function renderTemplate($template, $variables = array(), $return = false, $processOutput = false)
	{
		if (($output = craft()->templates->render($template, $variables)) !== false)
		{
			if ($processOutput)
			{
				$output = $this->processOutput($output);
			}

			if ($return)
			{
				return $output;
			}
			else
			{
				// Set the MIME type for the request based on the matched template's file extension (unless the
				// Content-Type header was already set, perhaps by the template via the {% header %} tag)
				if (!HeaderHelper::isHeaderSet('Content-Type'))
				{
					// Safe to assume that findTemplate() will return an actual template path here, and not `false`.
					// If the template didn't exist, a TemplateLoaderException would have been thrown when calling
					// craft()->templates->render().
					$templateFile = craft()->templates->findTemplate($template);
					$extension = IOHelper::getExtension($templateFile, 'html');

					if ($extension == 'twig')
					{
						$extension = 'html';
					}

					HeaderHelper::setContentTypeByExtension($extension);
				}

				// Set the charset header
				HeaderHelper::setHeader(array('charset' => 'utf-8'));

				// Are we serving HTML or XHTML?
				if (in_array(HeaderHelper::getMimeType(), array('text/html', 'application/xhtml+xml')))
				{
					// Are there any head/foot nodes left in the queue?
					$headHtml = craft()->templates->getHeadHtml();
					$footHtml = craft()->templates->getFootHtml();

					if ($headHtml)
					{
						if (($endHeadPos = mb_stripos($output, '</head>')) !== false)
						{
							$output = mb_substr($output, 0, $endHeadPos).$headHtml.mb_substr($output, $endHeadPos);
						}
						else
						{
							$output .= $headHtml;
						}
					}

					if ($footHtml)
					{
						if (($endBodyPos = mb_stripos($output, '</body>')) !== false)
						{
							$output = mb_substr($output, 0, $endBodyPos).$footHtml.mb_substr($output, $endBodyPos);
						}
						else
						{
							$output .= $footHtml;
						}
					}
				}

				// Output it into a buffer, in case TasksService wants to close the connection prematurely
				ob_start();
				echo $output;

				// End the request
				craft()->end();
			}
		}
		else
		{
			throw new HttpException(404);
		}
	}

	/**
	 * Redirects user to the login template if they're not logged in.
	 *
	 * @return null
	 */
	public function requireLogin()
	{
		if (craft()->userSession->isGuest())
		{
			craft()->userSession->requireLogin();
		}
	}

	/**
	 * Requires the current user to be logged in as an admin.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAdmin()
	{
		if (!craft()->userSession->isAdmin())
		{
			throw new HttpException(403, Craft::t('This action may only be performed by admins.'));
		}
	}

	/**
	 * Returns a 400 if this isn't a POST request
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requirePostRequest()
	{
		if (craft()->request->getRequestType() !== 'POST')
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Returns a 400 if this isn't an Ajax request.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireAjaxRequest()
	{
		if (!craft()->request->isAjaxRequest())
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Requires the current request to include a token.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function requireToken()
	{
		if (!craft()->request->getQuery(craft()->config->get('tokenParam')))
		{
			throw new HttpException(400);
		}
	}

	/**
	 * Redirect
	 *
	 * @param      $url
	 * @param bool $terminate
	 * @param int  $statusCode
	 *
	 * @return null
	 */
	public function redirect($url, $terminate = true, $statusCode = 302)
	{
		if (is_string($url))
		{
			$url = UrlHelper::getUrl($url);
		}

		if ($url !== null)
		{
			parent::redirect($url, $terminate, $statusCode);
		}
	}

	/**
	 * Redirects to the URI specified in the POST.
	 *
	 * @param mixed  $object  Object containing properties that should be parsed for in the URL.
	 * @param string $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
	 *                        null, then the current request’s path will be used.
	 *
	 * @return null
	 */
	public function redirectToPostedUrl($object = null, $default = null)
	{
		$url = craft()->request->getPost('redirect');

		if ($url === null)
		{
			if ($default !== null)
			{
				$url = $default;
			}
			else
			{
				$url = craft()->request->getPath();
			}
		}

		if ($object)
		{
			$url = craft()->templates->renderObjectTemplate($url, $object);
		}

		$this->redirect($url);
	}

	/**
	 * Respond with JSON
	 *
	 * @param array|null $var The array to JSON-encode and return
	 *
	 * @return null
	 */
	public function returnJson($var = array())
	{
		JsonHelper::sendJsonHeaders();

		// Output it into a buffer, in case TasksService wants to close the connection prematurely
		ob_start();
		echo JsonHelper::encode($var);

		craft()->end();
	}

	/**
	 * Respond with a JSON error message
	 *
	 * @param string $error The error message
	 *
	 * @return null
	 */
	public function returnErrorJson($error)
	{
		$this->returnJson(array('error' => $error));
	}

	/**
	 * Checks if a controller has overridden allowAnonymous either as an array with actions to allow anonymous access
	 * to or as a bool that applies to all actions.
	 *
	 * @param \CAction $action
	 *
	 * @return bool
	 */
	public function beforeAction($action)
	{
		if (is_array($this->allowAnonymous))
		{
			if (!preg_grep("/{$this->getAction()->id}/i", $this->allowAnonymous))
			{
				craft()->userSession->requireLogin();
			}
		}
		elseif (is_bool($this->allowAnonymous))
		{
			if ($this->allowAnonymous == false)
			{
				craft()->userSession->requireLogin();
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function filters()
	{

	}
}
