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
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ORMServiceFactory
 * @package iMSCP\Service
 */
class ORMServiceFactory implements FactoryInterface
{
	/**
	 * Create service
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 * @return mixed
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		/** @var \iMSCP_Database $databaseService */
		$databaseService = $serviceLocator->get('Database');
		$serviceLocator->get('Annotation'); // Fixme: We should get annotation reader
		$devmode = (bool)Registry::get('config')->DEVMODE;
		$config = Setup::createAnnotationMetadataConfiguration( // TODO make the path list configurable
			array(
				LIBRARY_PATH . '/iMSCP/Entity',
				LIBRARY_PATH . '/iMSCP/ApsStandard/Entity'
			),
			$devmode,
			CACHE_PATH . '/orm_proxy', // Proxy classes directory
			null, // Will use best available caching driver (none if devmode)
			false // Do not use simple annotation driver which is not compatible with auto-generated entities
		);

		//$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Doctrine\DBAL\Driver\PDOStatement', array()));
		$entityManager = EntityManager::create(
			array(
				'driver' => 'pdo_mysql',
				'pdo' => $databaseService::getRawInstance() // Reuse PDO instance that has been created by i-MSCP
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
			'/^admin|(?:aps_(?:package|instance))$/'
		);

		return $entityManager;
	}
}
