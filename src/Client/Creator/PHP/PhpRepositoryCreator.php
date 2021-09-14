<?php

namespace Leankoala\LeanApiBundle\Client\Creator\PHP;

use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;
use Leankoala\LeanApiBundle\Parameter\ParameterRule;
use Twig\Environment;

/**
 * Class PhpRepositoryCreator
 *
 * @package Leankoala\LeanApiBundle\Client\Creator\PHP
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * created 2020-07-01
 */
class PhpRepositoryCreator implements RepositoryCreator
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

        if (!file_exists($this->outputDirectory . 'Constants')) {
            mkdir($this->outputDirectory . 'Constants', 0777, true);
        }
    }

    /**
     * @inheritDoc
     */
    public function create($repositoryName, $endpoints, $constants = [], $repositoryMeta = [])
    {
        $jsDocs = [];
        $typeDefs = "";

        foreach ($endpoints as $endpoint) {
            $jsDocs[$endpoint->getName()] = $this->getJsDoc($endpoint);
            $typeDefs .= $this->getResultTypeDefinitionJsDoc($endpoint);
        }

        $className = $this->getClassName($repositoryName);

        $files = [];

        if (count($constants) > 0) {

            $constantContent = $this->template->render('PHP/Snippets/constants.php.twig',
                [
                    'repository' => $repositoryName,
                    'constants' => $constants
                ]);

            $constFilename = $this->outputDirectory . 'Constants/' . ucfirst($repositoryName) . '.php';
            file_put_contents($constFilename, $constantContent);

            $files[] = $constFilename;
        }

        $context = [
            'repository' => $repositoryName,
            'endpoints' => $endpoints,
            'jsDocs' => $jsDocs,
            'typeDefs' => $typeDefs,
            'className' => $className
        ];

        if (array_key_exists('interface', $repositoryMeta)) {
            $context['interface'] = $repositoryMeta['interface'];
        }

        $classContent = $this->template->render('PHP/Snippets/repository.php.twig', $context);

        $classContent = str_replace('{Integer}', '{Number}', $classContent);
        $classContent = str_replace('{Mixed}', '{*}', $classContent);
        $classContent = str_replace('{List}', '{Array}', $classContent);

        $filename = $this->outputDirectory . 'Entities/' . $className . '.php';

        file_put_contents($filename, $classContent);

        $files[] = $filename;

        return $files;
    }

    private function getClassName($repository, $withSuffix = true)
    {
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

        $classContent = $this->template->render('PHP/Snippets/collection.php.twig',
            ['repositories' => $repositoryClasses]);

        $filename = $this->outputDirectory . 'RepositoryCollection.php';

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
        if ($endpoint->getResultType()) {
            if (count($parameters) > 0) {
                $jsDoc .= "   *\n";
            }
            $jsDoc .= "   * @return {" . $endpoint->getName() . "Result}\n";
        }

        $jsDoc .= "   */";

        return $jsDoc;
    }

    private function getResultTypeDefinitionJsDoc(Endpoint $endpoint)
    {
        if ($endpoint->getResultType()) {
            $jsDocHeader = "/**\n";
            $jsDocHeader .= ' * The result type for the ' . $endpoint->getName() . " API request.\n *\n";

            $resultType = $endpoint->getResultType();

            if (is_array($resultType)) {
                $jsDocHeader .= $this->createTypeDefJsDoc('', $endpoint->getResultType(), $endpoint->getName());
            } else {

            }

            $jsDocHeader .= " */";

            return $jsDocHeader;
        } else {
            return '';
        }
    }

    private function createTypeDefJsDoc($name, $resultArray, $prefix)
    {
        $typeDef = " * @typedef {Object} " . $prefix . "Result" . ucfirst($name) . "\n";

        foreach ($resultArray as $typeName => $resultElement) {
            if (!array_key_exists(ParameterRule::DESCRIPTION, $resultElement)) {
                $typeDef = $this->createTypeDefJsDoc($typeName, $resultElement, $prefix) . $typeDef;
                $typeDef .= " * @property {" . $prefix . "Result" . ucfirst($typeName) . "} " . $typeName . "\n";
            } else {
                $typeDef .= " * @property {" . $resultElement[ParameterRule::TYPE] . "} " . $typeName . " - " . $resultElement[ParameterRule::DESCRIPTION] . "\n";
            }
        }

        $typeDef .= " *\n";

        return $typeDef;
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
