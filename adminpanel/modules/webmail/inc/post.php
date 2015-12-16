<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CWebMailPostAction extends ap_CoreModuleHelper
{
	public function SystemLogging()
	{
		if (isset($_POST['btnClearLog']) || isset($_POST['btnUserActivityClearLog']))
		{
			/* @var $oApiLoggerManager CApiLoggerManager */
			$oApiLoggerManager = CApi::GetCoreManager('logger');

			$bResult = false;
			if (isset($_POST['btnClearLog']))
			{
				$bResult = $oApiLoggerManager->deleteCurrentLog();
			}
			else
			{
				$bResult = $oApiLoggerManager->deleteCurrentUserActivityLog();
			}

			if ($bResult)
			{
				$this->LastMessage = WM_INFO_LOGCLEARSUCCESSFUL;
			}
			else
			{
				$this->LastError = AP_LANG_ERROR;
			}
		}
		else if ($this->isStandartSubmit())
		{
			$this->oSettings->SetConf('Common/EnableLogging', CPost::GetCheckBox('ch_EnableDebugLogging'));
			$this->oSettings->SetConf('Common/EnableEventLogging', CPost::GetCheckBox('ch_EnableUserActivityLogging'));

			$this->oSettings->SetConf('Common/LoggingLevel', EnumConvert::FromPost(CPost::get('selVerbosity', ''), 'ELogLevel'));

			$this->checkBolleanWithMessage($this->oSettings->SaveToXml());
		}
	}
	
}