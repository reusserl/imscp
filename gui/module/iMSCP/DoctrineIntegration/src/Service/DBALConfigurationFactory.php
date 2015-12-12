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

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Types\Type;
use RuntimeException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DBAL Configuration ServiceManager factory
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Kyle Spraggs <theman@spiffyjr.me>
 * @package iMSCP\DoctrineIntegration\Service
 */
class DBALConfigurationFactory implements FactoryInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc]
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = new Configuration();
        $this->setupDBALConfiguration($serviceLocator, $config);
        return $config;
    }

    /**
     * Setup DBAL configuration
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param Configuration $config
     */
    public function setupDBALConfiguration(ServiceLocatorInterface $serviceLocator, Configuration $config)
    {
        /** @var \iMSCP\DoctrineIntegration\Options\Configuration $options */
        $options = $this->getOptions($serviceLocator);

        /** @var $resultCache \Doctrine\Common\Cache\Cache */
        $resultCache = $serviceLocator->get($options->getResultCache());
        $config->setResultCacheImpl($resultCache);

        $sqlLogger = $options->getSqlLogger();
        if (is_string($sqlLogger) and $serviceLocator->has($sqlLogger)) {
            $sqlLogger = $serviceLocator->get($sqlLogger);
        }
        $config->setSQLLogger($sqlLogger);

        foreach ($options->getTypes() as $name => $class) {
            if (Type::hasType($name)) {
                Type::overrideType($name, $class);
            } else {
                Type::addType($name, $class);
            }
        }
    }

    /**
     * Get configuration options
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     * @throws RuntimeException
     */
    public function getOptions(ServiceLocatorInterface $serviceLocator)
    {
        $options = $serviceLocator->get('Config');
        $options = $options['doctrine_integration'];
        $options = isset($options['configuration'][$this->name]) ? $options['configuration'][$this->name] : null;

        if (null === $options) {
            throw new RuntimeException(sprintf(
                'Configuration with name "%s" could not be found in "doctrine_integration.configuration".',
                $this->name
            ));
        }

        $optionsClass = $this->getOptionsClass();
        return new $optionsClass($options);
    }

    /**
     * {@inheritdoc]
     */
    protected function getOptionsClass()
    {
        return 'iMSCP\DoctrineIntegration\Options\Configuration';
    }
}
