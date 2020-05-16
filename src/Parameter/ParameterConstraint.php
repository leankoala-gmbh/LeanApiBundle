<?php

namespace Leankoala\LeanApiBundle\Parameter;

use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;
use Leankoala\LeanApiBundle\Parameter\Exception\ValidationFailedException;

/**
 * Class ParameterConstraint
 *
 * @package Leankoala\LeanApiBundle\Parameter
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-05-16
 *
 * @todo the constraints must be part of the generated documentation
 */
abstract class ParameterConstraint
{
    const LENGTH_MIN = 'length_min';
    const LENGTH_MAX = 'length_max';

    /**
     * This function throws an ValidationFailedException if the given value does not match the given constraint.
     *
     * @param string $type
     * @param string $option
     * @param mixed $value
     *
     * @throws ValidationFailedException
     */
    static public function assertConstraint($type, $option, $value)
    {
        $allowedConstraints = [
            self::LENGTH_MAX,
            self::LENGTH_MIN
        ];

        if (!in_array($type, $allowedConstraints)) {
            throw new BadParameterException('The given type "' . $type . '" is not a valid constraint. ' . '
                                            Try ' . implode(', ', $allowedConstraints) . '.');
        }

        switch ($type) {
            case self::LENGTH_MIN:
                self::assertMinLength($value, $option);
                break;
            case self::LENGTH_MAX:
                self::assertMaxLength($value, $option);
                break;
        }
    }

    /**
     * Assert value min length.
     *
     * @param string $value
     * @param int $minLength
     */
    static private function assertMinLength($value, $minLength)
    {
        if (strlen($value) < $minLength) {
            throw new ValidationFailedException('the given value must be at least ' . $minLength . ' characters, given ' . strlen($value) . ' characters.');
        }
    }

    /**
     * Assert value max length.
     *
     * @param string $value
     * @param int $maxLength
     */
    static private function assertMaxLength($value, $maxLength)
    {
        if (strlen($value) > $maxLength) {
            throw new ValidationFailedException('the given value must be at most ' . $maxLength . ' characters, given ' . strlen($value) . ' characters.');
        }
    }
}
