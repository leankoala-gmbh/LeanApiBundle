<?php

namespace Leankoala\LeanApiBundle\ValueResolver;

use Leankoala\LeanApiBundle\Http\ApiRequest;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Class ApiRequestValueResolver
 *
 * THIS CLASS IS NOT IMPLEMENTED YET. THESE ARE ONLY FIRST STEPS.
 *
 * @package Leankoala\LeanApiBundle\ValueResolver
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 */
class ApiRequestValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var RegistryInterface
     */
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return ApiRequest::class === $argument->getType();
    }

    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield new ApiRequest($request, $this->doctrine);
    }
}
