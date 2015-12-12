<?php

namespace iMSCP\DoctrineIntegration\Options;

use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Repository\RepositoryFactory;
use Zend\Stdlib\Exception\InvalidArgumentException;

/**
 * Configuration options for an ORM Configuration
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Kyle Spraggs <theman@spiffyjr.me>
 * @author  Marco Pivetta <ocramius@gmail.com>
 * @package iMSCP\DoctrineIntegration\Options
 */
class Configuration extends DBALConfiguration
{
    /**
     * Set the cache key for the metadata cache. Cache key
     * is assembled as "doctrine_integration.cache.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $metadataCache = 'array';

    /**
     * Set the cache key for the query cache. Cache key
     * is assembled as "doctrine_integration.cache.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $queryCache = 'array';

    /**
     * Set the cache key for the result cache. Cache key
     * is assembled as "doctrine_integration.cache.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $resultCache = 'array';

    /**
     * Set the cache key for the hydration cache. Cache key
     * is assembled as "doctrine_integration.cache.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $hydrationCache = 'array';

    /**
     * Set the driver key for the metadata driver. Driver key
     * is assembled as "doctrine_integration.driver.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $driver = 'default';

    /**
     * @var bool Automatic generation of proxies (disable for production!)
     */
    protected $generateProxies = true;

    /**
     * @var string Proxy directory
     */
    protected $proxyDir = 'data';

    /**
     * @var string Proxy namespace
     */
    protected $proxyNamespace = 'iMSCP\DoctrineIntegration\Proxy';

    /**
     * @var array Entity alias map
     */
    protected $entityNamespaces = [];

    /**
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * @var array
     */
    protected $datetimeFunctions = [];

    /**
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * @var array
     */
    protected $stringFunctions = [];

    /**
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * @var array
     */
    protected $numericFunctions = [];

    /**
     * Keys must be the name of the custom filter and the value must be
     * the class name for the custom filter.
     *
     * @var array
     */
    protected $filters = [];

    /**
     * @var array Keys must be the name of the query and values the DQL query string
     */
    protected $namedQueries = [];

    /**
     * Keys must be the name of the query and the value is an array containing
     * the keys 'sql' for native SQL query string and 'rsm' for the Query\ResultSetMapping.
     *
     * @var array
     */
    protected $namedNativeQueries = [];

    /**
     * Keys must be the name of the custom hydration method and the value must be
     * the class name for the custom hydrator
     *
     * @var array
     */
    protected $customHydrationModes = [];

    /**
     * Naming strategy or name of the naming strategy service to be set in ORM
     * configuration (if any)
     *
     * @var string|null|NamingStrategy
     */
    protected $namingStrategy;

    /**
     * @var string|null Default repository class
     */
    protected $defaultRepositoryClassName;

    /**
     * Repository factory or name of the repository factory service to be set in ORM
     * configuration (if any)
     *
     * @var string|null|RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * Class name of MetaData factory to be set in ORM.
     * The entityManager will create a new instance on construction.
     *
     * @var string
     */
    protected $classMetadataFactoryName;

    /**
     * Entity listener resolver or service name of the entity listener resolver
     * to be set in ORM configuration (if any)
     *
     * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html
     * @var string|null|EntityListenerResolver
     */
    protected $entityListenerResolver;

    /**
     * Configuration for second level cache
     *
     * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/second-level-cache.html
     * @var SecondLevelCacheConfiguration|null
     */
    protected $secondLevelCache;

    /**
     * @var array List of tables which must be ignored by the ORM
     */
    protected $filterSchemaAssetNames;

    /**
     * Set datetime functions
     *
     * @param  array $datetimeFunctions
     * @return self
     */
    public function setDatetimeFunctions($datetimeFunctions)
    {
        $this->datetimeFunctions = $datetimeFunctions;
        return $this;
    }

    /**
     * Get datetime functions
     *
     * @return array
     */
    public function getDatetimeFunctions()
    {
        return $this->datetimeFunctions;
    }

    /**
     * Set driver
     *
     * @param string $driver
     * @return self
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get driver
     *
     * @return string
     */
    public function getDriver()
    {
        return "doctrine_integration.driver.{$this->driver}";
    }

    /**
     * Set entity namespaces
     *
     * @param array $entityNamespaces
     * @return self
     */
    public function setEntityNamespaces($entityNamespaces)
    {
        $this->entityNamespaces = $entityNamespaces;
        return $this;
    }

    /**
     * Get entity namespaces
     *
     * @return array
     */
    public function getEntityNamespaces()
    {
        return $this->entityNamespaces;
    }

