<?php

namespace Leankoala\LeanApiBundle\Client\Creator\JavaScript;

use Leankoala\LeanApiBundle\Client\Creator\RepositoryCreator;
use Twig\Environment;

class JavaScriptRepositoryCreator implements RepositoryCreator
{
    private $outputDirectory;
    private $template;

    public function __construct($outputDirectory, Environment $template)
    {
        $this->outputDirectory = $outputDirectory;
        $this->template = $template;

        if (!file_exists($this->outputDirectory)) {
            mkdir($this->outputDirectory, 0777, true);
        }
    }

    /**
     * @inheritDoc
     */
    public function create($repositoryName, $endpoints)
    {
        $classContent = $this->template->render(__DIR__ . '/Snippets/repository.js.twig',
            ['repository' => $repositoryName, 'endpoints' => $endpoints]);

        $filename = $this->outputDirectory . ucfirst($repositoryName) . 'Repository.js';

        file_put_contents($filename, $classContent);

        return [
            $filename
        ];
    }
}
