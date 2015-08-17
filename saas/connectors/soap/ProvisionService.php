<?php

namespace saas\connectors\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/ServiceException.php';
require_once APP_ROOTPATH.'/saas/Authenticator.php';

/**
 * NEC Service provision implementation.
 * 
 * @author saydex
 */
class ProvisionService
{
	/**
	 * @var \saas\IAuthenticator
	 */
	private $authenticator ;

	function __construct($authenticator)
	{
		$this->authenticator = $authenticator ;
	}
	
	/**
	 * Возвращает менеджер релмов.
	 * 
	 * @param mixed $request SOAP-запрос
	 * @return mixed
	 * 
	 * @throws ServiceException
	 */
	protected function GetTenantsManager($request)
	{
		$user_creds = new \stdClass;
		$user_creds->login = $request->AdminUserName;
		$user_creds->password = $request->AdminPassWord;

		$oUser = $this->authenticator->authenticate($user_creds);
		if (!$oUser)
		{
			throw new ServiceException(AUTH_EXCEPTION);
		}

		return $oUser->serviceManager('tenants');
	}

	/**
	 * Возвращает Реалм по SOAP запросу.
	 * 
	 * @param mixed $request SOAP-запрос.
	 * @return mixed
	 * 
	 * @throws ServiceException
	 */
	protected function GetTenant($request)
	{
		$oTenantManager = $this->GetTenantsManager($request);
		if ($oTenantManager === false)
		{
			log_error('User '.$request->UserName.' hasn\'t admin privileges');
			throw new ServiceException(AUTH_EXCEPTION);
		}
			
		return $oTenantManager->findByName($request->TenantName);
	}

	/**
	 * @param mixed $request SOAP-запрос.
	 * @return mixed
	 */
   	function InsertTenant($request)
	{
   		try
		{
			$oTenantManager = $this->GetTenantsManager($request);
			if (!$oTenantManager)
			{
				log_error('User '.$request->UserName.' hasn\'t admin privileges');
				return ServiceException::CreateSoapFault(AUTH_EXCEPTION) ;
			}
			
			// Checks for tenant existsing
			if( $oTenantManager->findByName($request->TenantName) !== false)
			{
				return ServiceException::CreateSoapFault(DUPLICATE_TENANT_EXCEPTION);
			}
	
			$tenant = $oTenantManager->createService();
			$tenant->setName($request->TenantName);
			$tenant->setChannelLogin($request->AdminUserName);
			
			if (isset($request->CustomFields))
			{
				$customFields = $request->CustomFields;
				foreach($customFields as $customField)
				{
					if ($customField->FieldName === 'userCountLimit')
					{
						$tenant->setUserCountLimit($customField->FieldValue);
					}
				}
			}

			if(!$oTenantManager->addInstance($tenant))
			{
				return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION);
			}

		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
    }
    
