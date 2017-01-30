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
	 * The setting defines size of the log file extract available through adminpanel (in Kbytes)
	 */
	//unused 'log.max-view-size' => 1000,

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
	 * Encoding used for composing mails
	 */
	//unused 'webmail.default-out-charset' => 'utf-8',

	/**
	 * Defines whether messages are prefetched to minimize response time
	 * when selecting a message, WebMail Pro fetches messages from server in background.
	 */
	//unused 'webmail.use-prefetch' => true,

	/**
	 * Languages considered to be RTL ones by WebMail
	 */
	//unused 'webmail.rtl-langs' => array('Hebrew', 'Arabic', 'Persian'),

	/**
	 * X-Mailer value used in outgoing mails
	 */
	'webmail.xmailer-value' => 'AfterLogic webmail client',

	/**
	 * IMAP4 only
	 * Allow creating system folders if those are not found on mail server
	 * WebMail attempts to locate special (system) folders like Trash, Drafts, Sent Items.
	 * If particular folder is not found, WebMail can create it, and you can disable this of course.
	 */
	//unused 'webmail.create-imap-system-folders' => true,

	/**
	 * Configuration option for creating required folders on each login.
	 */
	//unused 'webmail.system-folders-sync-on-each-login' => false,

	/**
	 * IMAP4 only
	 * Flag used for marking message as Forwarded
	 * If empty, the functionality is disabled
	 */
	'webmail.forwarded-flag-name' => '$Forwarded',

	/**
	 * Memory limit set by WebMail for resource-consuming operations (in Mbytes)
	 */
	//unused 'webmail.memory-limit' => 200,

	/**
	 * Time limit set by WebMail for resource-consuming operations (in seconds)
	 */
	//unused 'webmail.time-limit' => 3000,

	/**
	 * Enable saving drafts automatically. Saving is performed once a minute,
	 * assuming it is supported by particular IMAP server.
	 * Default value: true
	 */
	//unused 'webmail.autosave' => true,

	/**
	 * Enable joining reply prefixes when subject of the answer is formed.
	 * Default value: true
	 */
	//unused 'webmail.join-reply-prefixes' => true,

	/**
	 * Enable browsers to add WebMail as an application for mailto links.
	 * Default value: true
	 */
	//unused 'webmail.allow-app-register-mailto' => true,
	
	'mailsuite' => false,

	//unused 'files' => true,

	'tenant' => false,

	'helpdesk' => false,

	'capa' => false,

	//unused 'themes' => array('Default', 'DeepForest', 'OpenWater', 'Funny', 'BlueJeans', 'White'),

	//unused 'links.outlook-sync-plugin-32' => 'http://www.afterlogic.com/download/OutlookSyncAddIn.msi',
	//unused 'links.outlook-sync-plugin-64' => 'http://www.afterlogic.com/download/OutlookSyncAddIn64.msi',
	//unused 'links.outlook-sync-read-more' => 'http://www.afterlogic.com/wiki/Outlook_sync_(Aurora)',

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

	//unused 'langs.names' => array(
//		'Arabic' => 'العربية',
//		'Bulgarian' => 'Български',
//		'Chinese-Simplified' => '中文(简体)',
//		'Chinese-Traditional' => '中文(香港)',
//		'Czech' => 'Čeština',
//		'Danish' => 'Dansk',
//		'Dutch' => 'Nederlands',
//		'English' => 'English',
//		'Estonian' => 'eesti',
//		'Finnish' => 'Suomi',
//		'French' => 'Français',
//		'German' => 'Deutsch',
//		'Greek' => 'Ελληνικά',
//		'Hebrew' => 'עברית',
//		'Hungarian' => 'Magyar',
//		'Italian' => 'Italiano',
//		'Japanese' => '日本語',
//		'Korean' => '한국어',
//		'Latvian' => 'Latviešu',
//		'Lithuanian' => 'Lietuvių',
//		'Norwegian' => 'Norsk',
//		'Persian' => 'فارسی',
//		'Polish' => 'Polski',
//		'Portuguese-Portuguese' => 'Português',
//		'Portuguese-Brazil' => 'Português Brasileiro',
//		'Romanian' => 'Română',
//		'Russian' => 'Русский',
//		'Serbian' => 'Srpski',
//		'Slovenian' => 'Slovenščina',
//		'Spanish' => 'Español',
//		'Swedish' => 'Svenska',
//		'Thai' => 'ภาษาไทย',
//		'Turkish' => 'Türkçe',
//		'Ukrainian' => 'Українська',
//		'Vietnamese' => 'tiếng Việt'
//	),

	/**
	 * Enable plugins in WebMail
	 */
	//unused 'plugins' => true,

	/**
	 * Force enabling all the plugins.
	 */
	//unused 'plugins.config.include-all' => false,

	// labs.*
	// Experimental settings
	'labs.db.use-explain' => false,
	'labs.db.use-explain-extended' => false,
	'labs.db.log-query-params' => false,
	//unused 'labs.htmleditor-default-font-name' => '',
	//unused 'labs.htmleditor-default-font-size' => '',
	'labs.log.post-view' => true,
	//unused 'labs.allow-social-integration' => true,
	'labs.use-app-min-js' => true,
	//unused 'labs.webmail.gmail-fix-folders' => true,
	//unused 'labs.webmail.custom-login-url' => '',
	//unused 'labs.webmail.custom-logout-url' => '',
	//unused 'labs.webmail.disable-folders-manual-sort' => false,
	//unused 'labs.webmail.ios-detect-on-login' => true,
	'labs.webmail.display-server-error-information' => false,
	'labs.webmail.display-inline-css' => false,
	'labs.allow-thumbnail' => true,
	'labs.allow-post-login' => false,
	//unused 'labs.allow-save-as-pdf' => false,
	'labs.dav.use-browser-plugin' => false,
	//unused 'labs.dav.use-export-plugin' => true,
	//unused 'labs.dav.use-files' => false,
	'labs.dav.admin-principal' => 'principals/admin',
	//unused 'labs.cache.i18n' => true,
	'labs.cache.templates' => true,
	'labs.cache.static' => true,
	'labs.twilio' => false,
	'labs.voice' => false,
	//unused 'labs.open-pgp' => true,
	'labs.webmail.csrftoken-protection' => true,
	//unused 'labs.webmail-client-debug' => false,
	'labs.x-frame-options' => '',
	//unused 'labs.fetchers' => true,
	//unused 'labs.simple-saas-api-key' => '',
	'labs.message-body-size-limit' => 25000,
	//unused 'labs.unlim-quota-limit-size-in-kb' => 104857600,
	'labs.use-date-from-headers' => false,
	'labs.use-body-structures-for-has-attachments-search' => false,
	//unused 'labs.google-analytic.account' => '',
	'labs.app-cookie-path' => '/',
	'labs.prefer-starttls' => true,
	'labs.server-use-url-rewrite' => false,
	'labs.server-url-rewrite-base' => '',
	'labs.db-debug-backtrace-limit' => 0,
	'labs.allow-officeapps-viewer' => true,
	//unused 'labs.mail-expand-folders' => false,
	'labs.i18n' => 'en',
	
	/* Enable Social Auth plugin */
	//unused'plugins.external-services' => true,
/*	'plugins.external-services.connectors' => array(
		'google',
		'dropbox',
		'facebook',
		'twitter',
	),
*/	
);
