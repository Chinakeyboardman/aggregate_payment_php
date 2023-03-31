<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;


/**
 * 自定义业务代码规范如下：
 * 授权相关，1001……
 * 用户相关，2001……
 * 业务相关，3001……
 * @Constants
 */
class StatusCode extends AbstractConstants
{

    /**
     * @Message("操作成功")
     */
    const SUCCESS = 200;

    /**
     * @Message("Internal Server Error!")
     */
    const ERR_SERVER = 500;

    /**
     * @Message("方法不存在!")
     */
    const ERR_NON_EXISTENT = 404;


    /**
     * @Message("无权限访问！")
     */
    const ERR_NOT_ACCESS = 1001;

    /**
     * @Message("令牌过期！")
     */
    const ERR_EXPIRE_TOKEN = 1002;

    /**
     * @Message("令牌无效！")
     */
    const ERR_INVALID_TOKEN = 1003;

    /**
     * @Message("令牌不存在，请先登录！")
     */
    const ERR_NOT_EXIST_TOKEN = 1004;

    /**
     * @Message("数据保存失败")
     */
    const ERR_DATA_SAVE_FAIL = 4004;

    /**
     * @Message("数据已经存在")
     */
    const ERR_DATA_DUPLICATE = 4001;

    /**
     * 数据库响应失败
     * @Message("数据库响应出错，多次出现请联系管理员！")
     */
    const ERR_EXCEPTION_DATABASE = 4005;

    /**
     * @Message("请登录！")
     */
    const ERR_NOT_LOGIN = 700;

    /**
     * @Message("用户信息错误！")
     */
    const ERR_USER_INFO = 2002;

    /**
     * @Message("用户不存在！")
     */
    const ERR_USER_ABSENT = 2003;


    /**
     * @Message("业务逻辑异常！")
     */
    const ERR_EXCEPTION = 3001;

    /**
     * 用户相关逻辑异常
     * @Message("用户密码不正确！")
     */
    const ERR_EXCEPTION_USER = 3002;

    /**
     * 文件上传
     * @Message("文件上传异常！")
     */
    const ERR_EXCEPTION_UPLOAD = 3003;

    /**
     * 参数不正确
     * @Message("提交的数据存在格式或内容错误！")
     */
    const ERR_EXCEPTION_PARAMETER = 3004;

    /**
     * @Message("提交的参数异常")
     */
    const ERR_PARAMETER = 3004;

    /**
     * @Message("删除失败")
     */
    const ERR_DELETE_DATA = 3005;




    //用户已被禁用，请联系管理员
    /**
     * @Message("用户已被禁用，请联系管理员")
     */
    const USER_ALREADY_DISABLE = 20124;

    /**
     * @Message("缺少参数")
     */
    const EXECUTE_PARAMS = 10005;

    //用户名已存在
    /**
     * @Message("用户名已存在")
     */
    const USERNAME_EXISTENCE = 20401;

    //新增用户失败
    /**
     * @Message("新增用户失败")
     */
    const USER_CREATE_FAIL = 20403;

    /**
     * @Message("用户不存在")
     */
    const USER_NOT_FOUND = 20402;

    /**
     * @Message("没有权限修改自身信息")
     */
    const NOT_MODIFY_SELF = 20116;

    /**
     * @Message("没有操作权限")
     */
    const NOT_OPERATE_POWER = 20122;

    /**
     * @Message("用户更新失败")
     */
    const USER_SAVE_FAIL = 20404;

    /**
     * @Message("没有删除管理员权限")
     */
    const NOT_DELETE_ADMIN = 20120;

    /**
     * @Message("删除用户失败")
     */
    const USER_DELETE_FAIL = 20403;

    /**
     * @Message("不能删除自己")
     */
    const CANT_DELETE_ME = 20403;

    /**
     * @Message("数据不存在")
     */
    const NOT_DATA_EXTREME = 10108;

    /**
     * @Message("身份证号码格式错误")
     */
    const ID_CARD_FORMAT_ERROR = 10129;

    /**
     * @Message("更新失败")
     */
    const UPDATE_FAIL = 20508;

    /**
     * @Message("导入文件的扩展名错误")
     */
    const CLUE_IMPORT_EXTENSION = 20507;

    /**
     * @Message("上传文件失败")
     */
    const UPLOAD_FILE_FAIL = 20506;

    /**
     * @Message("导入失败")
     */
    const IMPORT_FAIL = 20505;

    /**
     * @Message("文件不存在")
     */
    const FILE_NOT_FOUND = 20406;

    /**
     * @Message("添加线索失败")
     */
    const CLUE_CREATE_FAIL = 20502;

    /**
     * @Message("申请外协失败")
     */
    const ASSIST_CREATE_FAIL = 20501;

    /**
     * @Message("驳回失败");
     */
    const ASSIGN_FAIL = 20503;


}