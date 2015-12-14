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

use iMSCP\Core\Auth\AuthEvent;
use Zend\Http\Response;

/**
 * Class DefaultAuthenticationListener
 * @package iMSCP\Core\Authentication\Listener
 */
class DefaultAuthenticationPostListener
{
    /**
     * Set 401 response status code in case of a failed authentication
     *
     * The status code set here is only valuable for API consumers. The 401 HTTP code is ignored when using a
     * form-based authentication adapter. We set this status code here because The auth component will provide different
     * authentication adpaters such Form-based, HTTP basic, HTTP digest, and OAuth.
     *
     * @listen AuthEvent::onAfterAuthentication
     * @param AuthEvent $authEvent
     * @return null|\Zend\Stdlib\ResponseInterface
     */
    public function __invoke(AuthEvent $authEvent)
    {
        if (!$authEvent->hasAuthenticationResult()) {
            return null;
        }

        $authResult = $authEvent->getAuthenticationResult();
        if ($authResult->isValid()) {
            return null;
        }

        $appEvent = $authEvent->getApplicationEvent();
        $response = $appEvent->getResponse();

        if (!$response instanceof Response) {
            return $response;
        }

        $response->setStatusCode(401);
        $response->setReasonPhrase('Unauthorized');
        return $response;
    }
}
