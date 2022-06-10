<?php

namespace Leankoala\LeanApiBundle\Parameter;

use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;

/**
 * Class ResultType
 *
 * @package Leankoala\LeanApiBundle\Parameter
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @author Sascha Fuchs (sascha.fuchs@webpros.com)
 *
 * @created 2022-05-11
 */
final class ResultType
{
    const STRING = 'string';
    const INTEGER = 'integer';
    const BOOLEAN = 'boolean';
}
