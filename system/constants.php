<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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

define('API_PATH_TO_AURORA', '/../');

define('API_CRLF', "\r\n");
define('API_TAB', "\t");
define('API_P7', true);

define('API_SESSION_WEBMAIL_NAME', 'PHPWEBMAILSESSID');
define('API_SESSION_ADMINPANEL_NAME', 'PHPWMADMINSESSID');
define('API_SESSION_CSRF_TOKEN', 'API_CSRF_TOKEN');

define('API_INC_PROTOCOL_POP3_DEF_PORT', 110);
define('API_INC_PROTOCOL_IMAP4_DEF_PORT', 143);
define('API_INC_PROTOCOL_SMTP_DEF_PORT', 25);

define('API_DEFAULT_SKIN', 'Default');
define('API_DUMMY', '*******');

define('API_HELPDESK_PUBLIC_NAME', '_helpdesk_');

// timezone fix
$sDefaultTimeZone = function_exists('date_default_timezone_get')
	? @date_default_timezone_get() : 'US/Pacific';

define('API_SERVER_TIME_ZONE', ($sDefaultTimeZone && 0 < strlen($sDefaultTimeZone))
	? $sDefaultTimeZone : 'US/Pacific');

if (defined('API_SERVER_TIME_ZONE') && function_exists('date_default_timezone_set'))
{
	@date_default_timezone_set(API_SERVER_TIME_ZONE);
}

unset($sDefaultTimeZone);
