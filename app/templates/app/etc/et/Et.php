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
	 * The `$options` parameter takes an associative array with the following
	 * options:
	 *
	 * - `timeout`: How long should we wait for a response? (integer, seconds, default: 10)
	 * - `useragent`: Useragent to send to the server (string, default: php-requests/$version)
	 * - `follow_redirects`: Should we follow 3xx redirects? (boolean, default: true)
	 * - `redirects`: How many times should we redirect before erroring? (integer, default: 10)
	 * - `blocking`: Should we block processing on this request? (boolean, default: true)
	 * - `filename`: File to stream the body to instead. (string|boolean, default: false)
	 * - `auth`: Authentication handler or array of user/password details to use for Basic authentication (RequestsAuth|array|boolean, default: false)
	 * - `idn`: Enable IDN parsing (boolean, default: true)
	 * - `transport`: Custom transport. Either a class name, or a transport object. Defaults to the first working transport from {@see getTransport()} (string|RequestsTransport, default: {@see getTransport()})
	 *
	 */
class Et
{
	private $_endpoint;
	private $_timeout;
	private $_model;
	private $_allowRedirects = true;
	private $_userAgent;
	private $_destinationFileName;

	/**
	 * The maximum number of seconds to allow for an entire transfer to take place before timing out.  Set 0 to wait indefinitely.
	 *
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->_timeout;
	}

	/**
	 * The maximum number of seconds to wait while trying to connect. Set to 0 to wait indefinitely.
	 *
	 * @return int
	 */
	public function getConnectTimeout()
	{
		return $this->_connectTimeout;
	}

	/**
	 * Whether or not to follow redirects on the request.  Defaults to true.
	 *
	 * @param $allowRedirects
	 * @return void
	 */
	public function setAllowRedirects($allowRedirects)
	{
		$this->_allowRedirects = $allowRedirects;
	}

	/**
	 * @return bool
	 */
	public function getAllowRedirects()
	{
		return $this->_allowRedirects;
	}

	/**
	 * @param $destinationFileName
	 * @return void
	 */
	public function setDestinationFileName($destinationFileName)
	{
		$this->_destinationFileName = $destinationFileName;
	}

	/**
	 * @param     $endpoint
	 * @param int $timeout
	 * @param int $connectTimeout
	 */
	function __construct($endpoint, $timeout = 30, $connectTimeout = 2)
	{
		$endpoint .= craft()->config->get('endpointSuffix');

		$this->_endpoint = $endpoint;
		$this->_timeout = $timeout;
		$this->_connectTimeout = $connectTimeout;

		$this->_model = new EtModel(array(
			'licenseKey'        => $this->_getLicenseKey(),
			'requestUrl'        => craft()->request->getHostInfo().craft()->request->getUrl(),
			'requestIp'         => craft()->request->getIpAddress(),
			'requestTime'       => DateTimeHelper::currentTimeStamp(),
			'requestPort'       => craft()->request->getPort(),
			'localBuild'        => CRAFT_BUILD,
			'localVersion'      => CRAFT_VERSION,
			'localEdition'      => craft()->getEdition(),
			'userEmail'         => craft()->userSession->getUser()->email,
			'track'             => CRAFT_TRACK,
		));

		$this->_userAgent = 'Craft/'.craft()->getVersion().'.'.craft()->getBuild();
	}

	/**
	 * @return EtModel
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Sets Custom Data on the EtModel.
	 */
	public function setData($data)
	{
		$this->_model->data = $data;
	}

