<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oAuthDecorator = \CApi::GetModuleDecorator('BasicAuth');

	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oAuthDecorator->CreateAccount(
				0, // tenant id
				$oHttp->GetPost('user_id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('password', '')
			);
			
			break;
		case 'update':
			$mResult = $oAuthDecorator->UpdateAccount(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('password', '')
			);
			break;
		case 'delete':
			$mResult = $oAuthDecorator->DeleteAccount(
				$oHttp->GetPost('id', '')
			);
			break;
	}
	header('Location: ' . $_SERVER['REQUEST_URI']);
}


