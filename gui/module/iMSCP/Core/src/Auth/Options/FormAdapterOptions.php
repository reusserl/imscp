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

namespace iMSCP\Core\Auth\Options;

use Zend\Stdlib\AbstractOptions;

/**
 * Class FormAdapterOptions
 * @package iMSCP\Core\Auth\Options
 */
class FormAdapterOptions extends AbstractOptions
{
    /**
     * @var string identity field
     */
    protected $identityField;

    /**
     * @var string credential field
     */
    protected $credentialField;

    /**
     * @var array Credential format
     */
    protected $credentialFormats;

    /**
     * @var string resolver configuration key
     */
    protected $resolver;

    /**
     * Get identity field
     *
     * @return string
     */
    public function getIdentityField()
    {
        return $this->identityField;
    }

    /**
     * Set identity field
     *
     * @param string $identityField
     * @return $this
     */
    public function setIdentityField($identityField)
    {
        if (!is_string($identityField) || $identityField === '') {
            throw new \InvalidArgumentException(
                sprintf('Provided $identityField is invalid, %s given', gettype($identityField))
            );
        }

        $this->identityField = $identityField;
        return $this;
    }

    /**
     * Get credential field
     *
     * @return string
     */
    public function getCredentialField()
    {
        return $this->credentialField;
    }

    /**
     * Set credential field
     *
     * @param string $credentialField
     * @return $this
     */
    public function setCredentialField($credentialField)
    {
        if (!is_string($credentialField) || $credentialField === '') {
            throw new \InvalidArgumentException(
                sprintf('Provided $credentialField is invalid, %s given', gettype($credentialField))
            );
        }

        $this->credentialField = $credentialField;
        return $this;
    }

    /**
     * Get credential formats
     *
     * @return array
     */
    public function getCredentialFormats()
    {
        return $this->credentialFormats;
    }

    /**
     * Set credential formats
     *
     * @param array $credentialFormats
     * @return $this
     */
    public function setCredentialFormats(array $credentialFormats)
    {
        $supportedFormats = ['apr1', 'crypt', 'md5', 'sha1'];
        if (array_intersect($credentialFormats, $supportedFormats) != $credentialFormats) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported credential format(s). Supported formats are: %s', implode(' ', $supportedFormats)
            ));
        }

        $this->credentialFormats = $credentialFormats;
        return $this;
    }

    /**
     * @return string
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param string $resolver
     * @return $this
     */
    public function setResolver($resolver)
    {
        $this->resolver = $resolver;
        return $this;
    }
}
