<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

/**
 * @package Db
 * @subpackage Classes
 */
class CDbSchemaHelper
{
	/**
	 * @staticvar string $sPrefix
	 *
	 * @return string
	 */
	public static function prefix()
	{
		static $sPrefix = null;
		if (null === $sPrefix)
		{
			$oSettings =&\Aurora\System\Api::GetSettings();
			$sPrefix = $oSettings->GetConf('DBPrefix');
		}

		return $sPrefix;
	}

	/**
	 * @param string $sName
	 *
	 * @return \Aurora\System\Db\Table
	 */
	public static function getTable($sName)
	{
		$oTable = null;
		$aNames = explode('_', strtolower($sName));
		$sFunctionName = implode(array_map('ucfirst', $aNames));
		if (is_callable(array('CDbSchema', $sFunctionName)))
		{
			$oTable = call_user_func(array('CDbSchema', $sFunctionName));
		}
		return $oTable;
	}

	/**
	 * @return array
	 */
	public static function getSqlFunctions()
	{
		static $aFunctionsCache = null;
		if (null !== $aFunctionsCache)
		{
			return $aFunctionsCache;
		}

		$aFunctions = array(
			CDbSchema::functionDP1()
		);

		$aFunctionsCache = $aFunctions;
		return $aFunctionsCache;
	}

	/**
	 * @staticvar array $aTablesCache
	 *
	 * @return array
	 */
	public static function getSqlTables()
	{
		static $aTablesCache = null;
		if (null !== $aTablesCache)
		{
			return $aTablesCache;
		}

		$aTables = array();
		CDbSchemaHelper::addTablesToArray($aTables, array(
			'awm_accounts', 'awm_settings', 'awm_domains',
			'awm_folders', 'awm_folders_tree', 'awm_filters',
			'awm_messages', 'awm_messages_body', 'awm_reads',
			'awm_columns', 'awm_senders',
			'awm_mailaliases', 'awm_mailforwards', 'awm_mailinglists',
			
			'awm_addr_groups_events',

			'awm_identities', 'awm_tenants', 'awm_fetchers', 'awm_system_folders',
			'awm_channels', 'awm_folders_order', 'awm_min', 'awm_subscriptions',
			'awm_folders_order_names', 'awm_social', 'awm_tenant_socials',

			// quotas
			'awm_account_quotas', 'awm_domain_quotas', 'awm_tenant_quotas',

			// helpdesk
			'ahd_users', 'ahd_threads', 'ahd_posts', 'ahd_reads', 'ahd_attachments', 'ahd_online', 'ahd_fetcher',

			// dav
			'adav_addressbooks', 'adav_calendars', 'adav_cache', 'adav_calendarobjects',
			'adav_cards', 'adav_locks', 'adav_groupmembers', 'adav_principals',
			'adav_reminders', 'adav_calendarshares',

            'twofa_accounts',
			
			'eav_entities', 'eav_properties_int', 'eav_properties_string',  
			'eav_properties_text', 'eav_properties_bool', 'eav_properties_datetime' 
		));

		$aTablesCache = $aTables;
		return $aTablesCache;
	}

	/**
	 * @param array &$aTables
	 * @param array $aNames
	 */
	protected static function addTablesToArray(array &$aTables, array $aNames)
	{
		foreach ($aNames as $sName)
		{
			$oTable = CDbSchemaHelper::getTable($sName);
			if ($oTable)
			{
				$aTables[] = $oTable;
			}
		}
	}
}

/**
 * @package Db
 * @subpackage Classes
 */
