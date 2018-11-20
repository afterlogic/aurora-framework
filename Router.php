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
 * @package Api
 */
class Router
{
    /**
     * @var array
     */
    protected $aRoutes;

    protected static $self = null;

    public function __construct()
    {
        $this->aRoutes = [];
    }

    public static function getInstance()
    {
        if (is_null(self::$self))
        {
            self::$self = new self();
        }

        return self::$self;
    }

    public function register($sModule, $sName, $mCallbak)
    {
        if (!isset($this->aRoutes[$sName][$sModule]))
        {
            $this->aRoutes[$sName][$sModule] = $mCallbak;
        }        
    }

    public function registerArray($sModule, $aRoutes)
    {
		foreach ($aRoutes as $sName => $mCallbak)
		{
			$this->register($sModule, $sName, $mCallbak);
		}
    }

    /**
	 * 
	 * @param stranig $sName
	 * @return mixed
	 */
	public function getCallback($sName)
	{
		$mResult = false;
		if (isset($this->aRoutes[$sName])) 
		{
			$mResult = $this->aRoutes[$sName];
		}
		
		return $mResult;
    }	
    
	public function hasCallback($mCallbak)
	{
        $aCallbacks = [];
        foreach ($this->aRoutes as $sRoute => $aRoutes)
        {
            foreach ($aRoutes as $sModule => $aRoute)
            {
                if (!in_array($aRoute[1], $aCallbacks))
                {
                    $aCallbacks[] = $aRoute[1];
                }
            }
        }

        return in_array($mCallbak, $aCallbacks);
    }    
    
    public function hasRoute($sName)
    {
		return isset($this->aRoutes[$sName]);
    }

    public function route($sName)
    {
        $mResult = false;

        $mMethod = $this->getCallback($sName);
        if ($mMethod)
        {
            foreach ($mMethod as $sModule => $mCallbak)
            {
                if (\Aurora\System\Api::GetModuleManager()->IsAllowedModule($sModule))
                {
                    $mResult .= call_user_func_array(
                        $mCallbak, 
                        []
                    );
                }
            }
		}			

        return $mResult;
    }

    public function removeRoute($sName)
    {
		unset($this->aRoutes[$sName]);
    }
}