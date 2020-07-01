<?php

namespace Leankoala\LeanApiBundle\EventListener;

use Leankoala\LeanApiBundle\Configuration\ApiConfiguration;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class CorsListener
 *
 * This listener is used to set the correct CORS headers as this is an open API. It is needed because
 * browsers send pre-flight requests (OPTIONS) before the real API request to check whether the method is allowed or
 * not.
 *
 * @important every API endpoint must also allow the method OPTIONS in the routing file to work for browsers
 * @example routing.yml => methods:  [POST, OPTIONS]
 *
 * The implementation is inspired by
 * @see https://www.upbeatproductions.com/blog/cors-pre-flight-requests-and-headers-symfony-httpkernel-component
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class CorsListener
{
    private $apiConfiguration;

    private $allowedHeaders = [
        'accept',
        'accept-encoding',
        'accept-language',
        'access-control-request-headers',
        'access-control-request-method',
        'cache-control',
        'origin',
        'pragma',
        'referer',
        'sec-fetch-dest',
        'sec-fetch-mode',
        'sec-fetch-site',
	'user-agent',
	'content-type',
	'Accept-Headers',
	'Authorization',

    ];

    /**
     * CorsListener constructor.
     *
     * @param ApiConfiguration $apiConfiguration
     */
    public function __construct(ApiConfiguration $apiConfiguration)
    {
        $this->apiConfiguration = $apiConfiguration;
    }

    /**
     * Redirect the request if it is an API request with OPTIONS as HTTP method.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->apiConfiguration->isApiRequest($event->getRequest())) {
            return;
        }

        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        $method = $event->getRequest()->getRealMethod();

        if ('OPTIONS' == $method) {
            $response = new Response();
            $event->setResponse($response);
        }
    }

    /**
     * Set the mandatory CORS header if it is an API request. This is needed if a browser is
     * communicating with the API.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // Don't do anything if it's not the master request.
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->apiConfiguration->isApiRequest($event->getRequest())) {
            $response = $event->getResponse();
            $headers = 'method';
            foreach ($this->allowedHeaders as $header) {
                $headers .= ', ' . $header;
            }
            //$response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Access-Control-Allow-Headers', $headers);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', '*');
        }
    }
}
