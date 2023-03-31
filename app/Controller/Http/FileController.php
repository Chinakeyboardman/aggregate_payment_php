<?php
declare(strict_types=1);
/**
 * user:cjw
 * time:2022/5/8 22:17
 */

namespace App\Controller\Http;

use App\Constants\StatusCode;
use App\Exception\BusinessException;
use App\Model\BaseModel;
use App\Core\Service\FileService;
use App\Core\Service\FileUpload;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

/**
 * FileController
 * @package \App\Controller\Http
 * @property FileService $fileService
 */
class FileController extends BaseController
{

    /**
     * 上传文件到临时目录
     * user:cjw
     * time:2022/5/17 16:14
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return Psr7ResponseInterface
     */
    public function uploadFile(RequestInterface $request, ResponseInterface $response): Psr7ResponseInterface
    {
//        var_dump(['上传文件']);
        $reqParam = $request->all();
        $files = $request->getUploadedFiles();
        if (empty($files)) {
            throw new BusinessException(StatusCode::ERR_EXCEPTION, '上传文件为空');
        }

        if (isset($files['file'])) {
            $upFiles = $files['file'];
        } else {
            throw new BusinessException(StatusCode::ERR_EXCEPTION_UPLOAD, '上传文件不存在！');
        }

        $fileList = [];
        if (is_array($upFiles)) {
            foreach ($upFiles as $k => $v) {
                // 如果没有md5，算一遍
                if (!isset($reqParam['md5']) || empty($reqParam['md5'])) {
                    $reqParam['md5'] = md5_file($v->getPathname());
                }
                // 根据md5查看今日是否上传过相同文件
                $where = [
                    ['md5', '=', $reqParam['md5']],
                    ['created_at', '>=', date("Y-m-d 00:00:00")],
                ];
                $res = $this->fileService->selectFile($where);
                $res = $res ? [end($res)] : [];
                $fileList = array_merge($fileList, $res);
                // 文件保存逻辑
                if (empty($res)){
                    $instance = make(FileUpload::class, [$v, $reqParam]);
                    $instance->uploadFile();
                    $fileList[] = $instance->getFileInfo();
                }
            }
        } else {
            // 如果没有md5，算一遍
            if (!isset($reqParam['md5']) || empty($reqParam['md5'])) {
                $reqParam['md5'] = md5_file($upFiles->getPathname());
            }
            // 根据md5查看今日是否上传过相同文件
            $where = [
                ['md5', '=', $reqParam['md5']],
                ['created_at', '>=', date("Y-m-d 00:00:00")],
            ];
            $fileList = $this->fileService->selectFile($where);
            $fileList = $fileList ? [end($fileList)] : [];
            // 文件保存逻辑
            if (empty($fileList)){
                $instance = make(FileUpload::class, [$upFiles, $reqParam]);
                $instance->uploadFile();
                $fileList[] = $instance->getFileInfo();
            }
        }

//        return $this->success($fileList);
        return $response->json(['code' => 200, 'msg' => '成功!', 'data' => $fileList]);
    }

    public function moveFile()
    {
        //
    }

    /**
     * 下载文件
     * user:cjw
     * time:2022/5/17 16:14
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return Psr7ResponseInterface
     */
    public function downloadFile(RequestInterface $request, ResponseInterface $response): Psr7ResponseInterface
    {
        $params = $request->all();
        $filePath = $params['path'];//数据库记录的文件名
        $name = $params['name'] ?? '';//数据库记录的文件名
        // 系统文件目录设置
        $rootPath = rtrim(config('upload.upload_path'), "/");
        $uploadPath = $params['module'] ?? trim(config('upload.attachments'));
        $path = $rootPath . "/" . $uploadPath;
        // 数据库存的相对文件地址
        $file = $path . $filePath;
//        $file = $path . '/20220510/T20220510115848_16816.txt';
        // 文件传输
        return $response->download($file, $name);
    }

    /**
     * 流传图片到前端(直接根据id拿到file表数据，拿相对路径就能传输到前端)
     * user:cjw
     * time:2022/5/13 12:12
     * @param RequestInterface $request
     * @return false|string
     */
    public function photo(RequestInterface $request)
    {
        $params = $request->all();
        $filePath = $params['path'];//数据库记录的文件名
        // 系统文件目录设置
        $rootPath = rtrim(config('upload.upload_path'), "/");
        $uploadPath = $params['module'] ?? trim(config('upload.attachments'));
        $path = $rootPath . "/" . $uploadPath;
        // 数据库存的相对文件地址
        $file = $path . $filePath;
        header('content-type:image/jpg;');
        if (!is_file($file)) {
            throw new BusinessException(StatusCode::FILE_NOT_FOUND);
        }
        return file_get_contents($file);
    }

}