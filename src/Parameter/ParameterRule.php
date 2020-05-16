<?php

namespace Leankoala\LeanApiBundle\Parameter;

/**
 * Class ParameterRule
 *
 * This abstract class represents all the rules that can be used for a parameter bag.
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
abstract class ParameterRule
{
    /**
     * Required parameters have to be part of the given parameters list
     */
    const REQUIRED = 'required';

    /**
     * Set a default value if the parameter in not set
     */
    const DEFAULT = 'default';
    const TYPE = 'type';
    const OPTIONS = 'options';
    const ENTITY = 'entity';
    const ALIAS = 'alias';

    const CONSTRAINTS = 'constraints';

    const GROUP = 'group';

    const DESCRIPTION = 'description';

    const REQUEST_DESCRIPTION = '_request_description';
    const REQUEST_PUBLIC = '_request_public';
}
