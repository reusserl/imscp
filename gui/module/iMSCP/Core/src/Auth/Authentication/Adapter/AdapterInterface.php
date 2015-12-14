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
namespace iMSCP\Core\Auth\Authentication\Adapter;

use iMSCP\Core\Auth\AuthEvent;
use iMSCP\Core\Auth\Identity\IdentityInterface;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 * Interface AdapterInterface
 * @package iMSCP\Core\Auth\Authentication\Adapter
 */
interface AdapterInterface
{
    /**
     * Perform pre-flight authenticationt tasks
     *
     * Use case would be providing authentication challenge headers.
     *
     * @param Request $request
     * @param Response $Response
     * @return null|Response
     */
    public function preAuth(Request $request, Response $Response);

    /**
     * Attempts to authenticate the current request
     *
     * @param Request $request
     * @param Response $Response
     * @param AuthEvent $authEvent
     * @return false|IdentityInterface An IdentityInterface object, FALSE on failure
     */
    public function authentication(Request $request, Response $Response, AuthEvent $authEvent);
}
