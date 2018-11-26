<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 */
class Decorator
{
    /**
	 *
	 * @var string
	 */
	protected $sModuleName;

    /**
	 * 
	 * @param string $sModuleName
	 */
	public function __construct($sModuleName) 
	{
		$this->sModuleName = $sModuleName;
	}	
	
	public static function __callStatic($sMethodName, $aArguments)
	{
		return new self($sMethodName);
	}
	
	/**
	 * 
	 * @param string $sMethodName
	 * @param array $aArguments
	 * @return mixed
	 */
	public function __call($sMethodName, $aArguments) 
	{
		$mResult = false;
		$oModule = \Aurora\System\Api::GetModule($this->sModuleName);
		if ($oModule instanceof AbstractModule)
		{
			$mResult = $oModule->CallMethod($sMethodName, $aArguments);
		}

		return $mResult;
	}
}

function Decorator()
{
	echo 'Decorator';
}