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

namespace iMSCP\Core\Auth\Authentication\Adapter\Resolver;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ObjectRepositoryResolverOptions
 * @package iMSCP\Core\Auth\Options
 */
class ObjectRepositoryResolverOptions extends ResolverOptions
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string Identity class
     */
    protected $identityClass;

    /**
     * @var string Identity property
     */
    protected $identityProperty;

    /**
     * @var string Credential property
     */
    protected $credentialProperty;

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
     * Set object manager
     *
     * @param ObjectManager $objectManager
     * @return $this
     */
    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;
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
     * Set identity class
     *
     * @param string $identityClass
     * @return $this
     */
    public function setIdentityClass($identityClass)
    {
        if (!is_string($identityClass) || $identityClass === '') {
            throw new \InvalidArgumentException(
                sprintf('Provided $identityClass is invalid, %s given', gettype($identityClass))
            );
        }

        $this->identityClass = $identityClass;
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
     * Set identity property
     *
     * @param string $identityProperty
     * @return $this
     */
    public function setIdentityProperty($identityProperty)
    {
        if (!is_string($identityProperty) || $identityProperty === '') {
            throw new \InvalidArgumentException(
                sprintf('Provided $identityProperty is invalid, %s given', gettype($identityProperty))
            );
        }

        $this->identityProperty = $identityProperty;
        return $this;
    }

    /**
     * Get credential property
     *
     * @return string
     */
    public function getCredentialProperty()
    {
        return $this->credentialProperty;
    }

    /**
     * Set credential property
     *
     * @param string $credentialProperty
     * @return $this
     */
    public function setCredentialProperty($credentialProperty)
    {
        if (!is_string($credentialProperty) || $credentialProperty === '') {
            throw new \InvalidArgumentException(
                sprintf('Provided $credentialProperty is invalid, %s given', gettype($credentialProperty))
            );
        }

        $this->credentialProperty = $credentialProperty;
        return $this;
    }
}
