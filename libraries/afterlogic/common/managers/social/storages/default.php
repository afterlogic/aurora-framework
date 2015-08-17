<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Social
 * @subpackage Storages
 */
class CApiSocialStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('social', $sStorageName, $oManager);
	}
	
	/**
	 * @param int $iIdAccount
	 *
	 * @return array
	 */
	public function getSocials($iIdAccount)
	{
	
	}	
	
	/**
	 * @param int $iIdAccount
	 * @param int $iType
	 * @return string
	 */
	public function getSocial($iIdAccount, $iType)
	{
	
	}
	
	/**
	 * @param string $sIdSocial
	 * @param int $iType
	 *
	 * @return \CSocial
	 */
	public function getSocialById($sIdSocial, $iType)
	{
		
	}	

	/**
	 * @param CSocial &$oSocial
	 *
	 * @return bool
	 */
	public function createSocial(CSocial &$oSocial)
	{
		
	}

	/**
	 * @param CSocial &$oSocial
	 *
	 * @return bool
	 */
	public function updateSocial(CSocial &$oSocial)
	{

	}
	
	/**
	 * @param int $iIdAccount
	 * @param int $iType
	 *
	 * @return bool
	 */
	public function deleteSocial($iIdAccount, $iType)
	{
		
	}
	
	public function deleteSocialByAccountId($iIdAccount)
	{

	}	

	/**
	 * @param string $sEmail
	 *
	 * @return bool
	 */
	public function deleteSocialsByEmail($sEmail)
	{
	
	}	
	
	/**
	 * @param CSocial &$oSocial
	 *
	 * @return bool
	 */
	public function isSocialExists(CSocial $oSocial)
	{
		
	}	
}

