<?php

//Use the Composer classes
//use Composer\Console\Application;
//use Composer\Command\UpdateCommand;
//use Symfony\Component\Console\Input\ArrayInput;

class CLibrariesStep extends AInstallerStep
{
	/**
	 * @var api_Settings
	 */
//	protected $oSettings;

	/**
	 * @var CApiLicensingManager
	 */
	protected $oApiLicensing;

	public function __construct()
	{
	}

	public function DoPost()
	{
		$sPhpPath = isset($_POST['sPhpPath']) ? (string)$_POST['sPhpPath'] : '';
		$sTempPath = PSEVEN_APP_ROOT_PATH.'data/temp/';
		
		if (file_exists($sTempPath.'composer.json'))
		{
			unlink($sTempPath.'composer.json');
		}
		
		if (file_exists($sTempPath.'composer.lock'))
		{
			unlink($sTempPath.'composer.lock');
		}
		
		copy(WM_INSTALLER_PATH.'composer/composer.json', $sTempPath.'composer.json');
		
		//string '"f:\web\modules\php\PHP-5.6-x64\php-cgi.EXE" F:\web\domains\project8.dev/composer.phar update -n -d "F:\web\domains\project8.dev/" 
		set_time_limit(600);
		$sCommand = ($sPhpPath ? '"'.$sPhpPath.'" ' : 'php ') . PSEVEN_APP_ROOT_PATH.'composer.phar update -n --working-dir "'.$sTempPath.'"';
//		var_dump($sCommand);
		$result = shell_exec($sCommand);

		return true;
	}

	public function TemplateValues()
	{
		$sJsonData = file_get_contents(PSEVEN_APP_ROOT_PATH.'/composer.json');
		$oModulesConf = json_decode($sJsonData, true);
		
		$sLibrariesList = '';
		if ($oModulesConf['require'] && is_array($oModulesConf['require']))
		{
			$i = -1;
			foreach ($oModulesConf['require'] as $sLibraryName => $sVersion)
			{
				$i *= -1;
				$sLibrariesList .= '<div class="row'.($i > 0 ? '0' : '1').'"><span class="field_label">'.$sLibraryName.'</span><span class="field_value">'.$sVersion.'</span></div>';
			}
		}
		
		return array(
			'LibrariesList' => $sLibrariesList,
			'sPhpPath' => $this->find('php')
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
	
	public function runComposer($bDevMode = false)
	{
		//http://stackoverflow.com/questions/17219436/run-composer-with-a-php-script-in-browser
		$sTmpDir = PSEVEN_APP_ROOT_PATH."/tmp/composer";

		if (file_exists($sTmpDir.'/vendor/autoload.php') == true) {
			echo "Extracted autoload already exists. Skipping phar extraction as presumably it's already extracted.";
		}
		else{
			$composerPhar = new Phar(PSEVEN_APP_ROOT_PATH."composer.phar");
			//php.ini setting phar.readonly must be set to 0
			$composerPhar->extractTo($sTmpDir);
		}

		//This requires the phar to have been extracted successfully.
		require_once ($sTmpDir.'/vendor/autoload.php');

		// change out of the webroot so that the vendors file is not created in
		// a place that will be visible to the intahwebz
		//chdir('../');
		//Create the commands
		$aConfig = array(
			'command' => 'update',
			'--working-dir' => PSEVEN_APP_ROOT_PATH,
			'--prefer-dist' => !(bool)$bDevMode
		);
		
		$input = new ArrayInput($aConfig);

		//Create the application and run it with the commands
		$application = new Application();
		$application->run($input);
	}
}