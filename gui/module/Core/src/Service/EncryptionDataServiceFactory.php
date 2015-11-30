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

namespace iMSCP\Core\Service;

use iMSCP\Core\Config\FileConfigHandler;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class EncryptionDataService
 * @package iMSCP\Core\Service
 */
class EncryptionDataService implements FactoryInterface
{
	/**
	 * @var string Encryption key
	 */
	protected $key;

	/**
	 * @var string Initialization vector
	 */
	protected $iv;

	/**
	 * Get encryption key
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Return initialization vector
	 *
	 * @return string
	 */
	public function getIv()
	{
		return $this->iv;
	}

	/**
	 * Create service
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 * @return mixed
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		$systemConfig = $serviceLocator->get('SystemConfig');
		$config = new FileConfigHandler($systemConfig['CONF_DIR'] . '/imscp-db-keys');

		if (!isset($config['KEY']) || !isset($config['IV'])) {
			throw new \RuntimeException('Encryption data file (imscp-db-keys) is corrupted.');
		}

		$this->key = $systemConfig['KEY'];
		$this->iv = $systemConfig['IV'];

		return $this;
	}
}
