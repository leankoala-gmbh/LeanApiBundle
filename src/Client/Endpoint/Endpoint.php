<?php

namespace Leankoala\LeanApiBundle\Client\Endpoint;

use Leankoala\LeanApiBundle\Client\Creator\TypeScript\TypeScriptRepositoryCreator;
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

    const PARAMETER_FIELD_REQUIRED = 'required';
    const PARAMETER_FIELD_NAME = 'name';

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

    public function hasPathParameters()
    {
        return count($this->getPathParameters()) > 0;
    }

    public function getName($firstLetterUppcase = false)
    {
        if ($firstLetterUppcase) {
            return ucfirst($this->schema[ParameterRule::METHOD_NAME]);
        } else {
            return $this->schema[ParameterRule::METHOD_NAME];
        }
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

    /**
     * Return the result the API result type.
     *
     * @return false|mixed
     *
     * @since 2020-11-03
     */
    public function getResultType()
    {
        if (array_key_exists(ParameterRule::RETURN, $this->schema)) {
            return $this->schema[ParameterRule::RETURN];
        } else {
            return false;
        }
    }

    public function hasResultType()
    {
        return !($this->getResultType() === false);
    }

    public function getParameters()
    {
        $parameters = [];

        foreach ($this->schema as $name => $parameter) {
            if (!is_array($parameter)) {
                continue;
            }

            if (in_array($name, [ParameterRule::RETURN])) {
                continue;
            }

            if ($name === ParameterRule::RESULT_FORMAT) {
                continue;
            }

            $currentParameter = [
                self::PARAMETER_FIELD_NAME => $name,
            ];

            if (array_key_exists(ParameterRule::DESCRIPTION, $parameter)) {
                $currentParameter['description'] = $parameter[ParameterRule::DESCRIPTION];

            } else {
                $currentParameter['description'] = "";
            }

            if (array_key_exists(ParameterRule::REQUIRED, $parameter)) {
                $currentParameter[self::PARAMETER_FIELD_REQUIRED] = $parameter[ParameterRule::REQUIRED];
            } else {
                $currentParameter[self::PARAMETER_FIELD_REQUIRED] = false;
            }

            if (array_key_exists(ParameterRule::TYPE, $parameter)) {
                $currentParameter['type'] = $parameter[ParameterRule::TYPE];
            } else {
                $currentParameter['type'] = 'mixed';
            }

            if (array_key_exists(ParameterRule::DEFAULT, $parameter)) {
                $currentParameter['default'] = $parameter[ParameterRule::DEFAULT];
            }

            if (array_key_exists(ParameterRule::ENTITY, $parameter)) {
                $currentParameter['type'] = ParameterType::INTEGER;
            }

            if (array_key_exists(ParameterRule::OPTIONS, $parameter)) {
                $currentParameter[ParameterRule::OPTIONS] = $parameter[ParameterRule::OPTIONS];
            }

            $parameters[] = $currentParameter;
        }

        return $parameters;
    }

    public function hasParameters()
    {
        return count($this->getParameters()) > 0;
    }

    /**
     * Returns true if the usage of this method forces a access token refresh.
     *
     * @return bool
     */
    public function forceAccessRefresh()
    {
        if (array_key_exists(ParameterRule::REQUEST_REFRESH_ACCESS, $this->schema)) {
            return $this->schema[ParameterRule::REQUEST_REFRESH_ACCESS];
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getSchema(): array
    {
        return $this->schema;
    }
}
