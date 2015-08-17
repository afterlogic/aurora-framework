<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/libraries/afterlogic/api.php';
require_once APP_ROOTPATH.'/saas/api/IChannelsManager.php';
require_once APP_ROOTPATH.'/saas/api/IChannel.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/ChannelsIterator.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

/**
 *
 * @author saydex
 *
 */
class ChannelsManager implements \saas\api\IChannelsManager
{
	protected function nativeChannelManager()
	{
		return \CApi::Manager('channels');
	}

	function __construct()
	{
	}

	/**
	 * Поиск области по ее имени
	 * @param unknown_type $name
	 */
	function findByName($name)
	{
		$it = $this->instances();

		foreach ($it as $channel)
		{
			if (strcasecmp($channel->name(), $name) === 0)
			{
				return $channel;
			}
		}

		return false;
	}

	/**
	 * Search domain by name
	 * @param string $name
	 */
	function findById($reqId)
	{
		$it = $this->instances();

		foreach ($it as $id => $tenant)
		{
			if ($id == $reqId)
			{
				return $tenant;
			}
		}

		return false;
	}

	/**
	 * Возвращает экземпляр сервиса типа, определяемого реализацией.
	 */
	function createService()
	{
		return new Channel();
	}

	/**
	 * @todo
	 * Добавление экземпляра в базу.
	 * @param unknown_type $instance
	 */
	function addInstance($tenant, $bTry = false)
	{
		/* if( ! $tenant ) {
		  Exception::throwException( new \Exception( 'Invalid tenant' ) ) ;
		  return false ;
		  }

		  $nativeTenant = $tenant->nativeService() ;
		  if( $this->nativeChannelManager()->isTenantExists( $nativeTenant ) ) {
		  Exception::throwException( new \Exception( 'Tenant ' . $tenant->name() . ' already exists' ) ) ;
		  return false ;
		  }

		  if( ! $bTry ) {
		  if( ! $this->nativeTenantManager()->createTenant( $nativeTenant ) ) {
		  Exception::throwException( $this->nativeTenantManager()->GetLastException() ) ;
		  return false ;
		  }

		  $tenant->postAddInstance() ;
		  } */

		return true;
	}

	/**
	 * @todo
	 * Удаление специфического экземляра.
	 * @param IService $instance Экемпляр сервиса
	 */
	function removeInstance($tenant, $bTry = false)
	{
		/* if( ! $tenant )
		  return false ;

		  if( ! $bTry ) {
		  if( ! $this->nativeTenantManager()->deleteTenant( $tenant->nativeService() ) ) {
		  Exception::throwException( $this->nativeTenantManager()->GetLastException() ) ;
		  return false ;
		  }

		  $tenant->cleanup() ;
		  } */

		return true;
	}

	/**
	 * Вернет итератор списка областей.
	 */
	function instances()
	{
		return new \saas\tool\iterators\ChannelsIterator();
	}
}
