<?php

// api
include_once WM_INSTALLER_PATH.'../system/api.php';

class CModulesStep extends AInstallerStep
{
	/**
	 * @var api_Settings
	 */
	protected $oSettings;

	/**
	 * @var CApiLicensingManager
	 */
	protected $oApiLicensing;

	public function __construct()
	{
		$this->oSettings =& CApi::GetSettings();
		$this->oApiLicensing = CApi::GetCoreManager('licensing');

		if (!isset($_SESSION['wm_install_t']))
		{
			$sKey = @file_exists(WM_INSTALLER_PATH.'KEY') ? @file_get_contents(WM_INSTALLER_PATH.'KEY') : '';
			if ($this->oApiLicensing && 0 === strlen($this->oApiLicensing->GetLicenseKey()))
			{
				if (empty($sKey))
				{
					$this->oApiLicensing->UpdateLicenseKey($this->oApiLicensing->GetT());
				}
				else
				{
					$this->oApiLicensing->UpdateLicenseKey($sKey);
					if (11 === $this->oApiLicensing->GetLicenseType())
					{
						$this->oApiLicensing->UpdateLicenseKey($this->oApiLicensing->GetT());
					}
				}
			}
		}
	}

	public function DoPost()
	{
		$bDevMode = (bool)CPost::get('chDevMode', 0);
		$sPhpPath = (string)CPost::get('sPhpPath', '');

		$sCommand = ($sPhpPath ? '"'.$sPhpPath.'" ' : 'php ') . PSEVEN_APP_ROOT_PATH.'composer.phar update -n -d "'.PSEVEN_APP_ROOT_PATH.'" '.($bDevMode ? '' : ' --prefer-dist');
		$result = shell_exec($sCommand);
		var_dump($sCommand);
		var_dump($result);
	
		return false;
	}

	public function TemplateValues()
	{
		$sJsonData = file_get_contents(PSEVEN_APP_ROOT_PATH.'/modules.json');
		$oModulesConf = json_decode($sJsonData, true);
		
		$sModulesList = '';
		if ($oModulesConf['require'] && is_array($oModulesConf['require']))
		{
			$i = -1;
			foreach ($oModulesConf['require'] as $sModuleName => $sBranchName)
			{
				$i *= -1; 
				$sModulesList .= '<div class="row'.($i > 0 ? '0' : '1').'"><span class="field_label">'.$sModuleName.'</span><span class="field_value">'.$sBranchName.'</span></div>';
			}
		}
		
		return array(
			'ModulesList' => $sModulesList,
			'DevModeChecked' => (!isset($_POST['chDevMode'])) ? '' : 'checked="cheched"',
			'chDevModeText' => 'Will be added .git',
			'sPhpPath' => $this->find('php-cgi')
		);
	}
	
	/**
     * Finds an executable by name. From Symfony
     *
     * @param string $name      The executable name (without the extension)
     * @param string $default   The default to return if no executable is found
     * @param array  $extraDirs Additional dirs to check into
     *
     * @return string The executable path or default value
     */
	public function find($name, $default = null, array $extraDirs = array())
    {
        if (ini_get('open_basedir')) {
            $searchPath = explode(PATH_SEPARATOR, ini_get('open_basedir'));
            $dirs = array();
            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } else {
                    if (basename($path) == $name && is_executable($path)) {
                        return $path;
                    }
                }
            }
        } else {
            $dirs = array_merge(
                explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path')),
                $extraDirs
            );
        }
        $suffixes = array('');
        if ('\\' === DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = $pathExt ? explode(PATH_SEPARATOR, $pathExt) : array('.exe', '.bat', '.cmd', '.com');
        }
        foreach ($suffixes as $suffix) {
            foreach ($dirs as $dir) {
                if (is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
                    return $file;
                }
            }
        }
        return $default;
    }
}