<?php
namespace AuroraPlatform;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class Installer
{
    public static function postInstall(Event $event)
    {
		$sourcePath = "web/";
		$destPath = "";
		
		self::recurse_copy($sourcePath."build/", $destPath."build/");
		self::recurse_copy($sourcePath."gulp-tasks/", $destPath."gulp-tasks/");
		
		copy($sourcePath."package.json", $destPath."package.json");
		copy($sourcePath."gulpfile.js", $destPath."gulpfile.js");
		copy($sourcePath."index.php", $destPath."index.php");
		copy($sourcePath."dav.php", $destPath."dav.php");
		copy($sourcePath."common.php.php", $destPath."common.php.php");
    }
	
	public static function recurse_copy($src,$dst) { 
		$dir = opendir($src); 
		@mkdir($dst); 
		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($src . '/' . $file) ) { 
					self::recurse_copy($src . '/' . $file,$dst . '/' . $file); 
				} 
				else { 
					copy($src . '/' . $file,$dst . '/' . $file); 
				} 
			} 
		} 
		closedir($dir); 
	} 
}
