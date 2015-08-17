<?php

require_once dirname(__FILE__).'/helper.php';

defined('INPUT_COMMAND') || define('INPUT_COMMAND', isset($argv[1]) ? $argv[1] : '');

function configureMboxFunction() {
	return apiRequest(rtrim(getSettingsWithValidation('site'), '/\\').'/saas/connectors/simple.php', array(
		'version' => 1,
		'script' => 'mbox-configuration',
		'command' => INPUT_COMMAND,

		'api_key' => getSettingsWithValidation('api_key'),

		'account_email' => getMail('account_EMAIL'),
		'account_login' => getMail('account_USER'),
		'account_password' => getMail('account_PASSWORD'),

		'account_imap_host' => getMail('account_IMAP_HOST'),
		'account_imap_port' => getMail('account_IMAP_PORT'),
		'account_imap_port_ssl' => getMail('account_IMAP_PORT_SSL'),

		'account_smtp_host' => getMail('account_SMTP_HOST'),
		'account_smtp_port' => getMail('account_SMTP_PORT'),
		'account_smtp_port_ssl' => getMail('account_SMTP_PORT_SSL'),

		'user_login' => getSettingsWithValidation('user_login'),
		'user_password' => getSettingsWithValidation('user_password')
	));
}

apiWrapper('configureMboxFunction');
