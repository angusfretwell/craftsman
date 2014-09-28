<?php
namespace Craft;

/**
 * Class TestApplication
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.tests
 * @since     1.0
 */
class TestApplication extends WebApp
{
	// Public Methods
	// =========================================================================

	/**
	 * @param null $config
	 *
	 * @return TestApplication
	 */
	public function __construct($config = null)
	{
		Craft::setApplication(null);
		clearstatcache();

		// SHOW EVERYTHING
		error_reporting(E_ALL & ~E_STRICT);
		ini_set('display_errors', 1);

		mb_internal_encoding('UTF-8');
		mb_http_input('UTF-8');
		mb_http_output('UTF-8');
		mb_detect_order('auto');

		// No matter how much you want to delete this line... DO NOT DO IT.
		Craft::$enableIncludePath = false;

		parent::__construct($config);
	}

	/**
	 * @return null
	 */
	public function loadGlobalState()
	{
		parent::loadGlobalState();
	}

	/**
	 * @return null
	 */
	public function saveGlobalState()
	{
		parent::saveGlobalState();
	}
}
