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
use iMSCP_Exception as Exception;
use iMSCP_Exception_Database as DatabaseException;

/**
 * Class DatabaseServiceFactory
 * @package iMSCP\Service
 */
class DatabaseServiceFactory implements FactoryInterface
{
	/**
	 * Create database service
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 * @return Database
	 * @throws DatabaseException
	 * @throws Exception
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		try {
			$config = Registry::get('config');
			$imscpDbKeys = new ConfigFileHandler($config['CONF_DIR'] . '/imscp-db-keys');

			if (isset($imscpDbKeys['KEY']) && isset($imscpDbKeys['IV'])) {
				Registry::set('MCRYPT_KEY', $imscpDbKeys['KEY']); // FIXME: Not the right place
				Registry::set('MCRYPT_IV', $imscpDbKeys['IV']); // FIXME: Not the right place

				$database = Database::connect(
					$config['DATABASE_USER'],
					Crypt::decryptRijndaelCBC($imscpDbKeys['KEY'], $imscpDbKeys['IV'], $config['DATABASE_PASSWORD']),
					$config['DATABASE_TYPE'],
					$config['DATABASE_HOST'],
					$config['DATABASE_NAME']
				);

				if (!$database->execute('SET NAMES `utf8`')) {
					throw new Exception(sprintf(
						'Could not set charset for database communication. SQL returned: %s', $database->errorMsg()
					));
				}
			} else {
				throw new Exception('imscp-db-keys file is corrupted.');
			}
		} catch (\PDOException $e) {
			throw new DatabaseException(sprintf(
				'Could not establish connection to the database. SQL returned: %s' . $e->getMessage()
			));
		}

		return $database;
	}
}
