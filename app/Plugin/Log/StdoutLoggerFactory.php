<?php
declare(strict_types=1);

namespace App\Plugin\Log;


use App\Plugin\Log\Log;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    /**
     * 日志输出类 容器/工厂
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): \Psr\Log\LoggerInterface
    {
        return Log::get('sys');
    }
}