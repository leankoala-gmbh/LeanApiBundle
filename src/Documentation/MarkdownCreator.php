<?php

namespace Leankoala\LeanApiBundle\Documentation;

use Leankoala\LeanApiBundle\Parameter\ParameterConstraint;
use Leankoala\LeanApiBundle\Parameter\ParameterRule;

/**
 * Class MarkdownCreator
 *
 * @package Leankoala\LeanApiBundle\Documentation
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-04-24
 */
class MarkdownCreator extends BaseCreator
{
    /**
     * @inheritDoc
     */
    public function createByPath($path, $method)
    {
        $schemaArray = $this->getControllerSchema($path, $method);

        $markdown = '';

        $markdown = $this->withDescription($markdown, $schemaArray);

        if (array_key_exists(ParameterRule::REQUEST_DESCRIPTION, $schemaArray)) {
            unset($schemaArray[ParameterRule::REQUEST_DESCRIPTION]);
        }

        $markdown = $this->withRequestParameters($markdown, $schemaArray);

        return $markdown;
    }

    /**
     * Process the request description and attach them to the markdown.
     *
     * @param string $markdown
     * @param array $parameters
     * @return string
     */
    private function withDescription($markdown, $parameters)
    {
        if (array_key_exists(ParameterRule::REQUEST_DESCRIPTION, $parameters)) {
            $markdown .= $parameters[ParameterRule::REQUEST_DESCRIPTION] . "\n\n";
        }

        return $markdown;
    }

    /**
     * Process the request parameters and attach them to the markdown.
     *
     * @param string $markdown
     * @param array $parameters
     * @return string
     */
    private function withRequestParameters($markdown, $parameters)
    {
        $markdown .= "## Request parameters\n";

        $markdown .= "| Name | Description | Required | Type | Constraints | \n";
        $markdown .= "|:-----|:------------|:---------|:-----|:------------|\n";

        foreach ($parameters as $name => $parameter) {

            if (!is_array($parameter)) {
                throw new \RuntimeException("The given parameter '" . $name . "' with value '" . $parameter . "' must be an array.");
            }

            if (array_key_exists(ParameterRule::DESCRIPTION, $parameter)) {
                $description = $parameter[ParameterRule::DESCRIPTION];
            } else {
                $description = '';
            }

            if (array_key_exists(ParameterRule::REQUIRED, $parameter)) {
                $required = $parameter[ParameterRule::REQUIRED] ? "true" : "false";
            } else {
                $required = "false";
            }

            if (array_key_exists(ParameterRule::TYPE, $parameter)) {
                $type = $parameter[ParameterRule::TYPE];
            } else {
                $type = "mixed";
            }

            if (array_key_exists(ParameterRule::OPTIONS, $parameter)) {
                $type = '(' . implode(' \| ', $parameter[ParameterRule::OPTIONS]) . ')';
            }

            if (array_key_exists(ParameterRule::CONSTRAINTS, $parameter)) {
                $constraints = '';
                foreach ($parameter[ParameterRule::CONSTRAINTS] as $constraintType => $option) {
                    $constraints .= ParameterConstraint::toMarkdown($constraintType, $option) . "<br>";
                }
            } else {
                $constraints = '';
            }

            $markdown .= '| ' . $name . ' | ' . $description . ' | ' . $required . ' | ' . $type . ' | ' . $constraints . '|' . "\n";
        }

        return $markdown;
    }
}
