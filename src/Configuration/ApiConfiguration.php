<?php

namespace Leankoala\LeanApiBundle\Configuration;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class ApiConfiguration
 *
 * This class is used to configure the LeanApiBundle. It is initialized via the service.yml file.
 *
 * @package Leankoala\LeanApiBundle\Configuration
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class ApiConfiguration
{
    private $urlPrefix;

    /**
     * ApiConfiguration constructor.
     *
     * @param string $urlPrefix the API prefix. All API urls must start with this string.
     */
    public function __construct($urlPrefix)
    {
        $this->urlPrefix = $urlPrefix;
    }

    /**
     * Check if the given request is an API request. This is for example used when setting the CORS headers.
     *
     * @param Request $request
     * @return bool
     */
    public function isApiRequest(Request $request)
    {
        $urlString = (string)$request->getUri();
        return strpos($urlString, $this->urlPrefix) !== false;
    }
}
