<?php
/**
 * user:cjw
 * time:2022/3/17 17:17
 */
declare(strict_types=1);

namespace App\Command;

use App\Exception\BusinessException;
use App\Model\File;
use App\Plugin\Log\Log;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Server\ServerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketServer\Sender;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Hyperf\Server\ServerInterface;


/**
 * 测试脚本
 * 执行语句： php bin/hyperf.php command:test
 *
 * @Command
 */
class TestCommand extends HyperfCommand
{

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ServerFactory
     */
    private $serverFactory;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @Inject()
     * @var ClientFactory
     */
    public $clientFactory;

    /**
     * 单元测试，里面写需要测试的代码
     */
    public function test()
    {
        $attachmentModel = new File;
        $res = $attachmentModel->first();
        if (!empty($res)) {
            $res = $res->getHidden();
        }
        var_dump(['test' => $res]);

//        $server = $this->serverFactory->getServer()->getServer();
//        $server->connection_list(1);

        $pushData = ['code' => 20000000000];
        $webSocketIp = '0.0.0.0:9502/ws';
        $client = $this->clientFactory->create($webSocketIp);
        $client->push(json_encode($pushData), 1, 2);

//        $testController = make(TestController::class);
//        $testController->test();

    }

    /**
     * 执行的命令行
     *
     * @var string
     */
    protected $name = 'command:test';

    public function configure()
    {
        parent::configure();
        $this->setDescription('测试脚本');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function handle(): bool
    {
        // Implement handle() method.
        date_default_timezone_set('PRC');
        $logger = Log::get('app');
        try {

            $t1 = microtime(true);
            $this->test();
            $t2 = microtime(true);
            var_dump('耗时' . round($t2 - $t1, 3) . '秒');

            $logger->info('测试脚本执行完毕！！！！！！！！');

        } catch (BusinessException $devException) {
            $logger->error(sprintf('[%s] %s[%s] in %s', get_class($devException), $devException->getMessage(),
                $devException->getLine(), $devException->getFile()));
            $logger->error($devException->getTraceAsString());
        } catch (\Throwable $throwable) {
            $logger->error(sprintf('[%s] %s[%s] in %s', get_class($throwable), $throwable->getMessage(),
                $throwable->getLine(), $throwable->getFile()));
            $logger->error($throwable->getTraceAsString());
        }
        return true;
    }


}