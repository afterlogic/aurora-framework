<?php

require_once dirname(__FILE__).'/helper.php';

defined('INPUT_COMMAND') || define('INPUT_COMMAND', isset($argv[1]) ? $argv[1] : '');

function tenantResourceScriptFunction() {
	return apiRequest(rtrim(getSettingsWithValidation('site'), '/\\').'/saas/connectors/simple.php', array(
		'version' => 1,
		'script' => 'tenant-resource',
		'command' => INPUT_COMMAND,
		'partner_login' => getSettingsWithValidation('partner_login'),
		'partner_password' => getSettingsWithValidation('partner_password'),
		'tenant_name' => getSettingsWithValidation('tenant_name')
	));
}

apiWrapper('tenantResourceScriptFunction');
