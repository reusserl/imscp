<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace iMSCP\DoctrineIntegration\Service;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use iMSCP\DoctrineIntegration\Service\DBALConfigurationFactory as DoctrineConfigurationFactory;
use Zend\ServiceManager\Exception\InvalidArgumentException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ConfigurationFactory
 * @package iMSCP\DoctrineIntegration\Service
 */
class ConfigurationFactory extends DoctrineConfigurationFactory
{
    /**
     * {@inheritdoc]
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var $options \iMSCP\DoctrineIntegration\Options\Configuration */
        $options = $this->getOptions($serviceLocator);
        $config = new Configuration();

        $config->setAutoGenerateProxyClasses($options->getGenerateProxies());
        $config->setProxyDir($options->getProxyDir());
        $config->setProxyNamespace($options->getProxyNamespace());
        $config->setEntityNamespaces($options->getEntityNamespaces());
        $config->setCustomDatetimeFunctions($options->getDatetimeFunctions());
        $config->setCustomStringFunctions($options->getStringFunctions());
        $config->setCustomNumericFunctions($options->getNumericFunctions());
        $config->setClassMetadataFactoryName($options->getClassMetadataFactoryName());

        if ($filterSchemaAssetNames = $options->getFilterSchemaAssetNames()) {
            $config->setFilterSchemaAssetsExpression('/^(?!(?:' . implode('|', $filterSchemaAssetNames) . ')$).*$/');
        }

        foreach ($options->getNamedQueries() as $name => $query) {
            $config->addNamedQuery($name, $query);
        }

        foreach ($options->getNamedNativeQueries() as $name => $query) {
            $config->addNamedNativeQuery($name, $query['sql'], new $query['rsm']);
        }

        foreach ($options->getCustomHydrationModes() as $modeName => $hydrator) {
            $config->addCustomHydrationMode($modeName, $hydrator);
        }

        foreach ($options->getFilters() as $name => $class) {
            $config->addFilter($name, $class);
        }

        /** @var $cache \Doctrine\Common\Cache\Cache */
        $cache = $serviceLocator->get($options->getMetadataCache());
        $config->setMetadataCacheImpl($cache);

        $cache = $serviceLocator->get($options->getQueryCache());
        $config->setQueryCacheImpl($cache);

        $cache = $serviceLocator->get($options->getResultCache());
        $config->setResultCacheImpl($cache);

        $cache = $serviceLocator->get($options->getHydrationCache());
        $config->setHydrationCacheImpl($cache);

        /** @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver $metadataDriver */
        $metadataDriver = $serviceLocator->get($options->getDriver());
        $config->setMetadataDriverImpl($metadataDriver);

        if ($namingStrategy = $options->getNamingStrategy()) {
            if (is_string($namingStrategy)) {
                if (!$serviceLocator->has($namingStrategy)) {
                    throw new InvalidArgumentException(sprintf('Naming strategy "%s" not found', $namingStrategy));
                }

                /** @var \Doctrine\ORM\Mapping\NamingStrategy $namingStrategy */
                $namingStrategy = $serviceLocator->get($namingStrategy);
                $config->setNamingStrategy($namingStrategy);
            } else {
                $config->setNamingStrategy($namingStrategy);
            }
        }

        if ($repositoryFactory = $options->getRepositoryFactory()) {
            if (is_string($repositoryFactory)) {
                if (!$serviceLocator->has($repositoryFactory)) {
                    throw new InvalidArgumentException(
                        sprintf('Repository factory "%s" not found', $repositoryFactory)
                    );
                }

                /** @var \Doctrine\ORM\Repository\RepositoryFactory $repositoryFactory */
                $repositoryFactory = $serviceLocator->get($repositoryFactory);
                $config->setRepositoryFactory($repositoryFactory);
            } else {
                $config->setRepositoryFactory($repositoryFactory);
            }
        }

        if ($entityListenerResolver = $options->getEntityListenerResolver()) {
            if ($entityListenerResolver instanceof EntityListenerResolver) {
                $config->setEntityListenerResolver($entityListenerResolver);
            } else {
                /** @var EntityListenerResolver $entityListenerResolver */
                $entityListenerResolver = $serviceLocator->get($entityListenerResolver);
                $config->setEntityListenerResolver($entityListenerResolver);
            }
        }

        $secondLevelCache = $options->getSecondLevelCache();

        if ($secondLevelCache->isEnabled()) {
            $regionsConfig = new RegionsConfiguration(
                $secondLevelCache->getDefaultLifetime(),
                $secondLevelCache->getDefaultLockLifetime()
            );

            foreach ($secondLevelCache->getRegions() as $regionName => $regionConfig) {
                if (isset($regionConfig['lifetime'])) {
                    $regionsConfig->setLifetime($regionName, $regionConfig['lifetime']);
                }

                if (isset($regionConfig['lock_lifetime'])) {
                    $regionsConfig->setLockLifetime($regionName, $regionConfig['lock_lifetime']);
                }
            }

            // As Second Level Cache caches queries results, we reuse the result cache impl
            $cacheFactory = new DefaultCacheFactory($regionsConfig, $config->getResultCacheImpl());
            $cacheFactory->setFileLockRegionDirectory($secondLevelCache->getFileLockRegionDirectory());

            $cacheConfiguration = new CacheConfiguration();
            $cacheConfiguration->setCacheFactory($cacheFactory);
            $cacheConfiguration->setRegionsConfiguration($regionsConfig);

            $config->setSecondLevelCacheEnabled();
            $config->setSecondLevelCacheConfiguration($cacheConfiguration);
        }

        if ($className = $options->getDefaultRepositoryClassName()) {
            $config->setDefaultRepositoryClassName($className);
        }

        $this->setupDBALConfiguration($serviceLocator, $config);
        return $config;
    }

    /**
     * {@inheritdoc]
     */
    protected function getOptionsClass()
    {
        return 'iMSCP\DoctrineIntegration\Options\Configuration';
    }
}
