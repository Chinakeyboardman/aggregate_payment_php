<?php
/**
 * 消息发送器
 */
declare(strict_types=1);

namespace App\Controller\Http;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Utils\Traits\StaticInstance;
use Hyperf\WebSocketServer\Sender;

/**
 * @AutoController
 */
class SenderController
{
    use StaticInstance;

    /**
     * @Inject
     * @var Sender
     */
    protected $sender;

    public function close(int $fd): string
    {
        go(function () use ($fd) {
            $this->sender->disconnect($fd);
        });
        return '';
    }

    public function send(int $fd, $data): string
    {
        // TODO Hyperf的bug，我可以成功给已知在线的fd发送消息，$server->getWorkerId()直接返回false所以$sender->check()失效
        // 得自己应对workerId问题
//        $server = ApplicationContext::getContainer()->get(SwooleServer::class);
//        $server = make(SwooleServer::class);
//        $res = $server->getWorkerId();
//        var_dump($res);

//        if($this->sender->check($fd)){
        $this->sender->push($fd, $data);
//        }
        return '';
    }
}
