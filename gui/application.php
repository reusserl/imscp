<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

chdir(__DIR__);

// Default error reporting
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('track_errors', 1);

// Composer autoloading
include '/var/cache/imscp/packages/vendor/autoload.php';

// Include core functions
require_once 'module/Core/src/Functions/Admin.php';
require_once 'module/Core/src/Functions/Client.php';
require_once 'module/Core/src/Functions/Email.php';
require_once 'module/Core/src/Functions/Input.php';
require_once 'module/Core/src/Functions/Intl.php';
require_once 'module/Core/src/Functions/Layout.php';
require_once 'module/Core/src/Functions/Login.php';
require_once 'module/Core/src/Functions/Shared.php';
require_once 'module/Core/src/Functions/Reseller.php';
require_once 'module/Core/src/Functions/View.php';

// Bootstrap and run application
iMSCP\Core\Application::init(require 'config/application.config.php')->run();
