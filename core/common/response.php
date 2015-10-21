<?php

/**
 * @package Api
 */
class CApiResponseManager
{
	protected static $sMethod = null;

	public static $objectNames = array(
			'CApiMailMessageCollection' => 'MessageCollection',
			'CApiMailMessage' => 'Message',
			'CApiMailFolderCollection' => 'FolderCollection',
			'CApiMailFolder' => 'Folder'
	);

	public static function GetMethod()
	{
		return  self::$sMethod;
	}
	
	public static function SetMethod($sMethod)
	{
		self::$sMethod = $sMethod;
	}

	/**
	 * @param string $sObjectName
	 *
	 * @return string
	 */
	public static function GetObjectName($sObjectName)
	{
		return !empty(self::$objectNames[$sObjectName]) ? self::$objectNames[$sObjectName] : $sObjectName;
	}
	
	/**
	 * @param object $oData
	 *
	 * @return array | false
	 */
	public static function objectWrapper($oData, $aParameters = array())
	{
		$mResult = false;
		if (is_object($oData))
		{
			$aNames = explode('\\', get_class($oData));
			$sObjectName = end($aNames);
			$mResult = array(
				'@Object' => self::GetObjectName($sObjectName)
			);			

			if ($oData instanceof \MailSo\Base\Collection)
			{
				$mResult['@Object'] = 'Collection/'.$mResult['@Object'];
				$mResult['@Count'] = $oData->Count();
				$mResult['@Collection'] = self::GetResponseObject($oData->CloneAsArray(), $aParameters);
			}
			else
			{
				$mResult['@Object'] = 'Object/'.$mResult['@Object'];
			}
		}

		return $mResult;
	}
	
	/**
	 * @param mixed $mResponse
	 *
	 * @return mixed
	 */
	public static function GetResponseObject($mResponse, $aParameters = array())
	{
		$mResult = null;

		if (is_object($mResponse))
		{
			if (method_exists($mResponse, 'toResponseArray'))	
			{
				$mResult = array_merge(self::objectWrapper($mResponse, $aParameters), $mResponse->toResponseArray($aParameters));
			}
		}
		else if (is_array($mResponse))
		{
			foreach ($mResponse as $iKey => $oItem)
			{
				$mResponse[$iKey] = self::GetResponseObject($oItem, $aParameters);
			}

			$mResult = $mResponse;
		}
		else
		{
			$mResult = $mResponse;
		}

		unset($mResponse);
		return $mResult;
	}	
	
	/**
	 * @param bool $bDownload
	 * @param string $sContentType
	 * @param string $sFileName
	 *
	 * @return bool
	 */
	public static function OutputHeaders($bDownload, $sContentType, $sFileName)
	{
	
		if ($bDownload)
		{
			header('Content-Type: '.$sContentType, true);
		}
		else
		{
			$aParts = explode('/', $sContentType, 2);
			if (in_array(strtolower($aParts[0]), array('image', 'video', 'audio')) ||
				in_array(strtolower($sContentType), array('application/pdf', 'application/x-pdf', 'text/html')))
			{
				header('Content-Type: '.$sContentType, true);
			}
			else
			{
				header('Content-Type: text/plain', true);
			}
		}

		header('Content-Disposition: '.($bDownload ? 'attachment' : 'inline' ).'; '.
			\trim(\MailSo\Base\Utils::EncodeHeaderUtf8AttributeValue('filename', $sFileName)), true);
		
		header('Accept-Ranges: none', true);
		header('Content-Transfer-Encoding: binary');	
	}
}

