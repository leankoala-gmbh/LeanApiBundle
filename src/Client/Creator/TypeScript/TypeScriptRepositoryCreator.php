<?php

namespace Leankoala\LeanApiBundle\Client\Creator\TypeScript;

use JsonSchema\Exception\RuntimeException;
use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;
use Leankoala\LeanApiBundle\Parameter\ParameterRule;
use Leankoala\LeanApiBundle\Parameter\ParameterType;
use Twig\Environment;

/**
 * Class TypeScriptRepositoryCreator
 *
 * @package Leankoala\LeanApiBundle\Client\Creator\JavaScript
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @author Nils Langner (sascha.fuchs@webpros.com)
 * created 2022-05-11
 */
class TypeScriptRepositoryCreator implements RepositoryCreator
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
    public function create($repositoryName, $endpoints, $constants = [])
    {
        $jsDocs = [];
        $responseInterfaces = "";
        $argumentInterfaces = "";

        foreach ($endpoints as $endpoint) {
            $jsDocs[$endpoint->getName()] = $this->getJsDoc($endpoint);

            if ($endpoint->hasResultType()) {
                $responseInterfaces .= $this->createResponseInterfaces($endpoint) . "\n\n";
            }

            if ($endpoint->hasParameters()) {
                $argumentInterfaces .= $this->createArgumentsInterfaces($endpoint) . "\n\n";
            }
        }

        $className = $this->getClassName($repositoryName);

        $files = [];

        if (count($constants) > 0) {

            $constantContent = $this->template->render(__DIR__ . '/Snippets/constants.ts.twig',
                [
                    'repository' => $repositoryName,
                    'constants' => $constants
                ]);

            $constFilename = $this->outputDirectory . 'Constants/' . ucfirst($repositoryName) . '.ts';
            file_put_contents($constFilename, $constantContent);

            $files[] = $constFilename;
        }

        $classContent = $this->template->render(__DIR__ . '/Snippets/repository.ts.twig',
            [
                'repository' => $repositoryName,
                'endpoints' => $endpoints,
                'jsDocs' => $jsDocs,
                'responseInterfaces' => $responseInterfaces,
                'argumentInterfaces' => $argumentInterfaces,
                'className' => $className
            ]);

        $classContent = str_replace('{Integer}', '{Number}', $classContent);
        $classContent = str_replace('{Mixed}', '{*}', $classContent);
        $classContent = str_replace('{List}', '{Array}', $classContent);

        $filename = $this->outputDirectory . 'Entities/' . $className . '.ts';

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

        $classContent = $this->template->render(__DIR__ . '/Snippets/collection.ts.twig',
            ['repositories' => $repositoryClasses]);

        $filename = $this->outputDirectory . 'RepositoryCollection.ts';

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

        if ($endpoint->getPath()) {
            $jsDoc .= "   * request url: /kapi/v1/" . $endpoint->getPath() . "\n";
            $jsDoc .= "   * request method: " . $endpoint->getMethod() . "\n";
            $jsDoc .= "   *\n";
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
            if ($endpoint->hasResultType()) {
                $jsDoc .= "   * @return " . $this->getResultTypeName($endpoint) . "\n";
            } else {
                $jsDoc .= "   * @return {" . $endpoint->getName() . "Result}\n";
            }
        }

        $jsDoc .= "   */";

        return $jsDoc;
    }

    private function getResultTypeName(Endpoint $endpoint)
    {
        return 'I' . $endpoint->getName(true) . "Result";
    }

    private function getArgumentInterfaceName(Endpoint $endpoint)
    {
        return 'I' . $endpoint->getName(true) . "Arguments";
    }

    private function createResponseInterfaces(Endpoint $endpoint, $resultArray = [], $level = 0)
    {
        $interfaceString = '';

        $indent = str_repeat(' ', ($level + 1) * 2);

        if ($level === 0) {
            $interfaceString = 'export interface ' . $this->getResultTypeName($endpoint) . "{\n";
            $resultArray = $endpoint->getResultType();
        }

        foreach ($resultArray as $resultIdentifier => $resultDescription) {
            if (array_key_exists(ParameterRule::TYPE, $resultDescription)) {
                $interfaceString .= $indent . $resultIdentifier . "? : " . self::translateType($resultDescription[ParameterRule::TYPE]) . "\n";
            } else {
                $interfaceString .= $indent . $resultIdentifier . ": { \n";
                $interfaceString .= substr($indent, 0, strlen($indent) - 2) . $this->createResponseInterfaces($endpoint, $resultDescription, $level + 1);
                $interfaceString .= $indent . "}\n";
            }
        }

        if ($level === 0) {
            $interfaceString .= '}';
        }

        return $interfaceString;
    }

    private function createArgumentsInterfaces(Endpoint $endpoint)
    {
        $interfaceString = 'export interface ' . $this->getArgumentInterfaceName($endpoint) . " {\n";

        foreach ($endpoint->getParameters() as $argumentDescription) {

            if (array_key_exists(ParameterRule::TYPE, $argumentDescription)) {
                if ($argumentDescription[Endpoint::PARAMETER_FIELD_REQUIRED]) {
                    $requiredString = '';
                } else {
                    $requiredString = '?';
                }

                if (array_key_exists(ParameterRule::OPTIONS, $argumentDescription)) {
                    $interfaceString .= '  ' . $argumentDescription[Endpoint::PARAMETER_FIELD_NAME] . $requiredString . ": '" . implode('\' | \'', $argumentDescription[ParameterRule::OPTIONS]  ) . "'\n";
                } else {
                    $interfaceString .= '  ' . $argumentDescription[Endpoint::PARAMETER_FIELD_NAME] . $requiredString . ": " . self::translateType($argumentDescription[ParameterRule::TYPE]) . "\n";
                }

            } else {
                throw new \RuntimeException('upsi');
            }
        }

        $interfaceString .= '}';

        return $interfaceString;
    }

    static private function translateType($type)
    {
        switch ($type) {
            case ParameterType::INTEGER:
                return 'number';
            case ParameterType::LIST:
                return 'any[]';
            case 'mixed':
                return 'any';
            case ParameterType::URL:
                return 'string';
        }

        return $type;
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
            if (is_array($default)) {
                $options = 'default: ' . json_encode($default);
            } else {
                $options = 'default: ' . $default;
            }
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
