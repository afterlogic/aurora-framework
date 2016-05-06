<?php

/**
 * @package Api
 */
class CApiModuleManager
{
	protected $_aModules = array();
	
    /**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $_aEventSubscriptions = array();
	
    protected $_aObjectsMap = array();
	
	public function __construct()
	{
	}
	
	public static function createInstance()
	{
		return new self();
	}
	
	public function init()
	{
		$sModulesPath = $this->GetModulesPath();
		if (@is_dir($sModulesPath))
		{
			if (false !== ($rDirHandle = @opendir($sModulesPath)))
			{
				while (false !== ($sFileItem = @readdir($rDirHandle)))
				{
					if (0 < strlen($sFileItem) && '.' !== $sFileItem{0} && preg_match('/^[a-zA-Z0-9\-]+$/', $sFileItem) &&
						@file_exists($sModulesPath.$sFileItem.'/index.php'))
					{
						$oModule = include_once $sModulesPath.$sFileItem.'/index.php';
						if ($oModule instanceof AApiModule)
						{
							$oModule->SetName($sFileItem);
							$oModule->SetPath($sModulesPath.$sFileItem);
							$oModule->init();
							$this->_aModules[strtolower($sFileItem)] = $oModule;
						}
					}
				}

				@closedir($rDirHandle);
			}
		}
	}
	
    /**
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
     *
     * @param string $sEvent
     * @param callback $fCallback
     * @param int $iPriority
     * @return void
     */
    public function subscribeEvent($sEvent, $fCallback, $iPriority = 100) 
	{
        if (!isset($this->_aEventSubscriptions[$sEvent])) {
            $this->_aEventSubscriptions[$sEvent] = array();
        }
        while(isset($this->_aEventSubscriptions[$sEvent][$iPriority]))	{
			$iPriority++;
		}
        $this->_aEventSubscriptions[$sEvent][$iPriority] = $fCallback;
        ksort($this->_aEventSubscriptions[$sEvent]);
    }	
	
    /**
     * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
     *
     * @param string $sEvent
     * @param array $aArguments
     * @return bool
     */
    public function broadcastEvent($sModule, $sEvent, $aArguments = array()) 
	{
        $bResult = true;
		$aEventSubscriptions = array();
		if (isset($this->_aEventSubscriptions[$sModule. '::' .$sEvent])) {
			$aEventSubscriptions = array_merge($aEventSubscriptions, $this->_aEventSubscriptions[$sModule. '::' .$sEvent]);
		}
		if (isset($this->_aEventSubscriptions[$sEvent])) {
			$aEventSubscriptions = array_merge($aEventSubscriptions, $this->_aEventSubscriptions[$sEvent]);
        }
		
		foreach($aEventSubscriptions as $fCallback) {
			$result = call_user_func_array($fCallback, $aArguments);
			if ($result === false) {
				$bResult = false;
				break;
			}
		}

        return $bResult;
    }	
	
	public function setObjectMap()
	{
		
	}

	/**
	 * @return string
	 */
	public function GetModulesPath()
	{
		return PSEVEN_APP_ROOT_PATH.'modules/';
	}

	/**
	 * @return array
	 */
	public function GetModules()
	{
		return $this->_aModules;
	}
	
	/**
	 * @param string $sModuleName
	 * @return AApiModule
	 */
	public function GetModule($sModuleName)
	{
		$sModuleName = strtolower($sModuleName);
		return (isset($this->_aModules[$sModuleName]) &&  $this->_aModules[$sModuleName] instanceof AApiModule) ? $this->_aModules[$sModuleName] : false;
	}
	
	/**
	 * @return array
	 */
	public function GetModuleByEntry($sEntryName)
	{
		$sModule = '';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if ($oHttp->IsPost()) {
			$sModule = $oHttp->GetPost('Module', null);
		} else {
			$aPath = \Core\Service::GetPaths();
			$sModule = (isset($aPath[1])) ? $aPath[1] : '';
		}
			
		$oResult = $this->GetModule($sModule);
		if ($oResult && !$oResult->HasEntry($sEntryName)) {
			$oResult = false;
		}
		if ($oResult === false) {
			foreach ($this->_aModules as $oModule) {
				if ($oModule instanceof AApiModule && $oModule->HasEntry($sEntryName)) {
					$oResult = $oModule;
					break;
				}
			}
		}
		
		return $oResult;
	}
	
