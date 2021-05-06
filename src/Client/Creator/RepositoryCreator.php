<?php

namespace Leankoala\LeanApiBundle\Client\Creator;

use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;

interface RepositoryCreator
{
    /**
     * @param string $repositoryName
     * @param Endpoint[] $endpoints
     * @param string[] $constants
     *
     * @return string[]
     */
    public function create($repositoryName, $endpoints, $constants = [], $repositoryMeta = []);

    /**
     * @param $repositories
     * @return mixed
     */
    public function finish($repositories);
}
