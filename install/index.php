<?php

/* -AFTERLOGIC LICENSE HEADER- */


	defined('WM_INSTALLER_PATH') || define('WM_INSTALLER_PATH', (dirname(__FILE__).'/'));

	include WM_INSTALLER_PATH.'installer.php';

	$oInstaller = new CInstaller();
	$oInstaller->Run();