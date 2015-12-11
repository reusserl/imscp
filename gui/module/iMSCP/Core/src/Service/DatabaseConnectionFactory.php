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

namespace iMSCP\Core\Service;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use iMSCP\Core\Utils\Crypt;
use iMSCP\DoctrineIntegration\Options\DBALConnection as DBALConnectionOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayUtils;

/**
 * DBAL Connection ServiceManager factory
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Kyle Spraggs <theman@spiffyjr.me>
 * @package iMSCP\DoctrineIntegration\Service
 */
class DatabaseConnectionFactory implements FactoryInterface
{
    /**
     * Create the default database connection using parameters from our encryption data service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $options = $config['doctrine_integration'];
        $options = isset($options['connection']['default']) ? $options['connection']['default'] : null;

        if (null === $options) {
            throw new \RuntimeException(sprintf(
                'Options with name "%s" could not be found in "doctrine_integration.%s".', 'default', 'connection'
            ));
        }

        $options = new DBALConnectionOptions($options);

        /** @var EncryptionDataService $encryptionDataService */
        $encryptionDataService = $serviceLocator->get('EncryptionDataService');

        $params = [
            'driverClass' => $options->getDriverClass(),
            'wrapperClass' => $options->getWrapperClass(),
            'driver' => 'pdo_' . $config['DATABASE_TYPE'],
            'host' => $config['DATABASE_HOST'],
            'port' => $config['DATABASE_PORT'],
            'dbname' => $config['DATABASE_NAME'],
            'user' => $config['DATABASE_USER'],
            'password' => Crypt::decryptRijndaelCBC(
                $encryptionDataService->getKey(), $encryptionDataService->getIV(), $config['DATABASE_PASSWORD']
            ),
            'charset' => 'utf8'
        ];

        $params = ArrayUtils::merge($params, $options->getParams());

        /** @var Configuration $configuration */
        $configuration = $serviceLocator->get($options->getConfiguration());
        $eventManager = $serviceLocator->get($options->getEventManager());

        $connection = DriverManager::getConnection($params, $configuration, $eventManager);
        $platform = $connection->getDatabasePlatform();

        foreach ($options->getDoctrineTypeMappings() as $dbType => $doctrineType) {
            $platform->registerDoctrineTypeMapping($dbType, $doctrineType);
        }

        foreach ($options->getDoctrineCommentedTypes() as $type) {
            $platform->markDoctrineTypeCommented(Type::getType($type));
        }

        return $connection;
    }
}
