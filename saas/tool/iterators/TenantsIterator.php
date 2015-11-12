<?php

namespace saas\tool\iterators;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/PageIterator.php' ;
require_once APP_ROOTPATH.'/saas/Tenant.php' ;

/**
 * Tenants iterator.
 *
 */
class TenantsIterator extends PageIterator
{
    private $oNativeTenantsManager;
    private $iItemsPerPage;
    private $iPage;

    function __construct($iItemsPerPage = 10)
	{
		$this->iPage = 0;
		$this->iItemsPerPage = $iItemsPerPage;
		$this->oNativeTenantsManager = \CApi::Manager('tenants');
		parent::__construct();
    }
	
    protected function next_page()
	{
    	$this->iPage++;
    	return $this->get_page_data();
    }

    protected function rewind_page()
	{
    	$this->iPage = 1 ;
    	return $this->get_page_data();
    }

    function current()
	{
    	// TODO: для производительности желательно добавить кеш-таблицу тенантов
    	$tenant = new \saas\Tenant($this->key());
    	$tenant->fromIterator(parent::current());
    	return $tenant;
    }
    
    protected function get_page_data()
	{
    	$res = $this->oNativeTenantsManager->getTenantList($this->iPage, $this->iItemsPerPage);
    	return ($res !== false && !empty($res)) ? $res : false;
    }
}
