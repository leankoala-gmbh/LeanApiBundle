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
<<<<<<< HEAD
    public function create($repositoryName, $endpoints, $constants = [], $repositoryMeta = []);
=======
    public function create($repositoryName, $endpoints, $constants = []);
>>>>>>> c312dd7d58662e3317ecf804be9548329ae53a2d

    /**
     * @param $repositories
     * @return mixed
     */
    public function finish($repositories);
}
