<?php

/* -AFTERLOGIC LICENSE HEADER- */

use \Modules\HelpDesk\CAccount as CHelpDeskAccount;

/**
 * CApiAccountsManager class summary
 * 
 * @api
 * @package Accounts
 */
class CApiHelpDeskAccountsManager extends AApiManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	public $oCoreDecorator = null;
	
	public $sAccountClassName = '';
	
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('accounts', $oManager, $oModule);
		
		$this->oEavManager = \CApi::GetCoreManager('eav', 'db');
		
		$this->oCoreDecorator = \CApi::GetModuleDecorator('Core');
		
		$this->sAccountClassName = 'Modules\HelpDesk\CAccount';

		$this->incClass('account');
	}

	/**
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * @api
	 * @todo not used
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CUser | false
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = null;
		try
		{
			if (is_numeric($iAccountId))
			{
				$iAccountId = (int) $iAccountId;
				CApi::Plugin()->RunHook('api-get-account-by-id-precall', array(&$iAccountId, &$oAccount));
				if (null === $oAccount)
				{
//					$oAccount = $this->oStorage->getUserById($iUserId);
					$oAccount = $this->oEavManager->getObjectById($iAccountId);
					
					if ($oAccount instanceof CHelpDeskAccount)
					{
						//TODO method needs to be refactored according to the new system of properties inheritance
//						$oApiDomainsManager = CApi::GetCoreManager('domains');
//						$oDomain = $oApiDomainsManager->getDefaultDomain();
						
//						$oAccount->setInheritedSettings(array(
//							'domain' => $oDomain
//						));
					}
				}
				CApi::Plugin()->RunHook('api-change-account-by-id', array(&$oAccount));
			}
			else
			{
				throw new CApiBaseException(Errs::Validation_InvalidParameters);
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
	}
	
	/**
	 * Retrieves information on particular WebMail Pro user. 
	 * 
	 * @api
	 * @todo not used
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CUser | false
	 */
	public function getAccountByCredentials($sLogin, $sPassword)
	{
		$oAccount = null;
		try
		{
			$aResults = $this->oEavManager->getObjects(
				$this->sAccountClassName, 
				array(
					'IsDisabled', 'Login', 'Password', 'IdUser'
				),
				0,
				0,
				array(
					'Login' => $sLogin,
					'Password' => $sPassword,
					'IsDisabled' => false
				)
			);
			
			if (is_array($aResults) && count($aResults) === 1)
			{
				$oAccount = $aResults[0];
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
	}
	
	
	public function getAccountByEmail($sIdTenant, $sLogin)
	{
		$oAccount = null;
		try
		{
			$aResults = $this->oEavManager->getObjects(
				$this->sAccountClassName, 
				array(
					'IsDisabled', 'Login', 'IdUser'
				),
				0,
				0,
				array(
					'Login' => $sLogin,
					'IsDisabled' => false
				)
			);
			
			if (is_array($aResults))
			{
//				$aUsserIds = Underscode\Types\Arrays::pluck($aResults, 'IdUser');
				
				foreach ($aResults as $key => $oAccount) {
					$oUser = $this->oCoreDecorator->GetUser($oAccount->IdUser);
					
					if ($oUser && $oUser->IdTenant !== $sIdTenant)
					{
						unset($aResults[$key]);
					}
				}
			}
			
			if (count($aResults) === 0)
			{
				$oAccount = array_values($aResults)[0];
			}
		}
		catch (CApiBaseException $oException)
		{
			$oAccount = false;
			$this->setLastException($oException);
		}
		return $oAccount;
	}

	/**
	 * Obtains list of information about users for specific domain. Domain identifier is used for look up.
	 * The answer contains information only about default account of founded user.
	 * 
	 * @api
	 * 
	 * @param int $iDomainId Domain identifier.
	 * @param int $iPage List page.
	 * @param int $iUsersPerPage Number of users on a single page.
	 * @param string $sOrderBy = 'email'. Field by which to sort.
	 * @param bool $bAscOrderType = true. If **true** the sort order type is ascending.
	 * @param string $sSearchDesc = ''. If specified, the search goes on by substring in the name and email of default account.
	 * 
	 * @return array | false [IdAccount => [IsMailingList, Email, FriendlyName, IsDisabled, IdUser, StorageQuota, LastLogin]]
	 */
	public function getAccountList($iPage, $iUsersPerPage, $sOrderBy = 'Login', $iOrderType = \ESortOrder::ASC, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
//			$aResult = $this->oStorage->getUserList($iDomainId, $iPage, $iUsersPerPage, $sOrderBy, $bAscOrderType, $sSearchDesc);
			
			$aFilters =  array();
			
			if ($sSearchDesc !== '')
			{
				$aFilters['Login'] = '%'.$sSearchDesc.'%';
			}
				
			$aResults = $this->oEavManager->getObjects(
				'CAccount', 
				array(
					'IsDisabled', 'Login', 'Password', 'IdUser'
				),
				$iPage,
				$iUsersPerPage,
				$aFilters,
				$sOrderBy,
				$iOrderType
			);

			if (is_array($aResults))
			{
				foreach($aResults as $oItem)
				{
					$aResult[$oItem->iObjectId] = array(
						$oItem->Login,
						$oItem->Password,
						$oItem->IdUser,
						$oItem->IsDisabled
					);
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$aResult = false;
			$this->setLastException($oException);
		}
		return $aResult;
	}

	/**
	 * @param CHelpDeskAccount $oAccount
	 *
	 * @return bool
	 */
	public function isExists(CHelpDeskAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$aResults = $this->oEavManager->getObjects(
				$this->sAccountClassName,
				array('Login'),
				0,
				0,
				array('Login' => $oAccount->Login)
			);

			if ($aResults)
			{
				foreach($aResults as $oObject)
				{
					if ($oObject->iObjectId !== $oAccount->iObjectId)
					{
						$bResult = true;
						break;
					}
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}
	
	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function createAccount (CHelpDeskAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
				if (!$this->isExists($oAccount))
				{
					if (!$this->oEavManager->saveObject($oAccount))
					{
						throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::UsersManager_UserAlreadyExists);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function updateAccount (CAccount &$oAccount)
	{
		$bResult = false;
		try
		{
			if ($oAccount->validate())
			{
//				if ($this->isExists($oAccount))
//				{
					if (!$this->oEavManager->saveObject($oAccount))
					{
						throw new CApiManagerException(Errs::UsersManager_UserCreateFailed);
					}
//				}
//				else
//				{
//					throw new CApiManagerException(Errs::UsersManager_UserAlreadyExists);
//				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * @param CChannel $oChannel
	 *
	 * @throws $oException
	 *
	 * @return bool
	 */
	public function deleteAccount(CAccount $oAccount)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oEavManager->deleteObject($oAccount->iObjectId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
