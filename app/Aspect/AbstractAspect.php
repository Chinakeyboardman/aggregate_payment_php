<?php

namespace App\Aspect;

use Hyperf\Di\Aop\AbstractAspect as A;
use Hyperf\Di\Aop\ProceedingJoinPoint;

class AbstractAspect extends A
{
    /**
     * 这里需要
     * public $classes = [
     *     //需要切入的类和方法，支持批量和模糊匹配，
     *     DemoAspectController::class.'::'.'aop',//具体接口名称
     *     DemoAspectController::class.'::'.'*Method',//支持模糊匹配Method结尾的方法
     * ];
     */


    /**
     * 需要干预的进程
     * user:cjw
     * time:2022/3/11 10:15
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return void
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // TODO: Implement process() method.
    }
}