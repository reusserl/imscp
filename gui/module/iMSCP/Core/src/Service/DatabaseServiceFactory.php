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

use iMSCP\Core\Database\Database;
use iMSCP\Core\Utils\Crypt;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class DatabaseServiceFactory
 * @package iMSCP\Core\Service
 */
class DatabaseServiceFactory implements FactoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		try {
			// We cannot use the Config service here (not available yet)
			/** @var ModuleManager $moduleManager */
			$moduleManager = $serviceLocator->get('ModuleManager');
			$config = $moduleManager->getEvent()->getConfigListener()->getMergedConfig();

			/** @var EncryptionDataService $encryptionDataService */
			$encryptionDataService = $serviceLocator->get('EncryptionDataService');

			$db = Database::connect(
				$config['DATABASE_USER'],
				Crypt::decryptRijndaelCBC(
					$encryptionDataService->getKey(), $encryptionDataService->getIV(), $config['DATABASE_PASSWORD']
				),
				$config['DATABASE_TYPE'],
				$config['DATABASE_HOST'],
				$config['DATABASE_PORT'],
				$config['DATABASE_NAME'],
				[\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
			);
		} catch (\PDOException $e) {
			// We need enforce int. See https://github.com/zendframework/zend-servicemanager/issues/41
			throw new \RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
		}

		return $db;
	}
}
