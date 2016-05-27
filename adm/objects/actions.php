<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

	switch ($oHttp->GetPost('action'))
	{
		case 'delete':
			$mResult = $oAuthDecorator->DeleteAccount(
				$oHttp->GetPost('id', '')
			);
			break;
	}
	header('Location: ' . $_SERVER['REQUEST_URI']);
}


