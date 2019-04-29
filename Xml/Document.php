<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Xml;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Document
{
	/**
	 * @var Node
	 */
	public $XmlRoot = null;

	/**
	 * @param string $sName
	 * @param string $sValue
	 */
	public function CreateElement($sName, $sValue = null)
	{
		$this->XmlRoot = new Node($sName, $sValue);
	}

	/**
	 * @param string $sXmlText
	 * @return bool
	 */
	public function ParseFromString($sXmlText)
	{
		$bResult = false;
		if (!empty($sXmlText))
		{
			$oParser = xml_parser_create();
			xml_parser_set_option($oParser, XML_OPTION_CASE_FOLDING, false);
			xml_parser_set_option($oParser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
//			xml_parser_set_option($oParser, XML_OPTION_SKIP_WHITE, true);

			xml_set_element_handler($oParser,
				array(&$this, '_startElement'), array(&$this, '_endElement'));

			xml_set_character_data_handler($oParser, array(&$this, '_charData'));

			$bResult = xml_parse($oParser, $sXmlText);
			if (!$bResult)
			{
				$sError = xml_error_string( xml_get_error_code($oParser));
			}
			xml_parser_free($oParser);
		}

		return (bool) $bResult;
	}

	/**
	 * @param bool $bSplitLines
	 * @return string
	 */
	public function ToString($bSplitLines = false)
	{
		$sOutStr = '<'.'?'.'xml version="1.0" encoding="utf-8"?'.'>';
		if ($bSplitLines)
		{
			$sOutStr .= "\r\n";
		}

		if (null !== $this->XmlRoot)
		{
			$sOutStr .= $this->XmlRoot->ToString($bSplitLines);
		}

		return $sOutStr;
	}

	/**
	 * @param string $sFileName
	 * @return bool
	 */
	public function LoadFromFile($sFileName)
	{
		$sXmlData = @file_get_contents($sFileName);
		if (false !== $sXmlData)
		{
			return $this->ParseFromString($sXmlData);
		}
		return false;
	}

	/**
	 * @param string $sFileName
	 * @return bool
	 */
	public function SaveToFile($sFileName)
	{
		$bResult = false;
		$rFilePointer = @fopen($sFileName, 'wb');
		if ($rFilePointer)
		{
			$bResult = (false !== @fwrite($rFilePointer, $this->ToString(true)));
			$bResult = @fclose($rFilePointer);
		}

		return $bResult;
	}

	/**
	 * @param string $sName
	 * @return string
	 */
	public function GetParamValueByName($sName)
	{
		$oParam =& $this->getParamNodeByName($sName);
		return (null !== $oParam && isset($oParam->Attributes['value']))
			? \Aurora\System\Utils::DecodeSpecialXmlChars($oParam->Attributes['value']) : '';
	}

	/**
	 * @param string $sName
	 * @return string
	 */
	public function GetParamTagValueByName($sName)
	{
		$oParam =& $this->getParamNodeByName($sName);
		return (null !== $oParam) ? \Aurora\System\Utils::DecodeSpecialXmlChars($oParam->Value) : '';
	}

	/**
	 * @param string $sName
	 * @return object
	 */
	protected function &getParamNodeByName($sName)
	{
		$iNodeKey = null;
		$oNull = null;
		if ($this->XmlRoot && is_array($this->XmlRoot->Children))
		{
			$aNodeKeys = array_keys($this->XmlRoot->Children);
			foreach ($aNodeKeys as $iNodeKey)
			{
				if ($this->XmlRoot->Children[$iNodeKey]->TagName == 'param' &&
					isset($this->XmlRoot->Children[$iNodeKey]->Attributes['name']) &&
					$this->XmlRoot->Children[$iNodeKey]->Attributes['name'] == $sName)
				{
					return $this->XmlRoot->Children[$iNodeKey];
				}
			}
		}
		return $oNull;
	}

	/**
	 * @access private
	 * @param object $oParser
	 * @param string $sName
	 * @param array $aAttributes
	 */
	public function _startElement($oParser, $sName, $aAttributes)
	{
		$this->_nullFunction($oParser);
		$oNode = new Node($sName);
		$oNode->Attributes = $aAttributes;
		if ($this->XmlRoot == null)
		{
			$this->XmlRoot =& $oNode;
		}
		else
		{
			$oRootNode = null;
			$oRootNode =& $this->_stack[count($this->_stack) - 1];
			$oRootNode->Children[] =& $oNode;
		}

		$this->_stack[] =& $oNode;
	}

	/**
	 * @access private
	 */
	public function _endElement()
	{
		array_pop($this->_stack);
	}

	/**
	 * @access private
	 * @param object $oParser
	 * @param string $sText
	 */
	function _charData($oParser, $sText)
	{
		$oNode = null;
		$this->_nullFunction($oParser);
		$oNode =& $this->_stack[count($this->_stack) - 1];
		if ($oNode->Value == null)
		{
			$oNode->Value = '';
		}

		if ($sText == '>')
		{
			$oNode->Value .= '&gt;';
		}
		else if ($sText == '<')
		{
			$oNode->Value .= '&lt;';
		}
		else
		{
			$oNode->Value .= $sText;
		}
	}

	/**
	 * @access private
	 * @return bool
	 */
	public function _nullFunction()
	{
		return true;
	}
}