	/**
	 * @param string $sModuleName
	 * @return bool
	 */
	public function ModuleExists($sModuleName)
	{
		return ($this->GetModule($sModuleName)) ? true  : false;
	}
	
	public function RunEntry($sEntryName)
	{
		$mResult = false;
		$oModule = $this->GetModuleByEntry($sEntryName);
		if ($oModule instanceof AApiModule) {
			
			$mResult = $oModule->RunEntry($sEntryName);
		}
		
		return $mResult;
	}

	public function ExecuteMethod($sModuleName, $sMethod, $aParameters = array())
	{
		$mResult = false;
		$oModule = $this->GetModule($sModuleName);
		if ($oModule instanceof AApiModule) {
			
			$mResult = $oModule->ExecuteMethod($sMethod, $aParameters);
		}
		
		return $mResult;
	}

	/**
	 * @return string
	 */
	public function Hash()
	{
		$sResult = md5(CApi::Version());
		foreach ($this->_aModules as $oModule) {
			
			$sResult = md5($sResult.$oModule->GetPath().$oModule->GetName().$oModule->GetHash());
		}

		return $sResult;
	}
}

/**
 * @package Api
 */
class CApiModuleDecorator
{
    protected $oModule;

    public function __construct($sModuleName) 
	{
       $this->oModule = \CApi::GetModule($sModuleName);
    }	
	
	public function __call($sMethodName, $aArguments) 
	{
		$mResult = false;
		if ($this->oModule instanceof AApiModule)
		{
			$mResult = $this->oModule->ExecuteMethod($sMethodName, $aArguments);
		}
		return $mResult;
	}
}


/**
 * @package Api
 */
abstract class AApiModule
{
	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sPath;

	/**
	 * @var string
	 */
	protected $sVersion;
	
	/**
	 * @var array
	 */
	protected $aManagersCache = array();	

	/**
	 * @var array
	 */
	protected $aEntries = array();	

	/**
	 * @var array
	 */
	protected $aParameters;

	/**
	 * @var array
	 */
	protected $aObjects = array();	

	/**
	 * @var \CApiCapabilityManager
	 */
	public $oApiCapabilityManager = null;
	
	/**
	 * @var \MailSo\Base\Http
	 */
	protected $oHttp;	
	
	/**
	 * @param string $sVersion
	 */
	public function __construct($sVersion)
	{
		$this->sVersion = (string) $sVersion;

		$this->sName = '';
		$this->sPath = '';
		$this->aParameters = array();
		$this->oApiCapabilityManager = \CApi::GetCoreManager('capability');
		$this->oHttp = \MailSo\Base\Http::NewInstance();
		
		$this->aEntries = array(
			'ajax' => 'EntryAjax',
			'upload' => 'EntryUpload',
			'download' => 'EntryDownload'
		);
	}

	public function init()
	{
	}
	
	public function subscribeEvent($sEvent, $fCallback)
	{
		\CApi::GetModuleManager()->subscribeEvent($sEvent, $fCallback);
	}

	public function broadcastEvent($sEvent, $aArguments = array())
	{
		\CApi::GetModuleManager()->broadcastEvent($this->GetName(), $sEvent, $aArguments);
	}
	
	public function setObjectMap($sType, $aMap)
	{
		$aResultMap = array();
		foreach ($aMap as $sKey => $aValue)
		{
			$aResultMap[$this->GetName() . '::' . $sKey] = $aValue;
		}
		$this->aObjects[$sType] = $aResultMap;
	}	
	
	public function getObjectMap($sType)
	{
		return isset($this->aObjects[$sType]) ? $this->aObjects[$sType] : array();
	}

	/**
	 * @param string $sName
	 */
	final public function SetName($sName)
	{
		$this->sName = $sName;
	}

	/**
	 * @param string $sPath
	 */
	final public function SetPath($sPath)
	{
		$this->sPath = $sPath;
	}

	/**
	 * @return string
	 */
	public function GetHash()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function GetName()
	{
		return $this->sName;
	}

