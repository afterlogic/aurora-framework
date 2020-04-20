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
 * @package Api
 */
class Validator
{
    public static function validate(array $aInputs, array $aRules, $aMessages = [])
    {
		$validation = (new \Rakit\Validation\Validator())->validate($aInputs, $aRules, $aMessages);
		if ($validation->fails())
		{
			$errors = $validation->errors();
			throw new \Aurora\System\Exceptions\ValidationException(implode("; ", $errors->all()), \Aurora\System\Notifications::InvalidInputParameter);
		}
    }
}