<?php

namespace saas\connectors\soap;

define('AUTH_EXCEPTION', 'AuthenticationException');
define('VALIDATION_EXCEPTION', 'ValidationException');
define('DUPLICATE_USER_EXCEPTION', 'DuplicateUserException');
define('DUPLICATE_TENANT_EXCEPTION', 'DuplicateTenantException');
define('USER_DOES_NOT_EXIST_EXCEPTION', 'UserDoesNotExistException');
define('TENANT_DOES_NOT_EXIST_EXCEPTION', 'TenantDoesNotExistException');
define('INTERNAL_EXCEPTION', 'InternalException');
define('INVALID_SESSION_EXCEPTION', 'InvalidSessionException');

// Soap Exception Fault Codes
define('CLIENT_FAULT', "Client");
define('SERVER_FAULT', "Server");

// Default messages for specific exceptions
$emsg[AUTH_EXCEPTION] = 'Authentication failed';
$emsg[VALIDATION_EXCEPTION] = 'Data validation failed';
$emsg[DUPLICATE_USER_EXCEPTION] = 'User already exists';
$emsg[DUPLICATE_TENANT_EXCEPTION] = 'Tenant already exists';
$emsg[USER_DOES_NOT_EXIST_EXCEPTION] = 'User not found';
$emsg[TENANT_DOES_NOT_EXIST_EXCEPTION] = 'Tenant not found';
$emsg[INTERNAL_EXCEPTION] = 'Internal error';
$emsg[INVALID_SESSION_EXCEPTION] = 'Invalid session';

/**
 * Service exception class.
 * 
 * @author saydex
 *
 */
class ServiceException extends \Exception
{

	private $object;

	/**
	 * Constructs exception by type.
	 * 
	 * @param string $type Type of the exception.
	 * @param string $message Details of the exception.
	 */
	function __construct($type, $message = null)
	{
		global $emsg;

		parent::__construct($message);

		$this->object = self::CreateSoapFault($type, ($message === null) ? 
			(isset($emsg[$type]) ? $emsg[$type] : null) : $message);
	}

	/**
	 * Access to Soap object.
	 * 
	 * @return SoapFault object.
	 */
	function getSoapFault()
	{
		return $this->object;
	}

	/**
	 * Static operation for construction SoapFault object.
	 * 
	 * @param string $type Type of the exception.
	 * @param string $message Details of the exception.
	 */
	static function CreateSoapFault($type, $message = null)
	{
		global $emsg;

		$type = isset($emsg[$type]) ? $type : INTERNAL_EXCEPTION;

		$exception = new \stdClass();
		$exception->message = ($message === null) ? $emsg[$type] : $message;

		$detail = new \stdClass();
		$detail->{$type} = $exception;

		$faultcode = CLIENT_FAULT;
		if ($type == INTERNAL_EXCEPTION)
		{
			$faultcode = SERVER_FAULT;
		}

		return new \SoapFault($faultcode, $type, null, $detail, $type);
	}
}
