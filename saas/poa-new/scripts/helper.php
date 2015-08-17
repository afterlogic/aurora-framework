<?php

/**
 * @param string $sName
 * @param bool $bTrim = true
 *
 * @return string
 */
function getMail($sName, $bTrim = true)
{
	$sValue = getenv('MAIL_'.$sName);
	return $bTrim ? trim($sValue) : (string) $sValue;
}

/**
 * @param string $sName
 * @param bool $bTrim = true
 *
 * @return string
 *
 * @throws Exception
 */
function getMailWithValidation($sName, $bTrim = true)
{
	$sValue = getMail($sName, $bTrim);
	if (0 === strlen($sValue))
	{
		throw new Exception($sName.' - Empty value');
	}

	return $sValue;
}

/**
 * @param string $sName
 * @param bool $bTrim = true
 * 
 * @return string
 */
function getSettings($sName, $bTrim = true)
{
	$sValue = getenv('SETTINGS_'.$sName);
	return $bTrim ? trim($sValue) : (string) $sValue;
}

/**
 * @param string $sName
 * @param bool $bTrim = true
 * 
 * @return string
 * 
 * @throws Exception
 */
function getSettingsWithValidation($sName, $bTrim = true)
{
	$sValue = getSettings($sName, $bTrim);
	if (0 === strlen($sValue))
	{
		throw new Exception($sName.' - Empty value');
	}
	
	return $sValue;
}

/**
 * @param string $sSettingID
 * @param string $sError
 * @param string $sErrorSystem = ''
 */
function simpleErrorStr($sSettingID, $sError, $sErrorSystem = '')
{
	$oXml = new XMLWriter();
	$oXml->openMemory();
	$oXml->setIndent(true);
	$oXml->setIndentString(' ');
	$oXml->startDocument('1.0');
	$oXml->startElement('output');
	$oXml->writeAttribute('xmlns', 'http://apstandard.com/ns/1/configure-output');

	$oXml->startElement('errors');

	$oXml->startElement('error');
	$oXml->writeAttribute('id', 0);
	$oXml->writeAttribute('setting-id', $sSettingID);
	$oXml->writeElement('message', $sError);
	$oXml->writeElement('system', $sErrorSystem);
	$oXml->endElement();

	$oXml->endElement();

	$oXml->endElement();
	$oXml->endDocument();

	@\header('Content-Type: text/xml; charset=utf-8');
	echo $oXml->outputMemory();
}

/**
 * @param string $sUrl
 * @param array $aPost = array()
 */
function apiWrapper($fCallback)
{
	@ob_start();

	$mExecResult = false;
	$oException = null;
	try
	{
		$mExecResult = call_user_func($fCallback);
	}
	catch (Exception $oException) {}

	$sObStr = @ob_get_clean();
	if ($sObStr)
	{
		echo simpleErrorStr('site', 'ob_start error', $sObStr);
	}
	else if ($oException)
	{
		echo simpleErrorStr('site', 'Exception error', $oException->getMessage());
	}
	else if (is_array($mExecResult) && 2 === count($mExecResult) &&
		isset($mExecResult['code'], $mExecResult['response']) &&
		0 === (int) $mExecResult['code'] && '' === $mExecResult['response'])
	{
		exit(0);
	}
	else if (is_array($mExecResult) && 2 === count($mExecResult) &&
		isset($mExecResult['code'], $mExecResult['response']) &&
		'<?xml' === substr($mExecResult['response'], 0, 5))
	{
		@\header('Content-Type: text/xml; charset=utf-8');

		echo $mExecResult['response'];
		exit((int) $mExecResult['code']);
	}
	else
	{
		echo simpleErrorStr('site', 'Api Response error', $mExecResult ? $mExecResult : '');
	}

	exit(1);
}

/**
 * @param string $sUrl
 * @param array $aPost = array()
 */
function apiRequest($sUrl, $aPost = array())
{
	if (!function_exists('curl_init'))
	{
		throw new Exception('curl exception');
	}

	if (!function_exists('json_decode'))
	{
		throw new Exception('json_decode exception');
	}

	$aOptions = array(
		CURLOPT_URL => $sUrl,
		CURLOPT_HEADER => false,
		CURLOPT_FAILONERROR => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $aPost,
		CURLOPT_TIMEOUT => 60
	);

	$oCurl = curl_init();

	curl_setopt_array($oCurl, $aOptions);
	$mExecResult = curl_exec($oCurl);

	if (is_resource($oCurl))
	{
		curl_close($oCurl);
	}

	if (!is_string($mExecResult) || 0 === strlen($mExecResult))
	{
		throw new Exception('Api Response exception');
	}

	return @\json_decode($mExecResult, true);
}
