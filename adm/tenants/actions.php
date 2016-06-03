<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oDecorator = \CApi::GetModuleDecorator('Core');
	
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oDecorator->CreateTenant(
				$oHttp->GetPost('name', ''),
				$oHttp->GetPost('description', ''),
				$oHttp->GetPost('channel_id', '')
//				$oHttp->GetPost('password', '')
			);
			
			break;
		case 'update':
			$mResult = $oDecorator->UpdateTenant(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('name', ''),
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
			
			if ($oHttp->GetPost('name', ''))
			{
				$aArguments[] = '--tenant '.$oHttp->GetPost('name', '');
			}
			
//			$sCommand = "node ".PSEVEN_APP_ROOT_PATH."node_modules/gulp/bin/gulp.js styles ".implode($aArguments, ' ') . " 2>&1";
			$sCommand = ('"c:/Program Files/nodejs/node.exe" '.PSEVEN_APP_ROOT_PATH.'node_modules/gulp/bin/gulp.js styles '.implode($aArguments, ' ') . ' 2>&1');

			$result = shell_exec($sCommand);
//			$result = exec($sCommand, $output, $return_var);
			var_dump($result);
//			var_dump($output);
//			var_dump($return_var);
//			var_dump(shell_exec("whoami"));
			break;
	}
//	header('Location: ' . $_SERVER['REQUEST_URI']);
}


