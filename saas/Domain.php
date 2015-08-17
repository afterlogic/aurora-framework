<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/libraries/afterlogic/api.php';
require_once APP_ROOTPATH.'/saas/api/IDomain.php';
require_once APP_ROOTPATH.'/saas/NativeService.php';

/**
 * Tenant proxy.
 * 
 * @author saydex
 *
 */
class Domain extends NativeService implements \saas\api\IDomain
{

	private $tenantId;

	protected function idField($obj)
	{
		return $obj->IdDomain;
	}

	protected function createNativeService()
	{
		$nativeDomain = new \CDomain('', null, $this->tenantId);
		$nativeDomain->IsDefaultDomain = false;
		$nativeDomain->IsInternal = true;
		return $nativeDomain;
	}

	protected function findNativeById($id)
	{
		return $this->nativeManager()->getDomainById($id);
	}

	protected function nativeManager()
	{
		return \CApi::Manager('domains');
	}

	protected function nativeFieldMap()
	{
		return array(
			'Name' => 'name', 'IsDisabled' => 'disabled', 'DefaultSkin' => 'skin',
			'DefaultTimeZone' => 'timeZone', 'SiteName' => 'siteName',
			'DefaultLanguage' => 'language', 'AutoCheckMailInterval' => 'checkInterval',
			'CalendarWeekStartsOn' => 'weekStartsOn'
		);
	}

	function update()
	{
		$manager = $this->nativeManager();
		$manager->updateDomain($this->nativeService());
	}

	function fromIterator($data)
	{
		$this->aCachedFields['IsInternal'] = $data[0];
		$this->aCachedFields['Name'] = $data[1];
	}

	/**
	 * Конструктор проксика.
	 * @param CUser $oUser
	 */
	function __construct($tenantId, $domainId = 0)
	{
		$this->tenantId = $tenantId;
		parent::__construct($domainId);
	}

	// IService implementation

	function disabled()
	{
		return $this->nativeField('IsDisabled');
	}

	function setDisabled($disabled = true)
	{
		$this->setNativeField('IsDisabled', $disabled);
	}

	// IDomain interface implementation

	function name()
	{
		return $this->nativeField('Name');
	}

	function setName($name)
	{
		$this->setNativeField('Name', $name);
	}

	function skin()
	{
		return $this->nativeField('DefaultSkin');
	}

	function setSkin($skinName)
	{
		$this->setNativeField('DefaultSkin', $skinName);
	}

	function timeZone()
	{
		return $this->nativeField('DefaultTimeZone');
	}

	function setTimeZone($tz)
	{
		$this->setNativeField('DefaultTimeZone', $tz);
	}

	function siteName()
	{
		return $this->nativeField('SiteName');
	}

	function setSiteName($siteName)
	{
		$this->setNativeField('SiteName', $siteName);
	}

	function language()
	{
		return $this->nativeField('DefaultLanguage');
	}

	function setLanguage($lang)
	{
		$this->setNativeField('DefaultLanguage', $lang);
	}

	function msgsPerPage()
	{
		return $this->nativeField('MailsPerPage');
	}

	function setMsgsPerPage($num)
	{
		$this->setNativeField('MailsPerPage', $num);
	}

	function checkPeriod()
	{
		return $this->nativeField('AutoCheckMailInterval');
	}

	function setCheckPeriod($period)
	{
		$this->setNativeField('AutoCheckMailInterval', $period);
	}

	function externalMailBoxes()
	{
		return $this->nativeField('AllowUsersAddNewAccounts');
	}

	function setExternalMailBoxes($en)
	{
		$this->setNativeField('AllowUsersAddNewAccounts', $en);
	}

	function weekStartsOn()
	{
		return $this->nativeField('CalendarWeekStartsOn');
	}

	function setWeekStartsOn($day)
	{
		$this->setNativeField('CalendarWeekStartsOn', $day);
	}
}
