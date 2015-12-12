<?php

namespace iMSCP\Core\Auth\Identity;

/**
 * Class AuthenticatedIdentity
 *
 * As it name suggest, this identity allow an authenticated user 'A' to become an user 'B' for a session time.
 *
 * @package iMSCP\Authentication\Identity
 */
class SuidIdentity extends AuthenticatedIdentity
{
    /**
     * @var AuthenticatedIdentity
     */
    protected $realIdentity;

    /**
     * Constructor
     *
     * @param mixed $identity
     * @param mixed $realIdentity
     */
    public function __construct($identity, AuthenticatedIdentity $realIdentity)
    {
        parent::__construct($identity);

        $this->realIdentity = $realIdentity;
    }

    /**
     * @return AuthenticatedIdentity|mixed
     */
    public function getRealIdentity()
    {
        return $this->realIdentity;
    }
}
