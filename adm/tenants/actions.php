<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oDecorator = \CApi::GetModuleDecorator('Core');
	
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oDecorator->CreateTenant(
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('description', ''),
				$oHttp->GetPost('channel_id', '')
//				$oHttp->GetPost('password', '')
			);
			
			break;
		case 'update':
			$mResult = $oDecorator->UpdateTenant(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('description', ''),
				$oHttp->GetPost('channel_id', '')
			);
			break;
		case 'delete':
			$mResult = $oDecorator->DeleteTenant(
				$oHttp->GetPost('id', '')
			);
			break;
		case 'build':
			$aArguments = array(
				'--modules Auth',
				'--themes Default,Funny'
			);
			
			if ($oHttp->GetPost('id', ''))
			{
				$aArguments[] = '--tenant '.$oHttp->GetPost('login', '');
			}
			
			$sCommand = escapeshellcmd("@gulp styles ".implode($aArguments, ' '));
//			$sCommand = escapeshellcmd("@gulp styles:test");
			var_dump($sCommand);
//			var_dump(shell_exec($sCommand));
			var_dump(shell_exec($sCommand));
			var_dump(shell_exec("whoami"));
			break;
	}
	header('Location: ' . $_SERVER['REQUEST_URI']);
}


