<?php

namespace Leankoala\LeanApiBundle\Client;

use Leankoala\LeanApiBundle\Client\Creator\JavaScript\JavaScriptRepositoryCreator;
use Leankoala\LeanApiBundle\Client\Creator\PHP\PhpRepositoryCreator;
use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;
use Leankoala\LeanApiBundle\Client\Exception\BrokenSchemaException;
use Leankoala\LeanApiBundle\Http\ApiRequest;
use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;
use Leankoala\LeanApiBundle\Parameter\ParameterRule;
use LeankoalaApi\CoreBundle\Controller\ApiController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Twig\Environment;

/**
 * Class Creator
 *
 * @package Leankoala\LeanApiBundle\Client
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-07-08
 */
class Creator
{
    /**
     * @var RepositoryCreator[]
     */
    private $languages = [];

    private $removePrefix;

    const PARAM_BUNDLE_NAME = 'bundle_name';

    /**
     * @var Router
     */
    private $router;

    /**
     * Creator constructor.
     *
     * @param Router $router
     * @param Environment $template
     * @param string $outputDir
     * @param string|null $removePrefix
     */
    public function __construct(Router $router, Environment $template, $outputDir, $removePrefix = null)
    {
        $this->router = $router;
        $this->initLanguages($outputDir, $template);
        $this->removePrefix = $removePrefix;
    }

    /**
     * Create a client for the given language.
     *
     * @param $outputLanguage
     * @param $pathPrefix
     * @return array
     */
    public function create($outputLanguage, $pathPrefix)
    {
        $repositoryCreator = $this->getRepositoryCreator($outputLanguage);

        $endpointContainer = $this->getAllEndpoints($pathPrefix);

        $endpoints = $endpointContainer['endpoints'];
        $constants = $endpointContainer['constants'];
        $repositoryMeta = $endpointContainer['repository'];

        $files = [];

        foreach ($endpoints as $repositoryName => $repositoryEndpoints) {
            /** @var Endpoint[] $repositoryEndpoints */
            $files = array_merge($repositoryCreator->create($repositoryName, $repositoryEndpoints, $constants[$repositoryName], $repositoryMeta[$repositoryName]), $files);
        }

        $files = array_merge($files, $repositoryCreator->finish(array_keys($endpoints)));

        sort($files);

        return $files;
    }

    /**
     * Return a list of endpoint objects representing all API endpoints.
     *
     * @param string $pathPrefix
     *
     * @return array
     */
    private function getAllEndpoints($pathPrefix)
    {
        $collection = $this->router->getRouteCollection();

        $endpoints = ['endpoints' => [], 'constants' => [], 'interfaces' => []];

        $knownEndpoints = [];

        foreach ($collection as $name => $route) {
            /** @var Route $route */
            $path = $route->getPath();

            if (strpos($path, $pathPrefix) === 0) {

                foreach ($route->getMethods() as $method) {
                    if (strtolower($method) != 'options') {
                        try {
                            $schemaContainer = $this->getControllerSchema($route->getPath(), $method);

                            $schema = $schemaContainer['schema'];
                            if (array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schema)) {
                                $repository = $schema[ParameterRule::REQUEST_REPOSITORY];
                            } else {
                                if (array_key_exists(self::PARAM_BUNDLE_NAME, $schema)) {
                                    $repository = $schema[self::PARAM_BUNDLE_NAME];
                                } else {
                                    $repository = 'general';
                                }
                            }

                            if (array_key_exists(ParameterRule::REQUEST_PRIVATE, $schema)) {
                                if ($schema[ParameterRule::REQUEST_PRIVATE] === true) {
                                    continue;
                                }
                            }

                            if (array_key_exists(ParameterRule::REPOSITORY_INTERFACE, $schema)) {
                                $interface = $schema[ParameterRule::REPOSITORY_INTERFACE];
                            } else {
                                $interface = false;
                            }

                            $identifier = $repository . '::' . $schema[ParameterRule::METHOD_NAME];

                            if (in_array($identifier, $knownEndpoints)) {
                                echo "  WARNING: Duplicate endpoint " . $identifier . " (path: " . $route->getPath() . ")\n\n";
                                continue;
                            } else {
                                $knownEndpoints[] = $identifier;
                            }
                        } catch (\RuntimeException $e) {
                            continue;
                        }
                        $path = $route->getPath();

                        if ($this->removePrefix) {
                            $path = str_replace($this->removePrefix, '', $path);
                        }

                        if (!array_key_exists('constants', $endpoints)
                            || !array_key_exists($repository, $endpoints['constants'])) {
                            $endpoints['constants'][$repository] = [];
                        }

                        $endpoints['endpoints'][$repository][] = new Endpoint($method, $path, $schema);
                        $endpoints['constants'][$repository] = array_merge($endpoints['constants'][$repository], $schemaContainer['constants']);

                        $endpoints['repository'][$repository] = ['interface' => $interface];
                    }
                }
            }
        }

