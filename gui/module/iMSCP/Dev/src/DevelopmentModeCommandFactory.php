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

namespace iMSCP\Dev;

use iMSCP\Core\Config\ConfigHandlerFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class DevelopmentModeCommandFactory
 * @package iMSCP\Dev
 */
class DevelopmentModeCommandFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $configCacheDir = null;
        $configCacheKey = null;
        $phpIniPath = null;

        if ($serviceLocator->has('ApplicationConfig')) {
            $config = $serviceLocator->get('ApplicationConfig');

            if (isset($config['module_listener_options']['cache_dir'])
                && !empty($config['module_listener_options']['cache_dir'])
            ) {
                $configCacheDir = $config['module_listener_options']['cache_dir'];
            }

            if (isset($config['module_listener_options']['config_cache_key'])
                && !empty($config['module_listener_options']['config_cache_key'])
            ) {
                $configCacheKey = $config['module_listener_options']['config_cache_key'];
            }
        }

        if ($serviceLocator->has('Config')) {
            $config = $serviceLocator->get('Config');
            $phpIniPath = $this->getPhpIniFilePath($config);
        }

        return new DevelopmentModeCommand($configCacheDir, $configCacheKey, $phpIniPath);
    }

    /**
     * Get PHP ini file path
     *
     * @param array $config
     * @return null|string
     */
    protected function getPhpIniFilePath($config)
    {
        if (isset($config['CONF_DIR']) && file_exists($config['CONF_DIR'] . '/nginx/nginx.data')) {
            $nginxConfig = ConfigHandlerFactory::factory('file', $config['CONF_DIR'] . '/nginx/nginx.data');

            if (isset($nginxConfig['PHP_STARTER_DIR'])
                && file_exists($nginxConfig['PHP_STARTER_DIR'] . '/master/php5/php.ini')
            ) {
                return $nginxConfig['PHP_STARTER_DIR'] . '/master/php5/php.ini';
            }
        }

        return null;
    }
}
