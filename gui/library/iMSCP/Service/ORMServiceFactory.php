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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use iMSCP_Registry as Registry;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ORMServiceFactory
 * @package iMSCP\Service
 */
class ORMServiceFactory implements FactoryInterface
{
	const ARRAY_CACHE_DRIVER_CLASS = 'Doctrine\\Common\\Cache\\ArrayCache';
	const APC_CACHE_DRIVER_CLASS = 'Doctrine\\Common\\Cache\\ApcCache';
	const XCACHE_CACHE_DRIVER_CLASS = 'Doctrine\\Common\\Cache\\XcacheCache';

	/**
	 * @var string
	 */
	protected $cacheDriverClass;

	/**
	 * {@inheritdoc}
	 */
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		// Get main configuration object
		$mainConfig = Registry::get('config');

		// Set devmode mode flag
		$devmode = (bool)$mainConfig['DEVMODE'];

		// Create new ORM configuration object
		$ORMConfig = new Configuration();

		// Get common cache object
		$cacheImpl = $this->getCacheDriverInstance($devmode, 'imscp_');

		// Setup metadata driver
		/** @var AnnotationReader $annotationReader */
		$annotationReader = new CachedReader(new AnnotationReader(), $cacheImpl);
		$annotationDriver = new AnnotationDriver($annotationReader, [
			LIBRARY_PATH . '/iMSCP/Entity',
			LIBRARY_PATH . '/iMSCP/ApsStandard/Entity'
		]);
		$ORMConfig->setMetadataDriverImpl($annotationDriver);

		// Setup proxy configuration
		$ORMConfig->setProxyDir(LIBRARY_PATH . '/iMSCP/proxies');
		$ORMConfig->setProxyNamespace('iMSCP\\Proxies');
		$ORMConfig->setAutoGenerateProxyClasses($devmode);

		// Setup entity namespaces
		$ORMConfig->setEntityNamespaces([
			'Core' => 'iMSCP\\Entity',
			'Aps' => 'iMSCP\\ApsStandard\\Entity'
		]);

		// Ignore tables which are not managed through ORM service
		$ORMConfig->setFilterSchemaAssetsExpression('/^(?:admin|aps_.*)$/');

		// Setup caches
		$ORMConfig->setHydrationCacheImpl($cacheImpl);
		$ORMConfig->setMetadataCacheImpl($cacheImpl);
		$ORMConfig->setQueryCacheImpl($cacheImpl);
		$ORMConfig->setResultCacheImpl($cacheImpl);

		// Setup second-level cache
		$cacheImpl = $this->getCacheDriverInstance($devmode, 'imscp_sec');
		$cacheFactory = new DefaultCacheFactory(new RegionsConfiguration(), $cacheImpl);
		//$cacheFactory->setFileLockRegionDirectory(CACHE_PATH . '/locks'); // Only needed for READ_WRITE mode
		$ORMConfig->setSecondLevelCacheEnabled(true);
		$ORMConfig->getSecondLevelCacheConfiguration()->setCacheFactory($cacheFactory);

		// Setup entity manager
		/** @var \PDO $pdo */
		$pdo = $serviceLocator->get('Database')->getRawInstance();
		$pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Doctrine\\DBAL\\Driver\\PDOStatement', []]);
		$entityManager = EntityManager::create(
			[
				'pdo' => $pdo, // Reuse PDO instance from Database service
				'host' => $mainConfig['DATABASE_HOST'], // Only there for later referral through connection object
				'port' => $mainConfig['DATABASE_PORT'] // Only there for later referral through connection object
			],
			$ORMConfig
		);

		return $entityManager;
	}

	/**
	 * Return new doctrine cache instance according current environment
	 *
	 * @param bool $devmode
	 * @param string $namespace
	 * @return CacheProvider
	 */
	protected function getCacheDriverInstance($devmode, $namespace)
	{
		if (null === $this->cacheDriverClass) {
			if (!$devmode && extension_loaded('apc')) {
				$this->cacheDriverClass = self::APC_CACHE_DRIVER_CLASS;
			} elseif (!$devmode && extension_loaded('xcache')) {
				$this->cacheDriverClass = self::XCACHE_CACHE_DRIVER_CLASS;
			} else {
				$this->cacheDriverClass = self::ARRAY_CACHE_DRIVER_CLASS;
			}
		}

		/** @var CacheProvider $cache */
		$cache = new $this->cacheDriverClass();
		$cache->setNamespace($namespace);

		return $cache;
	}
}
