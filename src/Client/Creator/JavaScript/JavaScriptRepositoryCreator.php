<?php

namespace Leankoala\LeanApiBundle\Client\Creator\JavaScript;

use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;
use Twig\Environment;

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
        foreach ($endpoints as $endpoint) {
            $jsDocs[$endpoint->getName()] = $this->getJsDoc($endpoint);
        }

        $classContent = $this->template->render(__DIR__ . '/Snippets/repository.js.twig',
            ['repository' => $repositoryName, 'endpoints' => $endpoints, 'jsDocs' => $jsDocs]);

        $classContent = str_replace('{Integer}', '{Number}', $classContent);
        $classContent = str_replace('{Mixed}', '{*}', $classContent);
        $classContent = str_replace('{List}', '{Array}', $classContent);

        $filename = $this->outputDirectory . 'Entities/' . ucfirst($repositoryName) . 'Repository.js';

        file_put_contents($filename, $classContent);

        return [
            $filename
        ];
    }

    /**
     * @inheritDoc
     */
    public function finish($repositories)
    {
        $classContent = $this->template->render(__DIR__ . '/Snippets/collection.js.twig',
            ['repositories' => $repositories]);

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
            $jsDoc .= "   * " . $endpoint->getDescription();
            if (count($endpoint->getPathParameters()) > 0 || count($endpoint->getParameters()) > 0) {
                $jsDoc .= "\n   *";
            }
            $jsDoc .= "\n";
        }

        foreach ($endpoint->getPathParameters() as $parameter) {
            $jsDoc .= "   * @param " . $parameter . "\n";
        }

        $parameters = $endpoint->getParameters();
        if (count($parameters) > 0) {
            $jsDoc .= "   * @param {Object} args\n";
            foreach ($parameters as $parameter) {
                $jsDoc .= "   * @param {" . ucfirst($parameter["type"]) . "} args." . $parameter['name'] . ' ' . $parameter['description'] . "\n";
            }
        }

        $jsDoc .= "   */";

        return $jsDoc;
    }
}
