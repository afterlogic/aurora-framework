<?php

require_once dirname(__FILE__).'/helper.php';

defined('INPUT_COMMAND') || define('INPUT_COMMAND', isset($argv[1]) ? $argv[1] : '');

function tenantConfigurationScriptFunction() {
	return apiRequest(rtrim(getSettingsWithValidation('site'), '/\\').'/saas/connectors/simple.php', array(
		'version' => 1,
		'script' => 'tenant-configuration',
		'command' => INPUT_COMMAND,
		'tenant_name' => getSettingsWithValidation('tenant_name'),
		'tenant_password' => getSettingsWithValidation('tenant_password'),
		'partner_login' => getSettingsWithValidation('partner_login'),
		'partner_password' => getSettingsWithValidation('partner_password'),
		'account_limit' => getSettingsWithValidation('account_limit'),
		'quota' => getSettingsWithValidation('tenant_quota_default_mb'),
		'capabilities' => 'FILES HELPDESK'
	));
}

apiWrapper('tenantConfigurationScriptFunction');
