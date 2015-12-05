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

namespace iMSCP\DoctrineIntegration\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ORMServiceFactory
 * @package iMSCP\Core\Service
 */
class ORMFactory implements FactoryInterface
{
    const ARRAY_CACHE_DRIVER_CLASS = 'Doctrine\Common\Cache\ArrayCache';
    const APC_CACHE_DRIVER_CLASS = 'Doctrine\Common\Cache\ApcCache';
    const XCACHE_CACHE_DRIVER_CLASS = 'Doctrine\Common\Cache\XcacheCache';

    /**
     * @var string
     */
    protected $cacheDriverClass;

    /**
     * {@inheritdoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var  \Doctrine\DBAL\Connection $connection */
        $connection = $serviceLocator->get('DBALConnection');

        $config = $serviceLocator->get('Config');

        // Set devmode mode flag
        $devmode = (bool)!$config['DEVMODE'];

        // Create new ORM configuration object
        /** @var Configuration $ORMConfig */
        $ORMConfig = $connection->getConfiguration();

        // Get common cache object
        $cacheImpl = $this->getCacheDriverInstance($devmode, 'imscp_');

        // Setup metadata driver
        //$driver = new MappingDriverChain()

        /** @var AnnotationReader $annotationReader */
        $annotationReader = new CachedReader(new AnnotationReader(), $cacheImpl);
        $annotationDriver = new AnnotationDriver($annotationReader, [
            __dir__ . '/../Entity',
            './module/iMSCP/ApsStandard/src/Entity'
        ]);
        $ORMConfig->setMetadataDriverImpl($annotationDriver);

        // Setup proxy configuration
        $ORMConfig->setProxyDir('data/doctrine/proxies');
        $ORMConfig->setProxyNamespace('iMSCP\Proxies');
        $ORMConfig->setAutoGenerateProxyClasses($devmode);

        // Setup entity namespaces
        $ORMConfig->setEntityNamespaces([
            'Core' => 'iMSCP\Core\Entity',
            'Aps' => 'iMSCP\ApsStandard\Entity'
        ]);

        // Setup caches
        $ORMConfig->setHydrationCacheImpl($cacheImpl);
        $ORMConfig->setMetadataCacheImpl($cacheImpl);
        $ORMConfig->setQueryCacheImpl($cacheImpl);
        $ORMConfig->setResultCacheImpl($cacheImpl);

        // Setup second-level cache
        $cacheImpl = $this->getCacheDriverInstance($devmode, 'imscp_sec');
        $cacheFactory = new DefaultCacheFactory(new RegionsConfiguration(), $cacheImpl);
        //$cacheFactory->setFileLockRegionDirectory('data/cache/locks'); // Only needed for READ_WRITE mode
        $ORMConfig->setSecondLevelCacheEnabled(true);
        $ORMConfig->getSecondLevelCacheConfiguration()->setCacheFactory($cacheFactory);

        return EntityManager::create($connection, $ORMConfig);
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