	/**
	 * @return string
	 */
	public function GetPath()
	{
		return $this->sPath;
	}

	/**
	 * @return string
	 */
	public function GetVersion()
	{
		return $this->sVersion;
	}

	/**
	 * @return string
	 */
	public function GetFullName()
	{
		return $this->sName.'-'.$this->sVersion;
	}
	
	protected function GetManagerPath($sManagerName)
	{
		return $this->GetPath().'/managers/'.$sManagerName.'/manager.php';
	}

	public function GetManager($sManagerName, $sForcedStorage = 'db')
	{
		$mResult = false;
		$sFileFullPath = '';
		if (!isset($this->aManagersCache[$sManagerName]))
		{
			$sFileFullPath = $this->GetManagerPath($sManagerName);
			if (@file_exists($sFileFullPath))
			{
				if (include_once $sFileFullPath)
				{
					$this->aManagersCache[$sManagerName] = true;
				}
			}
		}
		if (isset($this->aManagersCache[$sManagerName]))
		{
			$sClassName = 'CApi'.ucfirst($this->GetName()).ucfirst($sManagerName).'Manager';
			if (class_exists($sClassName))
			{
				$mResult = new $sClassName(\CApi::$oManager, $sForcedStorage, $this);
			}
		}
		
		return $mResult;
	}
	
	public function AddEntry($sName, $mCallbak)
	{
		if (!isset($this->aEntries[$sName]))
		{
			$this->aEntries[$sName] = $mCallbak;
		}
	}
	
	public function AddEntries($aEntries)
	{
		foreach ($aEntries as $sName => $mCallbak)
		{
			$this->AddEntry($sName, $mCallbak);
		}
	}
	
	public function HasEntry($sName)
	{
		return isset($this->aEntries[$sName]);
	}
	
	public function GetEntry($sName)
	{
		$mResult = false;
		if (isset($this->aEntries[$sName])) {
			
			$mResult = $this->aEntries[$sName];
		}
		
		return $mResult;
	}	
	
	public function RunEntry($sName)
	{
		$mResult = false;
		$mMethod = $this->GetEntry($sName);
		
		if ($mMethod) {
			
			$mResult = $this->ExecuteMethod($mMethod);
		}			
		
		return $mResult;
	}

	public function EntryAjax()
	{
		@ob_start();

		$aResponseItem = null;
		$sModule = $this->oHttp->GetPost('Module', null);

		if (strtolower($sModule) === strtolower($this->GetName())) {
			
			$sMethod = $this->oHttp->GetPost('Method', null);
			$sParameters = $this->oHttp->GetPost('Parameters', null);
			try
			{
				\CApi::Log('AJAX:');
				\CApi::Log('Module: '. $sModule);
				\CApi::Log('Method: '. $sMethod);

				if (strtolower($sModule) !== 'core' && strtolower($sMethod) !== 'SystemGetAppData' &&
					\CApi::GetConf('labs.webmail.csrftoken-protection', true) && !\Core\Service::validateToken()) {
					
					throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidToken);
				} else if (!empty($sModule) && !empty($sMethod)) {
					
					$aParameters = isset($sParameters) ? @json_decode($sParameters, true) : array();
					$sAuthToken = $this->oHttp->GetPost('AuthToken', '');
					
					if (!$this->CheckNonAuthorizedMethodAllowed($sMethod, $sAuthToken))
					{
						if (!\CApi::getLogginedUserId($sAuthToken))
						{
							throw new \Core\Exceptions\ClientException(\Core\Notifications::UnknownError);
						}
					}
					
					$mResult = $this->ExecuteMethod($sMethod, $aParameters);

					$aResponseItem = $this->DefaultResponse($sMethod, $mResult);
					
	/*						
					else if (\CApi::Plugin()->JsonHookExists($sMethodName))
					{
						$this->oActions->SetActionParams($this->oHttp->GetPostAsArray());
						$aResponseItem = \CApi::Plugin()->RunJsonHook($this->oActions, $sMethodName);
					}
	*/
				}

				if (!is_array($aResponseItem)) {
					
					throw new \Core\Exceptions\ClientException(\Core\Notifications::UnknownError);
				}
			}
			catch (\Exception $oException)
			{
				//if ($oException instanceof \Core\Exceptions\ClientException &&
				//	\Core\Notifications::AuthError === $oException->getCode())
				//{
				//	$oApiIntegrator = /* @var $oApiIntegrator \CApiIntegratorManager */ \CApi::GetCoreManager('integrator');
				//	$oApiIntegrator->setLastErrorCode(\Core\Notifications::AuthError);
				//	$oApiIntegrator->logoutAccount();
				//}

				\CApi::LogException($oException);

				$aAdditionalParams = null;
				if ($oException instanceof \Core\Exceptions\ClientException) {
					
					$aAdditionalParams = $oException->GetObjectParams();
				}

				$aResponseItem = $this->ExceptionResponse($sMethod, $oException, $aAdditionalParams);
			}

			@header('Content-Type: application/json; charset=utf-8');

			\CApi::Plugin()->RunHook('ajax.response-result', array($sMethod, &$aResponseItem));
		}

