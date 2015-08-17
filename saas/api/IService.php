<?php

namespace saas\api;

/**
 * Сервис.
 * 
 * Любой объект - User, Domain, Tenant - это есть сервисы.
 * 
 * Сервисы представлены и иерархичной структуре. Например
 * Tenant заведует сервисами Domain, в Domain предоставляет Users.
 * 
 * Созданием сервисов рулит IServiceManager.
 * 
 * @author saydex
 *
 */
interface IService
{

	// Состояние сервиса (вкл/выкл)
	function disabled();

	/// Изменение состояние сервиса
	function setDisabled($enable = true);

	/**
	 * Доступ к менеджеру сервисов
	 * @param string $name Имя сервиса
	 */
	function serviceManager($name);
}

