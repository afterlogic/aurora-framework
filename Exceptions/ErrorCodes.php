<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Exceptions;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2022, Afterlogic Corp.
 */
class ErrorCodes
{
	// users
	const UserManager_AccountCreateFailed = 1002;
	const UserManager_AccountUpdateFailed = 1003;

	const UserManager_AccountOldPasswordNotCorrect = 1020;
	const UserManager_AccountNewPasswordUpdateError = 1021;
	const UserManager_AccountNewPasswordRejected = 1022;

	// channels
	const ChannelsManager_ChannelAlreadyExists = 1801;
	const ChannelsManager_ChannelCreateFailed = 1802;
	const ChannelsManager_ChannelUpdateFailed = 1803;

	// validation
	const Validation_FieldIsEmpty = 1102;
	const Validation_FieldIsEmpty_OutInfo = 1104;
	const Validation_InvalidParameters = 1105;
	const Validation_InvalidChannelName = 1110;

	// container
	const Container_UndefinedProperty = 1601;

	// main
	const Main_UnknownError = 2002;

	// db
	const Db_ExceptionError = 3001;

	// Sabre
	const Sabre_PreconditionFailed = 5002;

	// Helpdesk
	const HelpdeskManager_UserAlreadyExists = 6001;
	const HelpdeskManager_UserCreateFailed = 6002;
	const HelpdeskManager_AccountAuthentication = 6004;
	const HelpdeskManager_AccountSystemAuthentication = 6008;
	const HelpdeskManager_UnactivatedUser = 6010;

	/**
	 * @param int $iCode
	 * @param array $aParams = array()
	 * @return string
	 */
	public static function GetMessageByCode($iCode, $aParams = array())
	{
		return '';
//		static $aMessages = null;
//		if (null === $aMessages)
//		{
//			$aMessages = array(
//				self::UserManager_AccountCreateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_CREATE_FAILED'),
//				self::UserManager_AccountUpdateFailed =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_UPDATE_FAILED'),
//
//				self::UserManager_AccountOldPasswordNotCorrect =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_OLD_PASSWORD_NOT_CORRECT'),
//				self::UserManager_AccountNewPasswordUpdateError =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_NEW_PASSWORD_UPDATE_ERROR'),
//				self::UserManager_AccountNewPasswordRejected =>\Aurora\System\Api::I18N('API/USERMANAGER_ACCOUNT_NEW_PASSWORD_REJECTED'),
//
//				self::ChannelsManager_ChannelAlreadyExists =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_ALREADY_EXISTS'),
//				self::ChannelsManager_ChannelCreateFailed =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_CREATE_FAILED'),
//				self::ChannelsManager_ChannelUpdateFailed =>\Aurora\System\Api::I18N('API/CHANNELSMANAGER_CHANNEL_UPDATE_FAILED'),
//
//				self::Validation_FieldIsEmpty =>\Aurora\System\Api::I18N('API/VALIDATION_FIELD_IS_EMPTY'),
//				self::Validation_FieldIsEmpty_OutInfo =>\Aurora\System\Api::I18N('API/VALIDATION_FIELD_IS_EMPTY_OUTINFO'),
//				self::Validation_InvalidParameters =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_PARAMETERS'),
//				self::Validation_InvalidChannelName =>\Aurora\System\Api::I18N('API/VALIDATION_INVALID_CHANNEL_NAME'),
//
//				self::Container_UndefinedProperty =>\Aurora\System\Api::I18N('API/CONTAINER_UNDEFINED_PROPERTY'),
//
//				self::Main_UnknownError =>\Aurora\System\Api::I18N('API/MAIN_UNKNOWN_ERROR'),
//
//				self::Db_ExceptionError =>\Aurora\System\Api::I18N('API/DB_EXCEPTION_ERROR'),
//
//				self::Sabre_PreconditionFailed =>\Aurora\System\Api::I18N('API/SABRE_PRECONDITION_FAILED')
//			);
//		}
//
//		return isset($aMessages[$iCode])
//			? ((0 < count($aParams)) ? strtr($aMessages[$iCode], $aParams) : $aMessages[$iCode])
//			:\Aurora\System\Api::I18N('API/UNKNOWN_ERROR');
	}
}
