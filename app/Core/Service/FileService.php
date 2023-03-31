<?php
/**
 * user:cjw
 * time:2022/5/10 16:42
 */
declare(strict_types=1);

namespace App\Core\Service;

use App\Core\Dao\AttachmentDao;
use Hyperf\Di\Annotation\Inject;

/**
 * DemoService
 * @package \App\Core\Service
 * @property AttachmentDao $attachmentDao
 */
class FileService extends BaseService
{

    // 移动文件(完整目录)
    public function moveFile($lastPath, $newPath): bool
    {
        if ($lastPath === $newPath){
            return true;
        }
        return rename($lastPath, $newPath);
    }

    // 把临时文件转移到业务永久目录中存储
    public function fileSetForever($id, $module)
    {
        //拿到文件info
        $fileInfo = $this->attachmentDao->getInfo($id);
        if (empty($fileInfo)) {
            return [];
        }
        $fileInfo['module'] = $fileInfo['module'] ?: '';
        $lastPath = $this->getAttachmentRealPath($fileInfo['path'], $fileInfo['module']);
        $arr = explode('/', $fileInfo['path']);
        $fileInfo['path'] = '/' . end($arr);
        $newPath = $this->getAttachmentRealPath($fileInfo['path'], $module);
        $res = $this->moveFile($lastPath, $newPath);
        if ($res) {
            // 修改数据库
            $saveRes = $this->attachmentDao->saveAttachment(['id' => $id, 'path' => $fileInfo['path'], 'module' => $module]);
        }
        return $res;
    }

    /**
     * 获取完整的真实地址
     * user:cjw
     * time:2022/5/11 12:00
     * @param string $path
     * @param string $module
     * @return string
     */
    public function getAttachmentRealPath(string $path, string $module = ''): string
    {
        if (!$module) {
            $module = 'tmp';
        }
        return config('upload.upload_path') . $module . $path;
    }

    public function selectFile($where): array
    {
        return $this->attachmentDao->selectFile($where);
    }

    /**
     * 删除文件，默认软删除
     * user:cjw
     * time:2022/5/11 11:46
     * @param $id
     * @param bool $softDelete
     * @return int
     */
    public function deleteFile($id, bool $softDelete = true): int
    {
        // 判断是否真的删除文件
        if (!$softDelete) {
            $fileInfo = $this->attachmentDao->getInfo($id);
            $fileInfo['module'] = $fileInfo['module'] ?: '';
            $realPath = $this->getAttachmentRealPath($fileInfo['path'], $fileInfo['module']);
            unlink($realPath);
        }
        // 数据表删除
        return $this->attachmentDao->deleteInfo($id);
    }

    /**
     * 根据业务模块把文件搬运到相应目录
     * user:cjw
     * time:2022/5/12 0:51
     * @param $fileData
     * @param string $module
     * @return bool
     */
    public function moveFileByFileJson($fileData, string $module = 'tmp'): bool
    {
        if (is_string($fileData)) {
            $fileData = json_decode($fileData, true);
        }
        if (empty($fileData)) {
            return false;
        }
        // 根据文件json拿到所有的文件id
        $fileIds = $this->getFileIdsByJson($fileData);
        // 批量移动文件到业务目录
        foreach ($fileIds as $id){
            $this->fileSetForever($id, $module);
        }
        return true;
    }

    /**
     * 从无限极目录结构数组中拿出id(递归)
     * user:cjw
     * time:2022/5/12 0:39
     * @param $fileData
     * @return array
     */
    function getFileIdsByJson($fileData): array
    {
        $list = [];
        if (empty($fileData)) {
            return $list;
        }
        if (isset($fileData['type']) && $fileData['type'] !== 'directory') {
            $list[] = $fileData['id'];
            return $list;
        }
        if (isset($fileData['children'])){
            foreach ($fileData['children'] as $datum) {
                if (isset($datum['type']) && $datum['type'] !== 'directory') {
                    $list[] = $datum['id'];
                }
                if (isset($datum['children']) && $datum['children']) {
                    $list = array_merge($list, $this->getFileIdsByJson($datum));
                }
            }
        }
        return $list;
    }

    // 比较更新前后的目录结构
    public function compareFileJsonUpdate(string $lastJson, string $newJson): array
    {
        $lastJson = json_decode($lastJson, true);
        $newJson = json_decode($newJson, true);
        $lastIds = $this->getFileIdsByJson($lastJson);
        $newIds = $this->getFileIdsByJson($newJson);
        $deleteIds = array_diff($lastIds, $newIds);
        $saveIds = array_diff($newIds, $lastIds);
        return [$deleteIds,$saveIds];
    }


}