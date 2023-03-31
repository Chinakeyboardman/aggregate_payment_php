<?php
declare(strict_types=1);

namespace App\Plugin\Log;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

class Log
{

    /**
     * 使用方法：Log:get()->info("");
     * 更多接口请参考\Psr\Log\LoggerInterface文件
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function get(string $name = 'app'): \Psr\Log\LoggerInterface
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $name);
    }
}