    function EnableTenant($request)
	{
    	try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

    		$oTenant->setDisabled(false);
    		$oTenant->update();
    		
    	}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
    }

    function DisableTenant($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oTenant->setDisabled(true);
			$oTenant->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function updateTenant($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oTenant->setUserCountLimit($request->UserCountLimit);
			$oTenant->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function RemoveTenant($request)
	{
		try
		{
			$oTenantsManager = $this->GetTenantsManager($request);
			if (!$oTenantsManager)
			{
				return ServiceException::CreateSoapFault(AUTH_EXCEPTION);
			}

			if (!$oTenantsManager->removeInstance($oTenantsManager->findByName($request->TenantName)))
			{
				return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION);
			}
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function InsertUser($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUserInfo = $request->UserInfo;
			$oUsersManager = $oTenant->serviceManager('users');
			if ($oUsersManager->findByUserName($oUserInfo->UserName))
			{
				return ServiceException::CreateSoapFault(DUPLICATE_USER_EXCEPTION);
			}

			$oDomainsManager = $oTenant->serviceManager('domains');
			$sDomainName = \api_Utils::GetDomainFromEmail($oUserInfo->UserName);

			$oDomain = $oDomainsManager->findByName($sDomainName);
			if (!$oDomain)
			{
				$oDomain = $oDomainsManager->createService();
				$oDomain->setName($sDomainName);
				
				if (!$oDomainsManager->addInstance($oDomain))
				{
					return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION);
				}
			}

			$oUser = $oUsersManager->createService();
			$oUser->setUserName($oUserInfo->UserName);
			$oUser->setPassword($oUserInfo->PassWord);
			$oUser->setPrimaryEmail($oUserInfo->Email);

			if (!$oUsersManager->addInstance($oUser))
			{
				return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION);
			}

			if (isset($request->CustomFields) && is_array($request->CustomFields))
			{
				$aCustomFields = $request->CustomFields;
				foreach ($aCustomFields as $customField)
				{
					if ($customField->FieldName === 'userCountLimit')
					{
						$oTenant->setUserCountLimit($customField->FieldValue);
					}
				}
			}
			
			$oTenant->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function UpdateUser($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUserInfo = $request->UserInfo;

			$oUsersManager = $oTenant->serviceManager('users');
			$oUser = $oUsersManager->findByUserName($oUserInfo->UserName);
			if (!$oUser)
			{
				return ServiceException::CreateSoapFault(USER_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUser->setUserName($oUserInfo->UserName);
			$oUser->setPassword($oUserInfo->PassWord);
			$oUser->setPrimaryEmail($oUserInfo->Email);
			$oUser->update();

			if (isset($request->CustomFields) && is_array($request->CustomFields))
			{
				$aCustomFields = $request->CustomFields;
				foreach ($aCustomFields as $customField)
				{
					if ($customField->FieldName === 'userCountLimit')
					{
						$oTenant->setUserCountLimit($customField->FieldValue);
					}
				}
			}

			$oTenant->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function SelectUser($oRequest)
	{
		try
		{
			$oTenant = $this->GetTenant($oRequest);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');

			$oUser = $oUsersManager->findByUserName($oRequest->UserName);
			if (!$oUser)
			{
				return ServiceException::CreateSoapFault(USER_DOES_NOT_EXIST_EXCEPTION);
			}

			$aRes = array(
				'UserName' => $oUser->userName(),
				'PassWord' => $oUser->password(),
				'Email' => $oUser->primaryEmail(),
				'TenantName' => $oRequest->TenantName,
			);

			return $aRes;
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function RemoveUser($oRequest)
	{
		try
		{
			$oTenant = $this->GetTenant($oRequest);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');

			$oUser = $oUsersManager->findByUserName($oRequest->UserName);
			if (!$oUser)
			{
				return ServiceException::CreateSoapFault(USER_DOES_NOT_EXIST_EXCEPTION);
			}

			if (!$oUsersManager->removeInstance($oUser))
			{
				return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION);
			}
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function EnableUser($oRequest)
	{
		try
		{
			$oTenant = $this->GetTenant($oRequest);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');

			$oUser = $oUsersManager->findByUserName($oRequest->UserName);
			if (!$oUser)
			{
				return ServiceException::CreateSoapFault(USER_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUser->setDisabled(false);
			$oUser->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function DisableUser($oRequest)
	{
		try
		{
			$oTenant = $this->GetTenant($oRequest);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');

			$oUser = $oUsersManager->findByUserName($oRequest->UserName);
			if (!$oUser)
			{
				return ServiceException::CreateSoapFault(USER_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUser->setDisabled(true);
			$oUser->update();
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function EnableAllUsers($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');
			$aUsers = $oUsersManager->instances();

			if (is_array($aUsers))
			{
				foreach ($aUsers as $oUser)
				{
					$oUser->setDisabled(false);
					$oUser->update();
				}
			}
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function DisableAllUsers($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');
			$aUsers = $oUsersManager->instances();

			if (is_array($aUsers))
			{
				foreach ($aUsers as $oUser)
				{
					$oUser->setDisabled(true);
					$oUser->update();
				}
			}
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function RemoveAllUsers($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');

			$aIt = $oUsersManager->instances();
			if (is_array($aIt))
			{
				foreach ($aIt as $user)
				{
					$oUsersManager->removeInstance($user);
				}
			}
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function SelectAllUsers($request)
	{
		try
		{
			$oTenant = $this->GetTenant($request);
			if (!$oTenant)
			{
				return ServiceException::CreateSoapFault(TENANT_DOES_NOT_EXIST_EXCEPTION);
			}

			$oUsersManager = $oTenant->serviceManager('users');
			$aIt = $oUsersManager->instances();

			$aRes = array();
			foreach ($aIt as $user)
			{
				$aNecUser = array(
					'UserName' => $user->userName(),
					'PassWord' => $user->password(),
					'Email' => $user->primaryEmail(),
					'TenantName' => $request->TenantName
				);

				$aRes[] = $aNecUser;
			}

			return array('users' => $aRes);
		}
		catch (ServiceException $se)
		{
			return $se->getSoapFault();
		}
		catch (Exception $e)
		{
			return SoapException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}
}
