<?php

namespace Leankoala\LeanApiBundle\Auth\Authenticator;

use Koalamon\IncidentDashboardBundle\Entity\User;
use Leankoala\LeanApiBundle\Entity\UserInterface;
use LeankoalaApi\AuthBundle\Scope\Scope;
use LeankoalaApi\CoreBundle\Business\Exception\ForbiddenException;

/**
 * Class OpenDoorAuthenticator
 *
 * This authenticator allows everybody to come in if the urls host is localhost.
 *
 * WARNING: do not use this in production !!!
 *
 * @package LeankoalaApi\AuthBundle\Authenticator
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class OpenDoorAuthenticator implements Authenticator
{
    /**
     * @inheritDoc
     */
    public function setUserToken($userToken)
    {

    }

    /**
     * @inheritDoc
     */
    public function isAllowed($action = null, $metaData = [])
    {
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
            throw new ForbiddenException('You are using the OpenDoorAuthenticator in a non localhost (development) environment. That is strictly forbidden as all access rules are dropped. Please use the JwtAuthenticator instead.');
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function createToken(UserInterface $user, Scope $scope, $timeToLiveInSeconds)
    {
        return 'comeInTheDoorIsAlwaysOpen_userid_' . $user->getId() . '_expire_' . $timeToLiveInSeconds;
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
