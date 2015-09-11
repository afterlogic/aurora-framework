
'use strict';

var
	index = 0,
	name = '',
	crlf = '\n',
	pkg = require('./package.json'),
	cfg = {
		license:
crlf +
'/*!' + crlf +
' * Copyright 2004-2015, AfterLogic Corp.' + crlf +
' * Licensed under AGPLv3 license or AfterLogic license' + crlf +
' * if commerical version of the product was purchased.' + crlf +
' * See the LICENSE file for a full license statement.' + crlf +
' */' + crlf + crlf,
		paths: {},
		watch: [],
		summary: {
			verbose: true,
			reasonCol: 'cyan,bold',
			codeCol: 'green'
		},
		uglify: {
			mangle: true,
			compress: true,
			drop_console: true,
			preserveComments: 'some'
		}
	},

	path = require('path'),
	gulp = require('gulp'),
	concat = require('gulp-concat-util'),
	header = require('gulp-header'),
	footer = require('gulp-footer'),
	rename = require('gulp-rename'),
	expect = require('gulp-expect-file'),
	minifyCss = require('gulp-minify-css'),
	less = require('gulp-less'),
	jshint = require('gulp-jshint'),
	uglify = require('gulp-uglify'),
	eol = require('gulp-eol'),
	gutil = require('gulp-util')
;

cfg.paths.skins = ['Default', 'Netvision', 'White', 'Quickme', 'DeepForest', 'Autumn', 'OpenWater', 'BlueJeans', 'Blue', 'Ecloud', 'Funny'];

cfg.paths.css = {
	libs: {
		dest: 'static/css/',
		name: 'libs.css',
		src: [
			"dev/Styles/normalize/normalize.css",
			"dev/Vendors/jquery-ui-1.10.4.custom/css/smoothness/jquery-ui-1.10.4.custom.min.css",
//			"dev/Vendors/fullcalendar-2.2.3/fullcalendar.min.css",
			"dev/Vendors/fullcalendar-3.2.1/fullcalendar.min.css",
			"dev/Vendors/inputosaurus/inputosaurus.css"
		]
	}
};

