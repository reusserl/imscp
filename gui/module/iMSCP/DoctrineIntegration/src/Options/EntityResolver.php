<?php

namespace iMSCP\DoctrineIntegration\Options;

use InvalidArgumentException;
use Zend\Stdlib\AbstractOptions;

/**
 * Class EntityResolver
 * @package iMSCP\DoctrineIntegration\Options
 */
class EntityResolver extends AbstractOptions
{
    /**
     * Set the configuration key for the EventManager. Event manager key
     * is assembled as "doctrine_integration.eventmanager.{key}" and pulled from
     * service locator.
     *
     * @var string
     */
    protected $eventManager = 'default';

    /**
     * An array that maps a class name (or interface name) to another class
     * name
     *
     * @var array
     */
    protected $resolvers = [];

    /**
     * @return string
     */
    public function getEventManager()
    {
        return "doctrine_integration.eventmanager.{$this->eventManager}";
    }

    /**
     * @param  string $eventManager
     * @return self
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;

        return $this;
    }

    /**
     * @return array
     */
    public function getResolvers()
    {
        return $this->resolvers;
    }

    /**
     * @param  array $resolvers
     * @throws InvalidArgumentException
     */
    public function setResolvers(array $resolvers)
    {
        foreach ($resolvers as $old => $new) {
            if (!class_exists($new)) {
                throw new InvalidArgumentException(
                    sprintf('%s is resolved to the entity %s, which does not exist', $old, $new)
                );
            }

            $this->resolvers[$old] = $new;
        }
    }
}
