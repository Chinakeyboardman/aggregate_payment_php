<?php
declare(strict_types=1);

namespace App\Controller\Websocket;

use App\Constants\StatusCode;
use Hyperf\Contract\OnCloseInterface;
use Hyperf\Contract\OnMessageInterface;
use Hyperf\Contract\OnOpenInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Http\Request;
use Swoole\Websocket\Frame;
use App\Core\Service\WebSocketService;
use Hyperf\HttpServer\Contract\RequestInterface;

class WebSocketController implements OnMessageInterface, OnOpenInterface, OnCloseInterface
{

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject()
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject()
     * @var WebSocketService
     */
    protected $wsService;

    public $wsClassArr;

    public function __construct(){
        $this->wsClassArr = config('ws_route.ws_class_arr');
    }

    public function onMessage($server, Frame $frame): void
    {
        $hasErr = false;
        $data = json_decode($frame->data, true);
        $res = '【' . $frame->fd . '】' . 'Recv: ' . $frame->data;
        // ws路由
        if (isset($data['protocol']) && !empty($data['protocol'])) {
            // 协议，类似于路由，执行某个服务中某个方法
            $protocolArr = $this->wsClassArr[$data['protocol']] ?? '';
            $protocolClassName = $protocolArr[0];
            $protocolFunctionName = $protocolArr[1];
            // 根据路由容器调用对象，并执行方法，直接传入参数
            if ($this->container->has($protocolClassName)) {
                try {
                    $obj = $this->container->get($protocolClassName);
                    $res = $obj->$protocolFunctionName($data);
                } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
                    $hasErr = true;
                    $errMsg = json_encode(['code' => StatusCode::ERR_SERVER, 'data' => "err"], JSON_UNESCAPED_UNICODE);
                    $server->push($frame->fd, $errMsg);
                }
            }
        }
        if (!$hasErr) {
            $server->push($frame->fd, $res);
        }
    }


    public function onClose($server, int $fd, int $reactorId): void
    {
        $this->wsService->offlineFd($fd);
//        var_dump('closed');
    }


    public function onOpen($server, Request $request): void
    {
        $fd = $request->fd;
        $token = $this->request->query('token');
        if (empty($token)) {
            $result = ['code' => 200, 'protocol' => '', 'msg' => 'Opened'];
            $server->push($fd, json_encode($result, JSON_UNESCAPED_UNICODE));
            $server->disconnect($fd);
        }
        $userInfo = getUserInfo($token);
        if (empty($userInfo)) {
            $server->push($fd, '{"active":1005,"data":"验证失败"}');
            $server->disconnect($fd);
        }
        if (!empty($userInfo)) {
            // 不在线清除缓存
            $this->wsService->outAllOfflineFd($server);

            $fdArr = ['fd' => $fd, 'user_id' => $userInfo['id']];
            $this->wsService->onlineUserFd($token, $fdArr);
            $result = ['code' => 200, 'protocol' => '', 'msg' => 'Opened'];
            $server->push($fd, json_encode($result, JSON_UNESCAPED_UNICODE));
        }

    }

}