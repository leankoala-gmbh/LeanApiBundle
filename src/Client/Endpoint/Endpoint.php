<?php

namespace Leankoala\LeanApiBundle\Client\Endpoint;

use Leankoala\LeanApiBundle\Parameter\ParameterRule;
use Leankoala\LeanApiBundle\Parameter\ParameterType;

/**
 * Class Endpoint
 *
 * @package Leankoala\LeanApiBundle\Client\Endpoint
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-07-08
 */
class Endpoint
{
    private $method;
    private $path;
    private $schema;

    /**
     * Endpoint constructor.
     *
     * @param string $method
     * @param string $path
     * @param array $schema
     */
    public function __construct($method, $path, $schema)
    {
        $this->method = $method;
        $this->path = $path;
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getPathParameters()
    {
        $parameters = [];
        preg_match_all('^{(.*?)}^', $this->getPath(), $matches);

        foreach ($matches[1] as $parameter) {
            $parameters[] = $parameter;
        }

        return $parameters;
    }

    public function getName()
    {
        return $this->schema[ParameterRule::METHOD_NAME];
    }

    public function getRequiredRequestParameters()
    {
        $parameters = [];

        foreach ($this->schema as $name => $parameter) {
            if (is_array($parameter) && array_key_exists(ParameterRule::REQUIRED, $parameter) && $parameter[ParameterRule::REQUIRED]) {
                $parameters[] = $name;
            }
        }

        return $parameters;
    }

    public function getDescription()
    {
        if (array_key_exists(ParameterRule::REQUEST_DESCRIPTION, $this->schema)) {
            return $this->schema[ParameterRule::REQUEST_DESCRIPTION];
        } else {
            return '';
        }
    }

    public function getParameters()
    {
        $parameters = [];

        foreach ($this->schema as $name => $parameter) {
            if (!is_array($parameter)) {
                continue;
            }

            $currentParameter = [
                'name' => $name,
            ];

            if (array_key_exists(ParameterRule::DESCRIPTION, $parameter)) {
                $currentParameter['description'] = $parameter[ParameterRule::DESCRIPTION];

            } else {
                $currentParameter['description'] = "";
            }

            if (array_key_exists(ParameterRule::REQUIRED, $parameter)) {
                $currentParameter['required'] = $parameter[ParameterRule::REQUIRED];
            } else {
                $currentParameter['required'] = false;
            }

            if (array_key_exists(ParameterRule::TYPE, $parameter)) {
                $currentParameter['type'] = $parameter[ParameterRule::TYPE];
            } else {
                $currentParameter['type'] = 'mixed';
            }

            if (array_key_exists(ParameterRule::ENTITY, $parameter)) {
                $currentParameter['type'] = ParameterType::INTEGER;
            }

            $parameters[] = $currentParameter;
        }

        return $parameters;
    }

    /**
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }
}
