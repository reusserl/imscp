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

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use iMSCP_Registry as Registry;

/**
 * Class ORMServiceFactory
 * @package iMSCP\Service
 */
class ORMServiceFactory
{
	/**
	 * @var EntityManager
	 */
	static $entityManager;

	/**
	 * Create doctrine entity manager service
	 *
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \iMSCP_Exception
	 * @param \PDO $pdo
	 * @return EntityManager
	 */
	public static function create(\PDO $pdo)
	{
		if (static::$entityManager === null) {
			$devmode = (bool)Registry::get('config')->DEVMODE;

			// TODO make the paths list configurable
			$config = Setup::createAnnotationMetadataConfiguration(
				array(LIBRARY_PATH . '/iMSCP/ApsStandard/Entity'), // Entity directory
				$devmode,
				CACHE_PATH . '/orm_proxy', // Proxy classes directory
				null, // Will use best available caching driver (none if devmode)
				false // Do not use simple annotation driver which is not compatible with auto-generated entities
			);

			//$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Doctrine\DBAL\Driver\PDOStatement', array()));
			static::$entityManager = $entityManager = EntityManager::create(
				array(
					'driver' => 'pdo_mysql',
					'pdo' => $pdo // Reuse PDO instance that has been created by i-MSCP
				),
				$config
			);

			//$config->getMetadataDriverImpl()->addPaths(array(LIBRARY_PATH . '/iMSCP/ApsStandard/Entity'));
			// Map MySQL ENUM type to varchar (Not needed ATM)
			//$connection = $entityManager->getConnection();
			//$platform = $connection->getDatabasePlatform();
			//$platform->registerDoctrineTypeMapping('enum', 'string');

			// Right now, we use Doctrine for APS Standard feature only. Thus, we ignore most of tables
			$entityManager->getConnection()->getConfiguration()->setFilterSchemaAssetsExpression(
				'/^admin|(?:aps_(?:packages|instances))$/'
			);

			// Add namespace for acccessing APS Standard entities
			// FIXME: Not the right place do do that !
			$config->addEntityNamespace('ApsStandard', '\\iMSCP\\ApsStandard\Entity\\');
		}

		return static::$entityManager;
	}
}
