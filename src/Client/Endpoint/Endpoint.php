<?php

namespace Leankoala\LeanApiBundle\Client\Endpoint;

use Leankoala\LeanApiBundle\Parameter\ParameterRule;

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

        foreach($this->schema as $name => $parameter) {
            if(is_array($parameter) && array_key_exists(ParameterRule::REQUIRED, $parameter)) {
                $parameters[] = $name;
            }
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