    /**
     * Set generate proxies
     *
     * @param boolean $generateProxies
     * @return self
     */
    public function setGenerateProxies($generateProxies)
    {
        $this->generateProxies = $generateProxies;
        return $this;
    }

    /**
     * Get generate proxies
     *
     * @return boolean
     */
    public function getGenerateProxies()
    {
        return $this->generateProxies;
    }

    /**
     * Set metadata cache
     *
     * @param string $metadataCache
     * @return self
     */
    public function setMetadataCache($metadataCache)
    {
        $this->metadataCache = $metadataCache;
        return $this;
    }

    /**
     * Get metatada cache
     *
     * @return string
     */
    public function getMetadataCache()
    {
        return "doctrine_integration.cache.{$this->metadataCache}";
    }

    /**
     * {@inheritdoc}
     * @return self
     */
    public function setResultCache($resultCache)
    {
        $this->resultCache = $resultCache;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @return self
     */
    public function getResultCache()
    {
        return "doctrine_integration.cache.{$this->resultCache}";
    }

    /**
     * Set hydration cache
     *
     * @param string $hydrationCache
     * @return self
     */
    public function setHydrationCache($hydrationCache)
    {
        $this->hydrationCache = $hydrationCache;
        return $this;
    }

    /**
     * Get hydration cache
     *
     * @return string
     */
    public function getHydrationCache()
    {
        return "doctrine_integration.cache.{$this->hydrationCache}";
    }

    /**
     * Set named native queries
     *
     * @param  array $namedNativeQueries
     * @return self
     */
    public function setNamedNativeQueries($namedNativeQueries)
    {
        $this->namedNativeQueries = $namedNativeQueries;
        return $this;
    }

    /**
     * Get named native queries
     *
     * @return array
     */
    public function getNamedNativeQueries()
    {
        return $this->namedNativeQueries;
    }

    /**
     * Set named queries
     *
     * @param  array $namedQueries
     * @return self
     */
    public function setNamedQueries($namedQueries)
    {
        $this->namedQueries = $namedQueries;
        return $this;
    }

    /**
     * Get named queries
     *
     * @return array
     */
    public function getNamedQueries()
    {
        return $this->namedQueries;
    }

    /**
     * Set numeric functions
     *
     * @param  array $numericFunctions
     * @return self
     */
    public function setNumericFunctions($numericFunctions)
    {
        $this->numericFunctions = $numericFunctions;
        return $this;
    }

    /**
     * Get numeric functions
     *
     * @return array
     */
    public function getNumericFunctions()
    {
        return $this->numericFunctions;
    }

    /**
     * Set filters
     *
     * @param array $filters
     * @return self
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set proxy directory
     *
     * @param  string $proxyDir
     * @return self
     */
    public function setProxyDir($proxyDir)
    {
        $this->proxyDir = $proxyDir;
        return $this;
    }

    /**
     * Get proxy directory
     *
     * @return string
     */
    public function getProxyDir()
    {
        return $this->proxyDir;
    }

    /**
     * Set proxy namespace
     *
     * @param  string $proxyNamespace
     * @return self
     */
    public function setProxyNamespace($proxyNamespace)
    {
        $this->proxyNamespace = $proxyNamespace;

        return $this;
    }

    /**
     * Get proxy namespace
     *
     * @return string
     */
    public function getProxyNamespace()
    {
        return $this->proxyNamespace;
    }

    /**
     * Set query cache
     *
     * @param string $queryCache
     * @return self
     */
    public function setQueryCache($queryCache)
    {
        $this->queryCache = $queryCache;

        return $this;
    }

    /**
     * Get query cache
     *
     * @return string
     */
    public function getQueryCache()
    {
        return "doctrine_integration.cache.{$this->queryCache}";
    }

    /**
     * Set string functions
     *
     * @param  array $stringFunctions
     * @return self
     */
    public function setStringFunctions($stringFunctions)
    {
        $this->stringFunctions = $stringFunctions;

        return $this;
    }

    /**
     * Get string functions
     *
     * @return array
     */
    public function getStringFunctions()
    {
        return $this->stringFunctions;
    }

    /**
     * Set custom hydration modes
     *
     * @param  array $modes
     * @return self
     */
    public function setCustomHydrationModes($modes)
    {
        $this->customHydrationModes = $modes;

        return $this;
    }

    /**
     * Get custom hydration modes
     *
     * @return array
     */
    public function getCustomHydrationModes()
    {
        return $this->customHydrationModes;
    }

    /**
     * Set naming strategy
     *
     * @param  string|null|NamingStrategy $namingStrategy
     * @return self
     * @throws InvalidArgumentException   when the provided naming strategy does not fit the expected type
     */
    public function setNamingStrategy($namingStrategy)
    {
        if (null === $namingStrategy || is_string($namingStrategy) || $namingStrategy instanceof NamingStrategy) {
            $this->namingStrategy = $namingStrategy;
            return $this;
        }

        throw new InvalidArgumentException(
            sprintf(
                'namingStrategy must be either a string, a Doctrine\ORM\Mapping\NamingStrategy '
                . 'instance or null, %s given',
                is_object($namingStrategy) ? get_class($namingStrategy) : gettype($namingStrategy)
            )
        );
    }

    /**
     * Get naming strategy
     *
     * @return string|null|NamingStrategy
     */
    public function getNamingStrategy()
    {
        return $this->namingStrategy;
    }

    /**
     * Set repository factory
     *
     * @param  string|null|RepositoryFactory $repositoryFactory
     * @return self
     * @throws InvalidArgumentException   when the provided repository factory does not fit the expected type
     */
    public function setRepositoryFactory($repositoryFactory)
    {
        if (
            null === $repositoryFactory
            || is_string($repositoryFactory)
            || $repositoryFactory instanceof RepositoryFactory
        ) {
            $this->repositoryFactory = $repositoryFactory;

            return $this;
        }

        throw new InvalidArgumentException(
            sprintf(
                'repositoryFactory must be either a string, a Doctrine\ORM\Repository\RepositoryFactory '
                . 'instance or null, %s given',
                is_object($repositoryFactory) ? get_class($repositoryFactory) : gettype($repositoryFactory)
            )
        );
    }

    /**
     * Get repository factory
     *
     * @return string|null|RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->repositoryFactory;
    }

    /**
     * Set the metadata factory class name to use
     *
     * @see \Doctrine\ORM\Configuration::setClassMetadataFactoryName()
     * @param string $factoryName
     */
    public function setClassMetadataFactoryName($factoryName)
    {
        $this->classMetadataFactoryName = (string)$factoryName;
    }

    /**
     * Get class metadata factory name
     *
     * @return string
     */
    public function getClassMetadataFactoryName()
    {
        return $this->classMetadataFactoryName;
    }

    /**
     * Set entity listener resolver
     *
     * @param  string|null|EntityListenerResolver $entityListenerResolver
     * @return self
     * @throws InvalidArgumentException           When the provided entity listener resolver
     *                                            does not fit the expected type
     */
    public function setEntityListenerResolver($entityListenerResolver)
    {
        if (null === $entityListenerResolver
            || $entityListenerResolver instanceof EntityListenerResolver
            || is_string($entityListenerResolver)
        ) {
            $this->entityListenerResolver = $entityListenerResolver;

            return $this;
        }

        throw new InvalidArgumentException(sprintf(
            'entityListenerResolver must be either a string, a Doctrine\ORM\Mapping\EntityListenerResolver '
            . 'instance or null, %s given',
            is_object($entityListenerResolver) ? get_class($entityListenerResolver) : gettype($entityListenerResolver)
        ));
    }

    /**
     * Get entity listener resolver
     *
     * @return string|null|EntityListenerResolver
     */
    public function getEntityListenerResolver()
    {
        return $this->entityListenerResolver;
    }

    /**
     * Set second level cache
     *
     * @param  array $secondLevelCache
     * @return void
     */
    public function setSecondLevelCache(array $secondLevelCache)
    {
        $this->secondLevelCache = new SecondLevelCacheConfiguration($secondLevelCache);
    }

    /**
     * Get second level cache
     *
     * @return SecondLevelCacheConfiguration
     */
    public function getSecondLevelCache()
    {
        return $this->secondLevelCache ?: new SecondLevelCacheConfiguration();
    }

    /**
     * Sets default repository class
     *
     * @param  string $className
     * @return void
     */
    public function setDefaultRepositoryClassName($className)
    {
        $this->defaultRepositoryClassName = (string)$className;
    }

    /**
     * Get default repository class name
     *
     * @return string|null
     */
    public function getDefaultRepositoryClassName()
    {
        return $this->defaultRepositoryClassName;
    }

    /**
     * Set filter schema asset names
     *
     * @param array $assetNames
     * @return array
     */
    public function setFilterSchemaAssetNames(array $assetNames)
    {
        $this->filterSchemaAssetNames = $assetNames;
        return $this;
    }

    /**
     * Get filter schema asset names
     *
     * @return array
     */
    public function getFilterSchemaAssetNames()
    {
        return $this->filterSchemaAssetNames;
    }
}
