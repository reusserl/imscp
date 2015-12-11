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

return [
    'doctrine_integration' => [
        'connection' => [
            // Configuration for service `doctrine_integration.connection.default` service
            'default' => [
                // Configuration instance to use. The retrieved service name will
                // be `doctrine_integration.configuration.$thisSetting`
                'configuration' => 'default',

                // Event manager instance to use. The retrieved service name will
                // be `doctrine_integration.eventmanager.$thisSetting`
                'eventmanager' => 'default',

                // Connection parameters, see
                // http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html
                // Note: For i-MSCP, connection parameters are set at runtime (they are pulled from external conffile)
                // Defining any parameter here (or in another module) would override the runtime parameters.
                'params' => []
            ]
        ],

        // Configuration details for the module.
        // See http://docs.doctrine-project.org/en/latest/reference/configuration.html
        'configuration' => [
            // Configuration for service `doctrine_integration.configuration.default` service
            'default' => [
                // metadata cache instance to use. The retrieved service name will
                // be `doctrine_integration.cache.$thisSetting`
                'metadata_cache' => 'array',

                // DQL queries parsing cache instance to use. The retrieved service
                // name will be `doctrine_integration.cache.$thisSetting`
                'query_cache' => 'array',

                // ResultSet cache to use. The retrieved service name will be
                // `doctrine_integration.cache.$thisSetting`
                'result_cache' => 'array',

                // Hydration cache to use. The retrieved service name will be
                // `doctrine_integration.cache.$thisSetting`
                'hydration_cache' => 'array',

                // Mapping driver instance to use. Change this only if you don't want
                // to use the default chained driver. The retrieved service name will
                // be `doctrine_integration.driver.$thisSetting`
                'driver' => 'default',

                // Generate proxies automatically (turn off for production)
                'generate_proxies' => true,

                // Directory where proxies will be stored. By default, this is in
                // the `data` directory of your application
                'proxy_dir' => 'data/DoctrineIntegrationModule/Proxy',

                // Namespace for generated proxy classes
                'proxy_namespace' => 'iMSCP\DoctrineIntegrationModule\Proxy',

                // Entity namespaces
                'entity_namespaces' => [],

                // SQL filters. See http://docs.doctrine-project.org/en/latest/reference/filters.html
                'filters' => [],

                // Custom DQL functions.
                // You can grab common MySQL ones at https://github.com/beberlei/DoctrineExtensions
                // Further docs at http://docs.doctrine-project.org/en/latest/cookbook/dql-user-defined-functions.html
                'datetime_functions' => [],
                'string_functions' => [],
                'numeric_functions' => [],

                // Second level cache configuration (see doc to learn about configuration)
                'second_level_cache' => []
            ]
        ],

        // Cache
        'cache' => [
            'apc' => [
                'class' => 'Doctrine\Common\Cache\ApcCache',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'array' => [
                'class' => 'Doctrine\Common\Cache\ArrayCache',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'filesystem' => [
                'class' => 'Doctrine\Common\Cache\FilesystemCache',
                'directory' => 'data/cache/DoctrineIntegration/cache',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'memcache' => [
                'class' => 'Doctrine\Common\Cache\MemcacheCache',
                'instance' => 'my_memcache_alias',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'memcached' => [
                'class' => 'Doctrine\Common\Cache\MemcachedCache',
                'instance' => 'my_memcached_alias',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'predis' => [
                'class' => 'Doctrine\Common\Cache\PredisCache',
                'instance' => 'my_predis_alias',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'redis' => [
                'class' => 'Doctrine\Common\Cache\RedisCache',
                'instance' => 'my_redis_alias',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'wincache' => [
                'class' => 'Doctrine\Common\Cache\WinCacheCache',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'xcache' => [
                'class' => 'Doctrine\Common\Cache\XcacheCache',
                'namespace' => 'iMSCP\DoctrineIntegration',
            ],
            'zenddata' => [
                'class' => 'Doctrine\Common\Cache\ZendDataCache',
                'namespace' => 'iMSCP\DoctrineIntegration'
            ]
        ],

        // Metadata Mapping driver configuration
        'driver' => [
            // Configuration for service `doctrine_integration.driver.default` service
            'default' => [
                // By default, the module uses a driver chain. This allows
                // multiple modules to define their own entities
                'class' => 'Doctrine\ORM\Mapping\Driver\DriverChain',

                // Map of driver names to be used within this driver chain,
                // indexed by entity namespace
                'drivers' => []
            ]
        ],

        // Entity Manager instantiation settings
        'entitymanager' => [
            // Configuration for the `doctrine_integration.entitymanager.default` service
            'default' => [
                // Connection instance to use. The retrieved service name will
                // be `doctrine_integration.connection.$thisSetting`
                'connection' => 'default',

                // Configuration instance to use. The retrieved service name
                // will be `doctrine_integration.configuration.$thisSetting`
                'configuration' => 'default'
            ]
        ],

        // entity resolver configuration, allows mapping associations to interfaces
        'entity_resolver' => [
            // configuration for the `doctrine.entity_resolver.default` service
            'default' => []
        ],

        'eventmanager' => [
            // Configuration for the `doctrine_integration.eventmanager.default` service
            'default' => []
        ],

        // Authentication service configuration
        'authentication' => [
            // Configuration for the `doctrine_integration.authentication.default`
            // authentication service
            'default' => [
                // name of the object manager to use. By default, the EntityManager is used
                'objectManager' => 'doctrine_integration.entitymanager.imscp',
                //'identity_class' => 'Application\Model\User',
                //'identity_property' => 'adminName',
                //'credential_property' => 'adminPass'
            ],
        ],
        'authenticationadapter' => [
            'default' => true
        ],
        'authenticationstorage' => [
            'default' => true
        ],
        'authenticationservice' => [
            'default' => true
        ]
    ],

    // Factory mappings
    'doctrine_integration_factories' => [
        'authenticationadapter' => 'iMSCP\DoctrineIntegration\Service\Authentication\AdapterFactory',
        'authenticationstorage' => 'iMSCP\DoctrineIntegration\Service\Authentication\StorageFactory',
        'authenticationservice' => 'iMSCP\DoctrineIntegration\Service\Authentication\AuthenticationServiceFactory',
        'cache' => 'iMSCP\DoctrineIntegration\Service\CacheFactory',
        'configuration' => 'iMSCP\DoctrineIntegration\Service\ConfigurationFactory',
        'connection' => 'iMSCP\DoctrineIntegration\Service\DBALConnectionFactory',
        'driver' => 'iMSCP\DoctrineIntegration\Service\DriverFactory',
        'entitymanager' => 'iMSCP\DoctrineIntegration\Service\EntityManagerFactory',
        'eventmanager' => 'iMSCP\DoctrineIntegration\Service\EventManagerFactory',
        'entity_resolver' => 'iMSCP\DoctrineIntegration\Service\EntityResolverFactory'
    ],

    'service_manager' => [
        'abstract_factories' => [
            'DoctrineIntegration' => 'iMSCP\DoctrineIntegration\Service\AbstractServiceFactory',
        ],
        'factories' => [
            'doctrine' => 'iMSCP\DoctrineIntegration\Service\ManagerRegistryFactory',
        ],
        'invokables' => [
            'iMSCP\DoctrineIntegration\Authentication\Storage\Session' => 'Zend\Authentication\Storage\Session',

            // DBAL commands
            'doctrine_integration.dbal_cmd.runsql' => '\Doctrine\DBAL\Tools\Console\Command\RunSqlCommand',
            'doctrine_integration.dbal_cmd.import' => '\Doctrine\DBAL\Tools\Console\Command\ImportCommand',

            // ORM Commands
            'doctrine_integration.clear_cache_metadata' => '\Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand',
            'doctrine_integration.clear_cache_result' => '\Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand',
            'doctrine_integration.clear_cache_query' => '\Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand',
            'doctrine_integration.schema_tool_create' => '\Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand',
            'doctrine_integration.schema_tool_update' => '\Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand',
            'doctrine_integration.schema_tool_drop' => '\Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand',
            'doctrine_integration.convert_d1_schema' => '\Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand',
            'doctrine_integration.generate_entities' => '\Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand',
            'doctrine_integration.generate_proxies' => '\Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand',
            'doctrine_integration.convert_mapping' => '\Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand',
            'doctrine_integration.run_dql' => '\Doctrine\ORM\Tools\Console\Command\RunDqlCommand',
            'doctrine_integration.validate_schema' => '\Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand',
            'doctrine_integration.info' => '\Doctrine\ORM\Tools\Console\Command\InfoCommand',
            'doctrine_integration.ensure_production_settings' => '\Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand',
            'doctrine_integration.generate_repositories' => '\Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand'
        ]
    ],

    'hydrators' => [
        'factories' => [
            'iMSCP\DoctrineIntegration\Stdlib\Hydrator\DoctrineObject' => 'iMSCP\DoctrineIntegration\Service\DoctrineObjectHydratorFactory'
        ]
    ]
];
