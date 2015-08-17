<?php

namespace saas\api;

/**
 * Креды используются для аутентификации операций.
 * 
 * В некоторых случаях используется TRY режим, который позволяет
 * не внося изменений проверить готовность выполнения операции.
 * Полезность этого метода в том, что избавляет вводить дополнительные
 * проверки в прикладном коде.
 * 
 * @author saydex
 */
class Creds
{
	public $bTry = false;	///< Режим TRY
	public $login;
	public $password;
}

class TenantCreds extends Creds
{
	public $tenantId;
}

class DomainCreds extends TenantCreds
{
	public $domainId;
}

class UserCreds extends TenantCreds
{
	public $userId;
}
