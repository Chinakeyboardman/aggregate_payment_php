<?php
/**
 * 上传文件服务，请使用make, 像使用model一样调用，禁止直接调用(不用new或者make会数据错乱)
 * user:cjw
 * time:2022/5/9 20:57
 */

namespace App\Core\Service;

use App\Constants\CommonEnum;
use App\Constants\StatusCode;
use App\Exception\BusinessException;
use Hyperf\Di\Annotation\Inject;
use \App\Core\Dao\AttachmentDao;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface as ValidationFactory;

class FileUpload
{

    private $file;     //上传对象
    private $config;   //配置信息
    private $oriName;  //原始文件名，包含扩展名
    private $filename; //新文件名，包含扩展名
    private $uploadPath; //相对路径
    private $fileSize; //文件大小
    private $fileType; //文件类型，后缀名
    private $fileMd5; //文件类型
    private $stateInfo; //上传状态信息,
    private $id = null; // 数据库存储主键id
    private $stateMap = array( //上传状态
        "SUCCESS", //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
        "文件大小超出 upload_max_filesize 限制",
        "文件大小超出 MAX_FILE_SIZE 限制",
        "没有文件被上传",
        "上传文件为空",
        "ERROR_FILE_NOT_COMPLETE" => "文件未被完整上传",
        "ERROR_TMP_FILE" => "临时文件错误",
        "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
        "ERROR_SIZE_EXCEED" => "文件大小超出网站限制",
        "ERROR_TYPE_NOT_ALLOWED" => "文件类型不允许",
        "ERROR_CREATE_DIR" => "目录创建失败",
        "ERROR_DIR_NOT_WRITEABLE" => "目录没有写权限",
        "ERROR_FILE_MOVE" => "文件保存时出错",
        "ERROR_FILE_NOT_FOUND" => "找不到上传文件",
        "ERROR_FILE_NAME_NOT_SAVE" => "文件名不安全",
        "ERROR_WRITE_CONTENT" => "写入文件内容错误",
        "ERROR_UNKNOWN" => "未知错误",
        "ERROR_DEAD_LINK" => "链接不可用",
        "ERROR_HTTP_LINK" => "链接不是http链接",
        "ERROR_HTTP_CONTENT_TYPE" => "链接contentType不正确",
    );
    private $fileInfo = [];
    private $uploadParams;//http请求参数

    /**
     * @Inject()
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    public function __construct($file, $uploadParams = [])
    {
        $this->file = $file;
        $this->config = config('upload');
        $this->uploadPath = $uploadParams['upload_path'] ?? $this->getUploadPath();
        $this->uploadParams = $uploadParams;
    }

    /**
     * @Inject()
     * @var AttachmentDao
     */
    private $attachmentDao;//只读不写

    private function getUploadPath(): string
    {
        $attachments = trim($this->config['attachments'], "/");
        $timePath = date('Ymd');
        $uploadPath = $attachments . "/" . $timePath . "/";
        return $uploadPath;
    }

    /**
     * 上传文件入口
     * user:cjw
     * time:2022/5/11 14:33
     * @param string $type
     */
    public function uploadFile(string $type = 'upload')
    {
        if ($type == "upload") {
            $this->upFile();
        } else {
            throw new BusinessException(StatusCode::ERR_EXCEPTION_UPLOAD, '错误的上传类型！');
        }
        $fileInfo = $this->getFileInfo();
//        var_dump($fileInfo);
        if ($fileInfo['state'] == 'SUCCESS') {
            $res = $this->saveDatabase($fileInfo);
        } else {
            $error = $fileInfo['state'] ?? '未知错误';
            throw new BusinessException(StatusCode::ERR_EXCEPTION_UPLOAD, $error);
        }
    }

    /**
     * 存库
     * user:cjw
     * time:2022/5/11 14:29
     * @param array $fileInfo
     * @return bool
     */
    private function saveDatabase(array $fileInfo = []): bool
    {
        if (!$fileInfo) {
            return false;
        }
        if (!isset($fileInfo['user_id'])) {
            $userInfo = getUserInfo();
            $userId = $userInfo['id'];
        } else {
            $userId = $fileInfo['user_id'];
        }
        $saveData = [
            'title' => $fileInfo['title'],
            'original_name' => $fileInfo['original'],
            'filename' => $fileInfo['filename'],
            'path' => $fileInfo['path'],
            'type' => $fileInfo['type'],
            'size' => $fileInfo['size'],
            'md5' => $fileInfo['md5'],
            'user_id' => $userId
        ];
        $this->id = $this->attachmentDao->saveAttachment($saveData);
        return true;
    }

