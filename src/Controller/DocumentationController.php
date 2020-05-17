<?php

namespace Leankoala\LeanApiBundle\Controller;

use Leankoala\LeanApiBundle\Documentation\MarkdownCreator;
use Leankoala\LeanApiBundle\Parameter\ParameterRule;
use LeankoalaApi\CoreBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentationController extends ApiController
{
    protected $schemas = [
        'markdown' => [
            ParameterRule::REQUEST_DESCRIPTION => 'This endpoint creates the API documentation in markdown using the schemas variable.',
            'path' => [
                ParameterRule::DESCRIPTION => 'The path for the controller and action that the API documentation should be created.',
                ParameterRule::TYPE => 'string',
                ParameterRule::REQUIRED => true,
            ],
            'method' => [
                ParameterRule::DESCRIPTION => "The HTTP method for the path.",
                ParameterRule::REQUIRED => true,
                ParameterRule::OPTIONS => [
                    Request::METHOD_GET,
                    Request::METHOD_POST,
                    Request::METHOD_DELETE,
                    Request::METHOD_PUT
                ]
            ]
        ]
    ];

    /**
     * Create a markdown API documentation using the API schema array.
     *
     * @apiSchema markdown
     *
     * @return JsonResponse|Response
     */
    public function markdownAction()
    {
        $apiRequest = $this->getApiRequest('markdown');

        $path = $apiRequest->getParameter('path');
        $method = $apiRequest->getParameter('method');

        try {
            $markdownCreator = new MarkdownCreator($this->get('router'));
            $markdown = $markdownCreator->createByPath($path, $method);
        } catch (\Exception $exception) {
            if ($this->container->get('kernel')->getEnvironment() == 'dev') {
                return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage(), 'exception' => $exception->getTrace()]);
            } else {
                return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
            }
        }

        return new Response($markdown);
    }
}
