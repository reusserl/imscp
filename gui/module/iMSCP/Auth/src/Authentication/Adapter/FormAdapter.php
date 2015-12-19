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
use iMSCP\Auth\Identity\AuthenticatedIdentity;
use iMSCP\Core\Utils\Crypt;
use Zend\Authentication\Result as AuthResult;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Validator\Csrf;

/**
 * Class FormAdapter
 *
 * Authentication adapter which attempts to authenticate credential information
 * submitted through a web form (Form-based authentication).
 *
 * @package iMSCP\Auth\Authentication\Adapter
 */
class FormAdapter extends AbstractAdapter
{
    /**
     * {@inheritdoc}
     * @return FormAdapterOptions
     */
    public function setOptions($options)
    {
        if (!$options instanceof FormAdapterOptions) {
            $options = new FormAdapterOptions($options);
        }

        return parent::setOptions($options);
    }

    /**
     * {@inheritdoc}
     * @return FormAdapterOptions
     */
    public function getOptions()
    {
        if (!$this->options) {
            $this->setOptions(new FormAdapterOptions());
        }

        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function canHandleAuthType($authType)
    {
        return ('form' === $authType);
    }

    /**
     * {@inheritdoc}
     */
    public function preAuth(Request $request, Response $response)
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request, Response $response, AuthEvent $authEvent)
    {
        $options = $this->getOptions();

        $username = $request->getPost($options->getIdentityField());
        $credential = $request->getPost($options->getCredentialField());

        if (empty($username)) {
            $result = new AuthResult(AuthResult::FAILURE_IDENTITY_NOT_FOUND, null, [tr('A username is required.')]);
            $authEvent->setAuthenticationResult($result);
            return false;
        }

        if (empty($credential)) {
            $result = new AuthResult(AuthResult::FAILURE_CREDENTIAL_INVALID, null, [tr('A password is required.')]);
            $authEvent->setAuthenticationResult($result);
            return false;
        }

        // Check for OPTIONAL CSRF token validity
        if ($csrfTokenField = $options->getCsrfTokenField()) {
            $csrfValidator = new Csrf(['name' => $csrfTokenField]);

            if (!$csrfValidator->isValid($request->getPost($csrfTokenField))) {
                $result = new AuthResult(AuthResult::FAILURE_UNCATEGORIZED, null, [tr('Invalid CSRF token.')]);
                $authEvent->setAuthenticationResult($result);
                return false;
            }
        }

        $credentials = $options->getCredentialResolver()->resolve($username, $credential);

        if ($credentials === false || !$this->checkCredential($credential, $credentials['credential'])) {
            $result = new AuthResult(AuthResult::FAILURE_CREDENTIAL_INVALID, null, [tr('Invalid credentials were supplied.')]);
            $authEvent->setAuthenticationResult($result);
            return false;
        }

        $result = new AuthResult(AuthResult::SUCCESS, $credentials['identity'], [tr('Authentication successful.')]);
        $authEvent->setAuthenticationResult($result);

        $resultIdentity = $result->getIdentity();

        // Pass fully discovered identity to AuthenticatedIdentity instance
        $identity = new AuthenticatedIdentity($resultIdentity);

        // Set identity name
        $identity->setName($username);

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthTypeFromRequest(Request $request)
    {
        $options = $this->getOptions();

        if (
            $request->isPost()
            && $request->getPost($options->getIdentityField()) !== null
            && $request->getPost($options->getCredentialField()) !== null
            && $request->getHeaders()->has('Content-Type')
            && $request->getHeaders()->get('Content-Type')->match('application/x-www-form-urlencoded')

        ) {
            return 'form';
        }

        return null;
    }

    /**
     * Check that the given credential matches the provided identity
     *
     * @param string $credential
     * @param string $hash
     * @return bool
     */
    protected function checkCredential($credential, $hash)
    {
        $options = $this->getOptions();
        $formats = array_map('strtolower', $options->getCredentialFormats());

        if (
            in_array('clear', $formats) && ($credential === $hash)
            || in_array('md5', $formats) && ($hash === md5($credential))
            || in_array($formats, ['apr1', 'crypt', 'sha1']) && Crypt::verify($credential, $hash)
        ) {
            return true;
        }

        return false;
    }
}
