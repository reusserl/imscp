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

namespace iMSCP\Core\Auth;

use iMSCP\Core\ApplicationEvent;
use iMSCP\Core\Auth\Identity\AuthenticatedIdentity;
use iMSCP\Core\Auth\Identity\GuestIdentity;
use iMSCP\Core\Auth\Identity\IdentityInterface;
use iMSCP\Core\Events;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Authentication\Result as AuthResult;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Request;
use Zend\Stdlib\ResponseInterface as Response;

/**
 * Class ScriptStartListener
 * @package iMSCP\Core
 */
class ScriptStartListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @var AuthenticationService
     */
    protected $authentication;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var AuthEvent
     */
    protected $authEvent;

    /**
     * Constructor
     *
     * @param $authEvent $mvcAuthEvent
     * @param AuthenticationServiceInterface $authentication
     */
    public function __construct(AuthEvent $authEvent, AuthenticationServiceInterface $authentication)
    {
        $authEvent->setTarget($this);
        $this->authEvent = $authEvent;
        $this->authentication = $authentication;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events)
    {
        $this->events = $events;
        $this->listeners[] = $events->attach(
            [
                Events::onLoginScriptStart, Events::onLostPasswordScriptStart, Events::onAdminScriptStart,
                Events::onClientScriptStart, Events::onResellerScriptStart
            ],
            [$this, 'authentication'],
            -50
        );

        $this->listeners[] = $events->attach(
            [
                Events::onLoginScriptStart, Events::onLostPasswordScriptStart, Events::onAdminScriptStart,
                Events::onClientScriptStart, Events::onResellerScriptStart
            ],
            [$this, 'authenticationPost'],
            -51
        );

        $this->listeners[] = $events->attach(
            [
                Events::onLoginScriptStart, Events::onLostPasswordScriptStart, Events::onAdminScriptStart,
                Events::onClientScriptStart, Events::onResellerScriptStart
            ],
            [$this, 'authorization'],
            -600
        );

        $this->listeners[] = $events->attach(
            [
                Events::onLoginScriptStart, Events::onLostPasswordScriptStart, Events::onAdminScriptStart,
                Events::onClientScriptStart, Events::onResellerScriptStart
            ],
            [$this, 'authorizationPost'],
            -601
        );
    }

    /**
     * Perform authentication tasks
     *
     * @trigger AuthEvent::onAuthentication
     * @param ApplicationEvent $appEvent
     * @return null|Response
     */
    public function authentication(ApplicationEvent $appEvent)
    {
        /** @var Request $request */
        $request = $appEvent->getRequest();
        if (!$request instanceof HttpRequest || $request->isOptions()) {
            return null;
        }

        $authEvent = $this->authEvent;

        // if We have already an authenticated identity, return immediately
        //if($mvcAuthEvent->getIdentity() instanceof AuthenticatedIdentity) {
        //   return $mvcAuthEvent->getAuthenticationService()->getIdentity();
        //}

        // Triggers the onAuthentication event until we get an identity, an authentication result, or a response
        $responses = $this->events->trigger($authEvent::onAuthentication, $authEvent, function ($r) {
            return (
                $r instanceof IdentityInterface ||
                $r instanceof AuthResult ||
                $r instanceof Response
            );
        });

        $result = $responses->last();

        // If we have a response, return immediately
        if ($result instanceof Response) {
            return $result;
        }

        // If we have a identity, store it
        if ($result instanceof IdentityInterface) {
            $storage = $this->authentication->getStorage();
            $storage->write($result);
        }

        // If we have an authentication result, we create an authenticated identity from it
        if ($result instanceof AuthResult && $result->isValid()) {
            $authEvent->setAuthenticationResult($result);
            $authEvent->setIdentity(new AuthenticatedIdentity($result->getIdentity()));
            return null;
        }

        $identity = $this->authentication->getIdentity();
        if ($identity === null && !$authEvent->hasAuthenticationResult()) {
            // If there is no authenticated identity nor an authentication result, it is safe to assume we have a guest
            $authEvent->setIdentity(new GuestIdentity());
            return null;
        }

        if ($authEvent->hasAuthenticationResult() && $authEvent->getAuthenticationResult()->isValid()) {
            $authEvent->setIdentity(new AuthenticatedIdentity($authEvent->getAuthenticationResult()->getIdentity()));
        }

        if ($identity instanceof IdentityInterface) {
            $authEvent->setIdentity($identity);
            return null;
        }

        if ($identity !== null) {
            // identity found in authentication; we can assume we're authenticated
            $authEvent->setIdentity(new AuthenticatedIdentity($identity));
        }

        return null;
    }

    /**
     * Perform post authentication tasks
     *
     * @trigger AuthEvent::onAfterAuthentication
     * @param ApplicationEvent $appEvent
     * @return Response|mixed
     */
    public function authenticationPost(ApplicationEvent $appEvent)
    {
        /** @var Request $request */
        $request = $appEvent->getRequest();
        if (!$request instanceof HttpRequest || $request->isOptions()) {
            return null;
        }

        $responses = $this->events->trigger(AuthEvent::onAfterAuthentication, $this->authEvent, function ($r) {
            return ($r instanceof Response);
        });

        return $responses->last();
    }

    /**
     * Perform authorization tasks
     *
     * @trigger AuthEvent::onAuthorization
     * @param ApplicationEvent $appEvent
     * @return null|Response
     */
    public function authorization(ApplicationEvent $appEvent)
    {
        /** @var Request $request */
        $request = $appEvent->getRequest();
        if (!$request instanceof HttpRequest || $request->isOptions()) {
            return null;
        }

        $responses = $this->events->trigger(AuthEvent::onAuthorization, $this->authEvent, function ($r) {
            return (
                is_bool($r) ||
                $r instanceof Response
            );
        });

        $result = $responses->last();

        if (is_bool($result)) {
            $this->authEvent->setIsAuthorized($result);
            return null;
        }

        if ($result instanceof Response) {
            return $result;
        }

        return null;
    }

    /**
     * Perform post authorization tasks
     *
     * @trigger AuthEvent::onAfterAuthorization
     * @param ApplicationEvent $appEvent
     * @return null|Response
     */
    public function authorizationPost(ApplicationEvent $appEvent)
    {
        /** @var Request $request */
        $request = $appEvent->getRequest();
        if (!$request instanceof HttpRequest || $request->isOptions()) {
            return null;
        }

        $responses = $this->events->trigger(AuthEvent::onAfterAuthorization, $this->authEvent, function ($r) {
            return ($r instanceof Response);
        });

        return $responses->last();
    }
}
