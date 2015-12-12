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
namespace iMSCP\DoctrineIntegration\Service\Authentication;

use iMSCP\DoctrineIntegration\Authentication\Storage\ObjectRepository;
use iMSCP\DoctrineIntegration\Service\AbstractFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Factory to create authentication storage object.
 *
 * @license MIT
 * @link    http://www.doctrine-project.org/
 * @author  Tim Roediger <superdweebie@gmail.com>
 * @package iMSCP\DoctrineIntegration\Service\Authentication
 */
class StorageFactory extends AbstractFactory
{
    /**
     * {@inheritdoc]
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /* @var $options \iMSCP\DoctrineIntegration\Options\Authentication */
        $options = $this->getOptions($serviceLocator, 'authentication');

        if (is_string($objectManager = $options->getObjectManager())) {
            $options->setObjectManager($serviceLocator->get($objectManager));
        }

        if (is_string($storage = $options->getStorage())) {
            $options->setStorage($serviceLocator->get($storage));
        }

        return new ObjectRepository($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsClass()
    {
        return 'iMSCP\DoctrineIntegration\Options\Authentication';
    }
}
