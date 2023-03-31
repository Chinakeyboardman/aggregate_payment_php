<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Constants\StatusCode;
use App\Exception\BusinessException;
use App\Core\Service\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;

class LoginMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;


    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @Inject()
     * @var UserService
     *
     */
    protected $userService;

    public function __construct(ContainerInterface $container, RequestInterface $request, HttpResponse $response)
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $signStatus = $this->checkSign($request);
        switch ($signStatus) {
            case StatusCode::ERR_NOT_EXIST_TOKEN:
                throw new BusinessException(StatusCode::ERR_NOT_EXIST_TOKEN);
            case StatusCode::ERR_INVALID_TOKEN:
                throw new BusinessException(StatusCode::ERR_INVALID_TOKEN);
            case StatusCode::ERR_EXPIRE_TOKEN:
                throw new BusinessException(StatusCode::ERR_EXPIRE_TOKEN);
            default:
                $Authorization = $request->getHeaderLine('Authorization');
                $userInfo = $this->userService->getUserInfoByToken($Authorization);
                if (empty($userInfo))
                    throw new BusinessException(StatusCode::ERR_INVALID_TOKEN);
//                Context::set('userInfo',$userInfo);
                return $handler->handle($request);
        }
    }

    private function checkSign(ServerRequestInterface $request): int
    {

        $authorizationBase64 = current($request->getHeader('Authorization'));

        if (empty($authorizationBase64)) {
            return StatusCode::ERR_NOT_EXIST_TOKEN;
        }

        if ($this->userService->checkTokenIsExpired($authorizationBase64)) {
            return StatusCode::ERR_EXPIRE_TOKEN;
        }

        return 1;
    }
}