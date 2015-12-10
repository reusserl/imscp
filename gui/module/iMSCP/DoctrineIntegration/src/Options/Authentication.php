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

namespace iMSCP\DoctrineIntegration\Options;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Zend\Authentication\Adapter\Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Stdlib\AbstractOptions;

/**
 * Class Authentication
 *
 * This options class can be consumed by five different classes:
 *
 * DoctrineIntegration\Authentication\Adapter\ObjectRepository
 * DoctrineIntegration\Service\Authentication\AdapterFactory
 * DoctrineIntegration\Authentication\Storage\ObjectRepository
 * DoctrineIntegration\Service\Authentication\ServiceFactory
 * DoctrineIntegration\Service\Authentication\AuthenticationServiceFactory
 *
 * When using with DoctrineIntegration\Authentication\Adapter\ObjectRepository the following
 * options are required:
 *
 * $identityProperty
 * $credentialProperty
 *
 * In addition either $objectRepository or $objectManager and $identityClass must be set.
 * If $objectRepository is set, it takes precedence over $objectManager and $identityClass.
 * If $objectManager is used, it must be an instance of ObjectManager.
 *
 * All remains the same using with DoctrineIntegration\Service\AuthenticationAdapterFactory,
 * however, a string may be passed to $objectManager. This string must be a valid key to
 * retrieve an ObjectManager instance from the ServiceManager.
 *
 * When using with DoctrineIntegration\Authentication\Service\Object repository the following
 * options are required:
 *
 * Either $objectManager, or $classMetadata and $objectRepository.
 *
 * All remains the same using with DoctrineIntegration\Service\AuthenticationStorageFactory,
 * however, a string may be passed to $objectManager. This string must be a valid key to
 * retrieve an ObjectManager instance from the ServiceManager.
 *
 * @license MIT
 * @link http://www.doctrine-project.org/
 * @author Michaël Gallego <mic.gallego@gmail.com>
 * @package iMSCP\DoctrineIntegration\Options
 */
class Authentication extends AbstractOptions
{
    /**
     * @var string|ObjectManager A valid object implementing ObjectManager interface
     */
    protected $objectManager;

    /**
     * @var ObjectRepository A valid object implementing the ObjectRepository interface (or ObjectManager/identityClass)
     */
    protected $objectRepository;

    /**
     * @var string Entity's class name
     */
    protected $identityClass;

    /**
     * @var string Property to use for the identity
     */
    protected $identityProperty;

    /**
     * @var string Property to use for the credential
     */
    protected $credentialProperty;

    /**
     * @var mixed Callable function to check if a credential is valid
     */
    protected $credentialCallable;

    /**
     * If an objectManager is not supplied, this metadata will be used
     * by iMSCP/DoctrineIntegration/Authentication/Storage/ObjectRepository
     *
     * @var ClassMetadata
     */
    protected $classMetadata;

    /**
     * When using this options class to create a iMSCP/DoctrineIntegration/Authentication/Storage/ObjectRepository
     * this is the storage instance that the object key will be stored in.
     *
     * When using this options class to create an AuthenticationService with and
     * the option storeOnlyKeys == false, this is the storage instance that the whole
     * object will be stored in.
     *
     * @var StorageInterface|string;
     */
    protected $storage = 'iMSCP\DoctrineIntegration\Authentication\Storage\Session';

    /**
     * Set object manager
     *
     * @param string | ObjectManager $objectManager
     * @return Authentication
     */
    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;
        return $this;
    }

    /**
     * Get object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Set object repository
     *
     * @param  ObjectRepository $objectRepository
     * @return Authentication
     */
    public function setObjectRepository(ObjectRepository $objectRepository)
    {
        $this->objectRepository = $objectRepository;
        return $this;
    }

    /**
     * Get object repository
     *
     * @return ObjectRepository
     */
    public function getObjectRepository()
    {
        if ($this->objectRepository) {
            return $this->objectRepository;
        }

        return $this->objectManager->getRepository($this->identityClass);
    }

    /**
     * Set identity class
     *
     * @param string $identityClass
     * @return Authentication
     */
    public function setIdentityClass($identityClass)
    {
        $this->identityClass = $identityClass;
        return $this;
    }

    /**
     * Get identity class
     *
     * @return string
     */
    public function getIdentityClass()
    {
        return $this->identityClass;
    }

    /**
     * Set identity property
     *
     * @param string $identityProperty
     * @throws Exception\InvalidArgumentException
     * @return Authentication
     */
    public function setIdentityProperty($identityProperty)
    {
        if (!is_string($identityProperty) || $identityProperty === '') {
            throw new Exception\InvalidArgumentException(
                sprintf('Provided $identityProperty is invalid, %s given', gettype($identityProperty))
            );
        }

        $this->identityProperty = $identityProperty;
        return $this;
    }

    /**
     * Get identity property
     *
     * @return string
     */
    public function getIdentityProperty()
    {
        return $this->identityProperty;
    }

    /**
     * Set crendential property
     *
     * @param string $credentialProperty
     * @throws Exception\InvalidArgumentException
     * @return Authentication
     */
    public function setCredentialProperty($credentialProperty)
    {
        if (!is_string($credentialProperty) || $credentialProperty === '') {
            throw new Exception\InvalidArgumentException(
                sprintf('Provided $credentialProperty is invalid, %s given', gettype($credentialProperty))
            );
        }

        $this->credentialProperty = $credentialProperty;
        return $this;
    }

    /**
     * @return string
     */
    public function getCredentialProperty()
    {
        return $this->credentialProperty;
    }

    /**
     * @param  mixed $credentialCallable
     * @throws Exception\InvalidArgumentException
     * @return Authentication
     */
    public function setCredentialCallable($credentialCallable)
    {
        if (!is_callable($credentialCallable)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    '"%s" is not a callable',
                    is_string($credentialCallable) ? $credentialCallable : gettype($credentialCallable)
                )
            );
        }

        $this->credentialCallable = $credentialCallable;
        return $this;
    }

    /**
     * Get credential callable
     *
     * @return mixed
     */
    public function getCredentialCallable()
    {
        return $this->credentialCallable;
    }

    /**
     * Get class metadata
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        if ($this->classMetadata) {
            return $this->classMetadata;
        }

        return $this->objectManager->getClassMetadata($this->identityClass);
    }

    /**
     * Set class metatada
     *
     * @param ClassMetadata $classMetadata
     */
    public function setClassMetadata(ClassMetadata $classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }

    /**
     * Get storage
     *
     * @return StorageInterface|string
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Set storage
     *
     * @param StorageInterface|string $storage
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;
    }
}
