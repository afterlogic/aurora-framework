<?php

namespace saas\connectors\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/saas/api/ISOAPServiceManager.php';
require_once APP_ROOTPATH.'/saas/tool/soap/ArrayTraits.php';
require_once APP_ROOTPATH.'/saas/connectors/soap/ServiceException.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

use
	\saas\api\ISOAPServiceManager,
	\saas\Exception,
	\saas\api\Field,
	\saas\tool\soap\ArrayTraits
;

/**
 * 
 * Базовый функционал сервис-менеджера.
 * 
 * @author saydex
 *
 */
abstract class BaseServiceManager implements ISOAPServiceManager
{
	/**
	 * Операция возвращает оборачиваемый экземпляр менеджера (SAAS интерфейс)
	 * по кредам.
	 * 
	 * @param *Creds $creds
	 */
	abstract protected function hostManager($creds);
	
	/**
	 * Возвращает экземпляр сервиса по кредам.
	 * @param *Creds $creds
	 */
	abstract protected function hostInstance($creds);
	
	/**
	 * Возвращает список полей на чтение.
	 */
	abstract protected function readFieldNames();
	
	/**
	 * Возвращает список полей на запись.
	 */
	abstract protected function writeFieldNames();

	protected function setInstanceField($instance, $name, $value)
	{
		if (!in_array($name, $this->writeFieldNames()))
		{
			Exception::throwException(new \Exception('Invalid field: '.$name));
			return;
		}

		$fname = 'set'.strtoupper($name[0]).substr($name, 1);

		$instance->$fname($value);
	}

	protected function instanceField($instance, $name)
	{
		if (!in_array($name, $this->readFieldNames()))
		{
			Exception::throwException(new \Exception('Invalid field: '.$name));
			return false;
		}
	
		return $instance->$name();
	}
	
	protected function toSoapObject($instance)
	{
		$res = ArrayTraits::toSOAPArray($instance->cachedFields());
		
		$idField = new Field();
		$idField->name = 'id';
		$idField->value = $instance->id();
		$res[] = $idField;
		
		return $res;
	}
	
	function field($name, $creds)
	{
		try
		{
			return $this->instanceField($this->hostInstance($creds), $name);
		}
		catch(ServiceException $se)
		{
			return $se->soapFault();
		}
		catch(\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function setField($name, $value, $creds)
	{
		try
		{
			$instance = $this->hostInstance($creds);
			$this->setInstanceField($instance, $name, $value);
			$instance->update();
		}
		catch(ServiceException $se)
		{
			return $se->soapFault();
		}
		catch(\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function fields($aNames, $creds)
	{
		try
		{
			$instance = $this->hostInstance($creds);
	
			$res = array();
			foreach ($aNames as $name)
			{
				$res[$name] = $this->instanceField($instance, $name);
			}
	
			return ArrayTraits::toSOAPArray($res);
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function setFields($aSoapFields, $creds)
	{
		try
		{
			$instance = $this->hostInstance($creds);
			foreach ($aSoapFields as $field)
			{
				$this->setInstanceField($instance, $field->name, $field->value);
			}
			
			$instance->update();
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch(\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function listInstances($creds)
	{
		try
		{
			$manager = $this->hostManager($creds);
	
			$res = array();
			$it = $manager->instances();

			foreach ($it as $instance)
			{
				$res[] = $this->toSoapObject($instance);
			}
	
			return $res;
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function addInstance( $aSoapFields, $creds )
	{
		try
		{
			$manager = $this->hostManager($creds);
			$instance = $manager->createService();

			foreach( $aSoapFields as $field )
			{
				if (isset($field->name, $field->value))
				{
					$this->setInstanceField( $instance, $field->name, $field->value);
				}
			}
	
			$manager->addInstance($instance, $creds->bTry);
	
			return $instance->id();
		} 
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function removeInstance($creds)
	{
		try
		{
			$manager = $this->hostManager($creds);
			$object = $this->hostInstance($creds);
	
			$manager->removeInstance($object, $creds->bTry);
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}

	function findInstances( $aSoapFields, $creds )
	{
		try
		{
			$manager = $this->hostManager($creds);
			
			$res = array();
			$it = $manager->instances();
			
			foreach ($it as $instance)
			{
				// Try find specific instance
				$successSearch = true;
				foreach ($aSoapFields as $field)
				{
					if ($this->instanceField($instance, $field->name) != $field->value)
					{
						$successSearch = false ;
						break ;
					}
				}
				
				if ($successSearch)
				{
					$res[] = $this->toSoapObject($instance);
				}
			}
			
			return $res;
		}
		catch (ServiceException $se)
		{
			return $se->soapFault();
		}
		catch (\Exception $e)
		{
			return ServiceException::CreateSoapFault(INTERNAL_EXCEPTION, $e->getMessage());
		}
	}
}
