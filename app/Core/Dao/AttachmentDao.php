<?php

declare(strict_types=1);

namespace App\Core\Dao;


use App\Constants\StatusCode;
use App\Exception\BusinessException;
use App\Model\File;

/**
 * 附件服务
 */
class AttachmentDao
{

    /**
     * getList
     * 条件获取友情链接列表
     * @param array $where 查询条件
     * @param array $order 排序条件
     * @param int $offset 偏移
     * @param int $limit 条数
     * @return array
     */
    public function getList(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array
    {
        $attachmentModel = new File;
        $list = $attachmentModel->getList($where, $order, $offset, $limit);
        foreach ($list as &$v) {
            $v['size_alias'] = formatBytes($v['size']);
            $v['path_alias'] = $v['path'] && mb_strlen($v['path']) > 32 ? mb_substr($v['path'], 0, 32) . '...' : '';
            $v['title_alias'] = $v['title'] && mb_strlen($v['title']) > 16 ? mb_substr($v['title'], 0, 16) . '...' : '';
        }
        unset($v);

        return $list;
    }

    /**
     * getPagesInfo
     * 获取分页信息
     * @param array $where
     * @return int[]
     */
    public function getPagesInfo(array $where = []): array
    {
        $attachmentModel = new File;
        $pageInfo = $attachmentModel->getPagesInfo($where);

        return $pageInfo;
    }

    /**
     * getInfo
     * 获取附件信息
     * @param $id
     * @return array
     */
    public function getInfo($id): array
    {
        if (!$id) {
            return [];
        }
        $attachmentModel = new File;
        $info = $attachmentModel->getInfo($id);

        return $info;
    }

    /**
     * addAttachment
     * 添加附件
     * @param mixed $userId
     * @access public
     * @return void
     */
    public function addAttachment($userId)
    {
        $saveData = [
            'title' => time(),
            'user_id' => $userId
        ];
        $attachmentModel = new File;
        return $attachmentModel->saveInfo($saveData);
    }

    /**
     * saveAttachment
     * 保存附件信息
     * @param $inputData
     * @return null
     */
    public function saveAttachment($inputData)
    {
        $saveData = [];
        if (isset($inputData['id']) && $inputData['id']) {
            $saveData['id'] = $inputData['id'];
        }
        if (isset($inputData['title'])) {
            $saveData['title'] = $inputData['title'];
        }
        if (isset($inputData['filename'])) {
            $saveData['filename'] = $inputData['filename'];
        }
        if (isset($inputData['original_name'])) {
            $saveData['original_name'] = $inputData['original_name'];
        }
        if (isset($inputData['path'])) {
            $saveData['path'] = $inputData['path'];
        }
        if (isset($inputData['type'])) {
            $saveData['type'] = $inputData['type'];
        }
        if (isset($inputData['size'])) {
            $saveData['size'] = $inputData['size'];
        }
        if (isset($inputData['md5'])) {
            $saveData['md5'] = $inputData['md5'];
        }
        if (isset($inputData['user_id'])) {
            $saveData['user_id'] = $inputData['user_id'];
        }
        if (isset($inputData['module'])) {
            $saveData['module'] = $inputData['module'];
        }
        $attachmentModel = new File;
        return $attachmentModel->saveInfo($saveData);
    }

    /**
     * deleteInfo
     * 根据id删除信息
     * @param $id
     * @param string $type delete删除|restore恢复
     * @return int
     */
    public function deleteInfo($id, string $type = 'delete'): int
    {
        $attachmentModel = new File;
        return $attachmentModel->deleteInfo($id, $type);
    }

    public function selectFile($where, $column = ['*']): array
    {
        $model = new File;
        $model = $model->select($column)->where($where)->get();
        return $model ? $model->toArray() : [];
    }

}