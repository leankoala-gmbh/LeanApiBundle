<?php

namespace Leankoala\LeanApiBundle\Parameter;

use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;

/**
 * Class ParameterType
 *
 * @package Leankoala\LeanApiBundle\Parameter
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-05-06
 */
abstract class ParameterType
{
    const STRING = 'string';
    const INTEGER = 'integer';
    const BOOLEAN = 'boolean';

    const IDENTIFIER = 'identifier';
    const LIST = 'list';

    const EMAIL = 'email';
    const URL = 'url';

    /**
     * Return true if the given URL is valid.
     *
     * @param string $url
     *
     * @return bool
     */
    static private function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Check if the given value has the correct type;
     *
     * @param string $type
     * @param string $value
     *
     * @return bool
     */
    static public function assertCorrectType($type, $value)
    {
        $allowedTypes = [
            self::STRING => ['string'],
            self::INTEGER => ['integer'],
            self::BOOLEAN => ['boolean'],
            self::EMAIL => [],
            self::URL => [],
            self::IDENTIFIER => ['integer'],
            self::LIST => ['array']
        ];

        if (!in_array($type, array_keys($allowedTypes))) {
            throw new BadParameterException('The given type "' . $type . '" is not valid. Try ' . implode(', ', array_keys($allowedTypes)) . '.');
        }

        if ($type === self::URL) {
            if (!self::isValidUrl($value)) {
                throw new BadParameterException('The given URL is not valid.');
            } else {
                return true;
            }
        }

        $valueType = gettype($value);

        if (!in_array($valueType, $allowedTypes[$type])) {
            throw new BadParameterException('The given parameter does not match the type. Expected: ' . $type . ', actual: ' . $valueType);
        }
    }
}
