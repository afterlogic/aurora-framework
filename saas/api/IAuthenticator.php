<?php

namespace saas\api;

/**
 * Интерфейс аутентификатора SAAS.
 * 
 * @author saydex
 *
 */
interface IAuthenticator
{

	/**
	 * Аутентификация по паролю.
	 * 
	 * Возвращает интерфейс пользователя в случае успешной аутентификации.
	 * В ином случае - false.
	 * 
	 * @param string $login
	 * @param string $password
	 * @return IUser, ITenant или false в случае неудачи
	 */
	function authenticate($creds);

	/**
	 * Аутентификация только мастер-аккаунта.
	 * @param Creds $creds
	 * @return IUser | false
	 */
	function authenticateMaster($creds);

	function authenticateChannel($creds);

	/**
	 * Аутентификация только кастомера.
	 * @param TenantCreds $creds
	 * @return ITenant
	 */
	function authenticateCustomer($creds);

	/**
	 * Аутентификация простого пользователя
	 * @param unknown_type $creds
	 * @return IUser
	 */
	function authenticateUser($creds);
}
