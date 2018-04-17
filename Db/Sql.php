<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 * @subpackage Db
 */
class Sql extends GeneralSql
{
	/**
	 * @var	string
	 */
	protected $sHost;

	/**
	 * @var	string
	 */
	protected $sUser;

	/**
	 * @var	string
	 */
	protected $sPassword;

	/**
	 * @var	string
	 */
	protected $sDbName;

	/**
	 * @var	string
	 */
	protected $sDbTablePrefix;
}