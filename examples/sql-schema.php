<?php

/* -AFTERLOGIC LICENSE HEADER- */

	// remove the following line for real use
	exit('remove this line');

	// utilizing API
	include_once __DIR__.'/../core/api.php';
	
	if (class_exists('CApi') && CApi::IsValid())
	{
		// Getting required API class
		$oApiDbManager = CApi::GetCoreManager('db');

		$oSettings =& CApi::GetSettings();
		$oSettings->SetConf('Common/DBPrefix', '');

		$sSql = $oApiDbManager->getSqlSchemaAsString(true);
//		file_put_contents('../SQL-'.gmdate('Y-m-d_H-i-s').'.txt', str_replace("\r", '', $sSql));
		echo $sSql;
	}
	else
	{
		echo 'AfterLogic API isn\'t available';
	}
