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

$sCurrentDate = date('Y-m-d');

return array(

	/**
	 * File used for webmail logging
	 */
	'log.log-file' => "log-$sCurrentDate.txt",

	/**
	 * File used for webmail logging
	 */
	'log.custom-full-path' => '',

	/**
	 * File used for for users activity logging
	 */
	'log.event-file' => "event-$sCurrentDate.txt",

	/**
	 * Socket connection timeout limit (in seconds)
	 */
	'socket.connect-timeout' => 20,

	/**
	 * Socket stream access timeout (in seconds)
	 */
	'socket.get-timeout' => 20,

	'socket.verify-ssl' => false,

	/**
	 * X-Mailer value used in outgoing mails
	 */
	'webmail.xmailer-value' => 'AfterLogic webmail client',

	/**
	 * IMAP4 only
	 * Flag used for marking message as Forwarded
	 * If empty, the functionality is disabled
	 */
	'webmail.forwarded-flag-name' => '$Forwarded',

	'mailsuite' => false,

	'tenant' => false,

	'helpdesk' => false,

	'capa' => false,

	/*
	 * temp.cron-time-*
	 * The settings affect functionality of purging folder of temporary files
	 * when API method CApiWebmailManager->ClearTempFiles() is called
	 */

	/**
	 * Minimal timeframe between two runs of cron script (in seconds).
	 */
	'temp.cron-time-to-run' => 10800, // (3 hours)

	/**
	 * If file is older than this it is considered outdated
	 */
	'temp.cron-time-to-kill' => 10800, // (3 hours)

	/**
	 * This file stores information on last launch of the script
	 */
	'temp.cron-time-file' => '.clear.dat',

	// labs.*
	// Experimental settings
	'labs.db.use-explain' => false,
	'labs.db.use-explain-extended' => false,
	'labs.db.log-query-params' => false,
	'labs.log.post-view' => true,
	'labs.use-app-min-js' => true,
	'labs.webmail.display-server-error-information' => false,
	'labs.webmail.display-inline-css' => false,
	'labs.allow-thumbnail' => true,
	'labs.allow-post-login' => false,
	'labs.dav.use-browser-plugin' => false,
	'labs.dav.admin-principal' => 'principals/admin',
	'labs.cache.templates' => true,
	'labs.cache.static' => true,
	'labs.twilio' => false,
	'labs.voice' => false,
	'labs.webmail.csrftoken-protection' => true,
	'labs.x-frame-options' => '',
	'labs.message-body-size-limit' => 25000,
	'labs.use-date-from-headers' => false,
	'labs.use-body-structures-for-has-attachments-search' => false,
	'labs.app-cookie-path' => '/',
	'labs.prefer-starttls' => true,
	'labs.server-use-url-rewrite' => false,
	'labs.server-url-rewrite-base' => '',
	'labs.db-debug-backtrace-limit' => 0,
	'labs.allow-officeapps-viewer' => true,
	'labs.i18n' => 'en'
);
