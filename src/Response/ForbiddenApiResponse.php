<?php

namespace Leankoala\LeanApiBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonResponse
 *
 * @package App\Api
 *
 * @author Nils Langner <nils.langner@leankoala.com>
 * created 2021-04-16
 */
class ForbiddenApiResponse extends ApiResponse
{
    public function __construct($message, $data = null)
    {
        $responseArray['status'] = ApiResponse::STATUS_FAILURE;
        $responseArray['message'] = $message;
        $responseArray['data'] = $data;

        parent::__construct($responseArray, Response::HTTP_FORBIDDEN);
    }
}
