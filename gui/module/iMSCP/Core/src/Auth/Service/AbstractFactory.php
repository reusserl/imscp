<?php

namespace iMSCP\Core\Auth\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractFactory
 * @package iMSCP\Core\Auth\Factory
 */
abstract class AbstractFactory implements FactoryInterface
{
    /**
     * Would normally be set to authentication or authorization
     *
     * @var string Component type
     */
    protected $componentType;

    /**
     * @var string Service name
     */
    protected $name;

    /**
     * Constructor
     *
     * @param string $name
     * @param null $componentType
     */
    public function __construct($name, $componentType = null)
    {
        $this->name = $name;
        $this->componentType = $componentType;
    }

    /**
     * Get service name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get optional component type
     *
     * Would normally be set to authentication or authorization
     *
     * @return string
     */
    public function getComponentType()
    {
        return $this->componentType;
    }

    /**
     * Gets options from configuration based on name
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @param  string $key
     * @param  null|string $name
     * @return \Zend\Stdlib\AbstractOptions
     * @throws \RuntimeException
     */
    public function getOptions(ServiceLocatorInterface $serviceLocator, $key, $name = null)
    {
        if ($name === null) {
            $name = $this->getName();
        }

        $options = $serviceLocator->get('Config');
        $options = $options['imscp_core_auth'];

        if ($componentType = $this->getComponentType()) {
            $options = $options[$componentType];
        }

        $options = isset($options[$key][$name]) ? $options[$key][$name] : null;

        if (null === $options) {
            $path = ($componentType) ? "$componentType.$key" : "$key";
            throw new \RuntimeException(
                sprintf('Options with name "%s" could not be found in "imscp_core_auth.%s"', $name, $path)
            );
        }

        $optionsClass = $this->getOptionsClass();

        return new $optionsClass($options);
    }

    /**
     * Get the class name of the options associated with this factory
     *
     * @abstract
     * @return string
     */
    abstract public function getOptionsClass();
}
