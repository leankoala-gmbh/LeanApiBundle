<?php

namespace Leankoala\LeanApiBundle\Http;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Leankoala\LeanApiBundle\Parameter\Exception\BadParameterException;
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
    const RATE_LIMIT_OFF = 'off';
    const RATE_LIMIT_ANONYMOUS = 'anonymous';
    const RATE_LIMIT_AUTHENTICATED = 'authenticated';

    const HEADER_ACCEPT_LANGUAGE = 'accept-language';

    const ANNOTATION_API_SCHEMA = 'apiSchema';

    const KEY_RATE_LIMIT_STRATEGY = '_internal_rate_limit';

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

    private $rateLimit = self::RATE_LIMIT_OFF;

    /**
     * ApiRequest constructor.
     *
     * @param Request $request
     * @param RegistryInterface|Registry $doctrine
     * @param array $schema
     */
    public function __construct(Request $request, RegistryInterface|Registry $doctrine = null, $schema = [])
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

        if (array_key_exists(self::KEY_RATE_LIMIT_STRATEGY, $this->schema)) {
            $this->rateLimit = $this->schema[self::KEY_RATE_LIMIT_STRATEGY];
            unset($this->schema[self::KEY_RATE_LIMIT_STRATEGY]);
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
        try {
            return $this->parameterBag->getParameter($identifier);
        } catch (BadParameterException $exception) {
            throw new \LeankoalaApi\CoreBundle\Business\Exception\BadParameterException($exception->getMessage());
        }
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

    /**
     * Return the preferred user language defined in the Accept-Language header.
     *
     * @param string $fallbackLanguage
     * @return mixed|string
     */
    public function getPreferredLanguage($fallbackLanguage = 'en')
    {
        $acceptedLanguageString = $this->request->headers->get(self::HEADER_ACCEPT_LANGUAGE);
        $languageArray = explode(',', $acceptedLanguageString);

        if (count($languageArray) === 0 || $languageArray[0] === "") {
            return $fallbackLanguage;
        }

        return $languageArray[0];
    }

    /**
     * Return the rate limit strategy.
     *
     * @return string
     */
    public function getRateLimitStrategy()
    {
        return $this->rateLimit;
    }
}
