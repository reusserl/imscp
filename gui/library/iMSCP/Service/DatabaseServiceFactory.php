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

namespace iMSCP\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use iMSCP_Config_Handler_File as ConfigFileHandler;
use iMSCP_Registry as Registry;
use iMSCP_Database as Database;
use iMSCP\Crypt;

/**
 * Class DatabaseServiceFactory
 * @package iMSCP\Service
 */
class DatabaseServiceFactory implements FactoryInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		try {
			/** @var EncryptionDataService $encryptionDataService */
			$encryptionDataService = $serviceLocator->get('EncryptionDataService');
			$config = Registry::get('config');
			$db = Database::connect(
				$config['DATABASE_USER'],
				Crypt::decryptRijndaelCBC(
					$encryptionDataService->getKey(), $encryptionDataService->getIV(),
					$config['DATABASE_PASSWORD']
				),
				$config['DATABASE_TYPE'],
				$config['DATABASE_HOST'],
				$config['DATABASE_PORT'],
				$config['DATABASE_NAME'],
				array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES `UTF8`')
			);
		} catch (\PDOException $e) {
			// We need enforce int. See https://github.com/zendframework/zend-servicemanager/issues/41
			throw new \RuntimeException($e->getMessage(), (int)$e->getCode(), $e);
		}

		return $db;
	}
}
