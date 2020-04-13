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
 */
class EventEmitter
{
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
	 *
	 * @return array
	 */
	public function getListenersByEvent($sModule, $sEvent)
	{
		$aListeners = [];

        if (isset($this->aListeners[$sEvent]))
		{
			$aListeners = $this->aListeners[$sEvent];
        }
		$sEvent = $sModule . Module\AbstractModule::$Delimiter . $sEvent;
		if (isset($this->aListeners[$sEvent]))
		{
			$aListeners = array_merge($aListeners, $this->aListeners[$sEvent]);
        }

        return $aListeners;
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
            $this->aListeners[$sEvent] = [];
        }
        while(isset($this->aListeners[$sEvent][$iPriority]))
		{
			$iPriority++;
		}
        $this->aListeners[$sEvent][$iPriority] = $fCallback;
        \ksort($this->aListeners[$sEvent]);
    }

    public function onAny($aListeners)
    {
        foreach ($aListeners as $sKey => $mListener)
        {
            if (is_array($mListener) && is_callable($mListener[1]))
            {
                if (isset($mListener[2]))
                {
                    $this->on($mListener[0], $mListener[1], $mListener[2]);
                }
                else
                {
                    $this->on($mListener[0], $mListener[1]);
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
     * @param callback $mCountinueCallback
     * @return boolean
     */
    public function emit($sModule, $sEvent, &$aArguments = [], &$mResult = null, $mCountinueCallback = null, $bSkipIsAllowedModuleCheck = false)
	{
		$bResult = false;
		$mListenersResult = null;

        $aListeners = $this->getListenersByEvent($sModule, $sEvent);
        if (count($aListeners) > 0)
        {
            \Aurora\System\Api::Log(" ===== Emit $sModule::$sEvent", Enums\LogLevel::Full, 'subscriptions-');
            \Aurora\System\Api::Log(' ========== START Execute subscriptions', Enums\LogLevel::Full, 'subscriptions-');
            foreach($aListeners as $fCallback)
            {
                $bIsAllowedModule = true;
                if (!$bSkipIsAllowedModuleCheck)
                {
                    $bIsAllowedModule =  Api::GetModuleManager()->IsAllowedModule($fCallback[0]::GetName());
                }
                if (\is_callable($fCallback) && $bIsAllowedModule)
                {
                    \Aurora\System\Api::Log(" =============== " . $fCallback[0]::GetName() . Module\AbstractModule::$Delimiter . $fCallback[1], Enums\LogLevel::Full, 'subscriptions-');

                    $mCallBackResult = \call_user_func_array(
                        $fCallback,
                        [
                            &$aArguments,
                            &$mResult,
                            &$mListenersResult
                        ]
                    );

                    if (\is_callable($mCountinueCallback))
                    {
                        $mCountinueCallback(
                            $fCallback[0]::GetName(),
                            $aArguments,
                            $mCallBackResult
                        );
                    }

                    if (isset($mListenersResult))
                    {
                        $this->aListenersResult[$fCallback[0]::GetName() . Module\AbstractModule::$Delimiter . $fCallback[1]] = $mListenersResult;
                    }

                    if ($mCallBackResult)
                    {
                        $bResult = $mCallBackResult;

                        break;
                    }
                }
            }
            \Aurora\System\Api::Log(" ========== END Execute subscriptions\n", Enums\LogLevel::Full, 'subscriptions-');
        }

        return $bResult;
    }
}