        return $endpoints;
    }

    /**
     * Initialize all possible output languages.
     *
     * @param string $outputDir
     * @param Environment $template
     */
    private function initLanguages($outputDir, Environment $template)
    {
        $this->languages['javascript'] = new JavaScriptRepositoryCreator($outputDir, $template);
        $this->languages['php'] = new PhpRepositoryCreator($outputDir, $template);
    }


    /**
     * @param $language
     * @return RepositoryCreator
     */
    private function getRepositoryCreator($language)
    {
        return $this->languages[$language];
    }

    protected function getControllerSchema($path, $method)
    {
        $controllerSpecs = $this->getControllerSpecs($path, $method);
        return $this->getSchema($controllerSpecs);
    }

    /**
     * @param $controllerSpecs
     *
     * @return mixed
     *
     * @throws BrokenSchemaException
     * @throws \ReflectionException
     */
    private function getSchema($controllerSpecs)
    {
        $reflectionMethod = new \ReflectionMethod($controllerSpecs['controller'], $controllerSpecs['action']);
        $doc = $reflectionMethod->getDocComment();

        preg_match('#' . ApiRequest::ANNOTATION_API_SCHEMA . '(.*?)\n#s', $doc, $annotations);

        if (count($annotations) == 0) {
            throw new \RuntimeException('The given action "' . $controllerSpecs['controller'] . '::' . $controllerSpecs['action']
                . '" does not have a @' . ApiRequest::ANNOTATION_API_SCHEMA . ' annotation.');
        }

        $schemaName = trim($annotations[1]);

        /** @var ApiController $controller */
        $controller = new $controllerSpecs['controller']();
        $schemas = $controller->getSchemas();

        if (!array_key_exists($schemaName, $schemas)) {
            throw new BrokenSchemaException('The action ' . $controllerSpecs['action']
                . ' in controller ' . $controllerSpecs['controller']
                . ' has a schema named ' . $schemaName . ' but it is not defined.');
        }

        $schema = $schemas[$schemaName];

        if (preg_match("^\\\(.*?)Bundle\\\^", $controllerSpecs['controller'], $matches)) {
            $schema[self::PARAM_BUNDLE_NAME] = strtolower($matches[1]);
        }

        if (array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schemas)) {
            if (!array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schema)) {
                $schema[ParameterRule::REQUEST_REPOSITORY] = $schemas[ParameterRule::REQUEST_REPOSITORY];
            }
        }
        
        if (array_key_exists(ParameterRule::REPOSITORY_INTERFACE, $schemas)) {
            $schema[ParameterRule::REPOSITORY_INTERFACE] = $schemas[ParameterRule::REPOSITORY_INTERFACE];
        }

        if (!array_key_exists(ParameterRule::METHOD_NAME, $schema)) {
            $schema[ParameterRule::METHOD_NAME] = $schemaName;
        }

        $constants = $this->getConstants($schemas);

        return [
            'schema' => $schema,
            'constants' => $constants
        ];
    }

    private function getConstants($schemas)
    {
        if (array_key_exists(ParameterRule::REPOSITORY_CONSTANT_FILE, $schemas)) {
            $oClass = new \ReflectionClass($schemas[ParameterRule::REPOSITORY_CONSTANT_FILE]);
            return $oClass->getConstants();
        } else {
            return [];
        }
    }

    private function getControllerSpecs($path, $method)
    {
        $collection = $this->router->getRouteCollection();

        foreach ($collection as $name => $route) {
            $routePathPattern = '%^' . preg_replace('/\{(.*?)\}/', '[^\/]+', $route->getPath()) . '$%';
            $routePathPattern = str_replace('/', '\/', $routePathPattern);

            if (preg_match($routePathPattern, $path) && in_array($method, $route->getMethods())) {

                $controllerSpec = $route->getDefault('_controller');
                [$controllerName, $actionName] = explode('::', $controllerSpec);

                return [
                    'controller' => $controllerName,
                    'action' => $actionName
                ];
            }
        }

        throw new BadParameterException('The given path does not fit any routes. Please check the path and the method.');
    }
}
