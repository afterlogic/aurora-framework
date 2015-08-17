<?php

namespace saas\connectors\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/BaseServiceManager.php';
require_once APP_ROOTPATH.'/saas/Authenticator.php';

class TenantsManager extends BaseServiceManager
{
	private $authenticator;

	function __construct()
	{
		$this->authenticator = new \saas\Authenticator();
	}

	protected function readFieldNames()
	{
		return array('disabled', 'id', 'name', 'channelLogin', 'quota', 'userCountLimit',
			'domainCountLimit', 'diskUsage', 'userCount', 'domainCount', 'enableLogin', 'gabVisibility');
	}

	protected function writeFieldNames()
	{
		return array('disabled', 'id', 'name', 'channelLogin', 'quota', 'password', 'userCountLimit',
			'domainCountLimit', 'enableLogin', 'gabVisibility');
	}

	protected function hostManager($creds)
	{
		$user = $this->authenticator->authenticateMaster($creds);

		if (!$user)
		{
			$user = $this->authenticator->authenticateChannel($creds);
		}

		if ($user)
		{
			return $user->serviceManager('tenants');
		}

		\saas\Exception::throwException(new \Exception('Authentication failed'));
		
		return false;
	}

	protected function hostInstance($creds)
	{
		if (isset($creds->tenantId))
		{
			// authenticate master
			$user = $this->authenticator->authenticateMaster($creds);
			if ($user)
			{
				$user = $user->serviceManager('tenants')->findById($creds->tenantId);
				if ($user)
				{
					return $user;
				}
			}

			$user = $this->authenticator->authenticateChannel($creds);
			if ($user)
			{
				$user = $user->serviceManager('tenants')->findById($creds->tenantId);
				if ($user)
				{
					return $user;
				}
			}
		}

		$user = $this->authenticator->authenticateCustomer($creds);
		if ($user)
		{
			return $user;
		}

		\saas\Exception::throwException(new \Exception('Authentication failed'));
		return false;
	}

	function validatePassword($password, $creds)
	{
		try
		{
			$instance = $this->hostInstance($creds);
			
			return $instance->validatePassword($password);
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(VALIDATION_EXCEPTION, $e->getMessage());
		}
	}
}
