<?php

class MinModule extends AApiModule
{
	public $oApiMinManager = null;

	public function init() {
		parent::init();
		
		$this->oApiMinManager = $this->GetManager('main', 'db');
		$this->AddEntry('min', 'EntryMin');
		$this->AddEntry('window', 'EntryMin');
	}
	
	public function EntryMin()
	{
		$sResult = '';
		$aPaths = \Core\Service::GetPaths();
		$sAction = empty($aPaths[1]) ? '' : $aPaths[1];
		try
		{
			if (!empty($sAction))
			{
				$sMethodName =  $aPaths[0].$sAction;
				if (method_exists($this->oActions, $sMethodName))
				{
					if ('Min' === $aPaths[0])
					{
						$mHashResult = $this->oApiMinManager->getMinByHash(empty($aPaths[2]) ? '' : $aPaths[2]);

						$this->oActions->SetActionParams(array(
							'Result' => $mHashResult,
							'Hash' => empty($aPaths[2]) ? '' : $aPaths[2],
						));
					}
					else
					{
						$this->oActions->SetActionParams(array(
							'AccountID' => empty($aPaths[2]) || '0' === (string) $aPaths[2] ? '' : $aPaths[2],
							'RawKey' => empty($aPaths[3]) ? '' : $aPaths[3]
						));
					}

					$mResult = call_user_func(array($this->oActions, $sMethodName));
					$sTemplate = isset($mResult['Template']) && !empty($mResult['Template']) &&
						is_string($mResult['Template']) ? $mResult['Template'] : null;

					if (!empty($sTemplate) && is_array($mResult) && file_exists(PSEVEN_APP_ROOT_PATH.$sTemplate))
					{
						$sResult = file_get_contents(PSEVEN_APP_ROOT_PATH.$sTemplate);
						if (is_string($sResult))
						{
							$sResult = strtr($sResult, $mResult);
						}
						else
						{
							\CApi::Log('Empty template.', \ELogLevel::Error);
						}
					}
					else if (!empty($sTemplate))
					{
						\CApi::Log('Empty template.', \ELogLevel::Error);
					}
					else if (true === $mResult)
					{
						$sResult = '';
					}
					else
					{
						\CApi::Log('False result.', \ELogLevel::Error);
					}
				}
				else
				{
					\CApi::Log('Invalid action.', \ELogLevel::Error);
				}
			}
			else
			{
				\CApi::Log('Empty action.', \ELogLevel::Error);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);
		}		
		
		return $sResult;		
	}
	
}

return new MinModule('1.0');
