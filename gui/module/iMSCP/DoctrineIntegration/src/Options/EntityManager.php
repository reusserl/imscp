<?php

namespace iMSCP\DoctrineIntegration\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Class EntityManager
 * @package iMSCP\DoctrineIntegration\Options
 */
class EntityManager extends AbstractOptions
{
    /**
     * Set the configuration key for the Configuration. Configuration key
     * is assembled as "doctrine_integration.configuration.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $configuration = 'default';

    /**
     * Set the connection key for the Connection. Connection key
     * is assembled as "doctrine_integration.connection.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * Set the connection key for the EntityResolver, which is
     * a service of type {@see \Doctrine\ORM\Tools\ResolveTargetEntityListener}.
     * The EntityResolver service name is assembled
     * as "doctrine_integration.entity_resolver.{key}"
     *
     * @var string
     */
    protected $entityResolver = 'default';

    /**
     * Get configuration
     *
     * @return string
     */
    public function getConfiguration()
    {
        return "doctrine_integration.configuration.{$this->configuration}";
    }

    /**
     * Set configuration
     *
     * @param  string $configuration
     * @return self
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Get connection
     *
     * @return string
     * @return self
     */
    public function getConnection()
    {
        return 'doctrine_integration.connection.' . $this->connection;
    }

    /**
     * Set connection
     *
     * @param  string $connection
     * @return self
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get entity resolver
     *
     * @return string
     * @return self
     */
    public function getEntityResolver()
    {
        return 'doctrine_integration.entity_resolver.' . $this->entityResolver;
    }

    /**
     * Set entity resolver
     *
     * @param  string $entityResolver
     * @return self
     */
    public function setEntityResolver($entityResolver)
    {
        $this->entityResolver = (string)$entityResolver;
        return $this;
    }
}
