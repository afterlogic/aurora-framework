<?php

class MinModule extends AApiModule
{
	public $oApiMinManager = null;

	public function init() {
		parent::init();
		
		$this->oApiMinManager = $this->GetManager('main', 'db');
		$this->AddEntry('window', 'EntryMin');
	}
	
	public function EntryMin()
	{
		$sResult = '';
		$aPaths = \Core\Service::GetPaths();
		$sModule = empty($aPaths[1]) ? '' : $aPaths[1];
		try
		{
			if (!empty($sModule))
			{
//				\CApi::GetModuleManager()->ExecuteMethod($sModule, $sMethod, $aParameters);
				if (/*method_exists($this->oActions, $sMethodName)*/ true)
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
	
	public function CreateMin()
	{
		return $this->oApiMinManager->createMin($this->getParamValue('HashId'), $this->getParamValue('Parameters'));
	}

	public function GetMinByHash()
	{
		return $this->oApiMinManager->getMinByHash($this->getParamValue('Hash'));
	}
	
	public function GetMinByID()
	{
		return $this->oApiMinManager->getMinByID($this->getParamValue('ID'));
	}

	public function UpdateMinByID()
	{
		return $this->oApiMinManager->updateMinByID($this->getParamValue('ID'), $this->getParamValue('Data'), $this->getParamValue('NewID'));
	}
	
	public function DeleteMinByID()
	{
		return $this->oApiMinManager->deleteMinByID($this->getParamValue('ID'));
	}
		

	
	
}
