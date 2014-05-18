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
class CraftTwigExtension extends \Twig_Extension
{
	private $_classMethods;

	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
	 */
	public function getTokenParsers()
	{
		return array(
			new Cache_TokenParser(),
			new Exit_TokenParser(),
			new Header_TokenParser(),
			new Hook_TokenParser(),
			new IncludeResource_TokenParser('includeCss'),
			new IncludeResource_TokenParser('includeCssFile'),
			new IncludeResource_TokenParser('includeCssResource'),
			new IncludeResource_TokenParser('includeHiResCss'),
			new IncludeResource_TokenParser('includeJs'),
			new IncludeResource_TokenParser('includeJsFile'),
			new IncludeResource_TokenParser('includeJsResource'),
			new IncludeTranslations_TokenParser(),
			new Namespace_TokenParser(),
			new Nav_TokenParser(),
			new Paginate_TokenParser(),
			new Redirect_TokenParser(),
			new RequireEdition_TokenParser(),
			new RequireLogin_TokenParser(),
			new RequirePermission_TokenParser(),
			new Switch_TokenParser(),
		);
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		$translateFilter = new \Twig_Filter_Function('\Craft\Craft::t');
		$namespaceFilter = new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputs');
		$markdownFilter = new \Twig_Filter_Method($this, 'markdownFilter');

		return array(
			'currency'           => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatCurrency'),
			'datetime'           => new \Twig_Filter_Function('\Craft\craft()->dateFormatter->formatDateTime'),
			'filesize'	         => new \Twig_Filter_Function('\Craft\craft()->formatter->formatSize'),
			'filter'             => new \Twig_Filter_Function('array_filter'),
			'group'              => new \Twig_Filter_Method($this, 'groupFilter'),
			'indexOf'            => new \Twig_Filter_Method($this, 'indexOfFilter'),
			'intersect'          => new \Twig_Filter_Function('array_intersect'),
			'lcfirst'            => new \Twig_Filter_Function('lcfirst'),
			'markdown'           => $markdownFilter,
			'md'                 => $markdownFilter,
			'namespace'          => $namespaceFilter,
			'ns'                 => $namespaceFilter,
			'namespaceInputName' => new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputName'),
			'namespaceInputId'   => new \Twig_Filter_Function('\Craft\craft()->templates->namespaceInputId'),
			'number'             => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatDecimal'),
			'parseRefs'          => new \Twig_Filter_Method($this, 'parseRefsFilter'),
			'percentage'         => new \Twig_Filter_Function('\Craft\craft()->numberFormatter->formatPercentage'),
			'replace'            => new \Twig_Filter_Method($this, 'replaceFilter'),
			'translate'          => $translateFilter,
			't'                  => $translateFilter,
			'ucfirst'            => new \Twig_Filter_Function('ucfirst'),
			'ucwords'            => new \Twig_Filter_Function('ucwords'),
			'without'            => new \Twig_Filter_Method($this, 'withoutFilter'),
		);
	}

	/**
	 * Returns an array without certain values.
	 *
	 * @param array $arr
	 * @param mixed $exclude
	 * @return array
	 */
	public function withoutFilter($arr, $exclude)
	{
		$filteredArray = array();

		if (!is_array($exclude))
		{
			$exclude = array($exclude);
		}

		foreach ($arr as $key => $value)
		{
			if (!in_array($value, $exclude))
			{
				$filteredArray[$key] = $value;
			}
		}

		return $filteredArray;
	}

	/**
	 * Parses a string for refernece tags.
	 *
	 * @param string $str
	 * @return \Twig_Markup
	 */
	public function parseRefsFilter($str)
	{
		$str = craft()->elements->parseRefs($str);
		return TemplateHelper::getRaw($str);
	}

	/**
	 * Replacecs Twig's |replace filter, adding support for passing in separate search and replace arrays.
	 *
	 * @param mixed $str
	 * @param mixed $search
	 * @param mixed $replace
	 * @return mixed
	 */
	public function replaceFilter($str, $search, $replace = null)
	{
		// Are they using the standard Twig syntax?
		if (is_array($search) && $replace === null)
		{
			return strtr($str, $search);
		}
		else
		{
			// Otherwise use str_replace
			return str_replace($search, $replace, $str);
		}
	}

	/**
	 * Groups an array by a common property.
	 *
	 * @param array $arr
	 * @param string $item
	 * @return array
	 */
	public function groupFilter($arr, $item)
	{
		$groups = array();

		$template = '{'.$item.'}';

		foreach ($arr as $key => $object)
		{
			$value = craft()->templates->renderObjectTemplate($template, $object);
			$groups[$value][] = $object;
		}

		return $groups;
	}

