<?php
/*
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */

require_once __DIR__ . '/../autoload.php';

\Aurora\System\Api::Init();

use Illuminate\Support\Str;

if (Str::endsWith($_SERVER['REQUEST_URI'], basename($_SERVER['SCRIPT_FILENAME']))) {
    \header('Location: ' . $_SERVER['REQUEST_URI'] . '/');
    exit;
}

\Afterlogic\DAV\Server::getInstance()->exec();