cfg.paths.js = {
	libs: {
		dest: 'static/js/',
		name: 'libs.js',
		watch: true,
		src: [
			"dev/Vendors/jquery-1.8.2.min.js",
			"dev/Vendors/modernizr.js",
			"dev/Vendors/underscore-min.js",
			"dev/Vendors/moment-with-locales.min.js",
			"dev/Vendors/json2.min.js",
			"dev/Vendors/signals-1.0.0.min.js",
			"dev/Vendors/hasher-1.1.3.min.js",
			"dev/Vendors/crossroads-0.12.0.min.js",
			"dev/Vendors/jua/jua.min.js",
			"dev/Vendors/knockout-2.3.0.js",
			"dev/Vendors/jquery-mousewheel.js",
			"dev/Vendors/customscroll.js",
			"dev/Vendors/jquery-ui-1.9.2.custom/js/jquery-ui-1.9.2.custom.min.js",
			"dev/Vendors/jstz/jstz.min.js",
			"dev/Vendors/crea.js",
			"dev/Vendors/favico/favico-0.3.5.min.js",
			"dev/Vendors/fullcalendar-2.2.3/fullcalendar.min.js",
//			"dev/Vendors/fullcalendar-3.2.1/fullcalendar.min.js",
			"dev/Vendors/knockout-sortable.js",
			"dev/Vendors/jquery.finger.min.js",
			"dev/Vendors/jquery.cookie.js",
			"dev/Vendors/inputosaurus/inputosaurus.js",
			"dev/Vendors/media-query-polyfill/media.match.min.js"
		]
	},

	extutils: {
		dest: 'static/js/',
		name: 'extutils.js',
		watch: true,
		header: '(function (window) {\n',
		footer: '\n\n}(window));',
		src: [
			"dev/Vendors/jquery-1.8.2.min.js",
			"dev/Vendors/underscore-min.js",
			"dev/Common/Utils/@ExtUtilsDeclaration.js",
			"dev/Common/Utils/CommonUtils.js",
			"dev/Common/Utils/FileUtils.js",
			"dev/Common/Utils/MessageUtils.js"
		]
	},
	app: {
		dest: 'static/js/',
		name: 'app.js',
		min: 'app.min.js',
		lint: true,
		watch: true,
		afterlogic: true,
		header: '(function ($, window, ko, crossroads, hasher) {\n',
		footer: '\n\n}(jQuery, window, ko, crossroads, hasher));',
		src: [
			"dev/Common/@Begin.js",

			"dev/Common/Browser.js",
			"dev/Common/Ajax.js",
			"dev/Common/Constants.js",
			"dev/Common/Enums.js",
			"dev/Common/Utils.js",
			"dev/Common/Utils/CalendarUtils.js",
			"dev/Common/Utils/CommonUtils.js",
			"dev/Common/Utils/FileUtils.js",
			"dev/Common/Utils/MessageUtils.js",
			"dev/Common/Utils/AddressUtils.js",
			"dev/Common/Knockout.js",
			"dev/Common/Routing.js",
			"dev/Common/LinkBuilder.js",
			"dev/Common/MessageSender.js",
			"dev/Common/Prefetcher.js",
			"dev/Common/Selector.js",
			"dev/Common/Splitter.js",
			"dev/Common/Autocomplete.js",
			"dev/Common/Api.js",
			"dev/Common/ApiMail.js",
			"dev/Common/ApiContacts.js",
			"dev/Common/ApiFiles.js",
			"dev/Common/AfterLogicApi.js",
			"dev/Common/Storage.js",
			"dev/Common/Phone.js",
			"dev/Common/PhoneWebrtc.js",
			"dev/Common/PhoneFlash.js",
			"dev/Common/PhoneTwilio.js",
			"dev/Common/OpenPgp/OpenPgp.js",
			"dev/Common/OpenPgp/OpenPgpKey.js",
			"dev/Common/OpenPgp/OpenPgpResult.js",

			"dev/Popups/ConfirmAnotherMessageComposedPopup.js",
			"dev/Popups/AlertPopup.js",
			"dev/Popups/ConfirmPopup.js",
			"dev/Popups/AccountCreatePopup.js",
			"dev/Popups/AccountCreateIdentityPopup.js",
			"dev/Popups/FetcherAddPopup.js",
			"dev/Popups/FolderSystemPopup.js",
			"dev/Popups/FolderCreatePopup.js",
			"dev/Popups/ChangePasswordPopup.js",
			"dev/Popups/FileStorageFolderCreatePopup.js",
			"dev/Popups/FileStorageLinkCreatePopup.js",
			"dev/Popups/FileStorageRenamePopup.js",
			"dev/Popups/FileStorageSharePopup.js",
			"dev/Popups/FileStoragePopup.js",
			"dev/Popups/CalendarPopup.js",
			"dev/Popups/CalendarImportPopup.js",
			"dev/Popups/CalendarSharePopup.js",
			"dev/Popups/CalendarGetLinkPopup.js",
			"dev/Popups/CalendarEventPopup.js",
			"dev/Popups/CalendarEditRecurrenceEventPopup.js",
			"dev/Popups/CalendarSelectCalendarsPopup.js",
			"dev/Popups/PhonePopup.js",
			"dev/Popups/GenerateOpenPgpKeyPopup.js",
			"dev/Popups/ShowOpenPgpKeyArmorPopup.js",
			"dev/Popups/ImportOpenPgpKeyPopup.js",
			"dev/Popups/OpenPgpEncryptPopup.js",
			"dev/Popups/ContactCreatePopup.js",
			"dev/Popups/PlayerPopup.js",

			"dev/Models/Common/AppSettingsModel.js",
			"dev/Models/Common/UserSettingsModel.js",
			"dev/Models/Common/AccountModel.js",
			"dev/Models/Common/AccountListModel.js",
			"dev/Models/Common/AddressModel.js",
			"dev/Models/Common/AddressListModel.js",
			"dev/Models/Common/DateModel.js",
			"dev/Models/Common/IdentityModel.js",
			"dev/Models/Common/FileModel.js",

			"dev/Models/Mail/MailAttachmentModel.js",
			"dev/Models/Mail/FolderModel.js",
			"dev/Models/Mail/FolderListModel.js",
			"dev/Models/Mail/MessageModel.js",
			"dev/Models/Mail/UidListModel.js",
			"dev/Models/Mail/IcalModel.js",
			"dev/Models/Mail/VcardModel.js",

			"dev/Models/Contacts/ContactModel.js",
			"dev/Models/Contacts/GroupModel.js",
			"dev/Models/Contacts/ContactListModel.js",

			"dev/Models/Calendar/CalendarModel.js",
			"dev/Models/Calendar/CalendarListModel.js",

			"dev/Models/FileStorage/FileModel.js",

			"dev/Models/Helpdesk/PostModel.js",
			"dev/Models/Helpdesk/ThreadListModel.js",
			"dev/Models/Helpdesk/HelpdeskAttachmentModel.js",

			"dev/Models/Settings/SignatureModel.js",
			"dev/Models/Settings/AutoresponderModel.js",
			"dev/Models/Settings/FetcherModel.js",
			"dev/Models/Settings/FetcherListModel.js",
			"dev/Models/Settings/ForwardModel.js",
			"dev/Models/Settings/SieveFiltersModel.js",
			"dev/Models/Settings/SieveFilterModel.js",

			"dev/ViewModels/Common/InformationViewModel.js",
			"dev/ViewModels/Common/HeaderBaseViewModel.js",
			"dev/ViewModels/Common/HeaderViewModel.js",
			"dev/ViewModels/Common/PageSwitcherViewModel.js",
			"dev/ViewModels/Common/HtmlEditorViewModel.js",
			"dev/ViewModels/Common/ColorPickerViewModel.js",
			"dev/ViewModels/Common/PhoneViewModel.js",

			"dev/ViewModels/Login/WrapLoginViewModel.js",
			"dev/ViewModels/Login/LoginViewModel.js",
			"dev/ViewModels/Login/ForgotViewModel.js",
			"dev/ViewModels/Login/RegisterViewModel.js",

			"dev/ViewModels/Mail/FolderListViewModel.js",
			"dev/ViewModels/Mail/MessageListViewModel.js",
			"dev/ViewModels/Mail/MessagePaneViewModel.js",
			"dev/ViewModels/Mail/MailViewModel.js",
			"dev/ViewModels/Mail/ComposeViewModel.js",
			"dev/ViewModels/Mail/SenderSelector.js",
			"dev/Popups/ComposePopup.js",

			"dev/ViewModels/Contacts/ContactsImportViewModel.js",
			"dev/ViewModels/Contacts/ContactsViewModel.js",

			"dev/ViewModels/Settings/SettingsViewModel.js",
			"dev/ViewModels/Settings/CommonSettingsViewModel.js",
			"dev/ViewModels/Settings/EmailAccountsSettingsViewModel.js",
			"dev/ViewModels/Settings/CalendarSettingsViewModel.js",
			"dev/ViewModels/Settings/MobileSyncSettingsViewModel.js",
			"dev/ViewModels/Settings/OutLookSyncSettingsViewModel.js",
			"dev/ViewModels/Settings/ResetPasswordViewModel.js",
			"dev/ViewModels/Settings/PgpSettingsViewModel.js",
			"dev/ViewModels/Settings/AccountPropertiesViewModel.js",
			"dev/ViewModels/Settings/AccountFoldersViewModel.js",
			"dev/ViewModels/Settings/AccountSignatureViewModel.js",
			"dev/ViewModels/Settings/AccountForwardViewModel.js",
			"dev/ViewModels/Settings/AccountAutoresponderViewModel.js",
			"dev/ViewModels/Settings/AccountFiltersViewModel.js",
			"dev/ViewModels/Settings/FetcherIncomingViewModel.js",
			"dev/ViewModels/Settings/FetcherOutgoingViewModel.js",
			"dev/ViewModels/Settings/FetcherSignatureViewModel.js",
			"dev/ViewModels/Settings/HelpdeskSettingsViewModel.js",
			"dev/ViewModels/Settings/CloudStorageSettingsViewModel.js",
			"dev/ViewModels/Settings/IdentityPropertiesViewModel.js",
			"dev/ViewModels/Settings/ServerPropertiesViewModel.js",

			"dev/ViewModels/Calendar/CalendarViewModel.js",

			"dev/ViewModels/FileStorage/FileStorageViewModel.js",

			"dev/ViewModels/Helpdesk/HelpdeskViewModel.js",

			"dev/Common/Screens.js",
			"dev/Common/ScreensMail.js",

			"dev/Common/CacheMail.js",
			"dev/Common/CacheContacts.js",
			"dev/Common/CacheCalendar.js",

			"dev/AbstractApp.js",
			"dev/AppBase.js",
			"dev/AppMain.js",

			"dev/Common/@EndAppMain.js",
			"dev/Common/@End.js"
		]
	},
	app_mobile: {
		dest: 'static/js/',
		name: 'app-mobile.js',
		min: 'app-mobile.min.js',
		lint: true,
		watch: true,
		afterlogic: true,
		header: '(function ($, window, ko, crossroads, hasher) {\n',
		footer: '\n\n}(jQuery, window, ko, crossroads, hasher));',
		src: [
			"dev/Common/@Begin.js",
			"dev/Common/@BeginMobile.js",

			"dev/Common/Browser.js",
			"dev/Common/Ajax.js",
			"dev/Common/Constants.js",
			"dev/Common/Enums.js",
			"dev/Common/Utils.js",
			"dev/Common/Utils/CommonUtils.js",
			"dev/Common/Utils/FileUtils.js",
			"dev/Common/Utils/MessageUtils.js",
			"dev/Common/Utils/AddressUtils.js",
			"dev/Common/Knockout.js",
			"dev/Common/Routing.js",
			"dev/Common/LinkBuilder.js",
			"dev/Common/MessageSender.js",
			"dev/Common/Prefetcher.js",
			"dev/Common/Selector.js",
//			"dev/Common/Splitter.js",
			"dev/Common/Api.js",
			"dev/Common/AfterLogicApi.js",
			"dev/Common/Storage.js",
//			"dev/Common/Phone.js",
//			"dev/Common/PhoneWebrtc.js",
//			"dev/Common/PhoneFlash.js",
//			"dev/Common/PhoneTwilio.js",
			"dev/Common/OpenPgp/OpenPgp.js",
			"dev/Common/OpenPgp/OpenPgpKey.js",
			"dev/Common/OpenPgp/OpenPgpResult.js",

			"dev/Popups/AlertPopup.js",
			"dev/Popups/ConfirmPopup.js",
//			"dev/Popups/AccountCreatePopup.js",
//			"dev/Popups/AccountCreateIdentityPopup.js",
//			"dev/Popups/FetcherAddPopup.js",
//			"dev/Popups/FolderSystemPopup.js",
//			"dev/Popups/FolderCreatePopup.js",
//			"dev/Popups/ChangePasswordPopup.js",
//			"dev/Popups/FileStorageFolderCreatePopup.js",
//			"dev/Popups/FileStorageRenamePopup.js",
//			"dev/Popups/FileStorageSharePopup.js",
//			"dev/Popups/CalendarPopup.js",
//			"dev/Popups/CalendarSharePopup.js",
//			"dev/Popups/CalendarGetLinkPopup.js",
//			"dev/Popups/CalendarEventPopup.js",
//			"dev/Popups/CalendarEditRecurrenceEventPopup.js",
//			"dev/Popups/PhonePopup.js",
//			"dev/Popups/GooglePickerPopup.js",
//			"dev/Popups/ContactCreatePopup.js",
//			"dev/Popups/PlayerPopup.js",
			"dev/Popups/ImportOpenPgpKeyPopup.js",

			"dev/Models/Common/AppSettingsModel.js",
			"dev/Models/Common/UserSettingsModel.js",
			"dev/Models/Common/AccountModel.js",
			"dev/Models/Common/AccountListModel.js",
			"dev/Models/Common/AddressModel.js",
			"dev/Models/Common/AddressListModel.js",
			"dev/Models/Common/DateModel.js",
			"dev/Models/Common/IdentityModel.js",
			"dev/Models/Common/FileModel.js",

			"dev/Models/Mail/MailAttachmentModel.js",
			"dev/Models/Mail/FolderModel.js",
			"dev/Models/Mail/FolderListModel.js",
			"dev/Models/Mail/MessageModel.js",
			"dev/Models/Mail/UidListModel.js",
			"dev/Models/Mail/IcalModel.js",
			"dev/Models/Mail/VcardModel.js",

			"dev/Models/Contacts/ContactModel.js",
			"dev/Models/Contacts/GroupModel.js",
			"dev/Models/Contacts/ContactListModel.js",

//			"dev/Models/Calendar/CalendarModel.js",
//			"dev/Models/Calendar/CalendarListModel.js",

//			"dev/Models/FileStorage/FileModel.js",

//			"dev/Models/Helpdesk/PostModel.js",
//			"dev/Models/Helpdesk/ThreadListModel.js",
//			"dev/Models/Helpdesk/HelpdeskAttachmentModel.js",

			"dev/Models/Settings/SignatureModel.js",
			"dev/Models/Settings/AutoresponderModel.js",
			"dev/Models/Settings/FetcherModel.js",
			"dev/Models/Settings/FetcherListModel.js",
			"dev/Models/Settings/ForwardModel.js",
			"dev/Models/Settings/SieveFiltersModel.js",
			"dev/Models/Settings/SieveFilterModel.js",

			"dev/ViewModels/Common/InformationViewModel.js",
			"dev/ViewModels/Common/HeaderBaseViewModel.js",
			"dev/ViewModels/Common/HeaderMobileViewModel.js",
			"dev/ViewModels/Common/PageSwitcherViewModel.js",
			"dev/ViewModels/Common/HtmlEditorViewModel.js",
			"dev/ViewModels/Common/ColorPickerViewModel.js",
			"dev/ViewModels/Common/PhoneViewModel.js",

			"dev/ViewModels/Login/WrapLoginViewModel.js",
			"dev/ViewModels/Login/LoginViewModel.js",
			"dev/ViewModels/Login/ForgotViewModel.js",
			"dev/ViewModels/Login/RegisterViewModel.js",

			"dev/ViewModels/Mail/FolderListViewModel.js",
			"dev/ViewModels/Mail/MessageListViewModel.js",
			"dev/ViewModels/Mail/MessagePaneViewModel.js",
			"dev/ViewModels/Mail/MailViewModel.js",
			"dev/ViewModels/Mail/ComposeViewModel.js",
			"dev/ViewModels/Mail/SenderSelector.js",

			"dev/ViewModels/Contacts/ContactsImportViewModel.js",
			"dev/ViewModels/Contacts/ContactsViewModel.js",

//			"dev/ViewModels/Settings/SettingsViewModel.js",
//			"dev/ViewModels/Settings/CommonSettingsViewModel.js",
//			"dev/ViewModels/Settings/EmailAccountsSettingsViewModel.js",
//			"dev/ViewModels/Settings/CalendarSettingsViewModel.js",
//			"dev/ViewModels/Settings/MobileSyncSettingsViewModel.js",
//			"dev/ViewModels/Settings/OutLookSyncSettingsViewModel.js",
//			"dev/ViewModels/Settings/AccountPropertiesViewModel.js",
//			"dev/ViewModels/Settings/AccountFoldersViewModel.js",
//			"dev/ViewModels/Settings/AccountSignatureViewModel.js",
//			"dev/ViewModels/Settings/AccountForwardViewModel.js",
//			"dev/ViewModels/Settings/AccountAutoresponderViewModel.js",
//			"dev/ViewModels/Settings/AccountFiltersViewModel.js",
//			"dev/ViewModels/Settings/FetcherIncomingViewModel.js",
//			"dev/ViewModels/Settings/FetcherOutgoingViewModel.js",
//			"dev/ViewModels/Settings/FetcherSignatureViewModel.js",
//			"dev/ViewModels/Settings/HelpdeskSettingsViewModel.js",
//			"dev/ViewModels/Settings/CloudStorageSettingsViewModel.js",
//			"dev/ViewModels/Settings/IdentityPropertiesViewModel.js",

//			"dev/ViewModels/Calendar/CalendarViewModel.js",
//			"dev/ViewModels/FileStorage/FileStorageViewModel.js",
//			"dev/ViewModels/Helpdesk/HelpdeskViewModel.js",

			"dev/Common/Screens.js",
			"dev/Common/ScreensMobile.js",

			"dev/Common/CacheMail.js",
			"dev/Common/CacheContacts.js",
			"dev/Common/CacheCalendar.js",

			"dev/AbstractApp.js",
			"dev/AppBase.js",
			"dev/AppMobile.js",

			"dev/Common/@EndAppMobile.js",
			"dev/Common/@End.js"
		]
	},
	app_helpdesk: {
		dest: 'static/js/',
		name: 'app-helpdesk.js',
		min: 'app-helpdesk.min.js',
		lint: true,
		watch: true,
		afterlogic: true,
		header: '(function ($, window, ko, crossroads, hasher) {\n',
		footer: '\n\n}(jQuery, window, ko, crossroads, hasher));',
		src: [
			"dev/Common/@Begin.js",
			"dev/Common/@BeginHelpdesk.js",

			"dev/Common/Browser.js",
			"dev/Common/Ajax.js",
			"dev/Common/Constants.js",
			"dev/Common/Enums.js",
			"dev/Common/Knockout.js",
			"dev/Common/Routing.js",
			"dev/Common/LinkBuilder.js",
			"dev/Common/Utils.js",
			"dev/Common/Utils/CommonUtils.js",
			"dev/Common/Utils/FileUtils.js",
			"dev/Common/Utils/AddressUtils.js",
			"dev/Common/Selector.js",
			"dev/Common/Splitter.js",
			"dev/Common/Autocomplete.js",
			"dev/Common/Autocomplete.js",
			"dev/Common/Api.js",
			"dev/Common/AfterLogicApi.js",
			"dev/Common/Storage.js",
			"dev/Common/OpenPgp/OpenPgp.js",
			"dev/Common/OpenPgp/OpenPgpKey.js",
			"dev/Common/OpenPgp/OpenPgpResult.js",

			"dev/Popups/AlertPopup.js",
			"dev/Popups/ConfirmPopup.js",
			"dev/Popups/ChangePasswordPopup.js",
			"dev/Popups/ImportOpenPgpKeyPopup.js",
			"dev/Popups/ContactCreatePopup.js",
			"dev/Popups/PlayerPopup.js",

			"dev/Models/Common/AppSettingsModel.js",
			"dev/Models/Common/DateModel.js",
			"dev/Models/Common/FileModel.js",

			"dev/Models/Contacts/ContactModel.js",

			"dev/Models/Helpdesk/PostModel.js",
			"dev/Models/Helpdesk/ThreadListModel.js",
			"dev/Models/Helpdesk/HelpdeskAttachmentModel.js",
			"dev/Models/Helpdesk/UserSettingsModel.js",

			"dev/ViewModels/Common/InformationViewModel.js",
			"dev/ViewModels/Common/PageSwitcherViewModel.js",

			"dev/ViewModels/Helpdesk/LoginViewModel.js",
			"dev/ViewModels/Helpdesk/HeaderViewModel.js",
			"dev/ViewModels/Helpdesk/HelpdeskViewModel.js",
			"dev/ViewModels/Helpdesk/SettingsViewModel.js",

			"dev/Common/Screens.js",
			"dev/Common/ScreensHelpdesk.js",

			"dev/AbstractApp.js",
			"dev/AppHelpDesk.js",

			"dev/Common/@EndAppHelpDesk.js",
			"dev/Common/@End.js"
		]
	},
	app_calendar_pub: {
		dest: 'static/js/',
		name: 'app-calendar-pub.js',
		min: 'app-calendar-pub.min.js',
		lint: true,
		watch: true,
		afterlogic: true,
		header: '(function ($, window, ko, crossroads, hasher) {\n',
		footer: '\n\n}(jQuery, window, ko, crossroads, hasher));',
		src: [
			"dev/Common/@Begin.js",
			"dev/Common/@BeginCalendar.js",

			"dev/Common/Browser.js",
			"dev/Common/Ajax.js",
			"dev/Common/Constants.js",
			"dev/Common/Enums.js",
			"dev/Common/Knockout.js",
			"dev/Common/Utils.js",
			"dev/Common/Utils/CommonUtils.js",
			"dev/Common/Utils/AddressUtils.js",
			"dev/Common/Splitter.js",
			"dev/Common/Autocomplete.js",
			"dev/Common/Api.js",
			"dev/Common/Storage.js",
			"dev/Common/OpenPgp/OpenPgp.js",
			"dev/Common/OpenPgp/OpenPgpKey.js",
			"dev/Common/OpenPgp/OpenPgpResult.js",

			"dev/Popups/AlertPopup.js",
			"dev/Popups/ConfirmPopup.js",
			"dev/Popups/ImportOpenPgpKeyPopup.js",

			"dev/Popups/CalendarPopup.js",
			"dev/Popups/CalendarImportPopup.js",
			"dev/Popups/CalendarSharePopup.js",
			"dev/Popups/CalendarGetLinkPopup.js",
			"dev/Popups/CalendarEventPopup.js",
			"dev/Popups/CalendarEditRecurrenceEventPopup.js",
			"dev/Popups/CalendarSelectCalendarsPopup.js",
			"dev/Popups/ContactCreatePopup.js",
			"dev/Popups/PlayerPopup.js",

			"dev/Models/Common/UserSettingsModel.js",

			"dev/Models/Calendar/CalendarModel.js",
			"dev/Models/Calendar/CalendarListModel.js",

			"dev/ViewModels/Calendar/CalendarViewModel.js",

			"dev/ViewModels/Common/InformationViewModel.js",

			"dev/Common/Screens.js",
			"dev/Common/ScreensCalendarPub.js",

			"dev/AbstractApp.js",
			"dev/AppCalendarPub.js",

			"dev/Common/@EndAppCalendarPub.js",
			"dev/Common/@End.js"
		]
	},
	app_filestorage_pub: {
		dest: 'static/js/',
		name: 'app-filestorage-pub.js',
		min: 'app-filestorage-pub.min.js',
		lint: true,
		watch: true,
		afterlogic: true,
		header: '(function ($, window, ko, crossroads, hasher) {\n',
		footer: '\n\n}(jQuery, window, ko, crossroads, hasher));',
		src: [
			"dev/Common/@Begin.js",
			"dev/Common/@BeginFileStorage.js",

			"dev/Common/Browser.js",
			"dev/Common/Ajax.js",
			"dev/Common/Constants.js",
			"dev/Common/Enums.js",
			"dev/Common/Knockout.js",
			"dev/Common/Utils.js",
			"dev/Common/Utils/FileUtils.js",
			"dev/Common/Utils/AddressUtils.js",
			"dev/Common/Selector.js",
			"dev/Common/Splitter.js",
			"dev/Common/Autocomplete.js",
			"dev/Common/Api.js",
			"dev/Common/Storage.js",
			"dev/Common/OpenPgp/OpenPgp.js",
			"dev/Common/OpenPgp/OpenPgpKey.js",
			"dev/Common/OpenPgp/OpenPgpResult.js",

			"dev/Popups/AlertPopup.js",
			"dev/Popups/ConfirmPopup.js",
			"dev/Popups/ImportOpenPgpKeyPopup.js",

			"dev/Popups/FileStorageFolderCreatePopup.js",
			"dev/Popups/FileStorageLinkCreatePopup.js",
			"dev/Popups/FileStorageRenamePopup.js",
			"dev/Popups/FileStorageSharePopup.js",
			"dev/Popups/ContactCreatePopup.js",
			"dev/Popups/PlayerPopup.js",

			"dev/Models/Common/UserSettingsModel.js",
			"dev/Models/Common/DateModel.js",
			"dev/Models/Common/FileModel.js",

			"dev/Models/FileStorage/FileModel.js",

			"dev/ViewModels/FileStorage/FileStorageViewModel.js",

			"dev/ViewModels/Common/InformationViewModel.js",

			"dev/Common/Screens.js",
			"dev/Common/ScreensFileStoragePub.js",

			"dev/AbstractApp.js",
			"dev/AppFileStoragePub.js",

			"dev/Common/@EndAppFileStoragePub.js",
			"dev/Common/@End.js"
		]
	},
	sipml: {
		dest: 'static/js/',
		name: 'sipml.js',
		src: [
			"dev/Vendors/SIPml-api.js"
		]
	},
	openpgp: {
		dest: 'static/js/',
		name: 'openpgp.js',
		src: [
			"dev/Vendors/openpgp/0.7.2/openpgp.min.js"
		]
	},
	swfobject: {
		dest: 'static/js/',
		name: 'swfobject.js',
		src: [
			"dev/Vendors/swfobject.js"
		]
	},
	jua: {
		dest: 'dev/Vendors/jua/',
		name: 'jua.js',
		min: 'jua.min.js',
		src: [
			"dev/Vendors/jua/jua.js"
		]
	}
};

