<?php

namespace saas\connectors\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/BaseServiceManager.php';
require_once APP_ROOTPATH.'/saas/Authenticator.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

use
	\saas\Exception
;

/**
 * SOAP реализация менеджера доменов.
 * 
 * @author saydex
 */
class DomainsManager extends BaseServiceManager
{
	private $authenticator;
	
	function __construct()
	{
		$this->authenticator = new \saas\Authenticator();
	}

	/**
	 * @return array
	 */
	protected function readFieldNames()
	{
		return array(
			'disabled', 'id', 'name', 'skin', 'timeZone',
			'siteName', 'language', 'msgsPerPage', 'checkPeriod',
			'externalMailBoxes', 'weekStartsOn'
		);
	}

	/**
	 * @return array
	 */
	protected function writeFieldNames() {
		return array(
			'disabled', 'id', 'name', 'skin', 'timeZone',
			'siteName', 'language', 'msgsPerPage', 'checkPeriod',
			'externalMailBoxes','weekStartsOn'
		);
	}
	
	protected function hostManager($creds)
	{
		if ($creds && isset($creds->tenantId))
		{
			$user = $this->authenticator->authenticateMaster($creds);
			if (!$user)
			{
				$user = $this->authenticator->authenticateChannel($creds);
			}
			 
			if ($user)
			{
				if ($creds->tenantId == 0)
				{
					// Доступ на read-only к глобальному менеджеру
					return $user->serviceManager('domains');
				}

				$tenant = $user->serviceManager('tenants')->findById($creds->tenantId);
				if ($tenant)
				{
					// Доступ к менеджеру доменов конкретного реалма
					return $tenant->serviceManager('domains');
				}
			}
		}

		$user = $this->authenticator->authenticateCustomer( $creds );
		if ($user)
		{
			return $user->serviceManager('domains');
		}
		
		Exception::throwException(new \Exception('Authentication failed'));
		return false;
	}
	
	protected function hostInstance($creds)
	{
		if ($creds && isset($creds->tenantId) && isset($creds->domainId))
		{
			if ($creds->tenantId === 0)
			{
				$user = $this->authenticator->authenticateMaster($creds);
				if (!$user)
				{
					$user = $this->authenticator->authenticateChannel($creds);
				}
				
				if ($user)
				{
					return $user->serviceManager('domains')->findById($creds->domainId);
				}
			}
			
			$user = $this->authenticator->authenticateMaster($creds);
			
			if (!$user)
			{
				$user = $this->authenticator->authenticateChannel($creds);
			}
			
			if ($user)
			{
				$tenant = $user->serviceManager('tenants')->findById($creds->tenantId);
				if ($tenant)
				{
					return $tenant->serviceManager('domains')->findById($creds->domainId);
				}
			}
		}

		if ($creds && isset($creds->domainId))
		{
			$user = $this->authenticator->authenticateCustomer($creds);
			if ($user)
			{
				return $user->serviceManager('domains')->findById($creds->domainId);
			}
		}
		
		Exception::throwException(new \Exception('Authentication failed'));
		return false;
	}
}
