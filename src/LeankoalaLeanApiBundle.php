<?php

namespace Leankoala\LeanApiBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class LeankoalaLeanApiBundle
 * @package Leankoala\LeanApiBundle
 *
 * @author Nils Langner (nils.langner@leankoala.com)
 * @created 2020-01-01
 */
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
            // general annotations
            AnnotationReader::addGlobalIgnoredName('created');

            // api annotations
            AnnotationReader::addGlobalIgnoredName('apiParamSchema');
            AnnotationReader::addGlobalIgnoredName('apiSchema');
        }
    }
}
