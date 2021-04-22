<?php

namespace Leankoala\LeanApiBundle\Business;

use Leankoala\LeanApiBundle\Entity\UserInterface;

/**
 * Interface ScopeAwareBusinessHandler
 */
interface ScopeAwareBusinessHandler
{
    public function getUserScope(UserInterface $user);
}
