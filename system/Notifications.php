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

namespace Aurora\System;

/**
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

	const CanNotGetMessageList = 201;
	const CanNotGetMessage = 202;
	const CanNotDeleteMessage = 203;
	const CanNotMoveMessage = 204;
	const CanNotMoveMessageQuota = 205;
	const CanNotCopyMessage = 206;
	const CanNotCopyMessageQuota = 207;
	const LibraryNoFound = 208;

	const CanNotSaveMessage = 301;
	const CanNotSendMessage = 302;
	const InvalidRecipients = 303;
	const CannotSaveMessageInSentItems = 304;
	const UnableSendToRecipients = 305;
	const ExternalRecipientsBlocked = 306;

	const CanNotCreateFolder = 401;
	const CanNotDeleteFolder = 402;
	const CanNotSubscribeFolder = 403;
	const CanNotUnsubscribeFolder = 404;

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
	const FetcherConnectError = 702;
	const FetcherAuthError = 703;
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

	const MailServerError = 901;
	const UnknownError = 999;
}