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

/**
 * @package Api
 * @subpackage Enum
 */
class ELogLevel extends \Aurora\System\Enums\AbstractEnumeration
{
	const Full = 100;
	const Warning = 50;
	const Error = 20;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Full' => self::Full,
		'Warning' => self::Warning,
		'Error' => self::Error,
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ELoginFormType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Email = 0;
	const Login = 3;
	const Both = 4;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Email' => self::Email,
		'Login' => self::Login,
		'Both' => self::Both
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ELoginSignMeType extends \Aurora\System\Enums\AbstractEnumeration
{
	const DefaultOff = 0;
	const DefaultOn = 1;
	const Unuse = 2;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'DefaultOff' => self::DefaultOff,
		'DefaultOn' => self::DefaultOn,
		'Unuse' => self::Unuse
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ECalendarDefaultWorkDay
{
	const Starts = 9;
	const Ends = 17;
}

/**
 * @package Api
 * @subpackage Enum
 */
class ECalendarWeekStartOn extends \Aurora\System\Enums\AbstractEnumeration
{
	const Saturday = 6;
	const Sunday = 0;
	const Monday = 1;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Saturday' => self::Saturday,
		'Sunday' => self::Sunday,
		'Monday' => self::Monday
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ECalendarDefaultTab extends \Aurora\System\Enums\AbstractEnumeration
{
	const Day = 1;
	const Week = 2;
	const Month = 3;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Day' => self::Day,
		'Week' => self::Week,
		'Month' => self::Month
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ESortOrder extends \Aurora\System\Enums\AbstractEnumeration
{
	const ASC = 0;
	const DESC = 1;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'ASC' => self::ASC,
		'DESC' => self::DESC
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ECapa extends \Aurora\System\Enums\AbstractEnumeration
{
	const WEBMAIL = 'WEBMAIL';
	const CALENDAR = 'CALENDAR';
	const CAL_SHARING = 'CAL_SHARING';
	const CONTACTS_SHARING = 'CONTACTS_SHARING';
	const MEETINGS = 'MEETINGS';
	const PAB = 'PAB';
	const GAB = 'GAB';
	const FILES = 'FILES';
	const VOICE = 'VOICE';
	const SIP = 'SIP';
	const TWILIO = 'TWILIO';
	const HELPDESK = 'HELPDESK';
	const MOBILE_SYNC = 'MOBILE_SYNC';
	const OUTLOOK_SYNC = 'OUTLOOK_SYNC';
	
	const NO = 'NO';
}

/**
 * @package Api
 * @subpackage Enum
 */
class ETenantCapa extends \Aurora\System\Enums\AbstractEnumeration
{
	const SIP = 'SIP';
	const TWILIO = 'TWILIO';
	const FILES = 'FILES';
	const HELPDESK = 'HELPDESK';
}

/**
 * @package Api
 * @subpackage Enum
 */
class ECalendarPermission extends \Aurora\System\Enums\AbstractEnumeration
{
	const RemovePermission = -1;
	const Write = 1;
	const Read = 2;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'RemovePermission' => self::RemovePermission,
		'Write' => self::Write,
		'Read' => self::Read

	);
}
	
/**
 * @package Api
 * @subpackage Enum
 */
class EFileStorageType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Personal = 0;
	const Corporate = 1;
	const Shared = 2;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Personal' => self::Personal,
		'Corporate' => self::Corporate,
		'Shared' => self::Shared

	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class EFileStorageTypeStr extends \Aurora\System\Enums\AbstractEnumeration
{
	const Personal = 'personal';
	const Corporate = 'corporate';
	const Shared = 'shared';

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Personal' => self::Personal,
		'Corporate' => self::Corporate,
		'Shared' => self::Shared

	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class EPeriodStr extends \Aurora\System\Enums\AbstractEnumeration
{
	const Secondly = 'secondly';
	const Minutely = 'minutely';
	const Hourly   = 'hourly';
	const Daily	   = 'daily';
	const Weekly   = 'weekly';
	const Monthly  = 'monthly';
	const Yearly   = 'yearly';

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Secondly' => self::Secondly,
		'Minutely' => self::Minutely,
		'Hourly'   => self::Hourly,
		'Daily'	   => self::Daily,
		'Weekly'   => self::Weekly,
		'Monthly'  => self::Monthly,
		'Yearly'   => self::Yearly
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class EPeriod extends \Aurora\System\Enums\AbstractEnumeration
{
	const Never   = 0;
	const Daily	   = 1;
	const Weekly   = 2;
	const Monthly  = 3;
	const Yearly   = 4;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Never'		=> self::Never,
		'Daily'		=> self::Daily,
		'Weekly'	=> self::Weekly,
		'Monthly'	=> self::Monthly,
		'Yearly'	=> self::Yearly
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ERepeatEnd extends \Aurora\System\Enums\AbstractEnumeration
{
	const Never		= 0;
	const Count		= 1;
	const Date		= 2;
	const Infinity	= 3;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Never'		=> self::Never,
		'Count'		=> self::Count,
		'Date'		=> self::Date,
		'Infinity'	=> self::Infinity
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class EAttendeeStatus extends \Aurora\System\Enums\AbstractEnumeration
{
	const Unknown = 0;
	const Accepted = 1;
	const Declined = 2;
	const Tentative = 3;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Unknown' => self::Unknown,
		'Accepted' => self::Accepted,
		'Declined' => self::Declined,
		'Tentative'   => self::Tentative
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class EFileStorageLinkType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Unknown = 0;
	const GoogleDrive = 1;
	const DropBox = 2;
	const YouTube = 3;
	const Vimeo = 4;
	const SoundCloud = 5;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Unknown' => self::Unknown,
		'GoogleDrive' => self::GoogleDrive,
		'DropBox' => self::DropBox,
		'YouTube' => self::YouTube,
		'Vimeo' => self::Vimeo,
		'SoundCloud' => self::SoundCloud
	);
}

/**
 * @package Api
 * @subpackage Enum
 */
class ESocialType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Unknown   = 0;
	const Google    = 1;
	const Dropbox   = 2;
	const Facebook  = 3;
	const Twitter   = 4;
	const Vkontakte = 5;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Unknown'   => self::Unknown,
		'Google'    => self::Google,
		'Dropbox'   => self::Dropbox,
		'Facebook'  => self::Facebook,
		'Twitter'   => self::Twitter,
		'Vkontakte' => self::Vkontakte
	);	
}

/**
 * @package Api
 * @subpackage Enum
 */
class ESocialTypeStr extends \Aurora\System\Enums\AbstractEnumeration
{
	const Unknown   = '';
	const Google    = 'google';
	const Dropbox   = 'dropbox';
	const Facebook  = 'faceboobk';
	const Twitter   = 'twitter';
	const Vkontakte = 'vkontakte';

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Unknown'   => self::Unknown,
		'Google'    => self::Google,
		'Dropbox'   => self::Dropbox,
		'Facebook'  => self::Facebook,
		'Twitter'   => self::Twitter,
		'Vkontakte' => self::Vkontakte
	);	
}

/**
 * @subpackage Enum
 */
class ETwofaType extends \Aurora\System\Enums\AbstractEnumeration
{
    CONST AUTH_TYPE_AUTHY = 'authy';
    CONST DATA_TYPE_AUTHY_ID = 1;

    CONST AUTH_TYPE_GOOGLE = 'google';
}
