<?php

namespace Leankoala\LeanApiBundle\Client\Creator;

use Leankoala\LeanApiBundle\Client\Endpoint\Endpoint;

interface RepositoryCreator
{
    /**
     * @param string $repositoryName
     * @param Endpoint[] $endpoints
     *
     * @return string[]
     */
    public function create($repositoryName, $endpoints);

    /**
     * @param $repositories
     * @return mixed
     */
    public function finish($repositories);
}
