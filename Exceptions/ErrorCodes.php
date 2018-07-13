<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Exceptions;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 */
class ErrorCodes
{
	// users
	const UserManager_AccountAlreadyExists = 1001;
	const UserManager_AccountCreateFailed = 1002;
	const UserManager_AccountUpdateFailed = 1003;
	const UserManager_AccountAuthenticationFailed = 1004;
	const UserManager_AccountCreateUserLimitReached = 1005;
	const UserManager_AccountDoesNotExist = 1006;
	const UserManager_LicenseKeyIsOutdated = 1007;
	const UserManager_LicenseKeyInvalid = 1008;
	const UserManager_IdentityCreateFailed = 1009;
	const UserManager_IdentityUpdateFailed = 1010;
	const UserManager_AccountConnectToMailServerFailed = 1011;

	const UserManager_AccountOldPasswordNotCorrect = 1020;
	const UserManager_AccountNewPasswordUpdateError = 1021;
	const UserManager_AccountNewPasswordRejected = 1022;

	const UserManager_CalUserCreateFailed = 1030;
	const UserManager_CalUserUpdateFailed = 1031;
	const UserManager_CalUserAlreadyExists = 1032;
	
	const UserManager_SocialAccountAlreadyExists = 1033;
	
	// validation
	const Validation_InvalidPort = 1101;
	const Validation_FieldIsEmpty = 1102;
	const Validation_InvalidPort_OutInfo = 1103;
	const Validation_FieldIsEmpty_OutInfo = 1104;
	const Validation_InvalidParameters = 1105;
	const Validation_ObjectNotComplete = 1106;
	const Validation_InvalidEmail = 1107;
	const Validation_InvalidEmail_OutInfo = 1108;
	const Validation_InvalidTenantName = 1109;
	const Validation_InvalidChannelName = 1110;

	// mailsuite
	const MailSuiteManager_MailingListAlreadyExists = 1401;
	const MailSuiteManager_MailingListCreateFailed = 1402;
	const MailSuiteManager_MailingListUpdateFailed = 1403;
	const MailSuiteManager_MailingListInvalid = 1404;
	const MailSuiteManager_MailingListDeleteFailed = 1405;

	// webmail
	const WebMailManager_AccountDisabled = 1501;
	const WebMailManager_AccountWebmailDisabled = 1502;
	const WebMailManager_AccountCreateOnLogin = 1503;
	const WebMailManager_NewUserRegistrationDisabled = 1504;
	const WebMailManager_AccountAuthentication = 1505;
	const WebMailManager_AccountConnectToMailServerFailed = 1507;

	// container
	const Container_UndefinedProperty = 1601;

	// tenants
	const TenantsManager_TenantAlreadyExists = 1701;
	const TenantsManager_TenantCreateFailed = 1702;
	const TenantsManager_TenantUpdateFailed = 1703;
	const TenantsManager_TenantDoesNotExist = 1704;
	const TenantsManager_AccountCreateUserLimitReached = 1705;
	const TenantsManager_QuotaLimitExided = 1707;
	const TenantsManager_AccountUpdateUserLimitReached = 1705;

	// channels
	const ChannelsManager_ChannelAlreadyExists = 1801;
	const ChannelsManager_ChannelCreateFailed = 1802;
	const ChannelsManager_ChannelUpdateFailed = 1803;
	const ChannelsManager_ChannelDoesNotExist = 1804;

	// main
	const Main_SettingLoadError = 2001;
	const Main_UnknownError = 2002;
	const Main_CustomError = 2003;

	// db
	const Db_ExceptionError = 3001;
	const Db_PdoExceptionError = 3002;

	// Sabre
	const Sabre_Exception = 5001;
	const Sabre_PreconditionFailed = 5002;

	// Helpdesk
	const HelpdeskManager_UserAlreadyExists = 6001;
	const HelpdeskManager_UserCreateFailed = 6002;
	const HelpdeskManager_UserUpdateFailed = 6003;
	const HelpdeskManager_AccountAuthentication = 6004;
	const HelpdeskManager_ThreadCreateFailed = 6005;
	const HelpdeskManager_ThreadUpdateFailed = 6006;
	const HelpdeskManager_PostCreateFailed = 6007;
	const HelpdeskManager_AccountSystemAuthentication = 6008;
	const HelpdeskManager_AccountCannotBeDeleted = 6009;
	const HelpdeskManager_UnactivatedUser = 6010;

