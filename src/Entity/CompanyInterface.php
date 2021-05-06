<?php


namespace Leankoala\LeanApiBundle\Entity;

/**
 * Interface CompanyInterface
 *
 * @package Leankoala\LeanApiBundle\Entity
 */
interface CompanyInterface
{
    const ROLE_OWNER = 0;
    const ROLE_ADMIN = 100;
    const ROLE_WORKER = 1000;
}
