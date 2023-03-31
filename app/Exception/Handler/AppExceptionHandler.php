<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Exception\Handler;

use App\Constants\StatusCode;
use App\Exception\BusinessException;
use App\Exception\LoginException;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

//    public function handle(Throwable $throwable, ResponseInterface $response)
//    {
//        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
//        $this->logger->error($throwable->getTraceAsString());
//        return $response->withHeader('Server', 'Hyperf')->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
//    }

    /**
     * user:cjw
     * time:2022/3/11 10:46
     *
     * @param \Throwable                          $throwable
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(),
            $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        if ($throwable instanceof NotFoundHttpException) { //方法不存在
            $code = StatusCode::ERR_NON_EXISTENT;
        } elseif ($throwable instanceof BusinessException) { //常规错误
            $code = StatusCode::ERR_EXCEPTION_PARAMETER;
        } elseif ($throwable instanceof LoginException) {  //登录错误
            $code = StatusCode::ERR_NOT_LOGIN;
        } else {
            $code = 500;
        }

        if (StatusCode::ERR_NON_EXISTENT === $code) { //404文件不存在时，直接返回状态码
            $this->stopPropagation();
            return $response->withStatus(StatusCode::ERR_NON_EXISTENT);
        }

        if (env('APP_ENV', '') == 'dev') {
            $data = json_encode([
                'code' => $throwable->getCode(),
                'msg'  => $throwable->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        } else {
            $data = json_encode([
                'code' => $code,
                'msg'  => "error", // 非开发模式下屏蔽报错，不暴露给前端
            ], JSON_UNESCAPED_UNICODE);
        }

        return $response
            ->withHeader("Content-Type", "application/json;charset=utf-8")
            ->withStatus(200)
            ->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
