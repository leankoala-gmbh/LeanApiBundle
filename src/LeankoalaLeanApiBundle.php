<?php

namespace Leankoala\LeanApiBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LeankoalaLeanApiBundle extends Bundle
{
    public function boot()
    {
        $this->registerAnnotations();
        return parent::boot();
    }

    /**
     * The API bundle can use some annotation for better readability. Those
     * annotations must be registered in doctrine.
     *
     * @todo interpret those annotations
     */
    private function registerAnnotations()
    {
        if (class_exists(AnnotationReader::class)) {
            AnnotationReader::addGlobalIgnoredName('apiParamSchema');
        }
    }
}
