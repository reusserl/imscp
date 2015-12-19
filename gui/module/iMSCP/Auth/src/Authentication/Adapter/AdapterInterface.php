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
 * Interface AdapterInterface
 * @package iMSCP\Auth\Authentication\Adapter
 */
interface AdapterInterface
{
    /**
     * Set options
     *
     * @param array|\Traversable|AdapterOptions $options
     * @return AdapterInterface Fluent interface
     */
    public function setOptions($options);

    /**
     * Get options
     *
     * @return AdapterOptions
     */
    public function getOptions();

    /**
     * Whether or not the adapter can handle the given authentication type
     *
     * @param string $authType Authentication type
     * @return bool
     */
    public function canHandleAuthType($authType);

    /**
     * Tries to determine authentication type based on request information.
     *
     * @param Request $request
     * @return null|string
     */
    public function getAuthTypeFromRequest(Request $request);

    /**
     * Perform pre-flight authenticationt tasks
     *
     * Use case would be providing authentication challenge headers.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function preAuth(Request $request, Response $response);

    /**
     * Attempts to authenticate the current request
     *
     * @param Request $request
     * @param Response $response
     * @param AuthEvent $authEvent
     * @return false|Response|IdentityInterface An IdentityInterface object, FALSE on failure
     */
    public function authenticate(Request $request, Response $response, AuthEvent $authEvent);
}
