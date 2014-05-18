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
 * Handles settings from the control panel.
 */
class SystemSettingsController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// All System Settings actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Shows the settings index.
	 */
	public function actionSettingsIndex()
	{
		// Get all the tools
		$tools = craft()->components->getComponentsByType(ComponentType::Tool);
		ksort($tools);

		// If there are no Asset sources, don't display the update Asset indexes tool.
		if (count(craft()->assetSources->getAllSources()) == 0)
		{
			unset($tools['AssetIndex']);
		}

		$variables['tools'] = ToolVariable::populateVariables($tools);

		$this->renderTemplate('settings/index', $variables);
	}

	/**
	 * Shows the general settings form.
	 *
	 * @param array $variables
	 */
	public function actionGeneralSettings(array $variables = array())
	{
		if (empty($variables['info']))
		{
			$variables['info'] = craft()->getInfo();
		}

		// Assemble the timezone options array
		// (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
		$variables['timezoneOptions'] = array();

		$utc = new DateTime();
		$offsets = array();
		$timezoneIds = array();
		$includedAbbrs = array();

		foreach (\DateTimeZone::listIdentifiers() as $timezoneId)
		{
			$timezone = new \DateTimeZone($timezoneId);
			$transition =  $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
			$abbr = $transition[0]['abbr'];

			$offset = round($timezone->getOffset($utc) / 60);

			if ($offset)
			{
				$hour = floor($offset / 60);
				$minutes = floor(abs($offset) % 60);

				$format = sprintf('%+d', $hour);

				if ($minutes)
				{
					$format .= ':'.sprintf('%02u', $minutes);
				}
			}
			else
			{
				$format = '';
			}

			$offsets[] = $offset;
			$timezoneIds[] = $timezoneId;
			$includedAbbrs[] = $abbr;
			$variables['timezoneOptions'][$timezoneId] = 'UTC'.$format.($abbr != 'UTC' ? " ({$abbr})" : '').($timezoneId != 'UTC' ? ' - '.$timezoneId : '');
		}

		array_multisort($offsets, $timezoneIds, $variables['timezoneOptions']);

		$this->renderTemplate('settings/general/index', $variables);
	}

	/**
	 * Saves the general settings.
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$info = craft()->getInfo();

		$info->on          = (bool) craft()->request->getPost('on');
		$info->siteName    = craft()->request->getPost('siteName');
		$info->siteUrl     = craft()->request->getPost('siteUrl');
		$info->timezone    = craft()->request->getPost('timezone');

		if (craft()->saveInfo($info))
		{
			craft()->userSession->setNotice(Craft::t('General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save general settings.'));

			// Send the info back to the template
			craft()->urlManager->setRouteVariables(array(
				'info' => $info
			));
		}
	}

	/**
	 * Saves the email settings.
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$settings = $this->_getEmailSettingsFromPost();

		// If $settings is an instance of EmailSettingsModel, there were validation errors.
		if (!$settings instanceof EmailSettingsModel)
		{
			if (craft()->systemSettings->saveSettings('email', $settings))
			{
				craft()->userSession->setNotice(Craft::t('Email settings saved.'));
				$this->redirectToPostedUrl();
			}
		}

		craft()->userSession->setError(Craft::t('Couldn’t save email settings.'));

		// Send the settings back to the template
		craft()->urlManager->setRouteVariables(array(
			'settings' => $settings
		));
	}

	/**
	 * Tests the email settings.
	 */
	public function actionTestEmailSettings()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$settings = $this->_getEmailSettingsFromPost();

		// If $settings is an instance of EmailSettingsModel, there were validation errors.
		if (!$settings instanceof EmailSettingsModel)
		{
			try
			{
				if (craft()->email->sendTestEmail($settings))
				{
					$this->returnJson(array('success' => true));
				}
			}
			catch (\Exception $e)
			{
				Craft::log($e->getMessage(), LogLevel::Error);
			}
		}

		$this->returnErrorJson(Craft::t('There was an error testing your email settings.'));
	}

	/**
	 * Global Set edit form.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditGlobalSet(array $variables = array())
	{
		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Settings'), 'url' => UrlHelper::getUrl('settings')),
			array('label' => Craft::t('Globals'),  'url' => UrlHelper::getUrl('settings/globals'))
		);

		// Tabs
		$variables['tabs'] = array(
			'settings'    => array('label' => Craft::t('Settings'),     'url' => '#set-settings'),
			'fieldlayout' => array('label' => Craft::t('Field Layout'), 'url' => '#set-fieldlayout')
		);

		if (empty($variables['globalSet']))
		{
			if (!empty($variables['globalSetId']))
			{
				$variables['globalSet'] = craft()->globals->getSetById($variables['globalSetId']);

				if (!$variables['globalSet'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['globalSet'] = new GlobalSetModel();
			}
		}

		if ($variables['globalSet']->id)
		{
			$variables['title'] = $variables['globalSet']->name;
		}
		else
		{
			$variables['title'] = Craft::t('Create a new global set');
		}

		// Render the template!
		$this->renderTemplate('settings/globals/_edit', $variables);
	}

	/**
	 * Returns the email settings from the post data.
	 *
	 * @access private
	 * @return array
	 */
	private function _getEmailSettingsFromPost()
	{
		$emailSettings = new EmailSettingsModel();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = craft()->request->getPost('protocol');
		$emailSettings->host                        = craft()->request->getPost('host');
		$emailSettings->port                        = craft()->request->getPost('port');
		$emailSettings->smtpAuth                    = (bool)craft()->request->getPost('smtpAuth');

		if ($emailSettings->smtpAuth && $emailSettings->protocol !== EmailerType::Gmail)
		{
			$emailSettings->username                = craft()->request->getPost('smtpUsername');
			$emailSettings->password                = craft()->request->getPost('smtpPassword');
		}
		else
		{
			$emailSettings->username                = craft()->request->getPost('username');
			$emailSettings->password                = craft()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (bool)craft()->request->getPost('smtpKeepAlive');
		$emailSettings->smtpSecureTransportType     = craft()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = craft()->request->getPost('timeout');
		$emailSettings->emailAddress                = craft()->request->getPost('emailAddress');
		$emailSettings->senderName                  = craft()->request->getPost('senderName');

		// Validate user input
		if (!$emailSettings->validate())
		{
			return $emailSettings;
		}

		$settings['protocol']     = $emailSettings->protocol;
		$settings['emailAddress'] = $emailSettings->emailAddress;
		$settings['senderName']   = $emailSettings->senderName;

		if (craft()->getEdition() >= Craft::Client)
		{
			$settings['template'] = craft()->request->getPost('template');
		}

		switch ($emailSettings->protocol)
		{
			case EmailerType::Smtp:
			{
				if ($emailSettings->smtpAuth)
				{
					$settings['smtpAuth'] = 1;
					$settings['username'] = $emailSettings->username;
					$settings['password'] = $emailSettings->password;
				}

				$settings['smtpSecureTransportType'] = $emailSettings->smtpSecureTransportType;

				$settings['port'] = $emailSettings->port;
				$settings['host'] = $emailSettings->host;
				$settings['timeout'] = $emailSettings->timeout;

				if ($emailSettings->smtpKeepAlive)
				{
					$settings['smtpKeepAlive'] = 1;
				}

				break;
			}

			case EmailerType::Pop:
			{
				$settings['port'] = $emailSettings->port;
				$settings['host'] = $emailSettings->host;
				$settings['username'] = $emailSettings->username;
				$settings['password'] = $emailSettings->password;
				$settings['timeout'] = $emailSettings->timeout;

				break;
			}

			case EmailerType::Gmail:
			{
				$settings['host'] = $gMailSmtp;
				$settings['smtpAuth'] = 1;
				$settings['smtpSecureTransportType'] = 'ssl';
				$settings['username'] = $emailSettings->username;
				$settings['password'] = $emailSettings->password;
				$settings['port'] = $emailSettings->smtpSecureTransportType == 'tls' ? '587' : '465';
				$settings['timeout'] = $emailSettings->timeout;
				break;
			}
		}

		return $settings;
	}
}
