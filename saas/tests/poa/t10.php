<?php

//Usage: install | disable | enable | configure | remove
$argv = array(
	'',
//	'install'
//	'disable'
	'configure'
);

putenv('SERVICE_ID=tenant');
putenv('SETTINGS_account_limit=5');
putenv('SETTINGS_tenant_quota_default_mb=3000');
putenv('SETTINGS_tenant_name=18xxx.ru');
putenv('SETTINGS_tenant_password=xxx');
putenv('SETTINGS_partner_login=local');
putenv('SETTINGS_partner_password=2c244d959c5240351614f82fbaac1231');
putenv('SETTINGS_site=http://localhost/p7trunk');

//putenv('SERVICE_ID=tenant');
//putenv('SETTINGS_account_limit=5');
//putenv('SETTINGS_tenant_quota_default_mb=2500');
//putenv('SETTINGS_tenant_name=Filippovskiyp@mail.ru');
//putenv('SETTINGS_tenant_password=xxx');
//putenv('SETTINGS_partner_login=dasreda');
//putenv('SETTINGS_partner_password=f128cb7765e57c71e5d7316821fb32cd');
//putenv('SETTINGS_site=http://quickme.net');


//include '../../poa-new/scripts/tenant-verify-script.php';
//include '../../poa-new/scripts/tenant-resource-script.php';
include '../../poa-new/scripts/tenant-configuration-script.php';