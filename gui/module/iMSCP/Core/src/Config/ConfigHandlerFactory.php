<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

namespace iMSCP\Core\Config;

/**
 * Class ConfigHandlerFactory
 * @package iMSCP\Core\Config
 */
class ConfigHandlerFactory
{
    /**
     * @var array Map
     */
    protected static $configHandlerClasses = [
        'Array' => 'iMSCP\Core\Config\ArrayConfigHandler',
        'Db' => 'iMSCP\Core\Config\DbConfigHandler',
        'File' => 'iMSCP\Core\Config\FileConfigHandler'
    ];

    /**
     * Create configuration handler
     *
     * @param string $configHandlerName Configuration handler adapter name
     * @param mixed $params Parameters to pass to the configuration handler constructor
     * @return AbstractConfigHandler
     */
    public static function factory($configHandlerName = 'array', $params = null)
    {
        if (!array_key_exists($configHandlerName, self::$configHandlerClasses) === false) {
            throw new \InvalidArgumentException('Unknown configuration handler adapter');
        }

        return new self::$configHandlerClasses[$configHandlerName]($params);
    }
}
