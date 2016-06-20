<?php
//http://stackoverflow.com/questions/17219436/run-composer-with-a-php-script-in-browser
define('EXTRACT_DIRECTORY', __Dir__."/test");

if (file_exists(EXTRACT_DIRECTORY.'/vendor/autoload.php') == true) {
    echo "Extracted autoload already exists. Skipping phar extraction as presumably it's already extracted.";
}
else{
    $composerPhar = new Phar("Composer.phar");
    //php.ini setting phar.readonly must be set to 0
    $composerPhar->extractTo(EXTRACT_DIRECTORY);
}

//This requires the phar to have been extracted successfully.
require_once (EXTRACT_DIRECTORY.'/vendor/autoload.php');

//Use the Composer classes
use Composer\Console\Application;
use Composer\Command\UpdateCommand;
use Symfony\Component\Console\Input\ArrayInput;

// change out of the webroot so that the vendors file is not created in
// a place that will be visible to the intahwebz
//chdir('../');

//Create the commands
$input = new ArrayInput(array('command' => 'update'));

//Create the application and run it with the commands
$application = new Application();
$application->run($input);

?>