		return \MailSo\Base\Utils::Php2js($aResponseItem, \CApi::MailSoLogger());		
	}

	public function EntryUpload()
	{
		@ob_start();
		$aResponseItem = null;
		$sModule = $this->oHttp->GetPost('Module', null);
		$sMethod = $this->oHttp->GetPost('Method', null);
		$sParameters = $this->oHttp->GetPost('Parameters', null);
		try
		{
			if (!empty($sModule) && !empty($sMethod))
			{
				$aParameters = isset($sParameters) ? @json_decode($sParameters, true) : array();
				$sError = '';
				$sInputName = 'jua-uploader';

				$iError = UPLOAD_ERR_OK;
				$_FILES = isset($_FILES) ? $_FILES : null;
				if (isset($_FILES, $_FILES[$sInputName], $_FILES[$sInputName]['name'], $_FILES[$sInputName]['tmp_name'], $_FILES[$sInputName]['size'], $_FILES[$sInputName]['type']))
				{
					$iError = (isset($_FILES[$sInputName]['error'])) ? (int) $_FILES[$sInputName]['error'] : UPLOAD_ERR_OK;
					if (UPLOAD_ERR_OK === $iError)
					{
						$aParameters = array_merge($aParameters, 
							array(
								'FileData' => $_FILES[$sInputName],
								'IsExt' => '1' === (string) $this->oHttp->GetPost('IsExt', '0') ? '1' : '0',
								'TenantHash' => (string) $this->oHttp->GetPost('TenantHash', ''),
								'Token' => $this->oHttp->GetPost('Token', ''),
								'AuthToken' => $this->oHttp->GetPost('AuthToken', '')
							)
						);


						$aResponseItem = $this->ExecuteMethod($sMethod, $aParameters);
					}
					else
					{
						$sError = 'unknown';
//								$sError = $this->oActions->convertUploadErrorToString($iError);
					}
				}
				else if (!isset($_FILES) || !is_array($_FILES) || 0 === count($_FILES))
				{
					$sError = 'size';
				}
				else
				{
					$sError = 'unknown';
				}
			}					

			if (!is_array($aResponseItem) && empty($sError))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::UnknownError);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);
			$aResponseItem = $this->ExceptionResponse(null, 'Upload', $oException);
			$sError = 'exception';
		}

		if (0 < strlen($sError))
		{
			$aResponseItem['Error'] = $sError;
		}
		else 
		{
			$aResponseItem = $this->DefaultResponse($sMethod, $aResponseItem);
		}

		@ob_get_clean();
		@header('Content-Type: text/html; charset=utf-8');
