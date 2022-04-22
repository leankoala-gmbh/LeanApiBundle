<?php

namespace Leankoala\LeanApiBundle\Parameter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;
use Leankoala\LeanApiBundle\Parameter\Exception\NotFoundException;
use Leankoala\LeanApiBundle\Parameter\Exception\ParameterBagException;
use Leankoala\LeanApiBundle\Parameter\Exception\ValidationFailedException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ParameterBag
 *
 * @package Leankoala\LeanApiBundle\Parameter
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * created 2020-03-01
 */
class ParameterBag implements \Countable
{
    /**
     * List of allowed rules in schema
     *
     * @var array
     */
    private $knownRules = [
        ParameterRule::DEFAULT,
        ParameterRule::REQUIRED,
        ParameterRule::TYPE,
        ParameterRule::DESCRIPTION,
        ParameterRule::OPTIONS,
        ParameterRule::ENTITY,
        ParameterRule::ALIAS,
        ParameterRule::GROUP,
        ParameterRule::CONSTRAINTS
    ];

    /**
     * The default rule set that is automatically merged with the given one.
     *
     * @var array
     */
    private $defaultRules = [
        ParameterRule::REQUIRED => false
    ];

    /**
     * The validated and processed parameters.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * The schema the parameters are validated against.
     *
     * @var array
     */
    private $schema = [];

    /**
     * The database layer
     *
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * ParameterBag constructor.
     *
     * @param array $parameters
     * @param array $schema
     * @param RegistryInterface $doctrine
     *
     * @throws ParameterBagException
     */
    public function __construct($parameters, $doctrine, $schema = [])
    {
        $this->doctrine = $doctrine;
        $this->parameters = $parameters;

        if (array_key_exists(ParameterRule::REQUEST_DESCRIPTION, $schema)) {
            unset($schema[ParameterRule::REQUEST_DESCRIPTION]);
        }

        if (array_key_exists(ParameterRule::REQUEST_WITHOUT_TOKEN, $schema)) {
            unset($schema[ParameterRule::REQUEST_WITHOUT_TOKEN]);
        }

        if (array_key_exists(ParameterRule::REQUEST_PRIVATE, $schema)) {
            unset($schema[ParameterRule::REQUEST_PRIVATE]);
        }

        if (array_key_exists(ParameterRule::RETURN, $schema)) {
            unset($schema[ParameterRule::RETURN]);
        }

        if (array_key_exists(ParameterRule::REQUEST_REFRESH_ACCESS, $schema)) {
            unset($schema[ParameterRule::REQUEST_REFRESH_ACCESS]);
        }

        $this->schema = $schema;

        $this->assertSchemaValid();
        $this->processParameters();
    }

    /**
     * Check for unknown fields in schema. See $knownRules for valid fields.
     */
    private function assertSchemaValid()
    {
        foreach ($this->schema as $key => $rules) {
            foreach ($rules as $ruleType => $value) {
                if (!in_array($ruleType, $this->knownRules)) {
                    throw new BadParameterException('Unknown rule field "' . $ruleType . '" in given schema.');
                }
            }
        }
    }

    /**
     * Set the parameters var. Check if all mandatory parameters are set and set
     * default values if not.
     */
    private function processParameters()
    {
        foreach ($this->schema as $identifier => $rules) {
            $rules = array_merge($this->defaultRules, $rules);

            /** THIS MUST BE THE FIRST HANDLER */
            if (array_key_exists(ParameterRule::ALIAS, $rules)) {
                $this->initAliasTable($identifier, $rules[ParameterRule::ALIAS]);
            }

            if ($rules[ParameterRule::REQUIRED] && !$this->hasParameter($identifier)) {
                if ($this->hasParameter(strtolower($identifier))) {
                    throw new BadParameterException('Required field "' . $identifier . '" is missing. But lower case version of the parameter found. Parameters are case sensitive.');
                } else {
                    throw new BadParameterException('Required field "' . $identifier . '" is missing.');
                }
            }

            if (array_key_exists(ParameterRule::DEFAULT, $rules) && !$this->hasParameter($identifier)) {
                $this->parameters[$identifier] = $rules[ParameterRule::DEFAULT];
            }

            if (array_key_exists(ParameterRule::GROUP, $rules)) {
                $rules = $this->processGroup($rules[ParameterRule::GROUP], $identifier);
            }

            if (array_key_exists(ParameterRule::TYPE, $rules) && array_key_exists($identifier, $this->parameters)) {
                $this->assertCorrectType($this->getParameter($identifier), $rules[ParameterRule::TYPE], $identifier);
            }

            if (array_key_exists(ParameterRule::OPTIONS, $rules) && array_key_exists($identifier, $this->parameters)) {
                $this->assertInOptions($this->getParameter($identifier), $rules[ParameterRule::OPTIONS], $identifier);
            }

            if (array_key_exists(ParameterRule::ENTITY, $rules) && array_key_exists($identifier, $this->parameters)) {
                $this->parameters[$identifier] = $this->getEntityByParameter($this->getParameter($identifier), $rules[ParameterRule::ENTITY]);
            }

            if (array_key_exists(ParameterRule::CONSTRAINTS, $rules)) {
                if ($this->hasParameter($identifier)) {
                    $this->assertConstraints($rules[ParameterRule::CONSTRAINTS], $identifier, $this->getParameter($identifier));
                }
            }
        }
    }

