<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
//	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			\CApi::GetModule('Core')->CreateUser(array(
			//	'IdTenant' => $oHttp->GetPost('tenant', 0),
				'IdDomain' => $oHttp->GetPost('domain', 0),
				'Name' => $oHttp->GetPost('name', 0)
			));
			break;
		case 'update': 
			\CApi::GetModule('Core')->UpdateUser(array(
				'IdUser' => $oHttp->GetPost('id', 0),
				'IdDomain' => $oHttp->GetPost('domain', 0),
				'Name' => $oHttp->GetPost('name', 0)
			));
			break;
	}
}


