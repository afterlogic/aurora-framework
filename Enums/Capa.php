<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Capa extends \Aurora\System\Enums\AbstractEnumeration
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