    /**
     * Process the group rule.
     *
     * It is possible that one parameter can be an entity or an string or others. The group
     * rule is a way to implement that.
     *
     * Returns the updated schema.
     *
     * @param array $groups
     * @param string $identifier
     *
     * @return array
     */
    private function processGroup($groups, $identifier)
    {
        if (!is_array($groups)) {
            throw new BadParameterException('The group rule must be an array.');
        }

        if (!$this->hasParameter($identifier)) {
            return [];
        }

        $type = gettype($this->getParameter($identifier));

        if (!in_array($type, array_keys($groups))) {
            throw new BadParameterException('The given parameter "' . $identifier . '" is type "' . $type . '". Allowed are ' . implode(', ', array_keys($groups)) . '.');
        }

        return $groups[$type];
    }

    /**
     * Check if the given parameter has the correct type (integer, boolean, string, ...)
     *
     * @param string $type
     * @param mixed $value
     * @param string $identifier
     */
    private function assertCorrectType($value, $type, $identifier)
    {
        try {
            ParameterType::assertCorrectType($type, $value);
        } catch (BadParameterException $e) {
            throw new BadParameterException('Unable to validate "' . $identifier . '". ' . $e->getMessage());
        }
    }

    /**
     * Check if the parameter fulfills the mandatory constraints.
     *
     * @param $constraints
     * @param $identifier
     * @param $value
     */
    private function assertConstraints($constraints, $identifier, $value)
    {
        foreach ($constraints as $type => $option) {
            try {
                ParameterConstraint::assertConstraint($type, $option, $value);
            } catch (ValidationFailedException $e) {
                throw new BadParameterException('Unable to validate API parameter "' . $identifier . '": ' . $e->getMessage());
            }
        }
    }

    /**
     * Check if the given parameters value is in the list of allowed values.
     *
     * @param string $value
     * @param array $options
     * @param string $identifier
     */
    private function assertInOptions($value, $options, $identifier)
    {
        if (!in_array($value, $options)) {
            throw new BadParameterException('The given parameter "' . $identifier . '" value (' . $value . ') is not allowed. Allowed values are: ' . implode(', ', $options));
        }
    }

    /**
     * Initialize the alias table
     *
     * @param string $identifier
     * @param string[] $aliases
     */
    private function initAliasTable($identifier, $aliases)
    {
        if (!is_array($aliases)) {
            throw new BadParameterException('Aliases must be defined as array of strings.');
        }

        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $this->parameters)) {
                $this->parameters[$identifier] = $this->parameters[$alias];
            }
        }
    }

    /**
     * Get a parameters value.
     *
     * If the key was defined as ENTITY a database entity will be returned.
     *
     * @param string $identifier
     * @return mixed
     */
    public function getParameter($identifier)
    {
        if (!$this->hasParameter($identifier)) {
            throw new BadParameterException('The given parameter (' . $identifier . ') does not exist.');
        }

        return $this->parameters[$identifier];
    }

    /**
     * True if the parameter exists.
     *
     * @param string $identifier
     * @return bool
     */
    public function hasParameter($identifier)
    {
        return array_key_exists($identifier, $this->parameters);
    }

    /**
     * Create entity class by parameter attributes.
     *
     * If the parameter is type of "list" an array of entities will be returned.
     *
     * @param string $parameter
     * @param string $class
     *
     * @return object|object[]
     */
    private function getEntityByParameter($parameter, $class)
    {
        if (is_array($parameter)) {
            $entities = [];
            foreach ($parameter as $singleParameter) {
                $entity = $this->doctrine->getRepository($class)->find($singleParameter);

                if (is_null($entity)) {
                    throw new NotFoundException('No entity (' . $class . ') with id ' . $singleParameter . ' found.');
                }

                $entities[] = $entity;
            }

            return $entities;
        } else {
            $entity = $this->doctrine->getRepository($class)->find($parameter);
            if (is_null($entity)) {
                throw new NotFoundException('No entity (' . $class . ') with id ' . $parameter . ' found.');
            }
            return $entity;
        }
    }

    /**
     * Return all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the number of elements in the parameter bag
     *
     * @return int
     */
    public function count()
    {
        return count($this->parameters);
    }
}
