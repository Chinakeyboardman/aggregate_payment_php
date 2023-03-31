<?php
/**
 * 跨域处理中间件 绑定到全局
 */

namespace App\Middleware;

use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = Context::get(ResponseInterface::class);
        $response = $response->withHeader('Access-Control-Allow-Origin', '*')->withHeader(
            'Access-Control-Allow-Credentials',
            'true'
        )
            // Headers 可以根据实际情况进行改写。
            ->withHeader(
                'Access-Control-Allow-Headers',
                'token,DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization'
            )->withHeader(
                'Access-Control-Allow-Methods',
                '*'
            );
        Context::set(ResponseInterface::class, $response);
        if ($request->getMethod() == 'OPTIONS') {
            return $response;
        }
        return $handler->handle($request);
    }
}