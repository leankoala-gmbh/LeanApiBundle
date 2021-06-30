<?php

namespace Leankoala\LeanApiBundle\Controller;

use App\Entity\Application;
use Leankoala\LeanApiBundle\Http\ApiRequest;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ApiController
 *
 * @package App\Controller
 *
 * @author Nils Langner <nils.langner@leankoala.com>
 * created 2021-04-19
 */
abstract class ApiController extends AbstractController
{
    const PARAMETER_APPLICATION = 'application';

    protected array $schemas = [];

    /**
     * The request payload as array. Can be get via getRequestPayload() function.
     *
     * @var array
     */
    private array $requestPayload = [];

    /**
     * Return the API request for the corresponding schema.
     *
     * @param string $schemaIdentifier
     * @return ApiRequest
     */
    protected function getApiRequest(string $schemaIdentifier): ApiRequest
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getCurrentRequest();

        return new ApiRequest($request, $this->getDoctrine(), $this->schemas[$schemaIdentifier]);
    }

    /**
     * Use the setContainer event to initialize the payload.
     *
     * @param ContainerInterface $container
     *
     * @return ContainerInterface|null
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        parent::setContainer($container);
        $this->initPayload();

        return $container;
    }

    /**
     * Initializes the payload.
     *
     * @throws BadRequestHttpException
     */
    private function initPayload(): void
    {
        $payloadJson = $this->container->get("request_stack")->getCurrentRequest()->getContent();

        if ($payloadJson == '') {
            $payloadJson = '[]';
        }

        $payload = json_decode($payloadJson, true);

        if (strlen($payloadJson) > 0 && is_null($payload)) {
            throw new BadRequestHttpException('Payload is not a valid JSON string.');
        }

        $this->setRequestPayload($payload);
    }

    /**
     * Set the request payload property that can be accessed via getRequestPayload()
     *
     * @param array $requestPayload
     */
    private function setRequestPayload(array $requestPayload)
    {
        $this->requestPayload = $requestPayload;
    }

    /**
     * All API requests use JSON encoded data as raw body. This controller is converting them
     * into an array that can be used in the actions.
     *
     * @return string[]
     */
    protected function getRequestPayload(): array
    {
        return $this->requestPayload;
    }

    /**
     * Return the schema of this controller.
     *
     * @return array
     */
    public function getSchemas()
    {
        return $this->schemas;
    }
}
