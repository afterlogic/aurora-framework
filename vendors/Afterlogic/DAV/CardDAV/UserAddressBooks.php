<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV\CardDAV;

class UserAddressBooks extends \Sabre\CardDAV\UserAddressBooks {

	/**
     * Returns a list of addressbooks
     *
     * @return array
     */
    public function getChildren() 
	{
        $objs = array();
		/* @var $oApiCapaManager \CApiCapabilityManager */
		$oApiCapaManager = \CApi::GetCoreManager('capability');
		
		$addressbooks = $this->carddavBackend->getAddressbooksForUser($this->principalUri);
		foreach($addressbooks as $addressbook) 
		{
			$objs[] = new AddressBook($this->carddavBackend, $addressbook);
		}
		if ($oApiCapaManager->isCollaborationSupported())
		{
			$sharedAddressbook = $this->carddavBackend->getSharedAddressBook($this->principalUri);
			$objs[] = new SharedAddressBook($this->carddavBackend, $sharedAddressbook, $this->principalUri);
		}
        return $objs;

    }	
}