	/**
	 * Returns the index of an item in a string or array, or -1 if it cannot be found.
	 *
	 * @param mixed $haystack
	 * @param mixed $needle
	 * @return int
	 */
	public function indexOfFilter($haystack, $needle)
	{
		if (is_string($haystack))
		{
			$index = strpos($haystack, $needle);
		}
		else if (is_array($haystack))
		{
			$index = array_search($needle, $haystack);
		}
		else if (is_object($haystack) && $haystack instanceof \IteratorAggregate)
		{
			$index = false;

			foreach ($haystack as $i => $item)
			{
				if ($item == $needle)
				{
					$index = $i;
					break;
				}
			}
		}

		if ($index !== false)
		{
			return $index;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Parses text through Markdown.
	 *
	 * @param string $str
	 * @return \Twig_Markup
	 */
	public function markdownFilter($str)
	{
		$html = StringHelper::parseMarkdown($str);
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return array An array of functions
	 */
	public function getFunctions()
	{
		return array(
			'actionUrl'            => new \Twig_Function_Function('\Craft\UrlHelper::getActionUrl'),
			'cpUrl'                => new \Twig_Function_Function('\Craft\UrlHelper::getCpUrl'),
			'ceil'                 => new \Twig_Function_Function('ceil'),
			'floor'                => new \Twig_Function_Function('floor'),
			'getHeadHtml'          => new \Twig_Function_Method($this, 'getHeadHtmlFunction'),
			'getFootHtml'          => new \Twig_Function_Method($this, 'getFootHtmlFunction'),
			'getTranslations'      => new \Twig_Function_Function('\Craft\craft()->templates->getTranslations'),
			'max'                  => new \Twig_Function_Function('max'),
			'min'                  => new \Twig_Function_Function('min'),
			'renderObjectTemplate' => new \Twig_Function_Function('\Craft\craft()->templates->renderObjectTemplate'),
			'round'                => new \Twig_Function_Function('round'),
			'resourceUrl'          => new \Twig_Function_Function('\Craft\UrlHelper::getResourceUrl'),
			'shuffle'              => new \Twig_Function_Method($this, 'shuffleFunction'),
			'siteUrl'              => new \Twig_Function_Function('\Craft\UrlHelper::getSiteUrl'),
			'url'                  => new \Twig_Function_Function('\Craft\UrlHelper::getUrl'),
		);
	}

	/**
	 * Returns getHeadHtml() wrapped in a Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getHeadHtmlFunction()
	{
		$html = craft()->templates->getHeadHtml();
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Returns getFootHtml() wrapped in a Twig_Markup object.
	 *
	 * @return \Twig_Markup
	 */
	public function getFootHtmlFunction()
	{
		$html = craft()->templates->getFootHtml();
		return TemplateHelper::getRaw($html);
	}

	/**
	 * Shuffles an array.
	 *
	 * @param mixed $arr
	 * @return mixed
	 */
	public function shuffleFunction($arr)
	{
		if ($arr instanceof \Traversable)
		{
			$arr = iterator_to_array($arr, false);
		}
		else
		{
			$arr = array_merge($arr);
		}

		shuffle($arr);

		return $arr;
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array An array of global variables
	 */
	public function getGlobals()
	{
		// Keep the 'blx' variable around for now
		$craftVariable = new CraftVariable();
		$globals['craft'] = $craftVariable;
		$globals['blx']   = $craftVariable;

		$globals['now'] = DateTimeHelper::currentUTCDateTime();
		$globals['loginUrl'] = UrlHelper::getUrl(craft()->config->getLoginPath());
		$globals['logoutUrl'] = UrlHelper::getUrl(craft()->config->getLogoutPath());

		if (craft()->isInstalled() && !craft()->updates->isCraftDbMigrationNeeded())
		{
			$globals['siteName'] = craft()->getSiteName();
			$globals['siteUrl'] = craft()->getSiteUrl();

			$globals['currentUser'] = craft()->userSession->getUser();

			// Keep 'user' around so long as it's not hurting anyone.
			// Technically deprecated, though.
			$globals['user'] = $globals['currentUser'];

			if (craft()->request->isSiteRequest())
			{
				foreach (craft()->globals->getAllSets() as $globalSet)
				{
					$globals[$globalSet->handle] = $globalSet;
				}
			}
		}
		else
		{
			$globals['siteName'] = null;
			$globals['siteUrl'] = null;
			$globals['user'] = null;
		}

		if (craft()->request->isCpRequest())
		{
			$globals['CraftEdition']  = craft()->getEdition();
			$globals['CraftPersonal'] = Craft::Personal;
			$globals['CraftClient']   = Craft::Client;
			$globals['CraftPro']      = Craft::Pro;
		}

		return $globals;
	}

	/**
	 * Returns the name of the extension.
	 *
	 * @return string The extension name
	 */
	public function getName()
	{
		return 'craft';
	}
}