function jsTask(sName, oData)
{
	if (oData && oData.src)
	{
		gulp.task('js:' + sName, function() {
			return gulp.src(oData.src)
				.pipe(expect(oData.src))
				.pipe(concat(oData.name))
				.pipe(header(((oData.afterlogic ? cfg.license : '') || '') + (oData.header || '')))
				.pipe(footer(oData.footer || ''))
				.pipe(eol('\n', true))
				.pipe(gulp.dest(oData.dest));
		});

		if (oData.watch)
		{
			cfg.watch.push(['js:' + sName, oData.src]);
		}

		if (oData.lint)
		{
			gulp.task('js:' + sName + ':lint', ['js:' + sName], function() {
				return gulp.src(oData.dest + oData.name)
					.pipe(jshint('.jshintrc'))
					.pipe(jshint.reporter('jshint-summary', cfg.summary))
					.pipe(jshint.reporter('fail'));
			});
		}

		if (oData.min)
		{
			gulp.task('js:' + sName + ':min', [oData.lint ? 'js:' + sName + ':lint' : 'js:' + sName], function() {
				return gulp.src(oData.dest + oData.name)
					.pipe(rename(oData.min))
					.pipe(uglify(cfg.uglify))
					.pipe(eol('\n', true))
					.pipe(gulp.dest(oData.dest))
					.on('error', gutil.log);
			});
		}
	}
}

