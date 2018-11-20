<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

class EventEmitter
{
	/**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $aListeners = array();

    /**
     * 
     */
    protected static $self = null;

    /**
	 * @var array
	 */
    private $aListenersResult;

    public static function getInstance()
    {
        if (is_null(self::$self))
        {
            self::$self = new self();
        }

        return self::$self;
    }
    
    /**
	 * 
	 * @return \self
	 */
	public static function createInstance()
	{
		return new self();
    }    
    
    /**
	 * 
	 * @return array
	 */
	public function getListeners() 
	{
		return $this->aListeners;
    }
    	
	/**
	 * @return array
	 */
	public function getListenersResult()
	{
		return $this->aListenersResult;
    }	

    /**
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
     *
     * @param string $sEvent
     * @param callback $fCallback
     * @param int $iPriority
     * @return void
     */
    public function on($sEvent, $fCallback, $iPriority = 100) 
	{
        if (!isset($this->aListeners[$sEvent])) 
		{
            $this->aListeners[$sEvent] = array();
        }
        while(isset($this->aListeners[$sEvent][$iPriority]))	
		{
			$iPriority++;
		}
        $this->aListeners[$sEvent][$iPriority] = $fCallback;
        \ksort($this->aListeners[$sEvent]);
    }	

    public function onArray($aListeners)
    {
        foreach ($aListeners as $sEvent => $mListener)
        {
            if (is_callable($mListener))
            {
                $this->on($sEvent, $mListener);   
            }
            elseif (is_array($mListener) && is_callable($mListener[0]))
            {
                if (isset($mListener[1]))
                {
                    $this->on($sEvent, $mListener[0], $mListener[1]);   
                }
                else
                {
                    $this->on($sEvent, $mListener[0]);   
                }
            }
        }
    }
	
    /**
     * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
     *
     * @param string $sEvent
     * @param array $aArguments
     * @param mixed $mResult
     * @return boolean
     */
    public function emit($sModule, $sEvent, &$aArguments = array(), &$mResult = null, &$bCountinue = true) 
	{
		$bResult = false;
		$aListeners = array();
		$mListenersResult = null;
		
		if (isset($this->aListeners[$sEvent])) 
		{
			$aListeners = array_merge(
				$aListeners, 
				$this->aListeners[$sEvent]
			);
        }
		$sEvent = $sModule . Module\AbstractModule::$Delimiter . $sEvent;
		if (isset($this->aListeners[$sEvent])) 
		{
			$aListeners = \array_merge(
				$aListeners, 
				$this->aListeners[$sEvent]
			);
		}
		
		foreach($aListeners as $fCallback) 
		{
			if (\is_callable($fCallback) && Api::GetModuleManager()->IsAllowedModule($fCallback[0]::GetName()))
			{
				\Aurora\System\Api::Log('Execute subscription: '. $fCallback[0]::GetName() . Module\AbstractModule::$Delimiter . $fCallback[1]);
				
				$mCallBackResult = \call_user_func_array(
					$fCallback, 
					array(
						&$aArguments,
						&$mResult,
						&$mListenersResult
					)
				);

				if (isset($mListenersResult))
				{
					$this->aListenersResult[$fCallback[0]::GetName() . Module\AbstractModule::$Delimiter . $fCallback[1]] = $mListenersResult;
				}
				
				Api::GetModuleManager()->AddResult(
					$fCallback[0]::GetName(), 
					$sEvent, 
					$aArguments,
					$mCallBackResult
				);

				if ($mCallBackResult) 
				{
					$bResult = $mCallBackResult;
					break;
				}
			}
		}

        return $bResult;
    }	
}