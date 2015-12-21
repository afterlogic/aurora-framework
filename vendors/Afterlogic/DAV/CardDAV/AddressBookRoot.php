<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace afterlogic\DAV\CardDAV;

class AddressBookRoot extends \Sabre\CardDAV\AddressBookRoot
{
	protected $oAccount = null;
	
	protected function getAccount($principalUri)
	{
		if (null === $this->oAccount)
		{
			$this->oAccount = \afterlogic\DAV\Utils::GetAccountByLogin(basename($principalUri));
		}
		return $this->oAccount;
	}

	public function getChildForPrincipal(array $principal)
	{
		$oApiCapabilityManager = /* @var \CApiCapabilityManager */ \CApi::GetCoreManager('capability');
		
		$oAccount = $this->getAccount($principal['uri']);
		if ($oAccount instanceof \CAccount &&
			$oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			return new UserAddressBooks($this->carddavBackend, $principal['uri']);
		}
		else
		{
			return new EmptyAddressBooks($this->carddavBackend, $principal['uri']);
		}
    }

}