function cssTask(sName, oData)
{
	if (oData && oData.src)
	{
		gulp.task('css:' + sName, function() {
			return gulp.src(oData.src)
				.pipe(concat(oData.name))
				.pipe(header(oData.header || ''))
				.pipe(footer(oData.footer || ''))
				.pipe(eol('\n', true))
				.pipe(gulp.dest(oData.dest));
		});
	}
}

function skinTask(sName)
{
	gulp.task('less:skin:' + sName, function() {
		return gulp.src(['skins/' + sName + '/less/styles.less', 'skins/' + sName + '/less/styles-mobile.less'])
			.pipe(less({
				'paths': ['skins/' + sName + '/less/']
			}))
			.pipe(eol('\n', true))
			.pipe(gulp.dest('skins/' + sName + '/'))
			.on('error', gutil.log);
	});


	var aWatchSrc = ['skins/Default/less/*.less'];
	if ('Default' !== sName)
	{
		aWatchSrc.push('skins/' + sName + '/less/*.less');
	}

	cfg.watch.push(['less:skin:' + sName, aWatchSrc]);
};

for (name in cfg.paths.js)
{
	if (cfg.paths.js.hasOwnProperty(name))
	{
		jsTask(name, cfg.paths.js[name]);
	}
}

//for (name in cfg.paths.css)
//{
//	if (cfg.paths.css.hasOwnProperty(name))
//	{
//		cssTask(name, cfg.paths.css[name]);
//	}
//}
//
//for (index in cfg.paths.skins)
//{
//	if (cfg.paths.skins[index])
//	{
//		skinTask(cfg.paths.skins[index]);
//	}
//}

gulp.task('default', ['js:libs', 'js:sipml', 'js:openpgp', 'js:swfobject', 'js:app:min', 'js:app_mobile:min',
	'js:app_helpdesk:min', 'js:app_calendar_pub:min', 'js:app_filestorage_pub:min', 'js:jua:min', 'js:extutils']);

gulp.task('fast', ['js:libs', 'js:app', 'js:app_mobile', 'js:app_helpdesk', 'js:app_calendar_pub', 'js:app_filestorage_pub', 'js:extutils']);

gulp.task('w', ['fast'], function() {
	for (index in cfg.watch)
	{
		if (cfg.watch[index])
		{
			gulp.watch(cfg.watch[index][1], {interval: 500}, [cfg.watch[index][0]]);
		}
	}
});