class CDbSchema
{
	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmSettings()
	{
		return new \Aurora\System\Db\Table('awm_settings', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_setting', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_subscription', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_helpdesk_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('msgs_per_page', \Aurora\System\Db\Field::INT_SMALL, 20),
			new \Aurora\System\Db\Field('contacts_per_page', \Aurora\System\Db\Field::INT_SMALL, 20),
			new \Aurora\System\Db\Field('created_time', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('last_login', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('last_login_now', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('logins_count', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('auto_checkmail_interval', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('def_skin', \Aurora\System\Db\Field::VAR_CHAR, API_DEFAULT_SKIN),
			new \Aurora\System\Db\Field('def_editor', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('save_mail', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('def_timezone', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('def_time_fmt', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('def_lang', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('def_date_fmt', \Aurora\System\Db\Field::VAR_CHAR, EDateFormat::MMDDYYYY, 100),
			new \Aurora\System\Db\Field('mailbox_limit', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('incoming_charset', \Aurora\System\Db\Field::VAR_CHAR, 'iso-8859-1', 30),

			new \Aurora\System\Db\Field('question_1', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('answer_1', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('question_2', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('answer_2', \Aurora\System\Db\Field::VAR_CHAR),

			new \Aurora\System\Db\Field('sip_enable', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('sip_impi', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('sip_password', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('twilio_number', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('twilio_enable', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('twilio_default_number', \Aurora\System\Db\Field::BIT, 0),

			new \Aurora\System\Db\Field('files_enable', \Aurora\System\Db\Field::BIT, 1),

			new \Aurora\System\Db\Field('helpdesk_signature', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('helpdesk_signature_enable', \Aurora\System\Db\Field::BIT, 0),

			new \Aurora\System\Db\Field('use_threads', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('save_replied_messages_to_current_folder', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('desktop_notifications', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('allow_change_input_direction', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('allow_helpdesk_notifications', \Aurora\System\Db\Field::BIT, 0),

			new \Aurora\System\Db\Field('enable_open_pgp', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('allow_autosave_in_drafts', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('autosign_outgoing_emails', \Aurora\System\Db\Field::BIT, 0),

			new \Aurora\System\Db\Field('capa', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('client_timezone', \Aurora\System\Db\Field::VAR_CHAR, '', 100),
			new \Aurora\System\Db\Field('custom_fields', \Aurora\System\Db\Field::TEXT),
			
			new \Aurora\System\Db\Field('email_notification', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('password_reset_hash', \Aurora\System\Db\Field::VAR_CHAR, ''),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_setting')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_UNIQUE_KEY, array('id_user'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmFilters()
	{
		return new \Aurora\System\Db\Table('awm_filters', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_filter', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('field', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('condition', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('filter', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('action', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('id_folder', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('applied', \Aurora\System\Db\Field::BIT, 1),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_filter')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct', 'id_folder')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmFolders()
	{
		return new \Aurora\System\Db\Table('awm_folders', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_folder', \Aurora\System\Db\Field::AUTO_INT_BIG),
			new \Aurora\System\Db\Field('id_parent', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('type', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('full_path', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('sync_type', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('hide', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('fld_order', \Aurora\System\Db\Field::INT_SMALL, 1),
			new \Aurora\System\Db\Field('flags', \Aurora\System\Db\Field::VAR_CHAR, '', 255),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_folder')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct', 'id_folder')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct', 'id_parent')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmFoldersTree()
	{
		return new \Aurora\System\Db\Table('awm_folders_tree', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_folder', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_parent', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('folder_level', \Aurora\System\Db\Field::INT_SHORT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_folder')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_folder', 'id_parent')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmFoldersOrderNames()
	{
		return new \Aurora\System\Db\Table('awm_folders_order_names', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('real_name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('order_name', \Aurora\System\Db\Field::VAR_CHAR, '')
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMessages()
	{
		return new \Aurora\System\Db\Table('awm_messages', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_BIG),
			new \Aurora\System\Db\Field('id_msg', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_folder_srv', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_folder_db', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('str_uid', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('int_uid', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('from_msg', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('to_msg', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('cc_msg', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('bcc_msg', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('subject', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('msg_date', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('attachments', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('size', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('seen', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('flagged', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('priority', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('downloaded', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('x_spam', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('rtl', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('deleted', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('is_full', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('replied', \Aurora\System\Db\Field::BIT),
			new \Aurora\System\Db\Field('forwarded', \Aurora\System\Db\Field::BIT),
			new \Aurora\System\Db\Field('flags', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('body_text', \Aurora\System\Db\Field::TEXT_LONG),
			new \Aurora\System\Db\Field('grayed', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('charset', \Aurora\System\Db\Field::INT, -1),
			new \Aurora\System\Db\Field('sensitivity', \Aurora\System\Db\Field::INT_SHORT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct', 'id_folder_db')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct', 'id_folder_db', 'seen')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMessagesBody()
	{
		return new \Aurora\System\Db\Table('awm_messages_body', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_BIG),
			new \Aurora\System\Db\Field('id_msg', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('msg', \Aurora\System\Db\Field::BLOB_LONG, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_UNIQUE_KEY, array('id_acct', 'id_msg')),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmColumns()
	{
		return new \Aurora\System\Db\Table('awm_columns', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_column', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('column_value', \Aurora\System\Db\Field::INT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_user'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmReads()
	{
		return new \Aurora\System\Db\Table('awm_reads', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_read', \Aurora\System\Db\Field::AUTO_INT_BIG),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('str_uid', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('tmp', \Aurora\System\Db\Field::BIT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_read')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmTenantSocials()
	{
		return new \Aurora\System\Db\Table('awm_tenant_socials', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('social_allow', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('social_name', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('social_id', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('social_secret', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('social_api_key', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('social_scopes', \Aurora\System\Db\Field::VAR_CHAR)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}	

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmFetchers()
	{
		return new \Aurora\System\Db\Table('awm_fetchers', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_fetcher', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_domain', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('enabled', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('locked', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('mail_check_interval', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('mail_check_lasttime', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('leave_messages', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('frienly_name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('signature', \Aurora\System\Db\Field::TEXT, ''),
			new \Aurora\System\Db\Field('signature_opt', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('inc_host', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('inc_port', \Aurora\System\Db\Field::INT, 110),
			new \Aurora\System\Db\Field('inc_login', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('inc_password', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('inc_security', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('out_enabled', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('out_host', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('out_port', \Aurora\System\Db\Field::INT, 110),
			new \Aurora\System\Db\Field('out_auth', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('out_security', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('dest_folder', \Aurora\System\Db\Field::VAR_CHAR, '')
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_fetcher'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmSubscriptions()
	{
		return new \Aurora\System\Db\Table('awm_subscriptions', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_subscription', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('description', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('capa', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('limit', \Aurora\System\Db\Field::INT, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_subscription'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmIdentities()
	{
		return new \Aurora\System\Db\Table('awm_identities', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_identity', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('def_identity', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('enabled', \Aurora\System\Db\Field::BIT, 1),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('friendly_nm', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('signature', \Aurora\System\Db\Field::TEXT_MEDIUM),
			new \Aurora\System\Db\Field('signature_type', \Aurora\System\Db\Field::INT_SHORT, 1),
			new \Aurora\System\Db\Field('use_signature', \Aurora\System\Db\Field::BIT, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_identity'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMailaliases()
	{
		return new \Aurora\System\Db\Table('awm_mailaliases', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('alias_name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('alias_domain', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('alias_to', \Aurora\System\Db\Field::VAR_CHAR, '')
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMin()
	{
		return new \Aurora\System\Db\Table('awm_min', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('hash_id', \Aurora\System\Db\Field::VAR_CHAR, '', 32),
			new \Aurora\System\Db\Field('hash', \Aurora\System\Db\Field::VAR_CHAR, '', 20),
			new \Aurora\System\Db\Field('data', \Aurora\System\Db\Field::TEXT),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('hash'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMailinglists()
	{
		return new \Aurora\System\Db\Table('awm_mailinglists', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('list_name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('list_to', \Aurora\System\Db\Field::VAR_CHAR, '')
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmMailforwards()
	{
		return new \Aurora\System\Db\Table('awm_mailforwards', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('forward_name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('forward_domain', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('forward_to', \Aurora\System\Db\Field::VAR_CHAR, '')
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmAccountQuotas()
	{
		return new \Aurora\System\Db\Table('awm_account_quotas', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, '', 100),
			new \Aurora\System\Db\Field('quota_usage_messages', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0),
			new \Aurora\System\Db\Field('quota_usage_bytes', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('name'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmDomainQuotas()
	{
		return new \Aurora\System\Db\Table('awm_domain_quotas', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, '', 100),
			new \Aurora\System\Db\Field('quota_usage_messages', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0),
			new \Aurora\System\Db\Field('quota_usage_bytes', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('name'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmTenantQuotas()
	{
		return new \Aurora\System\Db\Table('awm_tenant_quotas', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, '', 100),
			new \Aurora\System\Db\Field('quota_usage_messages', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0),
			new \Aurora\System\Db\Field('quota_usage_bytes', \Aurora\System\Db\Field::INT_BIG_UNSIGNED, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('name'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmAddrGroupsEvents()
	{
		return new \Aurora\System\Db\Table('awm_addr_groups_events', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::INT_BIG, 0),
			new \Aurora\System\Db\Field('id_group', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_calendar', \Aurora\System\Db\Field::VAR_CHAR, null, 250),
			new \Aurora\System\Db\Field('id_event', \Aurora\System\Db\Field::VAR_CHAR, null, 250),
		));
	}
	
	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AcalAppointments()
	{
		return new \Aurora\System\Db\Table('acal_appointments', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_appointment', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_event', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('access_type', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('status', \Aurora\System\Db\Field::INT_SHORT, 0),
			new \Aurora\System\Db\Field('hash', \Aurora\System\Db\Field::VAR_CHAR, null, 32),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_appointment'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavAddressbooks()
	{
		return new \Aurora\System\Db\Table('adav_addressbooks', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('principaluri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('displayname', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::VAR_CHAR, null, 200),
			new \Aurora\System\Db\Field('description', \Aurora\System\Db\Field::TEXT),
			new \Aurora\System\Db\Field('ctag', \Aurora\System\Db\Field::INT_UNSIGNED, 1),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavCalendars()
	{
		return new \Aurora\System\Db\Table('adav_calendars', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('principaluri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('displayname', \Aurora\System\Db\Field::VAR_CHAR, null, 100),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('ctag', \Aurora\System\Db\Field::INT_UNSIGNED, 0),
			new \Aurora\System\Db\Field('description', \Aurora\System\Db\Field::TEXT),
			new \Aurora\System\Db\Field('calendarorder', \Aurora\System\Db\Field::INT_UNSIGNED, 0),
			new \Aurora\System\Db\Field('calendarcolor', \Aurora\System\Db\Field::VAR_CHAR, null, 10),
			new \Aurora\System\Db\Field('timezone', \Aurora\System\Db\Field::TEXT),
			new \Aurora\System\Db\Field('components', \Aurora\System\Db\Field::VAR_CHAR, null, 20),
			new \Aurora\System\Db\Field('transparent', \Aurora\System\Db\Field::BIT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavCache()
	{
		return new \Aurora\System\Db\Table('adav_cache', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('user', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('calendaruri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('type', \Aurora\System\Db\Field::INT_SHORT),
			new \Aurora\System\Db\Field('time', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('starttime', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('eventid', \Aurora\System\Db\Field::VAR_CHAR, null, 45)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavCalendarobjects()
	{
		return new \Aurora\System\Db\Table('adav_calendarobjects', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('calendardata', \Aurora\System\Db\Field::TEXT_MEDIUM),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('calendarid', \Aurora\System\Db\Field::INT_UNSIGNED, null, null, true),
			new \Aurora\System\Db\Field('lastmodified', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('etag', \Aurora\System\Db\Field::VAR_CHAR, '', 32),
			new \Aurora\System\Db\Field('size', \Aurora\System\Db\Field::INT_UNSIGNED, 0),
			new \Aurora\System\Db\Field('componenttype', \Aurora\System\Db\Field::VAR_CHAR, '', 8),
			new \Aurora\System\Db\Field('firstoccurence', \Aurora\System\Db\Field::INT_UNSIGNED),
			new \Aurora\System\Db\Field('lastoccurence', \Aurora\System\Db\Field::INT_UNSIGNED),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavCards()
	{
		return new \Aurora\System\Db\Table('adav_cards', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('addressbookid', \Aurora\System\Db\Field::INT_UNSIGNED, null, null, true),
			new \Aurora\System\Db\Field('carddata', \Aurora\System\Db\Field::TEXT_MEDIUM),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('lastmodified', \Aurora\System\Db\Field::INT_UNSIGNED),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('addressbookid'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavCalendarshares()
	{
		return new \Aurora\System\Db\Table('adav_calendarshares', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('calendarid', \Aurora\System\Db\Field::INT_UNSIGNED),
			new \Aurora\System\Db\Field('member', \Aurora\System\Db\Field::INT_UNSIGNED),
			new \Aurora\System\Db\Field('status', \Aurora\System\Db\Field::INT_SHORT_SMALL),
			new \Aurora\System\Db\Field('readonly', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('summary', \Aurora\System\Db\Field::VAR_CHAR, null, 150),
			new \Aurora\System\Db\Field('displayname', \Aurora\System\Db\Field::VAR_CHAR, null, 100),
			new \Aurora\System\Db\Field('color', \Aurora\System\Db\Field::VAR_CHAR, null, 10),
			new \Aurora\System\Db\Field('principaluri', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}
	
	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavLocks()
	{
		return new \Aurora\System\Db\Table('adav_locks', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('owner', \Aurora\System\Db\Field::VAR_CHAR, null, 100),
			new \Aurora\System\Db\Field('timeout', \Aurora\System\Db\Field::INT_UNSIGNED, null),
			new \Aurora\System\Db\Field('created', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('token', \Aurora\System\Db\Field::VAR_CHAR, null, 100),
			new \Aurora\System\Db\Field('scope', \Aurora\System\Db\Field::INT_SHORT),
			new \Aurora\System\Db\Field('depth', \Aurora\System\Db\Field::INT_SHORT),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::TEXT),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavGroupmembers()
	{
		return new \Aurora\System\Db\Table('adav_groupmembers', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('principal_id', \Aurora\System\Db\Field::INT_UNSIGNED, null, null, true),
			new \Aurora\System\Db\Field('member_id', \Aurora\System\Db\Field::INT_UNSIGNED, null, null, true)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_UNIQUE_KEY, array('principal_id', 'member_id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavPrincipals()
	{
		return new \Aurora\System\Db\Table('adav_principals', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('uri', \Aurora\System\Db\Field::VAR_CHAR, null, 255, true),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, null, 80),
			new \Aurora\System\Db\Field('vcardurl', \Aurora\System\Db\Field::VAR_CHAR, null, 80),
			new \Aurora\System\Db\Field('displayname', \Aurora\System\Db\Field::VAR_CHAR, null, 80)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_UNIQUE_KEY, array('uri'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AdavReminders()
	{
		return new \Aurora\System\Db\Table('adav_reminders', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('user', \Aurora\System\Db\Field::VAR_CHAR, null, 100, true),
			new \Aurora\System\Db\Field('calendaruri', \Aurora\System\Db\Field::VAR_CHAR, null),
			new \Aurora\System\Db\Field('eventid', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
			new \Aurora\System\Db\Field('time', \Aurora\System\Db\Field::INT, null),
			new \Aurora\System\Db\Field('starttime', \Aurora\System\Db\Field::INT, null),
			new \Aurora\System\Db\Field('allday', \Aurora\System\Db\Field::BIT, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdUsers()
	{
		return new \Aurora\System\Db\Table('ahd_users', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_helpdesk_user', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('id_system_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('is_agent', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('activated', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('activate_hash', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('blocked', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('notification_email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('social_id', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('social_type', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('language', \Aurora\System\Db\Field::VAR_CHAR, 'English', 100),
			new \Aurora\System\Db\Field('date_format', \Aurora\System\Db\Field::VAR_CHAR, '', 50),
			new \Aurora\System\Db\Field('time_format', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('password_hash', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('password_salt', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('mail_notifications', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('created', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('signature', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('signature_enable', \Aurora\System\Db\Field::BIT, 0),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_helpdesk_user'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdFetcher()
	{
		return new \Aurora\System\Db\Table('ahd_fetcher', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('last_uid', \Aurora\System\Db\Field::INT, 0),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdThreads()
	{
		return new \Aurora\System\Db\Table('ahd_threads', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_helpdesk_thread', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('str_helpdesk_hash', \Aurora\System\Db\Field::VAR_CHAR, '', 50),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_owner', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('post_count', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('last_post_id', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('last_post_owner_id', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('type', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('has_attachments', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('archived', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('notificated', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('subject', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('created', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('updated', \Aurora\System\Db\Field::DATETIME),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_helpdesk_thread'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdAttachments()
	{
		return new \Aurora\System\Db\Table('ahd_attachments', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_helpdesk_attachment', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('id_helpdesk_post', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_helpdesk_thread', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_owner', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('created', \Aurora\System\Db\Field::DATETIME),
			new \Aurora\System\Db\Field('size_in_bytes', \Aurora\System\Db\Field::INT_UNSIGNED),
			new \Aurora\System\Db\Field('file_name', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('hash', \Aurora\System\Db\Field::TEXT),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_helpdesk_attachment'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdPosts()
	{
		return new \Aurora\System\Db\Table('ahd_posts', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_helpdesk_post', \Aurora\System\Db\Field::AUTO_INT_UNSIGNED),
			new \Aurora\System\Db\Field('id_helpdesk_thread', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_owner', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('type', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('system_type', \Aurora\System\Db\Field::INT_SMALL, 0),
			new \Aurora\System\Db\Field('text', \Aurora\System\Db\Field::TEXT),
			new \Aurora\System\Db\Field('deleted', \Aurora\System\Db\Field::BIT, 0),
			new \Aurora\System\Db\Field('created', \Aurora\System\Db\Field::DATETIME),
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id_helpdesk_post'))
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdReads()
	{
		return new \Aurora\System\Db\Table('ahd_reads', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_owner', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('id_helpdesk_thread', \Aurora\System\Db\Field::INT),
			new \Aurora\System\Db\Field('last_post_id', \Aurora\System\Db\Field::INT),
		));
	}

	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AhdOnline()
	{
		return new \Aurora\System\Db\Table('ahd_online', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id_helpdesk_thread', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_helpdesk_user', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_tenant', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR, ''),
			new \Aurora\System\Db\Field('ping_time', \Aurora\System\Db\Field::INT, 0)
		));
	}
	
	/**
	 * @return \Aurora\System\Db\Table
	 */
	public static function AwmSocial()
	{
		return new \Aurora\System\Db\Table('awm_social', CDbSchemaHelper::prefix(), array(
			new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
			new \Aurora\System\Db\Field('id_acct', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('id_social', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('type', \Aurora\System\Db\Field::INT, 0),
			new \Aurora\System\Db\Field('type_str', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('email', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('access_token', \Aurora\System\Db\Field::TEXT),
			new \Aurora\System\Db\Field('refresh_token', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('scopes', \Aurora\System\Db\Field::VAR_CHAR),
			new \Aurora\System\Db\Field('disabled', \Aurora\System\Db\Field::BIT, 0)
		), array(
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id')),
			new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_INDEX, array('id_acct'))
		));
	}

    /**
     * @return \Aurora\System\Db\Table
     */
    public static function TwofaAccounts()
    {
        return new \Aurora\System\Db\Table('twofa_accounts', CDbSchemaHelper::prefix(), array(
            new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT),
            new \Aurora\System\Db\Field('account_id', \Aurora\System\Db\Field::INT),
            new \Aurora\System\Db\Field('auth_type', \Aurora\System\Db\Field::VAR_CHAR, ETwofaType::AUTH_TYPE_AUTHY),
            new \Aurora\System\Db\Field('data_type', \Aurora\System\Db\Field::INT, ETwofaType::DATA_TYPE_AUTHY_ID),
            new \Aurora\System\Db\Field('data_value', \Aurora\System\Db\Field::VAR_CHAR)
        ), array(
            new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
        ));
    }
	
    /**
     * @return \Aurora\System\Db\Table
     */
    public static function EavEntities()
    {
        return new \Aurora\System\Db\Table('eav_entities', CDbSchemaHelper::prefix(), array(
            new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_BIG_UNSIGNED),
            new \Aurora\System\Db\Field('entity_type', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
            new \Aurora\System\Db\Field('module_name', \Aurora\System\Db\Field::VAR_CHAR, null, 255)
        ), array(
            new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
        ));
    }	
	
    /**
     * @return \Aurora\System\Db\Table
     */
    public static function EavAttributesInt()
    {
        return new \Aurora\System\Db\Table('eav_attributes_int', CDbSchemaHelper::prefix(), array(
            new \Aurora\System\Db\Field('id', \Aurora\System\Db\Field::AUTO_INT_BIG_UNSIGNED),
            new \Aurora\System\Db\Field('id_entity', \Aurora\System\Db\Field::INT_BIG_UNSIGNED),
            new \Aurora\System\Db\Field('name', \Aurora\System\Db\Field::VAR_CHAR, null, 255),
            new \Aurora\System\Db\Field('value', \Aurora\System\Db\Field::INT)
        ), array(
            new \Aurora\System\Db\Key(\Aurora\System\Db\Key::TYPE_PRIMARY_KEY, array('id'))
        ));
    }	
		
	/**
	 * @return CDbFunction
	 */
	public static function functionDP1()
	{
		return new CDbFunction('DP1', 'password VARCHAR(255)', 'VARCHAR(128)',
'DETERMINISTIC
READS SQL DATA
BEGIN
	DECLARE result VARCHAR(128) DEFAULT \'\';
	DECLARE passwordLen INT;
	DECLARE decodeByte CHAR(3);
	DECLARE plainBytes VARCHAR(128);
	DECLARE startIndex INT DEFAULT 3;
	DECLARE currentByte INT DEFAULT 1;
	DECLARE hexByte CHAR(3);

	SET passwordLen = LENGTH(password);
	IF passwordLen > 0 AND passwordLen % 2 = 0 THEN
		SET decodeByte = CONV((SUBSTRING(password, 1, 2)), 16, 10);
		SET plainBytes = UNHEX(SUBSTRING(password, 1, 2));

		REPEAT
			SET hexByte = CONV((SUBSTRING(password, startIndex, 2)), 16, 10);
			SET plainBytes = CONCAT(plainBytes, UNHEX(HEX(hexByte ^ decodeByte)));

			SET startIndex = startIndex + 2;
			SET currentByte = currentByte + 1;

		UNTIL startIndex > passwordLen
		END REPEAT;

		SET result = plainBytes;
	END IF;

	RETURN result;
END');

	}
}
