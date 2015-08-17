<?php

$argv = array(
	'xxx',
	'disable'
);

//putenv('SETTINGS_account_limit=5');
//putenv('SETTINGS_gab_visibility=3');
//putenv('SETTINGS_language=ru-RU');
//putenv('SETTINGS_tenant_name=t88-1');
//putenv('SETTINGS_tenant_password=UIasioQX');
//putenv('SETTINGS_tenant_quota_default_mb=20');
//putenv('SETTINGS_skin_name=');
//putenv('SETTINGS_time_zone=0');
//putenv('SETTINGS_partner_login=dasreda');
//putenv('SETTINGS_partner_password=4f3ebcf0a7daac9206b9e456eda89d13');
//putenv('SETTINGS_site=http://221.afterlogic.com');
//putenv('SETTINGS_smtp_mx_server=mail.afterlogic.com');

//putenv('SETTINGS_tenant_name=avkosykh@dasreda.ru');
//putenv('SETTINGS_tenant_password=qwe123QWE');
//putenv('SETTINGS_tenant_quota_default_mb=20');
//putenv('SETTINGS_partner_login=dasreda_test');
//putenv('SETTINGS_partner_password=477dd036d0bc9ca298d223326423338d');
//putenv('SETTINGS_site=http://quickme.net');

putenv('SERVICE_ID=tenant');
putenv('SETTINGS_account_limit=5');
putenv('SETTINGS_tenant_quota_default_mb=2500');
putenv('SETTINGS_tenant_name=Filippovskiyp@mail.ru');
putenv('SETTINGS_tenant_password=xxx');
putenv('SETTINGS_partner_login=dasreda');
putenv('SETTINGS_partner_password=f128cb7765e57c71e5d7316821fb32cd');
putenv('SETTINGS_site=http://quickme.net');
//putenv('SETTINGS_site=http://221.afterlogic.com');

include '../../poa/scripts/configure-tenant.php';