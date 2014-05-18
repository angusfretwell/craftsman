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
class RichTextFieldType extends BaseFieldType
{
	private static $_includedFieldResources = false;
	private static $_redactorLang = 'en';

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Rich Text');
	}

	/**
	 * Defines the settings.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'configFile'  => AttributeType::String,
			'cleanupHtml' => array(AttributeType::Bool, 'default' => true),
		);
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		$configOptions = array('' => Craft::t('Default'));
		$configPath = craft()->path->getConfigPath().'redactor/';

		if (IOHelper::folderExists($configPath))
		{
			$configFiles = IOHelper::getFolderContents($configPath, false, '\.json$');

			if (is_array($configFiles))
			{
				foreach ($configFiles as $file)
				{
					$configOptions[IOHelper::getFileName($file)] = IOHelper::getFileName($file, false);
				}
			}
		}

		return craft()->templates->render('_components/fieldtypes/RichText/settings', array(
			'settings' => $this->getSettings(),
			'configOptions' => $configOptions
		));
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return array(AttributeType::String, 'column' => ColumnType::Text);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValue($value)
	{
		if ($value)
		{
			// Prevent everyone from having to use the |raw filter when outputting RTE content
			$charset = craft()->templates->getTwig()->getCharset();
			return new RichTextData($value, $charset);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$this->_includeFieldResources();

		$id = craft()->templates->formatInputId($name);

		craft()->templates->includeJs('new Craft.RichTextInput(' .
			'"'.craft()->templates->namespaceInputId($id).'", ' .
			JsonHelper::encode($this->_getSectionSources()).', ' .
			'"'.(isset($this->element) ? $this->element->locale : craft()->language).'", ' .
			$this->_getConfigJs().', ' .
			'"'.static::$_redactorLang.'"' .
		');');

		if ($value instanceof RichTextData)
		{
			$value = $value->getRawContent();
		}

		if (strpos($value, '{') !== false)
		{
			// Preserve the ref tags with hashes
			// {type:id:url} => {type:id:url}#type:id
			$value = preg_replace_callback('/(href=|src=)([\'"])(\{(\w+\:\d+\:'.HandleValidator::$handlePattern.')\})\2/', function($matches)
			{
				return $matches[1].$matches[2].$matches[3].'#'.$matches[4].$matches[2];
			}, $value);

			// Now parse 'em
			$value = craft()->elements->parseRefs($value);
		}

		// Swap any <!--pagebreak-->'s with <hr>'s
		$value = str_replace('<!--pagebreak-->', '<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false" />', $value);

		return '<textarea id="'.$id.'" name="'.$name.'" style="display: none">'.htmlentities($value, ENT_NOQUOTES, 'UTF-8').'</textarea>';
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{
		if ($value)
		{
			// Swap any pagebreak <hr>'s with <!--pagebreak-->'s
			$value = preg_replace('/<hr class="redactor_pagebreak" style="display:none" unselectable="on" contenteditable="false"\s*(\/)?>/', '<!--pagebreak-->', $value);

			if ($this->getSettings()->cleanupHtml)
			{
				// Remove <span> and <font> tags
				$value = preg_replace('/<(?:span|font)\b[^>]*>/', '', $value);
				$value = preg_replace('/<\/(?:span|font)>/', '', $value);

				// Remove inline styles
				$value = preg_replace('/(<(?:h1|h2|h3|h4|h5|h6|p|div|blockquote|pre|strong|em|b|i|u|a)\b[^>]*)\s+style="[^"]*"/', '$1', $value);

				// Remove empty tags
				// (Leave empty <a> tags though, since they might be defining a standalone #anchor.)
				$value = preg_replace('/<(h1|h2|h3|h4|h5|h6|p|div|blockquote|pre|strong|em|b|i|u)\s*><\/\1>/', '', $value);
			}
		}

		// Find any element URLs and swap them with ref tags
		$value = preg_replace_callback('/(href=|src=)([\'"])[^\'"]+?#(\w+):(\d+)(:'.HandleValidator::$handlePattern.')?\2/', function($matches)
		{
			return $matches[1].$matches[2].'{'.$matches[3].':'.$matches[4].(!empty($matches[5]) ? $matches[5] : ':url').'}'.$matches[2];
		}, $value);

		return $value;
	}

	/**
	 * Get available section sources.
	 *
	 * @return array
	 */
	private function _getSectionSources()
	{
		$sources = array();
		$sections = craft()->sections->getAllSections();
		$showSingles = false;

		foreach ($sections as $section)
		{
			if ($section->type == SectionType::Single)
			{
				$showSingles = true;
			}
			else if ($section->hasUrls)
			{
				$sources[] = 'section:' . $section->id;
			}
		}

		if ($showSingles)
		{
			array_unshift($sources, 'singles');
		}

		return $sources;
	}

	/**
	 * Returns the Redactor config JS used by this field.
	 *
	 * @return string
	 */
	private function _getConfigJs()
	{
		if ($this->getSettings()->configFile)
		{
			$configPath = craft()->path->getConfigPath().'redactor/'.$this->getSettings()->configFile;
			$js = IOHelper::getFileContents($configPath);
		}

		if (empty($js))
		{
			$js = '{}';
		}

		return $js;
	}

	/**
	 * Includes the input resources.
	 *
	 * @access private
	 */
	private function _includeFieldResources()
	{
		craft()->templates->includeCssResource('lib/redactor/redactor.css');
		craft()->templates->includeCssResource('lib/redactor/plugins/pagebreak.css');

		// Gotta use the uncompressed Redactor JS until the compressed one gets our Live Preview menu fix
		craft()->templates->includeJsResource('lib/redactor/redactor.js');
		//craft()->templates->includeJsResource('lib/redactor/redactor'.(craft()->config->get('useCompressedJs') ? '.min' : '').'.js');

		craft()->templates->includeJsResource('lib/redactor/plugins/fullscreen.js');
		craft()->templates->includeJsResource('lib/redactor/plugins/pagebreak.js');

		craft()->templates->includeTranslations('Insert image', 'Insert URL', 'Choose image', 'Link', 'Link to an entry', 'Insert link', 'Unlink', 'Link to an asset');

		craft()->templates->includeJsResource('js/RichTextInput.js');

		// Check to see if the Redactor has been translated into the current locale
		if (craft()->language != craft()->sourceLanguage)
		{
			// First try to include the actual target locale
			if (!$this->_includeRedactorLangFile(craft()->language))
			{
				// Otherwise try to load the language (without the territory half)
				$languageId = craft()->locale->getLanguageID(craft()->language);
				$this->_includeRedactorLangFile($languageId);
			}
		}
	}

	/**
	 * Attempts to include a Redactor language file.
	 *
	 * @access private
	 * @param string $lang
	 * @return bool
	 */
	private function _includeRedactorLangFile($lang)
	{
		$path = 'lib/redactor/lang/'.$lang.'.js';

		if (IOHelper::fileExists(craft()->path->getResourcesPath().$path))
		{
			craft()->templates->includeJsResource($path);
			static::$_redactorLang = $lang;

			return true;
		}
		else
		{
			return false;
		}
	}
}