	/*// Rest
	const Rest_InvalidParameters = 7001;
	const Rest_InvalidCredentials = 7002;
	const Rest_InvalidToken = 7003;
	const Rest_TokenExpired = 7004;
	const Rest_AccountCreateFailed = 7010;
	const Rest_AccountUpdateFailed = 7011;
	const Rest_AccountDeleteFailed = 7012;
	const Rest_AccountFindFailed = 7013;
	const Rest_AccountEnableFailed = 7014;
	const Rest_AccountDisableFailed = 7015;
	const Rest_AccountPasswordChangeFailed = 7016;
	const Rest_AccountListGetFailed = 7017;
	const Rest_TenantFindFailed = 7030;*/

	/**
	 * @param int $iCode
	 * @param array $aParams = array()
	 * @return string
	 */
	public static function GetMessageByCode($iCode, $aParams = array())
	{
		static $aMessages = null;
		if (null === $aMessages)
		{
			$aMessages = array(
				self::UserManager_AccountAlreadyExists =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_ALREADY_EXISTS'),
				self::UserManager_AccountCreateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_CREATE_FAILED'),
				self::UserManager_AccountUpdateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_UPDATE_FAILED'),
				self::UserManager_AccountAuthenticationFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_AUTHENTICATION_FAILED'),
				self::UserManager_AccountCreateUserLimitReached =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_CREATE_USER_LIMIT_REACHED'),
				self::UserManager_AccountDoesNotExist =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_DOES_NOT_EXIST'),
				self::UserManager_LicenseKeyIsOutdated =>\Aurora\System\Api::I18N('API/USERMANAGER_LICENSE_KEY_IS_OUTDATED'),
				self::UserManager_LicenseKeyInvalid =>\Aurora\System\Api::I18N('API/USERMANAGER_LICENSE_KEY_INVALID'),
				self::UserManager_IdentityCreateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_IDENTIFY_CREATE_FAILED'),
				self::UserManager_IdentityUpdateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_IDENTITI_UPDATE_FAILED'),
				self::UserManager_AccountConnectToMailServerFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_CONNECT_TO_MAIL_SERVER_FAILED'),

				self::UserManager_AccountOldPasswordNotCorrect =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_OLD_PASSWORD_NOT_CORRECT'),
				self::UserManager_AccountNewPasswordUpdateError =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_NEW_PASSWORD_UPDATE_ERROR'),
				self::UserManager_AccountNewPasswordRejected =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_NEW_PASSWORD_REJECTED'),

				self::UserManager_CalUserCreateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_CALUSER_CREATE_FAILED'),
				self::UserManager_CalUserUpdateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_CALUSER_UPDATE_FAILED'),
				self::UserManager_CalUserAlreadyExists =>\Aurora\System\Api::I18N('API/USERMANAGER_CALUSER_ALREADY_EXISTS'),

				self::UserManager_SocialAccountAlreadyExists =>\Aurora\System\Api::I18N('API/USERMANAGER_SOCIAL_ACCOUNT_ALREADY_EXISTS'),

				self::TenantsManager_TenantAlreadyExists =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_TENANT_ALREADY_EXISTS'),
				self::TenantsManager_TenantCreateFailed =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_TENANT_CREATE_FAILED'),
				self::TenantsManager_TenantUpdateFailed =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_TENANT_UPDATE_FAILED'),
				self::TenantsManager_TenantDoesNotExist =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_TENANT_DOES_NOT_EXIST'),
				self::TenantsManager_AccountCreateUserLimitReached =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_ACCOUNT_CREATE_USER_LIMIT_REACHED'),
				self::TenantsManager_AccountUpdateUserLimitReached =>\Aurora\System\Api::I18N('API/TENANTSMANAGER_ACCOUNT_UPDATE_USER_LIMIT_REACHED'),
				self::TenantsManager_QuotaLimitExided =>\Aurora\System\Api::I18N('API/TENANTS_MANAGER_QUOTA_LIMIT_EXCEEDED'),

				self::ChannelsManager_ChannelAlreadyExists =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_ALREADY_EXISTS'),
				self::ChannelsManager_ChannelCreateFailed =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_CREATE_FAILED'),
				self::ChannelsManager_ChannelUpdateFailed =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_UPDATE_FAILED'),
				self::ChannelsManager_ChannelDoesNotExist =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_DOES_NOT_EXIST'),

				self::MailSuiteManager_MailingListAlreadyExists =>\Aurora\System\Api::I18N('API/MAILSUITEMANAGER_MAILING_LIST_ALREADY_EXISTS'),
				self::MailSuiteManager_MailingListCreateFailed =>\Aurora\System\Api::I18N('API/MAILSUITEMANAGER_MAILING_LIST_CREATE_FAILED'),
				self::MailSuiteManager_MailingListUpdateFailed =>\Aurora\System\Api::I18N('API/MAILSUITEMANAGER_MAILING_LIST_UPDATE_FAILED'),
				self::MailSuiteManager_MailingListInvalid =>\Aurora\System\Api::I18N('API/MAILSUITEMANAGER_MAILING_LIST_INVALID'),

				self::WebMailManager_AccountDisabled =>\Aurora\System\Api::I18N('API/WEBMAILMANAGER_ACCOUNT_DISABLED'),
				self::WebMailManager_AccountWebmailDisabled =>\Aurora\System\Api::I18N('API/WEBMAILMANAGER_ACCOUNT_WEBMAIL_DISABLED'),
				self::WebMailManager_AccountCreateOnLogin =>\Aurora\System\Api::I18N('API/WEBMAILMANAGER_CREATE_ON_LOGIN'),
				self::WebMailManager_AccountAuthentication =>\Aurora\System\Api::I18N('API/WEBMAILMANAGER_ACCOUNT_AUTHENTICATION'),
				self::WebMailManager_AccountConnectToMailServerFailed =>\Aurora\System\Api::I18N('API/WEBMAILMANAGER_ACCOUNT_CONNECT_TO_MAIL_SERVER_FAILED'),

				self::Validation_InvalidPort =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_PORT'),
				self::Validation_InvalidEmail =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_EMAIL'),
				self::Validation_FieldIsEmpty =>\Aurora\System\Api::I18N('API/VALIDATION_FIELD_IS_EMPTY'),
				self::Validation_InvalidPort_OutInfo =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_PORT_OUTINFO'),
				self::Validation_InvalidEmail_OutInfo =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_EMAIL_OUTINFO'),
				self::Validation_FieldIsEmpty_OutInfo =>\Aurora\System\Api::I18N('API/VALIDATION_FIELD_IS_EMPTY_OUTINFO'),
				self::Validation_InvalidParameters =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_PARAMETERS'),
				self::Validation_InvalidTenantName =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_TENANT_NAME'),
				self::Validation_InvalidChannelName =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_CHANNEL_NAME'),

				self::Container_UndefinedProperty =>\Aurora\System\Api::I18N('API/CONTAINER_UNDEFINED_PROPERTY'),

				self::Main_SettingLoadError =>\Aurora\System\Api::I18N('API/MAIN_SETTINGS_LOAD_ERROR'),
				self::Main_UnknownError =>\Aurora\System\Api::I18N('API/MAIN_UNKNOWN_ERROR'),
				self::Main_CustomError =>\Aurora\System\Api::I18N('API/MAIN_CUSTOM_ERROR'),

				self::Db_ExceptionError =>\Aurora\System\Api::I18N('API/DB_EXCEPTION_ERROR'),
				self::Db_PdoExceptionError =>\Aurora\System\Api::I18N('API/DB_PDO_EXCEPTION_ERROR'),

				self::Sabre_Exception =>\Aurora\System\Api::I18N('API/SABRE_EXCEPTION'),
				self::Sabre_PreconditionFailed =>\Aurora\System\Api::I18N('API/SABRE_PRECONDITION_FAILED')

				/*self::Rest_InvalidParameters =>\Aurora\System\Api::I18N('API/REST_INVALID_PARAMETERS'),
				self::Rest_InvalidCredentials =>\Aurora\System\Api::I18N('API/REST_INVALID_CREDENTIALS'),
				self::Rest_InvalidToken =>\Aurora\System\Api::I18N('API/REST_INVALID_TOKEN'),
				self::Rest_TokenExpired =>\Aurora\System\Api::I18N('API/REST_TOKEN_EXPIRED'),
				self::Rest_AccountCreateFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_CREATE_FAILED'),
				self::Rest_AccountUpdateFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_UPDATE_FAILED'),
				self::Rest_AccountDeleteFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_DELETE_FAILED'),
				self::Rest_AccountFindFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_FIND_FAILED'),
				self::Rest_AccountEnableFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_ENABLE_FAILED'),
				self::Rest_AccountDisableFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_DISABLE_FAILED'),
				self::Rest_AccountPasswordChangeFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_PASSWORD_CHANGE_FAILED'),
				self::Rest_AccountListGetFailed =>\Aurora\System\Api::I18N('API/REST_ACCOUNT_LIST_GET_FAILED'),
				self::Rest_TenantFindFailed =>\Aurora\System\Api::I18N('API/REST_TENANT_FIND_FAILED'),*/
			);
		}

		return isset($aMessages[$iCode])
			? ((0 < count($aParams)) ? strtr($aMessages[$iCode], $aParams) : $aMessages[$iCode])
			:\Aurora\System\Api::I18N('API/UNKNOWN_ERROR');
	}
}
