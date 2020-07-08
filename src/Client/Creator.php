<?php

namespace Leankoala\LeanApiBundle\Client;

use Leankoala\LeanApiBundle\Client\Creator\JavaScript\JavaScriptRepositoryCreator;
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
     * @param string $removePrefix
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
        $endpoints = $this->getAllEndpoints($pathPrefix);

        $files = [];

        foreach ($endpoints as $repositoryName => $repositoryEndpoints) {
            /** @var Endpoint[] $repositoryEndpoints */
            $files = array_merge($repositoryCreator->create($repositoryName, $repositoryEndpoints), $files);
        }

        return $files;
    }

    /**
     * Return a list of endpoint objects representing all API endpoints.
     *
     * @param string $pathPrefix
     *
     * @return Endpoint[]
     */
    private function getAllEndpoints($pathPrefix)
    {
        $collection = $this->router->getRouteCollection();

        $endpoints = [];

        foreach ($collection as $name => $route) {
            /** @var Route $route */
            $path = $route->getPath();

            if (strpos($path, $pathPrefix) === 0) {
                foreach ($route->getMethods() as $method) {
                    if (strtolower($method) != 'options') {
                        try {
                            $schema = $this->getControllerSchema($route->getPath(), $method);
                            if (array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schema)) {
                                $repository = $schema[ParameterRule::REQUEST_REPOSITORY];
                            } else {
                                $repository = 'general';
                            }
                        } catch (\RuntimeException $e) {
                            continue;
                        }
                        $path = $route->getPath();

                        if ($this->removePrefix) {
                            $path = str_replace($this->removePrefix, '', $path);
                        }

                        $endpoints[$repository][] = new Endpoint($method, $path, $schema);
                    }
                }
            }
        }

        return $endpoints;
    }

    private function initLanguages($outputDir, Environment $template)
    {
        $this->languages['javascript'] = new JavaScriptRepositoryCreator($outputDir, $template);
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

        if (array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schemas)) {
            if (!array_key_exists(ParameterRule::REQUEST_REPOSITORY, $schema)) {
                $schema[ParameterRule::REQUEST_REPOSITORY] = $schemas[ParameterRule::REQUEST_REPOSITORY];
            }
        }

        if (!array_key_exists(ParameterRule::METHOD_NAME, $schema)) {
            $schema[ParameterRule::METHOD_NAME] = $schemaName;
        }

        return $schema;
    }

    private function getControllerSpecs($path, $method)
    {
        $collection = $this->router->getRouteCollection();

        foreach ($collection as $name => $route) {
            $routePathPattern = '%^' . preg_replace('/{(.*?)}/', '(.*)', $route->getPath()) . '$%';
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
