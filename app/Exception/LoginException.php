<?php

declare(strict_types=1);

namespace App\Exception;

use App\Constants\StatusCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

/**
 * BusinessException
 * 业务异常处理类
 * @package App\Exception
 */
class LoginException extends ServerException
{
    public function __construct(int $code = 0, string $message = null, Throwable $previous = null)
    {
        if (is_null($message)) {
            $message = StatusCode::getMessage($code);
        }

        parent::__construct($message, $code, $previous);
    }
}
