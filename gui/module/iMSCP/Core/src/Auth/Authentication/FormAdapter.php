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
use iMSCP\Core\Auth\Identity\AuthenticatedIdentity;
use iMSCP\Core\Utils\Crypt;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 * Class FormAdapter
 * @package iMSCP\Core\Auth\Authentication
 */
class FormAdapter implements AdapterInterface
{
    /**
     * @var $authenticationService
     */
    protected $authenticationService;

    /**
     * @var string Identity field
     */
    protected $identityField;

    /**
     * @var string Credential field
     */
    protected $credentialField;

    /**
     * @var array Password formats
     */
    protected $passwordFormats;

    /**
     * @var CredentialsResolverInterface
     */
    protected $resolver;

    /**
     * Constructor
     *
     * @param AuthenticationService $authenticationService
     * @param CredentialsResolverInterface $resolver
     * @param $identityField
     * @param $credentialField
     * @param array $passwordFormats
     */
    public function __construct(
        AuthenticationService $authenticationService,
        CredentialsResolverInterface $resolver,
        $identityField,
        $credentialField,
        array $passwordFormats
    )
    {
        $this->authenticationService = $authenticationService;
        $this->resolver = $resolver;
        $this->identityField = (string)$identityField;
        $this->credentialField = (string)$credentialField;
        $this->passwordFormats = $passwordFormats;
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
        $username = $request->getPost($this->identityField);
        $credential = $request->getPost($this->credentialField);

        if (null === $username) {
            $messages[] = tr('A username is required.');
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, $messages);
        }

        if (null == $credential) {
            $messages[] = tr('A password is required');
            return new Result(Result::FAILURE_CREDENTIAL_INVALID, null, $messages);
        }

        $result = $this->resolver->resolve($username, $credential);
        $authEvent->setAuthenticationResult($result);

        if (!$result->isValid()) {
            return false;
        }

        $resultIdentity = $result->getIdentity();

        // Pass fully discovered identity to AuthenticatedIdentity instance
        $identity = new AuthenticatedIdentity($resultIdentity);

        // Set identity name
        $identity->setName($result->getIdentity());

        return $identity;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthTypeFromRequest(Request $request)
    {
        if (
            $request->isPost()
            && $request->getPost($this->identityField)
            && $request->getPost($this->credentialField)
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
    public function checkCredential($credential, $hash)
    {
        $formats = array_map('strtolower', $this->passwordFormats);

        if (in_array('clear', $formats) && ($credential === md5($hash))) {
            return true;
        }

        if (in_array($formats, ['crypt', 'sha', 'bcrypt']) && Crypt::verify($credential, $hash)) {
            return true;
        }

        if (in_array('clear', $formats) && ($credential === $hash)) {
            return true;
        }

        return false;
    }
}
