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

namespace iMSCP\Auth\Authentication\Adapter\Resolver;

/**
 * Class ObjectRepositoryResolver
 * @package iMSCP\Auth\Authentication\Adapter\Resolver
 */
class ObjectRepositoryResolver extends AbstractResolver
{
    /**
     * Resolve authentication credentials using an object repository
     *
     * @param string $identity
     * @param string $credential
     * @return array containing Identity object and credential, FALSE otherwise
     */
    public function resolve($identity, $credential)
    {
        if (empty($identity)) {
            throw new \InvalidArgumentException('Identity is required');
        }

        if (empty($credential)) {
            throw new \InvalidArgumentException('Credential is required');
        }

        $options = $this->getOptions();
        $identity = $options->getObjectRepository()->findOneBy([$options->getIdentityProperty() => $identity]);

        if (!$identity) {
            return false;
        }

        $credentialProperty = $options->getCredentialProperty();
        $getter = 'get' . ucfirst($credentialProperty);

        if (!is_callable([$identity, $getter])) {
            throw new \UnexpectedValueException(sprintf(
                'Property (%s) in (%s) is not accessible. You should implement %s::%s()',
                $credentialProperty,
                get_class($identity),
                get_class($identity),
                $getter
            ));
        }

        return ['identity' => $identity, 'credential' => $identity->$getter()];
    }

    /**
     * {@inheritdoc}
     * @return ObjectRepositoryResolverOptions
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(new ObjectRepositoryResolverOptions());
        }

        return $this->options;
    }

    /**
     * {@inheritdoc}
     * @return ObjectRepositoryResolverOptions
     */
    public function setOptions($options)
    {
        if (!$options instanceof ObjectRepositoryResolverOptions) {
            $options = new ObjectRepositoryResolverOptions($options);
        }

        return parent::setOptions($options);
    }
}
