<?php

namespace Leankoala\LeanApiBundle\Http;

use Leankoala\LeanApiBundle\Parameter\ParameterBag;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class ApiRequest
 *
 * The API request bundles all information like the payload of the request. It also validates the data
 * and converts it if necessary to Doctrine objects  by fetching them from the database. It uses the
 * ParameterBag class for that.
 *
 * @package LeankoalaApi\CoreBundle\Http
 */
class ApiRequest
{
    /**
     * The parameter container for all the request payload parameters
     *
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * The symfony HTTP request
     *
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $schema;

    /**
     * @var RegistryInterface
     */
    private $doctrine;

    /**
     * ApiRequest constructor.
     *
     * @param Request $request
     * @param RegistryInterface $doctrine
     * @param array $schema
     */
    public function __construct(Request $request, RegistryInterface $doctrine, $schema = [])
    {
        $this->request = $request;
        $this->schema = $schema;
        $this->doctrine = $doctrine;

        $this->initPayload();
    }

    /**
     * Initializes the payload.
     *
     * The payload is expected to be a JSON string.
     */
    private function initPayload()
    {
        $payloadJson = $this->request->getContent();

        if ($payloadJson == '') {
            $payloadJson = '[]';
        }

        $payload = json_decode($payloadJson, true);

        if (strlen($payloadJson) > 0 && is_null($payload)) {
            throw new BadRequestHttpException('Payload is not a valid JSON string.');
        }

        $this->parameterBag = new ParameterBag($payload, $this->doctrine, $this->schema);
    }

    /**
     * Return a validated and casted element from payload.
     *
     * @param $identifier
     * @return mixed
     * @see ParameterBag::$schema
     *
     */
    public function getParameter($identifier)
    {
        return $this->parameterBag->getParameter($identifier);
    }

    /**
     * Returns true of the parameter exists.
     *
     * @param $identifier
     * @return bool
     */
    public function hasParameter($identifier)
    {
        return $this->parameterBag->hasParameter($identifier);
    }

    /**
     * Return all parameters
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameterBag->getParameters();
    }
}
