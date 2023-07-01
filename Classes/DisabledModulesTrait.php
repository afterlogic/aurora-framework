<?php

namespace Aurora\System\Classes;

trait DisabledModulesTrait
{
    public function isModuleDisabled($sModuleName)
    {
        $aDisabledModules = $this->getDisabledModules();

        return in_array($sModuleName, $aDisabledModules);
    }

    public function getDisabledModules()
    {
        $sDisabledModules = $this->getExtendedProp('DisabledModules');
        $sDisabledModules = isset($sDisabledModules) ? \trim($sDisabledModules) : '';
        $aDisabledModules =  !empty($sDisabledModules) ? [$sDisabledModules] : [];
        if (substr_count($sDisabledModules, "|") > 0) {
            $aDisabledModules = explode("|", $sDisabledModules);
        }

        return $aDisabledModules;
    }

    public function clearDisabledModules()
    {
        $this->setExtendedProp('DisabledModules', '');
        $this->save();
    }

    public function disableModule($sModuleName)
    {
        $aDisabledModules = $this->getDisabledModules();
        if (!in_array($sModuleName, $aDisabledModules)) {
            $aDisabledModules[] = $sModuleName;
            // clear array from empty values
            $aDisabledModules = array_filter($aDisabledModules, function ($var) {
                return !empty($var);
            });
            $this->setExtendedProp('DisabledModules', implode("|", $aDisabledModules));
            $this->save();
        }
    }

    public function disableModules($aModules)
    {
        $aDisabledModules = $this->getDisabledModules();
        foreach ($aModules as $sModuleName) {
            if (!in_array($sModuleName, $aDisabledModules)) {
                $aDisabledModules[] = $sModuleName;
                // clear array from empty values
                $aDisabledModules = array_filter($aDisabledModules, function ($var) {
                    return !empty($var);
                });
            }
        }
        $this->setExtendedProp('DisabledModules', implode("|", $aDisabledModules));
        $this->save();
    }

    public function enableModule($sModuleName)
    {
        $aDisabledModules = $this->getDisabledModules();

        if (($iKey = array_search($sModuleName, $aDisabledModules)) !== false) {
            unset($aDisabledModules[$iKey]);
            $this->setExtendedProp('DisabledModules', implode("|", $aDisabledModules));
            $this->save();
        }
    }

    public function enableModules($aModules)
    {
        $aDisabledModules = $this->getDisabledModules();

        foreach ($aModules as $sModuleName) {
            if (($iKey = array_search($sModuleName, $aDisabledModules)) !== false) {
                unset($aDisabledModules[$iKey]);
            }
        }
        $this->setExtendedProp('DisabledModules', implode("|", $aDisabledModules));
        $this->save();
    }
}
