<?php

namespace Aurora\System;

trait ModelTrait
{
    public function getExtendedProp($sName)
    {
        $mResult = null;
        if (isset($this->Properties[$sName]))
        {
            $mResult = $this->Properties[$sName];
        }

        return $mResult;
    }

    public function setExtendedProp($sName, $sValue)
    {
        $properties = $this->Properties;
        $properties[$sName] = $sValue;
        $this->Properties = $properties;
    }
}