<?php

namespace Leankoala\LeanApiBundle\Auth\Scope;

use App\Entity\User;
use Leankoala\LeanApiBundle\Business\ScopeAwareBusinessHandler;
use Leankoala\LeanApiBundle\Entity\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ScopeHandler
 *
 * Collect scope (access rights) for a given user.
 *
 * @package LeankoalaApi\AuthBundle\Scope
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class ScopeHandler
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ScopeAwareBusinessHandler
     */
    private $businessHandlers = [];

    /**
     * ScopeHandler constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Return the user scope for a given user.
     *
     * This function collects the scope for the user and the user role.
     *
     * @param User $user
     * @return Scope
     */
    public function getScopeByUser(UserInterface $user)
    {
        $scope = new Scope();

        $scope = $this->getRolesForUser($scope, $user);

        return $scope;

        $userRoles = $user->getUserRoles();

        foreach ($userRoles as $userRole) {
            $scope = $this->getRolesForUserRole($scope, $userRole);
        }

        return $scope;
    }

    /**
     * Collect access scope for the given user.
     *
     * @param Scope $scope
     * @param User $user
     * @return Scope
     */
    private function getRolesForUser(Scope $scope, UserInterface $user)
    {
        foreach ($this->getBusinessHandlers() as $businessHandler) {
            $scope->merge($businessHandler->getUserScope($user));
        }

        return $scope;
    }

    /**
     * Collect access scope for the given user role.
     *
     * @param Scope $scope
     * @param UserRole $role
     *
     * @return Scope
     */
    private function getRolesForUserRole(Scope $scope, UserRole $role)
    {
        foreach ($this->getBusinessHandlers() as $businessHandler) {
            $scope->merge($businessHandler->getScope($role));
        }

        return $scope;
    }

    /**
     * Get a list of all Leankoala business handlers
     *
     * @return ScopeAwareBusinessHandler[]
     */
    private function getBusinessHandlers()
    {
        return $this->businessHandlers;

        // den teil heben wir uns auf für leankoala
        $businessHandlers = [];

        try {
            $businessHandlers[] = $this->get('leankoala_business.company');
            $businessHandlers[] = $this->get('leankoala_business.subscription');
            $businessHandlers[] = $this->get('leankoala_business.system');
            $businessHandlers[] = $this->get('leankoala_business.component');
            $businessHandlers[] = $this->get('leankoala_business.project');
            $businessHandlers[] = $this->get('leankoala_business.user');
            $businessHandlers[] = $this->get('leankoala_business.provider');

            // Incident Bundle
            $businessHandlers[] = $this->get('leankoala_business.incident');

            // Websocket
            $businessHandlers[] = $this->get('leankoala_business.websocket');

            // Memory
            $businessHandlers[] = $this->get('leankoala_business.memory');

        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to create business handler. Sometimes the DIC must be rebuild. Try running the update script. ' . $e->getMessage());
        }

        return $businessHandlers;
    }

    public function addBusinessHandler(ScopeAwareBusinessHandler $businessHandler)
    {
        $this->businessHandlers[] = $businessHandler;
    }

    /**
     * Shortcut for using the DIC
     *
     * @param string $key
     *
     * @return BaseBusinessHandler
     */
    private function get($key)
    {
        /** @var BaseBusinessHandler $businessHandler */
        $businessHandler = $this->container->get($key);
        return $businessHandler;
    }
}
