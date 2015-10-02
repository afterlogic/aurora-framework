<?php

class FilesModule extends AApiModule
{
	public $oApiFilesManager = null;
	public $oApiCapabilityManager = null;
	
	public function init() 
	{
		$this->oApiFilesManager = $this->GetManager('main');
		$this->oApiCapabilityManager = \CApi::GetCoreManager('capability');
	}
	
	public function GetExternalStorages()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		$oResult = array();
		\CApi::Plugin()->RunHook('filestorage.get-external-storages', array($oAccount, &$oResult));

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}

	public function GetFiles()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$sPath = $this->getParamValue('Path');
		$sType = $this->getParamValue('Type');
		$sPattern = $this->getParamValue('Pattern');
		
		$oResult = array(
			'Items' => $this->oApiFilesManager->getFiles($oAccount, $sType, $sPath, $sPattern),
			'Quota' => $this->oApiFilesManager->getQuota($oAccount)
		);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	

	public function GetPublicFiles()
	{
		$oAccount = null;
		$oResult = array();

		$oMin = \CApi::GetCoreManager('min');

		$sHash = $this->getParamValue('Hash');
		$sPath = $this->getParamValue('Path', '');
		
		$mMin = $oMin->getMinByHash($sHash);
		if (!empty($mMin['__hash__']))
		{
			$oApiUsers = \CApi::GetCoreManager('users');
			$oAccount = $oApiUsers->getAccountById($mMin['Account']);
			if ($oAccount)
			{
				if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
				}
				$sType = $mMin['Type'];

				$sPath =  implode('/', array($mMin['Path'], $mMin['Name']))  . $sPath;

				$oResult['Items'] = $this->oApiFilesManager->getFiles($oAccount, $sType, $sPath);
				$oResult['Quota'] = $this->oApiFilesManager->getQuota($oAccount);
				
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	

	public function GetQuota()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, 
				array(
					'Quota' => $this->oApiFilesManager->getQuota($oAccount)
				)
		);
	}	

	public function CreateFolder()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapability->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$sType = $this->getParamValue('Type');
		$sPath = $this->getParamValue('Path');
		$sFolderName = $this->getParamValue('FolderName');
		$oResult = null;
		
		$oResult = $this->oApiFilesManager->createFolder($oAccount, $sType, $sPath, $sFolderName);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}
	
	public function CreateLink()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$sType = $this->getParamValue('Type');
		$sPath = $this->getParamValue('Path');
		$sLink = $this->getParamValue('Link');
		$sName = $this->getParamValue('Name');
		$oResult = $this->oApiFilesManager->createLink($oAccount, $sType, $sPath, $sLink, $sName);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}
	
	public function Delete()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$sType = $this->getParamValue('Type');
		$aItems = @json_decode($this->getParamValue('Items'), true);
		$oResult = false;
		
		foreach ($aItems as $oItem)
		{
			$oResult = $this->oApiFilesManager->delete($oAccount, $sType, $oItem['Path'], $oItem['Name']);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	

	public function Rename()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		$sType = $this->getParamValue('Type');
		$sPath = $this->getParamValue('Path');
		$sName = $this->getParamValue('Name');
		$sNewName = $this->getParamValue('NewName');
		$bIsLink = !!$this->getParamValue('IsLink');
		$oResult = null;

		$sNewName = \trim(\MailSo\Base\Utils::ClearFileName($sNewName));
		
		$sNewName = $this->oApiFilesManager->getNonExistingFileName($oAccount, $sType, $sPath, $sNewName);
		$oResult = $this->oApiFilesManager->rename($oAccount, $sType, $sPath, $sName, $sNewName, $bIsLink);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	

	public function Copy()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$sFromType = $this->getParamValue('FromType');
		$sToType = $this->getParamValue('ToType');
		$sFromPath = $this->getParamValue('FromPath');
		$sToPath = $this->getParamValue('ToPath');
		$aItems = @json_decode($this->getParamValue('Files'), true);
		$oResult = null;
		
		foreach ($aItems as $aItem)
		{
			$bFolderIntoItself = $aItem['IsFolder'] && $sToPath === $sFromPath.'/'.$aItem['Name'];
			if (!$bFolderIntoItself)
			{
				$sNewName = $this->oApiFilesManager->getNonExistingFileName($oAccount, $sToType, $sToPath, $aItem['Name']);
				$oResult = $this->oApiFilesManager->copy($oAccount, $sFromType, $sToType, $sFromPath, $sToPath, $aItem['Name'], $sNewName);
			}
		}
		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	

	public function Move()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		$sFromType = $this->getParamValue('FromType');
		$sToType = $this->getParamValue('ToType');
		$sFromPath = $this->getParamValue('FromPath');
		$sToPath = $this->getParamValue('ToPath');
		$aItems = @json_decode($this->getParamValue('Files'), true);
		$oResult = null;
		
		foreach ($aItems as $aItem)
		{
			$bFolderIntoItself = $aItem['IsFolder'] && $sToPath === $sFromPath.'/'.$aItem['Name'];
			if (!$bFolderIntoItself)
			{
				$sNewName = $this->oApiFilesManager->getNonExistingFileName($oAccount, $sToType, $sToPath, $aItem['Name']);
				$oResult = $this->oApiFilesManager>move($oAccount, $sFromType, $sToType, $sFromPath, $sToPath, $aItem['Name'], $sNewName);
			}
		}
		return $this->DefaultResponse($oAccount, __FUNCTION__, $oResult);
	}	
	
	public function CreatePublicLink()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		$sType = $this->getParamValue('Type'); 
		$sPath = $this->getParamValue('Path'); 
		$sName = $this->getParamValue('Name');
		$sSize = $this->getParamValue('Size');
		$bIsFolder = $this->getParamValue('IsFolder', '0') === '1' ? true : false;
		
		$mResult = $this->oApiFilesManager->createPublicLink($oAccount, $sType, $sPath, $sName, $sSize, $bIsFolder);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	public function DeletePublicLink()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}
		
		$sType = $this->getParamValue('Type'); 
		$sPath = $this->getParamValue('Path'); 
		$sName = $this->getParamValue('Name');
		
		$mResult = $this->oApiFilesManager->deletePublicLink($oAccount, $sType, $sPath, $sName);
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function CheckUrl()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$mResult = false;

		if ($oAccount)
		{
			$sUrl = trim($this->getParamValue('Url', ''));

			if (!empty($sUrl))
			{
				$iLinkType = \api_Utils::GetLinkType($sUrl);
				if ($iLinkType === \EFileStorageLinkType::GoogleDrive)
				{
					$oApiTenants = \CApi::GetCoreManager('tenants');
					if ($oApiTenants)
					{
						$oTenant = (0 < $oAccount->IdTenant) ? $oApiTenants->getTenantById($oAccount->IdTenant) :
							$oApiTenants->getDefaultGlobalTenant();
					}
					$oSocial = $oTenant->getSocialByName('google');
					if ($oSocial)
					{
						$oInfo = \api_Utils::GetGoogleDriveFileInfo($sUrl, $oSocial->SocialApiKey);
						if ($oInfo)
						{
							$mResult['Size'] = 0;
							if (isset($oInfo->fileSize))
							{
								$mResult['Size'] = $oInfo->fileSize;
							}
							else
							{
								$aRemoteFileInfo = \api_Utils::GetRemoteFileInfo($sUrl);
								$mResult['Size'] = $aRemoteFileInfo['size'];
							}
							$mResult['Name'] = isset($oInfo->title) ? $oInfo->title : '';
							$mResult['Thumb'] = isset($oInfo->thumbnailLink) ? $oInfo->thumbnailLink : null;
						}
					}
				}
				else
				{
					//$sUrl = \api_Utils::GetRemoteFileRealUrl($sUrl);
					$oInfo = \api_Utils::GetOembedFileInfo($sUrl);
					if ($oInfo)
					{
						$mResult['Size'] = isset($oInfo->fileSize) ? $oInfo->fileSize : '';
						$mResult['Name'] = isset($oInfo->title) ? $oInfo->title : '';
						$mResult['LinkType'] = $iLinkType;
						$mResult['Thumb'] = isset($oInfo->thumbnail_url) ? $oInfo->thumbnail_url : null;
					}
					else
					{
						if (\api_Utils::GetLinkType($sUrl) === \EFileStorageLinkType::DropBox)
						{
							$sUrl = str_replace('?dl=0', '', $sUrl);
						}

						$sUrl = \api_Utils::GetRemoteFileRealUrl($sUrl);
						if ($sUrl)
						{
							$aRemoteFileInfo = \api_Utils::GetRemoteFileInfo($sUrl);
							$sFileName = basename($sUrl);
							$sFileExtension = \api_Utils::GetFileExtension($sFileName);

							if (empty($sFileExtension))
							{
								$sFileExtension = \api_Utils::GetFileExtensionFromMimeContentType($aRemoteFileInfo['content-type']);
								$sFileName .= '.'.$sFileExtension;
							}

							if ($sFileExtension === 'htm')
							{
								$oCurl = curl_init();
								\curl_setopt_array($oCurl, array(
									CURLOPT_URL => $sUrl,
									CURLOPT_FOLLOWLOCATION => true,
									CURLOPT_ENCODING => '',
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_AUTOREFERER => true,
									CURLOPT_SSL_VERIFYPEER => false, //required for https urls
									CURLOPT_CONNECTTIMEOUT => 5,
									CURLOPT_TIMEOUT => 5,
									CURLOPT_MAXREDIRS => 5
								));
								$sContent = curl_exec($oCurl);
								//$aInfo = curl_getinfo($oCurl);
								curl_close($oCurl);

								preg_match('/<title>(.*?)<\/title>/s', $sContent, $aTitle);
								$sTitle = isset($aTitle['1']) ? trim($aTitle['1']) : '';
							}

							$mResult['Name'] = isset($sTitle) && strlen($sTitle)> 0 ? $sTitle : urldecode($sFileName);
							$mResult['Size'] = $aRemoteFileInfo['size'];
						}
					}
				}
			}
		}
		
		return $this->DefaultResponse(null, __FUNCTION__, $mResult);
	}	
	
	
}

return new FilesModule('1.0');
