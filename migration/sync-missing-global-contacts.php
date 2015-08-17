<?php

/* -AFTERLOGIC LICENSE HEADER- */

// remove the following line for real use
//exit('remove this line');

require_once dirname(__FILE__).'/../libraries/afterlogic/api.php';

/* @var $oApiGcontactsManager CApiGcontactsManager */
$oApiGcontactsManager = CApi::Manager('gcontacts');

echo $oApiGcontactsManager ? ($oApiGcontactsManager->syncMissingGlobalContacts() ? 'DONE' : 'ERROR') : 'ERROR';
