<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiUsersManager class summary
 * 
 * @api
 * @package Users
 */
class CApiCoreUsersManager extends AApiManager
{
	/**
	 * @var CApiEavManager
	 */
	public $oEavManager = null;
	
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('users', $oManager, $oModule);
		
		$this->oEavManager = \CApi::GetCoreManager('eav', 'db');

		$this->incClass('user');
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
	public function getUserById($iUserId)
	{
		$oUser = null;
		try
		{
			if (is_numeric($iUserId))
			{
				$iUserId = (int) $iUserId;
				CApi::Plugin()->RunHook('api-get-user-by-id-precall', array(&$iUserId, &$oUser));
				if (null === $oUser)
				{
//					$oUser = $this->oStorage->getUserById($iUserId);
					
					$oUser = $this->oEavManager->getObjectById($iUserId);
					
					if ($oUser instanceof \CUser)
					{
						//TODO method needs to be refactored according to the new system of properties inheritance
						$oApiDomainsManager = CApi::GetCoreManager('domains');
						$oDomain = $oApiDomainsManager->getDefaultDomain();
						
						$oUser->setInheritedSettings(array(
							'domain' => $oDomain
						));
					}
				}
				CApi::Plugin()->RunHook('api-change-user-by-id', array(&$oUser));
			}
			else
			{
				throw new CApiBaseException(Errs::Validation_InvalidParameters);
			}
		}
		catch (CApiBaseException $oException)
		{
			$oUser = false;
			$this->setLastException($oException);
		}
		return $oUser;
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
	public function getUserList($iPage, $iUsersPerPage, $sOrderBy = 'Email', $iOrderType = \ESortOrder::ASC, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
//			$aResult = $this->oStorage->getUserList($iDomainId, $iPage, $iUsersPerPage, $sOrderBy, $bAscOrderType, $sSearchDesc);
			
			$aFilters =  array();
			
			if ($sSearchDesc !== '')
			{
//				$aFilters['FriendlyName'] = '%'.$sSearchDesc.'%';
				$aFilters['Email'] = '%'.$sSearchDesc.'%';
			}
				
			$aResults = $this->oEavManager->getObjects(
				'CUser', 
				array(
//					'IsMailingList', 'Email', 'FriendlyName', 'IsDisabled', 'IdUser', 'StorageQuota', 'LastLogin'
					'IsDisabled', 'LastLogin', 'Name'
				),
				$iPage,
				$iUsersPerPage,
				$aFilters,
				$sOrderBy,
				$iOrderType
			);
			
			foreach($aResults as $oUser)
			{
				$aResult[$oUser->iObjectId] = array(
					$oUser->Name,
//					$oUser->IsMailingList,
//					$oUser->Email,
//					$oUser->FriendlyName,
					$oUser->IsDisabled,
//					$oUser->IdUser,
//					$oUser->StorageQuota,
					$oUser->LastLogin
				);
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
	 * Determines how many users are in particular domain, with optional filtering. Domain identifier is used for look up.
	 * 
	 * @api
	 * 
	 * @param int $iDomainId Domain identifier.
	 * @param string $sSearchDesc = '' If not empty, only users matching this pattern are counted.
	 * 
	 * @return int | false
	 */
	public function getUsersCountForDomain($iDomainId, $sSearchDesc = '')
	{
		$mResult = false;
		try
		{
//			$mResult = $this->oStorage->getUsersCountForDomain($iDomainId, $sSearchDesc);
						
			$aFilters = array (
				'IsDefaultAccount' => true,
				'IdDomain' => $iDomainId
			);
			
			if ($sSearchDesc !== '')
			{
				$aFilters['Email'] = '%'.$sSearchDesc.'%';
				$aFilters['FriendlyName'] = '%'.$sSearchDesc.'%';
			}
			
			//TODO rewrite logic to use corresponding objects
			$mResult = $this->oEavManager->getObjectsCount(
				'CAccount', 
				$aFilters
			);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * Determines how many users are in particular tenant. Tenant identifier is used for look up.
	 * 
	 * @api
	 * 
	 * @param int $iTenantId Tenant identifier.
	 * 
	 * @return int | false
	 */
	public function getUsersCountForTenant($iTenantId)
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->getUsersCountForTenant($iTenantId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * Calculates total number of users registered in WebMail Pro.
	 * 
	 * @api
	 * 
	 * @return int
	 */
	public function getTotalUsersCount()
	{
		$iResult = 0;
		try
		{
			$iResult = $this->oStorage->getTotalUsersCount();
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $iResult;
	}
	
	/**
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function isExists(CUser $oUser)
	{
		$bResult = false;
		try
		{
			$aResults = $this->oEavManager->getObjects(
				'CUser',
				array('Name'),
				0,
				0,
				array('Name' => $oUser->Name)
			);

			if ($aResults)
			{
				foreach($aResults as $oObject)
				{
					if ($oObject->iObjectId !== $oUser->iObjectId)
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
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function createUser (CUser &$oUser)
	{
		$bResult = false;
		try
		{
			if ($oUser->validate())
			{
				if (!$this->isExists($oUser))
				{
//					$oChannel->Password = md5($oChannel->Login.mt_rand(1000, 9000).microtime(true));
					
					if (!$this->oEavManager->saveObject($oUser))
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
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function updateUser (CUser &$oUser)
	{
		$bResult = false;
		try
		{
			if ($oUser->validate())
			{
				var_dump($oUser);
//				if ($this->isExists($oUser))
//				{
//					$oChannel->Password = md5($oChannel->Login.mt_rand(1000, 9000).microtime(true));
					
					if (!$this->oEavManager->saveObject($oUser))
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
}
