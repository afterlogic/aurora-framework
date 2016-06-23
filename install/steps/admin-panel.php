<?php

// api
include_once WM_INSTALLER_PATH.'../system/api.php';

class CAdminpanelStep extends AInstallerStep
{
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	public function __construct()
	{
		$this->oSettings =& CApi::GetSettings();
	}

	public function DoPost()
	{
		if (isset($_POST['next_btn']))
		{
			if (5 > strlen(trim(CPost::get('txtPassword1', ''))))
			{
				CSession::Set('wm_install_pass_error', 'Minimum password length is 5 characters.');
			}
			else if (CPost::get('txtPassword1', '') !== CPost::get('txtPassword2', ''))
			{
				CSession::Set('wm_install_pass_error', 'The password and its confirmation don\'t match.');
			}
			else
			{
				$this->oSettings->SetConf('Common/AdminPassword', md5(CPost::get('txtPassword1', '')));
				return $this->oSettings->Save();
			}
		}
		
		return false;
	}

	public function TemplateValues()
	{
		$sFootError = '';
		if (CSession::Has('wm_install_pass_error'))
		{
			$sFootError = CSession::get('wm_install_pass_error', '');
			CSession::clear('wm_install_pass_error');
		}

		return array(
			'Login' => $this->oSettings->GetConf('AdminLogin'),
			'Password1' => '',
			'Password2' => '',
			'FootError' => $sFootError,
		);
	}
}