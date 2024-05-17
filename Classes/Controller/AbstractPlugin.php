<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * (c) Gert Kaae Hansen, Simon Schaufelberger
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace GertKaaeHansen\GkhRssImport\Controller;

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class AbstractPlugin
{
    use DefaultJavaScriptAssetTrait;

    protected ?ContentObjectRenderer $cObj = null;

    /**
     * Should be same as classname of the plugin, used for CSS classes, variables
     *
     * @var string
     */
    protected $prefixId;

    /**
     * Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'
     *
     * @var string
     */
    protected $scriptRelPath;

    /**
     * Extension key.
     *
     * @var string
     */
    protected $extKey;

    /**
     * Local Language content
     *
     * @var array
     */
    protected $LOCAL_LANG = [];

    /**
     * Contains those LL keys, which have been set to (empty) in TypoScript.
     * This is necessary, as we cannot distinguish between a nonexisting
     * translation and a label that has been cleared by TS.
     * In both cases ['key'][0]['target'] is "".
     *
     * @var array
     */
    protected $LOCAL_LANG_UNSET = [];

    /**
     * Flag that tells if the locallang file has been fetch (or tried to
     * be fetched) already.
     *
     * @var bool
     */
    protected $LOCAL_LANG_loaded = false;

    /**
     * Pointer to the language to use.
     *
     * @var string
     */
    protected $LLkey = 'default';

    /**
     * Pointer to alternative fall-back language to use.
     *
     * @var string
     */
    protected $altLLkey = '';

    /**
     * You can set this during development to some value that makes it
     * easy for you to spot all labels that ARe delivered by the getLL function.
     *
     * @var string
     */
    protected $LLtestPrefix = '';

    /**
     * Save as LLtestPrefix, but additional prefix for the alternative value
     * in getLL() function calls
     *
     * @var string
     */
    protected $LLtestPrefixAlt = '';

    /**
     * Should normally be set in the main function with the TypoScript content passed to the method.
     *
     * $conf[LOCAL_LANG][_key_] is reserved for Local Language overrides.
     * $conf[userFunc] reserved for setting up the USER / USER_INT object. See TSref
     *
     * @var array
     */
    protected $conf = [];

    /**
     * Property for accessing TypoScriptFrontendController centrally
     */
    protected TypoScriptFrontendController $frontendController;

    protected MarkerBasedTemplateService $templateService;

    /**
     * Initializes $this->piVars if $this->prefixId is set to any value
     * Will also set $this->LLkey based on the config.language setting.
     *
     * @param null $_ unused,
     */
    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null)
    {
        $this->frontendController = $frontendController ?: $GLOBALS['TSFE'];
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $this->LLkey = $this->frontendController->getLanguage()->getTypo3Language();

        $locales = GeneralUtility::makeInstance(Locales::class);
        if (in_array($this->LLkey, $locales->getLocales())) {
            foreach ($locales->getLocaleDependencies($this->LLkey) as $language) {
                $this->altLLkey .= $language . ',';
            }
            $this->altLLkey = rtrim($this->altLLkey, ',');
        }
    }

    /**
     * This setter is called when the plugin is called from UserContentObject (USER)
     * via ContentObjectRenderer->callUserFunction().
     */
    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /***************************
     *
     * Stylesheet, CSS
     *
     **************************/

    /**
     * Returns a class-name prefixed with $this->prefixId and with all underscores substituted to dashes (-)
     *
     * @param string $class The class name (or the END of it since it will be prefixed by $this->prefixId.'-')
     * @return string The combined class name (with the correct prefix)
     */
    public function pi_getClassName($class)
    {
        return str_replace('_', '-', $this->prefixId) . ($this->prefixId ? '-' : '') . $class;
    }

    /**
     * Returns the class-attribute with the correctly prefixed classname
     * Using pi_getClassName()
     *
     * @param string $class The class name(s) (suffix) - separate multiple classes with commas
     * @param string $addClasses Additional class names which should not be prefixed - separate multiple classes with commas
     * @return string A "class" attribute with value and a single space char before it.
     */
    public function pi_classParam($class, $addClasses = '')
    {
        $output = '';
        $classNames = GeneralUtility::trimExplode(',', $class);
        foreach ($classNames as $className) {
            $output .= ' ' . $this->pi_getClassName($className);
        }
        $additionalClassNames = GeneralUtility::trimExplode(',', $addClasses);
        foreach ($additionalClassNames as $additionalClassName) {
            $output .= ' ' . $additionalClassName;
        }
        return ' class="' . trim($output) . '"';
    }

    /**
     * Wraps the input string in a <div> tag with the class attribute set to the prefixId.
     * All content returned from your plugins should be returned through this function so all content from your plugin is encapsulated in a <div>-tag nicely identifying the content of your plugin.
     *
     * @param string $str HTML content to wrap in the div-tags with the "main class" of the plugin
     * @return string HTML content wrapped, ready to return to the parent object.
     */
    public function pi_wrapInBaseClass($str)
    {
        $content = '<div class="' . str_replace('_', '-', $this->prefixId) . '">
		' . $str . '
	</div>
	';
        if (!($this->frontendController->config['config']['disablePrefixComment'] ?? false)) {
            return '
	<!--
		BEGIN: Content of extension "' . $this->extKey . '", plugin "' . $this->prefixId . '"
	-->
	' . $content . '
	<!-- END: Content of extension "' . $this->extKey . '", plugin "' . $this->prefixId . '" -->
	';
        }
        return $content;
    }

    /***************************
     *
     * Localization, locallang functions
     *
     **************************/

    /**
     * Returns the localized label of the LOCAL_LANG key, $key
     * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string $alternativeLabel Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
     * @return string|null The value from LOCAL_LANG.
     */
    public function pi_getLL($key, $alternativeLabel = '')
    {
        $word = null;
        if (
            !empty($this->LOCAL_LANG[$this->LLkey][$key][0]['target'])
            || isset($this->LOCAL_LANG_UNSET[$this->LLkey][$key])
        ) {
            $word = $this->LOCAL_LANG[$this->LLkey][$key][0]['target'];
        } elseif ($this->altLLkey !== '') {
            $alternativeLanguageKeys = GeneralUtility::trimExplode(',', $this->altLLkey, true);
            foreach ($alternativeLanguageKeys as $languageKey) {
                if (
                    !empty($this->LOCAL_LANG[$languageKey][$key][0]['target'])
                    || isset($this->LOCAL_LANG_UNSET[$languageKey][$key])
                ) {
                    // Alternative language translation for key exists
                    $word = $this->LOCAL_LANG[$languageKey][$key][0]['target'];
                    break;
                }
            }
        }
        if ($word === null) {
            if (
                !empty($this->LOCAL_LANG['default'][$key][0]['target'])
                || isset($this->LOCAL_LANG_UNSET['default'][$key])
            ) {
                // Get default translation (without charset conversion, english)
                $word = $this->LOCAL_LANG['default'][$key][0]['target'];
            } else {
                // Return alternative string or empty
                $word = $this->LLtestPrefixAlt !== '' ? $this->LLtestPrefixAlt . $alternativeLabel : $alternativeLabel;
            }
        }
        return $this->LLtestPrefix !== '' ? $this->LLtestPrefix . $word : $word;
    }

    /**
     * Loads local-language values from the file passed as a parameter or
     * by looking for a "locallang" file in the
     * plugin class directory ($this->scriptRelPath).
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are
     * merged onto the values found in the "locallang" file.
     * Supported file extensions xlf
     *
     * @param string $languageFilePath path to the plugin language file in format EXT:....
     */
    public function pi_loadLL($languageFilePath = '')
    {
        if ($this->LOCAL_LANG_loaded) {
            return;
        }

        if ($languageFilePath === '' && $this->scriptRelPath) {
            $languageFilePath = 'EXT:' . $this->extKey . '/' . PathUtility::dirname($this->scriptRelPath) . '/locallang.xlf';
        }
        if ($languageFilePath !== '') {
            $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
            $this->LOCAL_LANG = $languageFactory->getParsedData($languageFilePath, $this->LLkey);
            $alternativeLanguageKeys = GeneralUtility::trimExplode(',', $this->altLLkey, true);
            foreach ($alternativeLanguageKeys as $languageKey) {
                $tempLL = $languageFactory->getParsedData($languageFilePath, $languageKey);
                if ($this->LLkey === 'default') {
                    continue;
                }
                if (!isset($tempLL[$languageKey])) {
                    continue;
                }
                $this->LOCAL_LANG[$languageKey] = $tempLL[$languageKey];
            }
            // Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
            if (isset($this->conf['_LOCAL_LANG.'])) {
                // Clear the "unset memory"
                $this->LOCAL_LANG_UNSET = [];
                foreach ($this->conf['_LOCAL_LANG.'] as $languageKey => $languageArray) {
                    // Remove the dot after the language key
                    $languageKey = substr((string)$languageKey, 0, -1);
                    // Don't process label if the language is not loaded
                    if (!is_array($languageArray)) {
                        continue;
                    }
                    if (!isset($this->LOCAL_LANG[$languageKey])) {
                        continue;
                    }
                    foreach ($languageArray as $labelKey => $labelValue) {
                        if (!is_array($labelValue)) {
                            $this->LOCAL_LANG[$languageKey][$labelKey][0]['target'] = $labelValue;
                            if ($labelValue === '') {
                                $this->LOCAL_LANG_UNSET[$languageKey][$labelKey] = '';
                            }
                        }
                    }
                }
            }
        }
        $this->LOCAL_LANG_loaded = true;
    }

    /*******************************
     *
     * FlexForms related functions
     *
     *******************************/

    /**
     * Converts $this->cObj->data['pi_flexform'] from XML string to flexForm array.
     *
     * @param string $field Field name to convert
     */
    public function pi_initPIflexForm($field = 'pi_flexform')
    {
        // Converting flexform data into array
        $fieldData = $this->cObj->data[$field] ?? null;
        if (!is_array($fieldData) && $fieldData) {
            $this->cObj->data[$field] = GeneralUtility::xml2array((string)$fieldData);
            if (!is_array($this->cObj->data[$field])) {
                $this->cObj->data[$field] = [];
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array|null $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     * @return string|null The content.
     */
    public function pi_getFFvalue(
        $T3FlexForm_array,
        $fieldName,
        $sheet = 'sDEF',
        $lang = 'lDEF',
        $value = 'vDEF'
    ) {
        if ($T3FlexForm_array === null) {
            return null;
        }

        $sheetArray = $T3FlexForm_array['data'][$sheet][$lang] ?? null;
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensional array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array and return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     * @return mixed The value, typ. string.
     * @see pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } elseif (isset($tempArr[$v])) {
                $tempArr = $tempArr[$v];
            }
        }
        return $tempArr[$value] ?? '';
    }
}
