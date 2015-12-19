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

namespace iMSCP\Auth\Authentication\Adapter;

use iMSCP\Auth\AuthEvent;
use iMSCP\Auth\Identity\IdentityInterface;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 * Class AdapterChain
 * @package iMSCP\Auth\Authentication\Adapter
 */
class AdapterChain extends AbstractAdapter implements AdapterChainInterface
{
    /**
     * @var AdapterInterface[]
     */
    protected $adapters;

    /**
     * Add an authentication adapter in the chain
     *
     * @param AdapterInterface $adapter
     * @return self
     */
    public function addAdapter(AdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;
    }

    /**
     * Whether or not the adapter can handle the given authentication type
     *
     * @param string $authType Authentication type
     * @return bool
     */
    public function canHandleAuthType($authType)
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->canHandleAuthType($authType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the authentication type based on request information
     *
     * @param Request $request
     * @return null|string
     */
    public function getAuthTypeFromRequest(Request $request)
    {
        foreach ($this->adapters as $adapter) {
            if (null !== ($authType = $adapter->getAuthTypeFromRequest($request))) {
                return $authType;
            }
        }

        return null;
    }

    /**
     * Perform pre-flight authenticationt tasks
     *
     * Use case would be providing authentication challenge headers.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function preAuth(Request $request, Response $response)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->preAuth($request, $response);
        }
    }

    /**
     * Attempts to authenticate the current request
     *
     * @param Request $request
     * @param Response $response
     * @param AuthEvent $authEvent
     * @return false|Response|IdentityInterface An IdentityInterface object, FALSE on failure
     */
    public function authenticate(Request $request, Response $response, AuthEvent $authEvent)
    {
        foreach($this->adapters as $adapter) {

        }
    }
}
