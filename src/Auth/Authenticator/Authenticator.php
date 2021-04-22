<?php

namespace Leankoala\LeanApiBundle\Auth\Authenticator;

use Leankoala\LeanApiBundle\Auth\Scope\Scope;
use Leankoala\LeanApiBundle\Entity\UserInterface;

/**
 * Interface Authenticator
 *
 * @package Leankoala\LeanApiBundle\Auth\Authenticator
 */
interface Authenticator
{
    /**
     * Check of the given action is allowed with the additional meta information
     *
     * @param string $action
     * @param array $metaData
     * @return boolean
     */
    public function isAllowed($action, $metaData = []): bool;

    /**
     * Check if the authenticator was called at least once
     *
     * @return mixed
     */
    public function wasCalled(): bool;

    /**
     * Create a token for the given scope that is valid for a given time
     *
     * @param UserInterface $user
     * @param Scope $scope
     * @param integer $timeToLiveInSeconds
     *
     * @return string
     */
    public function createToken(UserInterface $user, Scope $scope, $timeToLiveInSeconds): string;

    /**
     * Return the scope.
     *
     * @return Scope
     */
    public function getScope(): Scope;
}
