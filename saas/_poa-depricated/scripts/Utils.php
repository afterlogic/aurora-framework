<?php

require_once dirname(__FILE__).'/StructuredOutput.php';

class InvalidSettings extends Exception
{
	private $errors;

	function __construct($errors)
	{
		parent::__construct('Settings validation failed');
		$this->errors = $errors;
	}

	function getErrors()
	{
		return $this->errors;
	}
}

function getTenantName()
{
	$errors = array();

	$partnerLogin = getenv('SETTINGS_partner_login');
	if (!$partnerLogin)
		$errors[] = makeMsg('partner_login', 'Empty Partner Administrator Login');

	$adminLogin = getenv('SETTINGS_tenant_name');
	if (!$adminLogin)
		$errors[] = makeMsg('tenant_name', 'Empty tenant login');

	if (sizeof($errors) > 0)
		throw new InvalidSettings($errors);

	return $adminLogin.'_'.$partnerLogin;
}

function settingArray($name, $old = false)
{
	$prefix = $old ? 'OLD' : '';
	$stringTemplate = $prefix.'SETTINGS_'.$name.'_';
	$res = array();
	for ($i = 1; getenv($stringTemplate.$i); ++$i)
		$res[] = getenv($stringTemplate.$i);
	return $res;
}

function settingValue($name, $old = false)
{
	$prefix = $old ? 'OLD' : '';
	if ($name === 'tenant_name')
	{
		return getTenantName();
	}
	
	return getenv($prefix.'SETTINGS_'.$name);
}

function settingCheckedValue($name, $checkFn = 'checkNotEmpty', $errorMessage = 'Invalid value')
{
	$value = settingValue($name);
	if (!$checkFn($value))
		throw new InvalidSettings(array(makeMsg($name, $errorMessage)));
	return $value;
}

function oldSettingCheckedValue($name, $checkFn = 'checkNotEmpty', $errorMessage = 'Invalid value')
{
	$value = settingValue($name, true);
	if (!$checkFn($value))
		throw new InvalidSettings(array(makeMsg($name, $errorMessage)));
	return $value;
}

function checkNotEmpty($value)
{
	return $value != "";
}
