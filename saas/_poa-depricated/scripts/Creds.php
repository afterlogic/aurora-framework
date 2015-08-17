<?php

class Creds
{
	public $bTry = false;
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
