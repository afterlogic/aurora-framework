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
		case 'build-themes':
			$aArguments = array(
				'--themes Default,Funny'
			);
			
			if ($oHttp->GetPost('name', ''))
			{
				$aArguments[] = '--tenant '.$oHttp->GetPost('name', '');
				
				$aModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'modules/'), array('.','..'));
				if (is_dir(PSEVEN_APP_ROOT_PATH.'tenants/' . $oHttp->GetPost('name', '') . '/modules/')) 
				{
					$aTenantModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'tenants/' . $oHttp->GetPost('name', '') . '/modules/'), array('.','..'));
					$aModules = array_merge(array_diff($aModules, $aTenantModules), $aTenantModules);
				}
				
				$aArguments[] =	'--modules '.implode($aModules, ',');
			}
			else
			{
				$aModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'modules/'), array('.','..'));
						
				$aArguments[] =	'--modules '.implode(',', $aModules);
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
		case 'build-langs':
			$aArguments = array(
				'--langs Arabic,Bulgarian,Chinese-Simplified,Chinese-Traditional,Czech,Danish,Dutch,English,Estonian,Finnish,French,German,Greek,Hebrew,Hungarian,Italian,Japanese,Korean,Latvian,Lithuanian,Norwegian,Persian,Polish,Portuguese-Brazil,Portuguese-Portuguese,Romanian,Russian,Serbian,Slovenian,Spanish,Swedish,Thai,Turkish,Ukrainian,Vietnamese'
			);
			
			if ($oHttp->GetPost('name', ''))
			{
				$aArguments[] = '--tenant '.$oHttp->GetPost('name', '');
				
				$aModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'modules/'), array('.','..'));
				if (is_dir(PSEVEN_APP_ROOT_PATH.'tenants/' . $oHttp->GetPost('name', '') . '/modules/')) 
				{
					$aTenantModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'tenants/' . $oHttp->GetPost('name', '') . '/modules/'), array('.','..'));
					$aModules = array_merge(array_diff($aModules, $aTenantModules), $aTenantModules);
				}
				
				$aArguments[] =	'--modules '.implode($aModules, ',');
			}
			else
			{
				$aModules = array_diff(scandir(PSEVEN_APP_ROOT_PATH.'modules/'), array('.','..'));
						
				$aArguments[] =	'--modules '.implode(',', $aModules);
			}
			
			$sCommand = ('"c:/Program Files/nodejs/node.exe" '.PSEVEN_APP_ROOT_PATH.'node_modules/gulp/bin/gulp.js langs '.implode($aArguments, ' ') . ' 2>&1');

			$result = shell_exec($sCommand);
			var_dump($result);

			break;
	}
//	header('Location: ' . $_SERVER['REQUEST_URI']);
}


