<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @category Core
 */
class Notifications
{
	const InvalidToken = 101;
	const AuthError = 102;
	const InvalidInputParameter = 103;
	const DataBaseError = 104;
	const LicenseProblem = 105;
	const DemoAccount = 106;
	const CaptchaError = 107;
	const AccessDenied = 108;
	const UnknownEmail = 109;
	const UserNotAllowed = 110;
	const UserAlreadyExists = 111;
	const SystemNotConfigured = 112;
	const ModuleNotFound = 113;
	const MethodNotFound = 114;
	const LicenseLimit = 115;

	const CanNotSaveSettings = 501;
	const CanNotChangePassword = 502;
	const AccountOldPasswordNotCorrect = 503;

	const CanNotCreateContact = 601;
	const CanNotCreateGroup = 602;
	const CanNotUpdateContact = 603;
	const CanNotUpdateGroup = 604;
	const ContactDataHasBeenModifiedByAnotherApplication = 605;
	const CanNotGetContact = 607;

	const CanNotCreateAccount = 701;
    const AccountExists = 704;

	// Rest
	const RestOtherError = 710;
	const RestApiDisabled = 711;
	const RestUnknownMethod = 712;
	const RestInvalidParameters = 713;
	const RestInvalidCredentials = 714;
	const RestInvalidToken = 715;
	const RestTokenExpired = 716;
	const RestAccountFindFailed = 717;
	const RestTenantFindFailed = 719;

	const CalendarsNotAllowed = 801;
	const FilesNotAllowed = 802;
	const ContactsNotAllowed = 803;
	const HelpdeskUserAlreadyExists = 804;
	const HelpdeskSystemUserExists = 805;
	const CanNotCreateHelpdeskUser = 806;
	const HelpdeskUnknownUser = 807;
	const HelpdeskUnactivatedUser = 808;
	const VoiceNotAllowed = 810;
	const IncorrectFileExtension = 811;
	const CanNotUploadFileQuota = 812;
	const FileAlreadyExists = 813;
	const FileNotFound = 814;
	const CanNotUploadFileLimit = 815;

	const MailServerError = 901;
	const UnknownError = 999;
}