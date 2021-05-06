<?php

namespace Leankoala\LeanApiBundle\Documentation;

use Leankoala\LeanApiBundle\Http\ApiRequest;
use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;
use LeankoalaApi\CoreBundle\Controller\ApiController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class BaseCreator
 *
 * @package Leankoala\LeanApiBundle\Documentation
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-04-24
 */
abstract class BaseCreator implements Creator
{
    /**
     * @var Router
     */
    private $router;

    /**
     * MarkdownCreator constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    protected function getControllerSchema($path, $method)
    {
        $controllerSpecs = $this->getControllerSpecs($path, $method);
        return $this->getSchema($controllerSpecs);
    }

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

        return $schemas[$schemaName];
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
