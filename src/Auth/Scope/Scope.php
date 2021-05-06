<?php

namespace Leankoala\LeanApiBundle\Auth\Scope;

use LeankoalaApi\CoreBundle\Business\Exception\BadParameterException;

/**
 * Class Scope
 *
 * The scope class is used to bundle access rights within one object.
 *
 * @package LeankoalaApi\AuthBundle\Scope
 */
class Scope
{
    /**
     * These are the targets used to define a scope.
     */
    const TARGET_USER = 'user';
    const TARGET_COMPANY = 'company';
    const TARGET_PROJECT = 'project';
    const TARGET_SYSTEM = 'system';
    const TARGET_PROVIDER = 'provider';
    const TARGET_GLOBAL = '';

    /**
     * List of valid targets
     *
     * @var string[]
     */
    private $validTargets = [
        self::TARGET_PROJECT,
        self::TARGET_PROVIDER,
        self::TARGET_GLOBAL,
        self::TARGET_USER
    ];

    private $scope = [];

    /**
     * Add a scope rule.
     *
     * The $value parameter can be an object but must implement a getId method.
     *
     * @param string $scope
     * @param string $target
     * @param mixed $value
     *
     * @example add('user.create', 'user', '1')
     *
     */
    public function add($scope, $target, $value = "")
    {
        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                $value = $value->getId();
            } else {
                throw new BadParameterException("The given value is an object but does not provide a getId() method.");
            }
        }

        if (!array_key_exists($scope, $this->scope)) {
            $this->scope[$scope] = [];
        }

        if ($target == self::TARGET_GLOBAL) {
            $this->scope[$scope] = new \stdClass;
        } else {
            if (!array_key_exists($target, $this->scope[$scope])) {
                $this->scope[$scope][$target] = [];
            }

            if (!in_array($value, $this->scope[$scope][$target])) {
                $this->scope[$scope][$target][] = $value;
            }
        }
    }

    /**
     * Return a list sub scopes as array.
     *
     * @param string $scope
     * @param string $target
     *
     * @return array
     */
    public function get($scope, $target)
    {
        if (!array_key_exists($scope, $this->scope) || !array_key_exists($target, $this->scope[$scope])) {
            return [];
        }
        return $this->scope[$scope][$target];
    }

    /**
     * Merge the new scope with the current
     *
     * @param Scope $scope
     */
    public function merge(Scope $scope)
    {
        $this->scope = array_merge_recursive($this->scope, $scope->toArray());
    }

    /**
     * Return this scope as arrays
     *
     * @return string[]
     */
    public function toArray()
    {
        return $this->scope;
    }

    /**
     * Create a Scope object by an array.
     *
     * @param array $array
     * @return Scope
     */
    static public function fromArray($array)
    {
        $scope = new self();


        // $array can be an array or stdObj
        $scope->scope = json_decode(json_encode($array), true);

        return $scope;
    }
}