	/**
	 * @throws EtException|\Exception
	 * @return EtModel|null
	 */
	public function phoneHome()
	{
		try
		{
			$missingLicenseKey = empty($this->_model->licenseKey);

			// No craft/config/license.key file and we can't even write to the config folder.  Don't even make the call home.
			if ($missingLicenseKey && !$this->_isConfigFolderWritable())
			{
				throw new EtException('Craft needs to be able to write to your “craft/config” folder and it can’t.', 10001);
			}

			if (!craft()->cache->get('etConnectFailure'))
			{
				$data = JsonHelper::encode($this->_model->getAttributes(null, true));

				$client = new \Guzzle\Http\Client();
				$client->setUserAgent($this->_userAgent, true);

				$options = array(
					'timeout'         => $this->getTimeout(),
					'connect_timeout' => $this->getConnectTimeout(),
					'allow_redirects' => $this->getAllowRedirects(),
				);

				$request = $client->post($this->_endpoint, $options);

				$request->setBody($data, 'application/json');
				$response = $request->send();

				if ($response->isSuccessful())
				{
					// Clear the connection failure cached item if it exists.
					if (craft()->cache->get('etConnectFailure'))
					{
						craft()->cache->delete('etConnectFailure');
					}

					if ($this->_destinationFileName)
					{
						$body = $response->getBody();

						// Make sure we're at the beginning of the stream.
						$body->rewind();

						// Write it out to the file
						IOHelper::writeToFile($this->_destinationFileName, $body->getStream(), true);

						// Close the stream.
						$body->close();

						return IOHelper::getFileName($this->_destinationFileName);
					}

					$etModel = craft()->et->decodeEtModel($response->getBody());

					if ($etModel)
					{
						if ($missingLicenseKey && !empty($etModel->licenseKey))
						{
							$this->_setLicenseKey($etModel->licenseKey);
						}

						// Cache the license key status and which edition it has
						craft()->cache->set('licenseKeyStatus', $etModel->licenseKeyStatus);
						craft()->cache->set('licensedEdition', $etModel->licensedEdition);
						craft()->cache->set('editionTestableDomain@'.craft()->request->getHostName(), $etModel->editionTestableDomain ? 1 : 0);

						if ($etModel->licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
						{
							craft()->cache->set('licensedDomain', $etModel->licensedDomain);
						}

						return $etModel;
					}
					else
					{
						Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), LogLevel::Warning);

						if (craft()->cache->get('etConnectFailure'))
						{
							// There was an error, but at least we connected.
							craft()->cache->delete('etConnectFailure');
						}
					}
				}
				else
				{
					Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), LogLevel::Warning);

					if (craft()->cache->get('etConnectFailure'))
					{
						// There was an error, but at least we connected.
						craft()->cache->delete('etConnectFailure');
					}
				}
			}
		}
		// Let's log and rethrow any EtExceptions.
		catch (EtException $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);

			if (craft()->cache->get('etConnectFailure'))
			{
				// There was an error, but at least we connected.
				craft()->cache->delete('etConnectFailure');
			}

			throw $e;
		}
		catch (\Exception $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);

			// Cache the failure for 5 minutes so we don't try again.
			craft()->cache->set('etConnectFailure', true, 300);
		}

		return null;
	}

	/**
	 * @return null|string
	 */
	private function _getLicenseKey()
	{
		$licenseKeyPath = craft()->path->getLicenseKeyPath();

		if (($keyFile = IOHelper::fileExists($licenseKeyPath)) !== false)
		{
			return trim(preg_replace('/[\r\n]+/', '', IOHelper::getFileContents($keyFile)));
		}

		return null;
	}

	/**
	 * @param $key
	 * @return bool
	 * @throws Exception|EtException
	 */
	private function _setLicenseKey($key)
	{
		// Make sure the key file does not exist first. Et will never overwrite a license key.
		if (($keyFile = IOHelper::fileExists(craft()->path->getLicenseKeyPath())) == false)
		{
			$keyFile = craft()->path->getLicenseKeyPath();

			if ($this->_isConfigFolderWritable())
			{
				preg_match_all("/.{50}/", $key, $matches);

				$formattedKey = '';
				foreach ($matches[0] as $segment)
				{
					$formattedKey .= $segment.PHP_EOL;
				}

				return IOHelper::writeToFile($keyFile, $formattedKey);
			}

			throw new EtException('Craft needs to be able to write to your “craft/config” folder and it can’t.', 10001);
		}

		throw new Exception(Craft::t('Cannot overwrite an existing license.key file.'));
	}

	/**
	 * @return bool
	 */
	private function _isConfigFolderWritable()
	{
	 return IOHelper::isWritable(IOHelper::getFolderName(craft()->path->getLicenseKeyPath()));
	}
}
