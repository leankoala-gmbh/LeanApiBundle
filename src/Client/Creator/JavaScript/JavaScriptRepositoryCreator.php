<?php

namespace Leankoala\LeanApiBundle\Client\Creator\JavaScript;

use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;
use Twig\Environment;

/**
 * Class JavaScriptRepositoryCreator
 *
 * @package Leankoala\LeanApiBundle\Client\Creator\JavaScript
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * created 2020-07-01
 */
class JavaScriptRepositoryCreator implements RepositoryCreator
{
    private $outputDirectory;
    private $template;

    public function __construct($outputDirectory, Environment $template)
    {
        $this->outputDirectory = $outputDirectory;
        $this->template = $template;

        if (!file_exists($this->outputDirectory . 'Entities')) {
            mkdir($this->outputDirectory . 'Entities', 0777, true);
        }
    }

    /**
     * @inheritDoc
     */
    public function create($repositoryName, $endpoints)
    {
        $jsDocs = [];

        foreach ($endpoints as $endpoint) {
            $jsDocs[$endpoint->getName()] = $this->getJsDoc($endpoint);
        }

        $className = $this->getClassName($repositoryName);

        $classContent = $this->template->render(__DIR__ . '/Snippets/repository.js.twig',
            [
                'repository' => $repositoryName,
                'endpoints' => $endpoints,
                'jsDocs' => $jsDocs,
                'className' => $className
            ]);

        $classContent = str_replace('{Integer}', '{Number}', $classContent);
        $classContent = str_replace('{Mixed}', '{*}', $classContent);
        $classContent = str_replace('{List}', '{Array}', $classContent);

        $filename = $this->outputDirectory . 'Entities/' . $className . '.js';

        file_put_contents($filename, $classContent);

        return [
            $filename
        ];
    }

    private function getClassName($repository, $withSuffix = true)
    {
        // var_dump($repository);

        $class = ucfirst($repository);
        if ($withSuffix) {
            $class .= 'Repository';
        }

        return $class;
    }

    /**
     * @inheritDoc
     */
    public function finish($repositories)
    {
        $repositoryClasses = [];

        foreach ($repositories as $repository) {
            $repositoryClasses[] = $this->getClassName($repository, false);
        }

        $classContent = $this->template->render(__DIR__ . '/Snippets/collection.js.twig',
            ['repositories' => $repositoryClasses]);

        $filename = $this->outputDirectory . 'RepositoryCollection.js';

        file_put_contents($filename, $classContent);

        return [
            $filename
        ];
    }

    /**
     * Generate the js docs for the given endpoint
     *
     * @param Endpoint $endpoint
     * @return string
     */
    private function getJsDoc(Endpoint $endpoint)
    {
        $jsDoc = "  /**\n";

        if ($endpoint->getDescription()) {

            $jsDoc .= $this->getIntendedDescription($endpoint->getDescription());

            if (count($endpoint->getPathParameters()) > 0 || count($endpoint->getParameters()) > 0) {
                $jsDoc .= "\n   *";
            }

            $jsDoc .= "\n";
        }

        foreach ($endpoint->getPathParameters() as $parameter) {
            $jsDoc .= "   * @param " . $parameter . "\n";
        }

        $parameters = $endpoint->getParameters();
        $jsDoc .= "   * @param {Object} args\n";
        if (count($parameters) > 0) {
            foreach ($parameters as $parameter) {
                $paramType = "@param {" . ucfirst($parameter["type"]) . "} args." . $parameter['name'] . ' ';
                $optional = $this->getOptionString($parameter);
                $jsDoc .= $this->getIntendedDescription($parameter['description'], '   * ' . $paramType, strlen($paramType)) . $optional . "\n";
            }
        }

        $jsDoc .= "   */";

        return $jsDoc;
    }

    private function getOptionString($parameter)
    {
        $options = '';

        if (array_key_exists('default', $parameter)) {
            $default = $parameter['default'];
            if ($default === true) {
                $default = 'true';
            } else if ($default === false) {
                $default = 'false';
            }
            $options = 'default: ' . $default;
        } else if (!$parameter['required']) {
            $options = 'optional';
        }

        if ($options) {
            $options = ' (' . $options . ')';
        }

        return $options;
    }


    /**
     * Return the description with the correct indention
     *
     * @param string $description
     * @param string $prefix
     * @param int $intend
     * @return string
     */
    private function getIntendedDescription($description, $prefix = '   * ', $intend = 0)
    {
        $descRows = explode("\n", wordwrap($description, 100 - $intend));
        $count = 0;

        $jsDoc = '';

        foreach ($descRows as $descRow) {
            $count++;
            if ($count === 1) {
                $jsDoc .= $prefix . $descRow;
            } else {
                $jsDoc .= "   * " . str_repeat(" ", max(0, $intend)) . $descRow;
            }
            if ($count != count($descRows)) {
                $jsDoc .= "\n";
            }
        }

        return $jsDoc;
    }
}
