<?php

namespace Leankoala\LeanApiBundle\Auth\Authenticator;

use Leankoala\LeanApiBundle\Auth\Scope\Scope;
use Leankoala\LeanApiBundle\Entity\UserInterface;

/**
 * Class SimpleTokenAuthenticator
 *
 * The simple token authenticator just checks for a single token within the request payload. There
 * are no auth rules only allowed all or disallowed all.
 *
 * @package LeankoalaApi\AuthBundle\Authenticator
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class SimpleTokenAuthenticator implements Authenticator
{
    private $masterToken;
    private $userToken;
    private $wasCalled = false;

    /**
     * SimpleTokenAuthenticator constructor.
     *
     * @param string $masterToken
     */
    public function __construct($masterToken)
    {
        $this->masterToken = $masterToken;
    }

    /**
     * @inheritDoc
     */
    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;
    }

    /**
     * @inheritDoc
     */
    public function isAllowed($action = null, $metaData = [])
    {
        $this->wasCalled = true;

        if (is_null($action)) {
            return true;
        }

        if (!$this->userToken) {
            return false;
        }

        return $this->userToken === $this->masterToken;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    /**
     * @inheritDoc
     */
    public function createToken(UserInterface $user, Scope $scope, $timeToLiveInSeconds): string
    {
        return $this->masterToken;
    }

    /**
     * IMPORTANT this simple authenticator does not support this function and
     * always returns an empty array.
     *
     * @inheritDoc
     */
    public function getScope()
    {
        return new Scope();
    }
}
