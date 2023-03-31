<?php
/**
 * user:cjw
 * time:2022/3/910:06
 */

namespace App\Crontab;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

/**
 * @ 【去掉这段注释生效】 Crontab(name="Demo", rule="* * * * *", callback="execute", memo="这是一个示例的定时任务")
 */
class DemoTask
{
    /**
     * @Inject()
     * @var \Hyperf\Contract\StdoutLoggerInterface
     */
    private $logger;

    public function execute()
    {
        $this->logger->info(date('Y-m-d H:i:s', time()));
    }

    /**
     * 支持方法级别的crontab，注解写上去即可生效
     * (rule="* * * * * *", memo="foo")
     */
    public function foo()
    {
        var_dump('foo');
    }

}