<?php

/*
 *
 * @author saydex
 * 
 */

namespace saas\api;

/**
 * @brief Менеджер сессий.
 */
interface ISessionManager
{

	/**
	 * Создание сессия по объекту-пользователь.
	 * @param IUser $user
	 * @return string Идентификатор сессии.
	 */
	function createSession($user);

	/**
	 * Удаляет сессию по её идентификатору.
	 * @param string $session_id Идентификатор сессии
	 */
	function deleteSession($session_id);
}