    /**
     * 上传文件逻辑
     * user:cjw
     * time:2022/5/11 14:33
     */
    private function upFile()
    {
        /** 校验上传的合法性 */
        if (!$this->file) {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
            return;
        }
        /** 获取上传文件信息 */
        $arr = $this->file->toArray();
        if ($arr['error']) {
            $this->stateInfo = $this->getStateInfo($arr['error']);
            return;
        }
        if (!file_exists($arr['tmp_file'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE_NOT_FOUND");
            return;
        }
        if (!is_uploaded_file($arr['tmp_file'])) {
            $this->stateInfo = $this->getStateInfo("ERROR_TMP_FILE");
            return;
        }
        $this->oriName = $arr['name'];
        $this->fileSize = $arr['size'];

        $this->fileType = $this->file->getExtension();// 后缀名

        /** 安全性校验 */
        $validator = $this->validationFactory->make(
            $arr,
            [
                'name' => ['required', 'not_regex:' . CommonEnum::CHECK_SQL],
            ],
            [
                'name.required' => '文件名称不能为空',
                'name.not_regex' => '不合法文件名',
            ]
        );
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first();
            $this->stateInfo = $errorMessage;
            return;
        }
        /** 检查文件大小是否超出限制 */
        if (!$this->checkSize()) {
            $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
            return;
        }
        /** 检查是否不允许的文件格式 */
        if (!$this->checkType()) {
            $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
            return;
        }
        /** 对上传文件进行md5计算 */
        $this->fileMd5 = md5_file($this->file->getPathname());
//        if (isset($this->uploadParams['md5']) && $this->uploadParams['md5'] && $this->uploadParams['md5'] !== $this->fileMd5){
//            $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_COMPLETE");
//            return;
//        }
        $this->filename = $this->getFilename();//文件名
        /** 存储位置 */
        $savePath = $this->getSavePath();
        $targetPath = $this->getSavePath() . $this->filename;
        /** 创建目录失败 */
        if (!file_exists($savePath) && !mkdir($savePath, 0777, true)) {
            $this->stateInfo = $this->getStateInfo("ERROR_CREATE_DIR");
            return;
        }
        if (!is_writeable($savePath)) {
            $this->stateInfo = $this->getStateInfo("ERROR_DIR_NOT_WRITEABLE");
            return;
        }
        /** 存储文件 */
        $this->file->moveTo($targetPath);
        /** 判断文件是否已经移动 */
        if ($this->file->isMoved()) {
            $this->stateInfo = $this->stateMap[0];
        } else {
            $this->stateInfo = $this->getStateInfo("ERROR_FILE_MOVE");
        }
    }

    private function getStateInfo($key)
    {
        return !$this->stateMap[$key] ?? $this->stateMap["ERROR_UNKNOWN"];
    }

    public function getFileInfo(): array
    {
        $this->fileInfo['id'] = $this->id;
        $this->fileInfo['state'] = $this->stateInfo;
        $filePath = "/" . trim($this->uploadPath, "/") . "/" . $this->filename;
        // 配置文件附件目录参数过滤，改参数不存库
        $attachments = $this->config['attachments'];
        if ($attachments) {
            $this->fileInfo['path'] = str_replace("/{$attachments}", '', $filePath);
        } else {
            $this->fileInfo['path'] = $filePath;
        }
//        $this->fileInfo['full_path'] = $this->attachmentDao->getAttachmentFullUrl($this->fileInfo['path']);
        $this->fileInfo['title'] = str_replace('.' . $this->fileType, '', $this->filename);
        $this->fileInfo['original'] = $this->oriName;
        $this->fileInfo['filename'] = $this->filename;
        $this->fileInfo['type'] = $this->fileType;
        $this->fileInfo['size'] = $this->fileSize;
        $this->fileInfo['md5'] = $this->fileMd5;
        return $this->fileInfo;
    }

    /**
     * user:cjw
     * time:2022/5/10 16:37
     * @return string
     */
    private function getFilename(): string
    {
        // 文件直接按照时间+md5命名
        $format = date('YmdHis') . '_' . $this->fileMd5;
        $ext = $this->fileType;
        return $format . '.' . $ext;
    }

    // 获取保存地址
    private function getSavePath(): string
    {
        $rootPath = rtrim($this->config['upload_path'], "/");
        $uploadPath = trim($this->uploadPath, "/");
        return $rootPath . "/" . $uploadPath . "/";
    }

    private function checkSize(): bool
    {
        return $this->fileSize <= ($this->config["file_max_size"]);
    }

    private function checkType(): bool
    {
        return in_array(strtolower($this->fileType), $this->config["file_allow_files"]);
    }

}