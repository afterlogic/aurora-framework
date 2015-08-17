<?php

namespace saas\api;

/**
 * SOAP интерфейс. Это SOAP обёртка для IServiceManager.
 * 
 * @note Instance == array Of Field
 * 
 * @author saydex
 *
 */
interface ISOAPServiceManager
{

	/**
	 * Поиск экземплятор по критериям.
	 * Критерии перечисляются в массиве $aFields (элементы типа Field).
	 * Найденные экземпляты возвращаются в виде массива полей Field.
	 * 
	 * @param array(Field) $aFields Критерии поиска.
	 * @param Creds $creds Авторизация операции.
	 * @return array(Instance) Список найденных экземпляров.
	 */
	function findInstances($aFields, $creds);

	/**
	 * Создание экземпляра и добавление его в базу.
	 * 
	 * @param array(Field) $aFields Массив полей экземпляра.
	 * @param Creds $creds Авторизация операции.
	 */
	function addInstance($aFields, $creds);

	/**
	 * Уданение экземплята по его идентификатору.
	 * Идентификатор экземпляра содержится в структуре *Creds.
	 * 
	 * @param Creds $creds Авторизация операции.
	 */
	function removeInstance($creds);

	/**
	 * Возвращает список существующих экземпляров.
	 * 
	 * @param Creds* $creds Авторизация операции.
	 * @return array(Instance) Список экземпляров.
	 */
	function listInstances($creds);

	/**
	 * Возвращеет поле по его имени для конкретного экземпляра.
	 * Идентификатор экземпляра находиться в кредах.
	 * 
	 * @param string $name Имя поля
	 * @param *Creds $creds Авторизация операции
	 * @return Field Поле с именем $name или false
	 */
	function field($name, $creds);

	/**
	 * 
	 * Возвращает массив полей перечисленных по именам (см. \a fields()).
	 * 
	 * @param array(string) $aNames Массив имён полей
	 * @param *Creds $creds Авторизация операции
	 * @return array(Field) Массив полей.
	 */
	function fields($aNames, $creds);

	/**
	 * Устанавливает значение поля.
	 * 
	 * @param string $name Имя поля
	 * @param string $value Новое значение поля.
	 * @param Creds* $creds Авторизация операции + идентификатор экземпляра.
	 */
	function setField($name, $value, $creds);

	/**
	 * Меняет нескопько полей.
	 * 
	 * @param array(Field) $aFields Массив полей Fields.
	 * @param *Creds $creds Авторизация операции + идентификатор экземпляра.
	 */
	function setFields($aFields, $creds);
}