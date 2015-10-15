<?php

/**
 * @package Api
 */
class CApiResponseManager
{
	public static $objectNames = array(
			'CApiMailMessageCollection' => 'MessageCollection',
			'CApiMailMessage' => 'Message',
			'CApiMailFolderCollection' => 'FolderCollection',
			'CApiMailFolder' => 'Folder'
	);
	
	
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
	public static function objectWrapper($oData)
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
				$mResult['@Collection'] = self::GetResponseObject($oData->CloneAsArray());
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
	public static function GetResponseObject($mResponse)
	{
		$mResult = null;

		if (is_object($mResponse))
		{
			if (method_exists($mResponse, 'toResponseArray'))	
			{
				$mResult = array_merge(self::objectWrapper($mResponse), $mResponse->toResponseArray());
			}
		}
		else if (is_array($mResponse))
		{
			foreach ($mResponse as $iKey => $oItem)
			{
				$mResponse[$iKey] = self::GetResponseObject($oItem);
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
}

