<?php

namespace saas\api;

/**
 * Фабрика сервисов.
 *
 * @author saydex
 *
 */
interface IServiceFactory
{
	/**
	 * Возвращает экземпляр сервиса типа, определяемого реализацией.
	 */
	function createService();
}
