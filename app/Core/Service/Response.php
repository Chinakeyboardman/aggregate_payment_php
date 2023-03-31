<?php


namespace App\Core\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\HttpMessage\Cookie\Cookie;
use App\Constants\StatusCode;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

/**
 * ReqResponse
 * 请求响应结果
 *
 * @package App\Service
 */
class Response
{

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 成功返回请求结果
     * success
     *
     * @param array       $res
     * @param string|null $msg
     *
     * @return PsrResponseInterface
     */
    public function success(array $res = [], string $msg = null): PsrResponseInterface
    {
        $msg = $msg ?? StatusCode::getMessage(StatusCode::SUCCESS);
        if (isset($res['count']) && isset($res['list'])) { //兼容ok列表模式
            $data = [
                'count' => $res['count'],
                'data'  => $res['list'],
            ];
        } else {
            $data = [
                'data' => $res,
            ];
        }
        $data = array_merge(
            $data,
            [
                'code' => StatusCode::SUCCESS,
                'msg'  => $msg,
            ]
        );
        return $this->response->json($data);
    }

    /**
     * 业务相关错误结果返回
     * error
     * @param int $code
     * @param string|null $msg
     *
     * @return PsrResponseInterface
     */
    public function error(int $code = StatusCode::ERR_EXCEPTION, string $msg = null): PsrResponseInterface
    {
        $msg  = $msg ?? StatusCode::getMessage(StatusCode::ERR_EXCEPTION);
        $data = [
            'code' => $code,
            'msg'  => $msg,
            'data' => [],
        ];
        return $this->response->json($data);
    }

    /**
     * json
     * 直接返回数据
     *
     * @param array $data
     *
     * @return PsrResponseInterface
     */
    public function json(array $data): PsrResponseInterface
    {
        return $this->response->json($data);
    }

    /**
     * xml
     * 返回xml数据
     *
     * @param array $data
     *
     * @return PsrResponseInterface
     */
    public function xml(array $data): PsrResponseInterface
    {
        return $this->response->xml($data);
    }

    /**
     * redirect
     * 重定向
     * @param string $url
     * @param string $schema
     * @param int $status
     *
     * @return PsrResponseInterface
     */
    public function redirect(string $url, string $schema = 'http', int $status = 302): PsrResponseInterface
    {
        return $this->response->redirect($url, $status, $schema);
    }

    /**
     * download
     * 下载文件
     * @param string $name
     *
     * @return PsrResponseInterface
     */
    public function download(string $file, string $name = ''): PsrResponseInterface
    {
        return $this->response->redirect($file, $name);
    }

    /**
     * cookie
     * 设置cookie
     *
     * @param string      $name
     * @param string      $value
     * @param int         $expire
     * @param string      $path
     * @param string      $domain
     * @param bool        $secure
     * @param bool        $httpOnly
     * @param bool        $raw
     * @param null|string $sameSite
     */
    public function cookie(string $name, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true, bool $raw = false, ?string $sameSite = null)
    {
        // convert expiration time to a Unix timestamp
        if ($expire instanceof \DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
            if ($expire === false) {
                throw new \RuntimeException('The cookie expiration time is not valid.');
            }
        }

        $cookie   = new Cookie($name, $value, $expire, $path, $domain, $secure,
            $httpOnly, $raw, $sameSite);
        $response = $this->response->withCookie($cookie);
        Context::set(PsrResponseInterface::class, $response);
        return;
    }

}