<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace afterlogic\DAV\Locks\Backend;

use afterlogic\DAV\Constants;

class PDO extends \Sabre\DAV\Locks\Backend\PDO {

    /**
     * Constructor 
     */
    public function __construct() {

		$oPdo = \CApi::GetPDO();
		$dbPrefix = \CApi::GetSettings()->GetConf('Common/DBPrefix');
		
		parent::__construct($oPdo, $dbPrefix.Constants::T_LOCKS);

    }
}
