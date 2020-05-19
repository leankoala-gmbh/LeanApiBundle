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
 */
abstract class ParameterConstraint
{
    const LENGTH_MIN = 'length_min';
    const LENGTH_MAX = 'length_max';

    const NUMBER_GREATER_THAN = 'number_greater_than';
    const NUMBER_LESS_THAN = 'number_less_than';

    const ELEMENTS_MIN = 'element_min';

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
            self::LENGTH_MIN,
            self::NUMBER_GREATER_THAN,
            self::NUMBER_LESS_THAN,
            self::ELEMENTS_MIN
        ];

        if (!in_array($type, $allowedConstraints)) {
            throw new BadParameterException('The given type "' . $type . '" is not a valid constraint. '
                                            . 'Try ' . implode(', ', $allowedConstraints) . '.');
        }

        switch ($type) {
            case self::LENGTH_MIN:
                self::assertMinLength($value, $option);
                break;
            case self::LENGTH_MAX:
                self::assertMaxLength($value, $option);
                break;
            case self::NUMBER_GREATER_THAN:
                self::assertGreaterThan($value, $option);
                break;
            case self::NUMBER_LESS_THAN:
                self::assertLessThan($value, $option);
                break;
            case self::ELEMENTS_MIN:
                self::assertElementsMin($value, $option);
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

    /**
     * Assert that the given value is greater than a given limit
     *
     * @param $value
     * @param $number
     */
    static private function assertGreaterThan($value, $number)
    {
        if ($value <= $number) {
            throw new ValidationFailedException('the given value must be greater than ' . $number . ', given ' . $value . '.');
        }
    }

    /**
     * Assert that the given value is less than a given limit
     *
     * @param $value
     * @param $number
     */
    static private function assertLessThan($value, $number)
    {
        if ($value >= $number) {
            throw new ValidationFailedException('the given value must be less than ' . $number . ', given ' . $value . '.');
        }
    }

    /**
     * Assert that at least n elements are in the given list
     *
     * @param $value
     * @param $minElements
     */
    static private function assertElementsMin($value, $minElements)
    {
        if (count($value) < $minElements) {
            throw new ValidationFailedException('the given list must contain at least ' . $minElements . ' element(s), given ' . count($value) . '.');
        }
    }

    /**
     * Convert a constraint to markdown.
     *
     * @param string $type
     * @param mixed $option
     *
     * @return string
     */
    static public function toMarkdown($type, $option)
    {
        switch ($type) {
            case self::LENGTH_MIN:
                return 'Min length ' . $option;
            case self::LENGTH_MAX:
                return 'Max length ' . $option;
            case self::NUMBER_GREATER_THAN:
                return 'Greater than ' . $option;
            case self::NUMBER_LESS_THAN:
                return 'Less than ' . $option;
            case self::ELEMENTS_MIN:
                return 'element count > ' . $option;
        }

        return '';
    }
}
