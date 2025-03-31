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
    public const UserManager_AccountCreateFailed = 1002;
    public const UserManager_AccountUpdateFailed = 1003;
    public const UsersManager_UserCreateFailed = 1012;

    public const UserManager_AccountOldPasswordNotCorrect = 1020;
    public const UserManager_AccountNewPasswordUpdateError = 1021;
    public const UserManager_AccountNewPasswordRejected = 1022;

    // channels
    public const ChannelsManager_ChannelAlreadyExists = 1801;
    public const ChannelsManager_ChannelCreateFailed = 1802;
    public const ChannelsManager_ChannelUpdateFailed = 1803;

    // validation
    public const Validation_FieldIsEmpty = 1102;
    public const Validation_FieldIsEmpty_OutInfo = 1104;
    public const Validation_InvalidParameters = 1105;
    public const Validation_InvalidChannelName = 1110;

    // container
    public const Container_UndefinedProperty = 1601;

    // main
    public const Main_UnknownError = 2002;

    // db
    public const Db_ExceptionError = 3001;

    // Sabre
    public const Sabre_PreconditionFailed = 5002;

    /**
     * @param int $iCode
     * @param array $aParams = array()
     * @return string
     */
    public static function GetMessageByCode($iCode, $aParams = array())
    {
        return '';
    }
}
