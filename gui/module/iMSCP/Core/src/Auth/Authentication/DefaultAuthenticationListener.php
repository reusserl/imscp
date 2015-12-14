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

namespace iMSCP\Core\Auth\Authentication;

use iMSCP\Core\Auth\Authentication\Adapter\AdapterInterface;
use iMSCP\Core\Auth\AuthEvent;
use iMSCP\Core\Auth\Identity\GuestIdentity;
use iMSCP\Core\Auth\Identity\IdentityInterface;

/**
 * Class DefaultAuthenticationListener
 * @package iMSCP\Core\Authentication\Listener
 */
class DefaultAuthenticationListener
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Constructor
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @listen AuthEvent::onAuthentication
     * @param AuthEvent $authEvent
     * @return null|mixed
     */
    public function __invoke(AuthEvent $authEvent)
    {
        $applicationEvent = $authEvent->getApplicationEvent();

        /** @var \Zend\Http\PhpEnvironment\Request $request */
        $request = $applicationEvent->getRequest();

        /** @var \Zend\Http\PhpEnvironment\Response $response */
        $response = $applicationEvent->getResponse();

        $identity = $this->adapter->authenticate($request, $response, $authEvent);

        if (!$identity instanceof IdentityInterface) {
            // No identity returned, create a guest identity
            $identity = new GuestIdentity();
        }

        $applicationEvent->setParam('iMSCP\Core\Auth\Identity', $identity);
        return $identity;
    }
}
