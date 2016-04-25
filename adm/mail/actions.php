<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oMailDecorator = \CApi::GetModuleDecorator('Mail');
	
//	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oMailDecorator->CreateAccount(
				$oHttp->GetPost('user_id', ''),
				$oHttp->GetPost('email', ''),
				$oHttp->GetPost('password', ''),
				$oHttp->GetPost('server', '')
			);
			
			break;
		case 'update':
			$mResult = $oMailDecorator->UpdateAccount(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('email', ''),
				$oHttp->GetPost('password', ''),
				$oHttp->GetPost('server', '')
			);
			break;
		case 'delete':
			$mResult = $oMailDecorator->DeleteAccount(
				$oHttp->GetPost('id', '')
			);
			break;
	}
}


