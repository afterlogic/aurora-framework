<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @category Core
 */
class Notifications
{
    public const InvalidToken = 101;
    public const AuthError = 102;
    public const InvalidInputParameter = 103;
    public const DataBaseError = 104;
    public const LicenseProblem = 105;
    public const DemoAccount = 106;
    public const CaptchaError = 107;
    public const AccessDenied = 108;
    public const UnknownEmail = 109;
    public const HttpsApiAccess = 110;
    public const UserAlreadyExists = 111;
    public const SystemNotConfigured = 112;
    public const ModuleNotFound = 113;
    public const MethodNotFound = 114;
    public const LicenseLimit = 115;
    public const MethodAccessDenied = 116;

    public const CanNotSaveSettings = 501;
    public const CanNotChangePassword = 502;
    public const AccountOldPasswordNotCorrect = 503;

    public const CanNotCreateContact = 601;
    public const CanNotCreateGroup = 602;
    public const CanNotUpdateContact = 603;
    public const CanNotUpdateGroup = 604;
    public const ContactDataHasBeenModifiedByAnotherApplication = 605;
    public const CanNotGetContact = 607;

    public const CanNotCreateAccount = 701;
    public const AccountExists = 704;

    // Rest
    public const RestOtherError = 710;
    public const RestApiDisabled = 711;
    public const RestUnknownMethod = 712;
    public const RestInvalidParameters = 713;
    public const RestInvalidCredentials = 714;
    public const RestInvalidToken = 715;
    public const RestTokenExpired = 716;
    public const RestAccountFindFailed = 717;
    public const RestTenantFindFailed = 719;

    public const CalendarsNotAllowed = 801;
    public const FilesNotAllowed = 802;
    public const ContactsNotAllowed = 803;
    public const HelpdeskUserAlreadyExists = 804;
    public const HelpdeskSystemUserExists = 805;
    public const CanNotCreateHelpdeskUser = 806;
    public const HelpdeskUnknownUser = 807;
    public const HelpdeskUnactivatedUser = 808;
    public const VoiceNotAllowed = 810;
    public const IncorrectFileExtension = 811;
    public const CanNotUploadFileQuota = 812;
    public const FileAlreadyExists = 813;
    public const FileNotFound = 814;
    public const CanNotUploadFileLimit = 815;
    public const CanNotUploadFileErrorData = 816;

    public const MailServerError = 901;
    public const UnknownError = 999;
}