//				if ('iframe' === $this->oHttp->GetPost('jua-post-type', ''))
//				{
//					@header('Content-Type: text/html; charset=utf-8');
//				}
//				else
//				{
//					@header('Content-Type: application/json; charset=utf-8');
//				}

		return \MailSo\Base\Utils::Php2js($aResponseItem);		
	}

	public function EntryDownload()
	{
		$mResult = false;
		
		$aPaths = \Core\Service::GetPaths();
		$sMethod = empty($aPaths[2]) ? '' : $aPaths[2];

		try
		{
			if (!empty($sMethod)) {
				
				$sRawKey = empty($aPaths[3]) ? '' : $aPaths[3];
				$aParameters = CApi::DecodeKeyValues($sRawKey);				
				$aParameters['AuthToken'] = empty($aPaths[4]) ? '' : $aPaths[4];

				$mResult = $this->ExecuteMethod($sMethod, $aParameters);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);
			$this->oHttp->StatusHeader(404);
		}
		
		return $mResult;
	}

	/**
	 * @param string $sFileName
	 * @param bool $bDoExitOnError = true
	 * @return bool
	 */
	public function Inc($sFileName, $bDoExitOnError = true)
	{
		static $aCache = array();

		$sFileFullPath = '';
		$sFileName = preg_replace('/[^a-z0-9\._\-]/', '', strtolower($sFileName));
		$sFileName = preg_replace('/[\.]+/', '.', $sFileName);
		$sFileName = str_replace('.', '/', $sFileName);
		if (isset($aCache[$sFileName])) {
			return true;
		} else {
			$sFileFullPath = $this->GetPath().'/managers/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath)) {
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}

		if ($bDoExitOnError) {
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__.'<br />');
		}
		
		return false;
	}	
	
	public function getParamValue($sKey, $mDefault = null)
	{
		return is_array($this->aParameters) && isset($this->aParameters[$sKey])
			? $this->aParameters[$sKey] : $mDefault;
	}
	
	public function setParamValue($sKey, $mValue)
	{
		if (is_array($this->aParameters)) {
			
			$this->aParameters[$sKey] = $mValue;
		}
	}

	/**
	 * @param string $sParamName
	 * @param mixed $oObject
	 *
	 * @return void
	 */
	protected function paramToObject($sParamName, &$oObject, $sType = 'string')
	{
		switch ($sType)
		{
			default:
			case 'string':
				$oObject->{$sParamName} = (string) $this->getParamValue($sParamName, $oObject->{$sParamName});
				break;
			case 'int':
				$oObject->{$sParamName} = (int) $this->getParamValue($sParamName, $oObject->{$sParamName});
				break;
			case 'bool':
				$oObject->{$sParamName} = '1' === (string) $this->getParamValue($sParamName, $oObject->{$sParamName} ? '1' : '0');
				break;
		}
	}
	
	/**
	 * @param mixed $oObject
	 * @param array $aParamsNames
	 */
	protected function paramsStrToObjectHelper(&$oObject, $aParamsNames)
	{
		foreach ($aParamsNames as $sName)
		{
			$this->paramToObject($sName, $oObject);
		}
	}	
	
	/**
	 * @param string $sMethod
	 * @param mixed $mResult = false
	 *
	 * @return array
	 */
	public function DefaultResponse($sMethod, $mResult = false)
	{
		$aResult = array(
			'Module' => $this->GetName(),
			'Method' => $sMethod
		);

		$aResult['Result'] = \CApiResponseManager::GetResponseObject($mResult, array(
			'Module' => $this->GetName(),
			'Method' => $sMethod,
			'Parameters' => $this->aParameters
		));
		$aResult['@Time'] = microtime(true) - PSEVEN_APP_START;
		return $aResult;
	}	
	
	/**
	 * @param string $sMethod
	 *
	 * @return array
	 */
	public function TrueResponse($sMethod)
	{
		return $this->DefaultResponse($sMethod, true);
	}

	/**
	 * @param string $sMethod
	 * @param int $iErrorCode
	 * @param string $sErrorMessage
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function FalseResponse($sMethod, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null)
	{
		$aResponseItem = $this->DefaultResponse($sMethod, false);

		if (null !== $iErrorCode) {
			
			$aResponseItem['ErrorCode'] = (int) $iErrorCode;
			if (null !== $sErrorMessage) {
				
				$aResponseItem['ErrorMessage'] = null === $sErrorMessage ? '' : (string) $sErrorMessage;
			}
		}

		if (is_array($aAdditionalParams)) {
			
			foreach ($aAdditionalParams as $sKey => $mValue) {
				$aResponseItem[$sKey] = $mValue;
			}
		}

		return $aResponseItem;
	}	
	
	/**
	 * @param string $sActionName
	 * @param \Exception $oException
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function ExceptionResponse($sActionName, $oException, $aAdditionalParams = null)
	{
		$iErrorCode = null;
		$sErrorMessage = null;

		$bShowError = \CApi::GetConf('labs.webmail.display-server-error-information', false);

		if ($oException instanceof \Core\Exceptions\ClientException) {
			$iErrorCode = $oException->getCode();
			$sErrorMessage = null;
			if ($bShowError) {
				$sErrorMessage = $oException->getMessage();
				if (empty($sErrorMessage) || 'ClientException' === $sErrorMessage) {
					$sErrorMessage = null;
				}
			}
		}
		else if ($bShowError && $oException instanceof \MailSo\Imap\Exceptions\ResponseException) {
			$iErrorCode = \Core\Notifications::MailServerError;
			
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			if ($oResponse instanceof \MailSo\Imap\Response) {
				$sErrorMessage = $oResponse instanceof \MailSo\Imap\Response ?
					$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : null;
			}
		} else {
			$iErrorCode = \Core\Notifications::UnknownError;
//			$sErrorMessage = $oException->getCode().' - '.$oException->getMessage();
		}

		return $this->FalseResponse($sActionName, $iErrorCode, $sErrorMessage, $aAdditionalParams);
	}	
	
	/**
	 * @param string $sAuthToken = ''
	 * @return \CAccount | null
	 */
	public function GetDefaultAccount($sAuthToken = '')
	{
		$oResult = null;
		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		$iUserId = $oApiIntegrator->getLogginedUserId($sAuthToken);
		if (0 < $iUserId) {
			
			$oApiUsers = \CApi::GetCoreManager('users');
			$oResult = $oApiUsers->getDefaultAccount($iUserId);
		}

		return $oResult;
	}
	
	/**
	 * @param int $iAccountId
	 * @param bool $bVerifyLogginedUserId = true
	 * @param string $sAuthToken = ''
	 * @return CAccount | null
	 */
	public function getAccount($iAccountId, $bVerifyLogginedUserId = true, $sAuthToken = '')
	{
		$oResult = null;
		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
		$iUserId = $bVerifyLogginedUserId ? $oApiIntegrator->getLogginedUserId($sAuthToken) : 1;
		if (0 < $iUserId) {
			
			$oApiUsers = \CApi::GetCoreManager('users');
			
			$oAccount = $oApiUsers->getAccountById($iAccountId);
			if ($oAccount instanceof \CAccount && 
				($bVerifyLogginedUserId && $oAccount->IdUser === $iUserId || !$bVerifyLogginedUserId) 
					&& !$oAccount->IsDisabled) {
				
				$oResult = $oAccount;
			}
		}

		return $oResult;
	}	
	
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getDefaultAccountFromParam($bThrowAuthExceptionOnFalse = true)
	{
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$oResult = $this->GetDefaultAccount($sAuthToken);
		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount)) {
			
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
		}

		return $oResult;
	}	
	
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 * @param bool $bVerifyLogginedUserId Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getAccountFromParam($bThrowAuthExceptionOnFalse = true, $bVerifyLogginedUserId = true)
	{
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$sAccountID = (string) $this->getParamValue('AccountID', '');
		if (0 === strlen($sAccountID) || !is_numeric($sAccountID)) {
			
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oResult = $this->getAccount((int) $sAccountID, $bVerifyLogginedUserId, $sAuthToken);

		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount)) {
			
			$oApiUsers = \CApi::GetCoreManager('users');
			$oExc = $oApiUsers->GetLastException();
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError,
				$oExc ? $oExc : null, $oExc ? $oExc->getMessage() : '');
		}

		return $oResult;
	}	

	public function ExecuteMethod($sMethodName, $aArguments = array())
	{
		$mResult = false;
		if (method_exists($this, $sMethodName))
		{
			$this->broadcastEvent($sMethodName . '::before', array(&$aArguments));

			$mResult = call_user_func_array(array($this, $sMethodName), $aArguments);

			$aArguments['@Result'] = $mResult;
			$this->broadcastEvent($sMethodName . '::after', array(&$aArguments));
			$mResult = $aArguments['@Result'];
		}
				
		return $mResult;
	}
	
	public function CheckNonAuthorizedMethodAllowed($sMethodName = '', $sAuthToken = '')
	{
		return !!in_array($sMethodName, array('Login'));
	}
}

