<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/api/IUser.php';
require_once APP_ROOTPATH.'/saas/NativeService.php';

/**
 * Класс управления пользователем.
 * @author saydex
 */
class User extends NativeService implements \saas\api\IUser
{
	/**
	 * @param interger $accountId WebMail primary account id
	 */
	function __construct($accountId = 0)
	{
		parent::__construct($accountId);
	}

	protected function idField($obj)
	{
		return $obj->IdAccount;
	}

	protected function nativeManager()
	{
		return \CApi::GetCoreManager('users');
	}

	protected function nativeDomainManager()
	{
		return \CApi::GetCoreManager('domains');
	}

	protected function findNativeById($id)
	{
		return $this->nativeManager()->getAccountById($id);
	}

	protected function nativeFieldMap()
	{
		return array(
			'Email' => 'userName',
			'IsDisabled' => 'disabled',
			'IncomingMailPassword' => 'password',
			'IncomingMailLogin' => 'primaryEmail',
			'StorageQuota' => 'quota',
			'StorageUsedSpace' => 'diskUsage'
		);
	}

	protected function createNativeService()
	{
		$domainName = \api_Utils::GetDomainFromEmail($this->nativeCachedField('Email'));

		$nativeDomain = $this->nativeDomainManager()->getDomainByName($domainName);
		if (!$nativeDomain)
		{
			Exception::throwException(new \Exception('Account domain not found'));
			return false;
		}

		return new \CAccount($nativeDomain);
	}

	/**
	 * Обновление нативного аккаунта в соответствии с поступившими данными.
	 */
	function update()
	{
		$manager = $this->nativeManager();
		$nativeAccount = $this->nativeService();
		$manager->updateAccount($nativeAccount);
	}

	function fromIterator($data)
	{
		$this->aCachedFields['IsMailingList'] = $data[0];
		$this->aCachedFields['Email'] = $data[1];
		$this->aCachedFields['FriendlyName'] = $data[2];
		$this->aCachedFields['IsDisabled'] = $data[3];
	}

	// IService implementation
	// Включение-выключение сервиса
	function disabled()
	{
		return $this->nativeField('IsDisabled');
	}

	function setDisabled($disabled = true)
	{
		$this->setNativeField('IsDisabled', $disabled);
	}

	// IUser implementation

	function userName()
	{
		return $this->nativeField('Email');
	}

	function setUserName($username)
	{
		$this->setNativeField('Email', $username);
	}

	function validatePassword($password)
	{
		return $this->nativeField('IncomingMailPassword') === $password;
	}

	function password()
	{
		return $this->nativeField('IncomingMailPassword');
	}

	function setPassword($password)
	{
		$this->setNativeField('IncomingMailPassword', $password);
	}

	function primaryEmail()
	{
		return $this->nativeField('IncomingMailLogin');
	}

	function setPrimaryEmail($email)
	{
		$this->setNativeField('IncomingMailLogin', $email);
	}

	function quota()
	{
		return $this->nativeField('StorageQuota');
	}

	function setQuota($value)
	{
		$this->setNativeField('StorageQuota', $value);
	}

	function diskUsage()
	{
		return $this->nativeField('StorageUsedSpace');
	}

	function hasCapa($name)
	{
		return $this->nativeService()->User->getCapa($name);
	}

	function setCapa($name, $value = true)
	{
		$this->nativeService()->User->setCapa($name, !!$value);
	}
}
