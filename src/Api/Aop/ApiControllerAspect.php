<?php
namespace SixMQ\Api\Aop;

use Imi\Aop\Annotation\Aspect;
use Imi\Aop\Annotation\PointCut;
use Imi\Aop\AfterReturningJoinPoint;
use Imi\Aop\Annotation\AfterReturning;

/**
 * @Aspect
 */
class ApiControllerAspect
{
    /**
     * @PointCut(
     *         allow={
     *             "SixMQ\Api\Controller\*::*",
     *         },
     * )
     * @AfterReturning
     * @return mixed
     */
    public function parse(AfterReturningJoinPoint $joinPoint)
    {
        $returnValue = $joinPoint->getReturnValue();
        if(null === $returnValue || (is_array($returnValue) && !isset($returnValue['status'])))
        {
            $returnValue['success'] = true;
            $returnValue['message'] = '';
            $returnValue['status'] = 0;
        }
        $joinPoint->setReturnValue($returnValue);
    }
}