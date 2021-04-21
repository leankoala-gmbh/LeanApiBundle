<?php

namespace Leankoala\LeanApiBundle\Auth\Authenticator;

use Leankoala\LeanApiBundle\Entity\UserInterface;
use LeankoalaApi\AuthBundle\Scope\Scope;

interface Authenticator
{
    /**
     * Check of the given action is allowed with the additional meta information
     *
     * @param string $action
     * @param array $metaData
     * @return boolean
     */
    public function isAllowed($action, $metaData = []);

    /**
     * Check if the authenticator was called at least once
     *
     * @return mixed
     */
    public function wasCalled();

    /**
     * Create a token for the given scope that is valid for a given time
     *
     * @param UserInterface $user
     * @param Scope $scope
     * @param integer $timeToLiveInSeconds
     *
     * @return string
     */
    public function createToken(UserInterface $user, Scope $scope, $timeToLiveInSeconds);

    /**
     * @return Scope
     */
    public function getScope();
}
