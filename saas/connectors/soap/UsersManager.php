<?php

namespace saas\connectors\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/BaseServiceManager.php';
require_once APP_ROOTPATH.'/saas/Authenticator.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

use
	saas\tool\soap\ArrayTraits,
	saas\api\Field
;

class UsersManager extends BaseServiceManager
{

	private $authenticator;

	function __construct()
	{
		$this->authenticator = new \saas\Authenticator();
	}

	protected function readFieldNames()
	{
		return array('disabled', 'id', 'userName', 'primaryEmail', 'quota', 'diskUsage');
	}

	protected function writeFieldNames()
	{
		return array('disabled', 'id', 'userName', 'password', 'primaryEmail', 'quota');
	}

	protected function hostManager($creds)
	{
		if (isset($creds->tenantId))
		{
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
					return $tenant->serviceManager('users');
				}
			}
		}

		$user = $this->authenticator->authenticateCustomer($creds);
		if (!$user)
		{
			$user = $this->authenticator->authenticateChannel($creds);
		}

		if ($user)
		{
			return $user->serviceManager('users');
		}

		\saas\Exception::throwException(new \Exception('Authentication failed'));
		return false;
	}

	protected function hostInstance($creds)
	{
		if (isset($creds->userId) && isset($creds->tenantId))
		{
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
					$user = $tenant->serviceManager('users')->findById($creds->userId);
					if ($user)
					{
						return $user;
					}
				}
			}
		}

		if (isset($creds->userId))
		{
			// TODO
			$tenant = $this->authenticator->authenticateCustomer($creds);
			if (!$user)
			{
				$user = $this->authenticator->authenticateChannel($creds);
			}

			if ($user)
			{
				$user = $tenant->serviceManager('users')->findById($creds->userId);
				if ($user)
				{
					return $user;
				}
			}
		}

		$user = $this->authenticator->authenticateUser($creds);
		if ($user)
		{
			return $user;
		}

		\saas\Exception::throwException(new \Exception('Authentication failed'));
		return false;
	}

	function hasCapas($aNames, $creds)
	{
		try
		{
			$object = $this->hostInstance($creds);

			$res = array();
			foreach ($aNames as $name)
			{
				$res[] = new Field($name, $object->hasCapa($name));
			}

			return $res;
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function setCapas($aFields, $creds)
	{
		try
		{
			$object = $this->hostInstance($creds);

			$fields = ArrayTraits::toPHPArray($aFields);
			foreach ($fields as $name => $value)
			{
				$object->setCapa($name, $value);
			}

			$object->update();
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}
}
