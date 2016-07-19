<?php

/**
 * @package Api
 */
class CApiUserSession
{
	/**
     * @var \MailSo\Cache\CacheClient
     */
    protected $Session = null;
	
	/**
     * @var \MailSo\Cache\CacheClient
     */
	protected $Path = '';
	
	public function __construct()
	{
		$this->Path = \CApi::DataPath().'/sessions';
		$oSession = \MailSo\Cache\CacheClient::NewInstance();
		$oSessionDriver = \MailSo\Cache\Drivers\File::NewInstance($this->Path);
		$oSessionDriver->bRootDir = true;
		$oSession->SetDriver($oSessionDriver);
		$oSession->SetCacheIndex(\CApi::Version());

		$this->Session = $oSession;
	}
	
	protected function getList()
	{
		$aResult = array();
		$aItems = scandir($this->Path);
		foreach ($aItems as $sItemName)
		{
			if ($sItemName === '.' or $sItemName === '..')
			{
				continue;
			}
			
			$sItemPath = $this->Path . DIRECTORY_SEPARATOR . $sItemName;
			$aItem = \CApi::DecodeKeyValues(file_get_contents($sItemPath));
			if (is_array($aItem) && isset($aItem['token']))
			{
				$aResult[$sItemPath] = $aItem;
			}
		}
		
		return $aResult;
	}

	public function Set($aData)
	{
			$sAccountHashTable = \CApi::EncodeKeyValues($aData);
			$sAuthToken = \md5(\microtime(true).\rand(10000, 99999));
			return $this->Session->Set('AUTHTOKEN:'.$sAuthToken, $sAccountHashTable) ? $sAuthToken : '';
	}
	
	public function Get($sAuthToken)
	{
		$mResult = false;
		
		if (strlen($sAuthToken) !== 0) {
			
			$sKey = $this->Session->get('AUTHTOKEN:'.$sAuthToken);
		}
		if (!empty($sKey) && is_string($sKey)) {
			
			$mResult = \CApi::DecodeKeyValues($sKey);
		}
		
		return $mResult;
	}
	
	public function Delete($sAuthToken)
	{
		$this->Session->Delete('AUTHTOKEN:'.$sAuthToken);
	}
	
	public function DeleteById($iId)
	{
		$aList = $this->getList();
		foreach ($aList as $sKey => $aItem)
		{
			if (isset($aItem['id']) && (int)$aItem['id'] === $iId)
			{
				@unlink($sKey);
			}
		}
	}
}