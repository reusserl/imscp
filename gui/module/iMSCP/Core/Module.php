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

namespace iMSCP\Core;

use iMSCP\Core\Config\FileConfigHandler;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

/**
 * Class Module
 * @package iMSCP\Core
 */
class Module implements ConfigProviderInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getConfig()
	{
		$moduleConfig = include __DIR__ . '/config/module.config.php';

		if (!($configFilePath = getenv('IMSCP_CONF'))) {
			switch (PHP_OS) {
				case 'FreeBSD':
				case 'OpenBSD':
				case 'NetBSD':
					$configFilePath = '/usr/local/etc/imscp/imscp.conf';
					break;
				default:
					$configFilePath = '/etc/imscp/imscp.conf';
			}
		}

		$config = array_merge($moduleConfig, (new FileConfigHandler($configFilePath))->toArray());

		// Convert IDN to ASCII
		$config['DEFAULT_ADMIN_ADDRESS'] = encode_idna($config['DEFAULT_ADMIN_ADDRESS']);
		$config['SERVER_HOSTNAME'] = encode_idna($config['SERVER_HOSTNAME']);
		$config['BASE_SERVER_VHOST'] = encode_idna($config['BASE_SERVER_VHOST']);
		$config['DATABASE_HOST'] = encode_idna($config['DATABASE_HOST']);

		// Add runtime configuration parameters
		$config['ROOT_TEMPLATE_PATH'] = realpath(dirname(__DIR__) . '/../../themes/' . $config['USER_INITIAL_THEME']);
		if ($config['DEVMODE']) {
			$config['ASSETS_PATH'] = '/assets';
		} else {
			$config['ROOT_TEMPLATE_PATH'] .= '/dist';
			$config['ASSETS_PATH'] = '/dist/assets';
		}

		return $config;
	}
}
