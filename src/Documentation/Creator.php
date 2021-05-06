<?php

namespace Leankoala\LeanApiBundle\Documentation;

/**
 * Interface Creator
 *
 * @package Leankoala\LeanApiBundle\Documentation
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-04-24
 */
interface Creator
{
    public function createByPath($path, $method);
}
