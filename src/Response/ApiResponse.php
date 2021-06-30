<?php


namespace Leankoala\LeanApiBundle\Response;

use App\Business\Exception\AlreadyExistingException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Class ApiResponse
 *
 * @package App\Api\Response
 *
 * @author Nils Langner <nils.langner@leankoala.com>
 * created 2021-04-19
 */
abstract class ApiResponse extends JsonResponse
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';

    /**
     * @param Throwable $exception
     *
     * @return ApiResponse
     */
    static public function fromException(Throwable $exception): ApiResponse
    {
        switch (get_class($exception)) {
            case AlreadyExistingException::class:
                return new AlreadyExistingApiResponse($exception->getMessage());
            default:
                return new ErrorApiResponse($exception->getMessage());
        }
    }
}
