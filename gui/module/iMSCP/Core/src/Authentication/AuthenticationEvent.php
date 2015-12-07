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

namespace iMSCP\Core\Authentication;

use iMSCP\Authentication\Identity\IdentityInterface;
use iMSCP\Core\ApplicationEvent;
use Zend\Authentication\Result as AuthResult;
use Zend\EventManager\Event;

/**
 * Class AuthenticationEvent
 * @package iMSCP\Core\Authentication
 */
class AuthenticationEvent extends Event
{
    const onAuthentication = 'onAuthentication';
    const onAfterAuthentication = 'onAfterAuthentication';
    const onAuthorization = 'onAuthorization';
    const onAfterAuthorization = 'onAfterAuthorization';

    /**
     * @var ApplicationEvent
     */
    protected $appEvent;

    /**
     * @var mixed
     */
    protected $authentication;

    /**
     * @var AuthResult
     */
    protected $authenticationResult;

    /**
     * @var mixed
     */
    protected $authorization;

    /**
     * Whether or not authorization has completed/succeeded
     * @var bool
     */
    protected $authorized = false;

    /**
     * The resource used for authorization queries
     *
     * @var mixed
     */
    protected $resource;

    /**
     * Constructor
     *
     * @param ApplicationEvent $appEvent
     * @param mixed $authentication
     * @param mixed $authorization
     */
    public function __construct(ApplicationEvent $appEvent, $authentication, $authorization)
    {
        parent::__construct(); // Only to make IDEs happy

        $this->appEvent = $appEvent;
        $this->authentication = $authentication;
        $this->authorization = $authorization;
    }

    /**
     * Get authentication service
     *
     * @return mixed
     */
    public function getAuthenticationService()
    {
        return $this->authentication;
    }

    /**
     * Has authentication result?
     *
     * @return bool
     */
    public function hasAuthenticationResult()
    {
        return ($this->authenticationResult !== null);
    }

    /**
     * Set authentication result
     *
     * @param  AuthResult $result
     * @return self
     */
    public function setAuthenticationResult(AuthResult $result)
    {
        $this->authenticationResult = $result;
        return $this;
    }

    /**
     * Get authentication result
     *
     * @return null|AuthResult
     */
    public function getAuthenticationResult()
    {
        return $this->authenticationResult;
    }

    /**
     * Get authorization service
     *
     * @return mixed
     */
    public function getAuthorizationService()
    {
        return $this->authorization;
    }

    /**
     * Get application event
     * @return ApplicationEvent
     */
    public function getApplicationEvent()
    {
        return $this->appEvent;
    }

    /**
     * Get identity
     *
     * @return mixed|null
     */
    public function getIdentity()
    {
        return $this->authentication->getIdentity();
    }

    /**
     * Set identity
     *
     * @param IdentityInterface $identity
     * @return self
     */
    public function setIdentity(IdentityInterface $identity)
    {
        $this->authentication->getStorage()->write($identity);
        return $this;
    }

    /**
     * Get resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @param  mixed $resource
     * @return self
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Is authorized?
     *
     * @return bool
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * Set authorization flag
     *
     * @param bool $flag
     * @return self
     */
    public function setIsAuthorized($flag)
    {
        $this->authorized = (bool)$flag;
        return $this;
    }
}
