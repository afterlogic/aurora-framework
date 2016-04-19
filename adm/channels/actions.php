<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oDecorator = \CApi::GetModuleDecorator('Core');
	
	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oDecorator->CreateChannel(
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('description', '')
			);
			
			break;
		case 'update':
			$mResult = $oDecorator->UpdateChannel(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('description', '')
			);
			break;
		case 'delete':
			$mResult = $oDecorator->DeleteChannel(
				$oHttp->GetPost('id', '')
			);
			break;
	}
}


