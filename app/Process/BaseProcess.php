<?php
declare(strict_types=1);
/**
 * user:cjw
 * time:2022/3/8 14:52
 */

namespace App\Process;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use App\Plugin\Log\Log;

/**
 * 协程开启自定义轮询进程，基类
 */
class BaseProcess extends AbstractProcess
{
    /**
     * 进程数量
     * @var int
     */
    public $nums = 1;

    /**
     * 进程名称
     * @var string
     */
    public $name = 'user-process';

    /**
     * 重定向自定义进程的标准输入和输出
     * @var bool
     */
    public $redirectStdinStdout = false;

    /**
     * 管道类型
     * @var int
     */
    public $pipeType = 2;

    /**
     * 是否启用协程
     * @var bool
     */
    public $enableCoroutine = true;

    /**
     * user:cjw
     * time:2022/3/8 14:59
     * 子类必须继承这个方法，来运行自定义脚本
     */
    public function handle(): void
    {
        // TODO: Implement handle() method.
    }
}