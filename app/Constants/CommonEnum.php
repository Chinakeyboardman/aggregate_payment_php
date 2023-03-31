<?php


namespace App\Constants;

/**
 * 公共枚举类
 *
 * Class CommonEnum
 * @package App\Constants
 */
class CommonEnum
{
    //用户名允许字母数字下划线
    public const USERNAME_REGEX = '/^[\w_]{1,32}$/i';

    //密码允许6-12字节,允许字母数字下划线和减号字符
//    public const PASSWORD_REGEX = '/^[\w_-]{6,12}$/i';
    public const PASSWORD_REGEX = '/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z#@!~%^&*-_]{6,12}$/';//不能纯数字和字母，必须至少组合两种字符

    //密码不允许弱口令
    public const PASSWORD_NOT_REGEX = '/(?![0-9A-Z]+$)(?![0-9a-z]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,12}$/';

    //手机号验证
    public const PHONE = '/0?(13|14|15|17|18|19)[0-9]{9}/';

    //银行卡号正则表达式
    public const BANK_CARD = '/^[0-9]{12,19}$/i';

    public const CHECK_SQL = '/[\s]*(select|insert|update|delete)\s|\s(and|or|join|like|regexp|where|union|into)\s|\#|\'|\\*|\*|\.\.\/|\.\/|load_file|outfile/i';

    public const SPECIAL_CHARACTER = '/((?=[\x21-\x7e]+)[^A-Za-z0-9])/';

    /** @var string[] 用户信息 */
    public const USERINFO = [
        'username'       => '用户名',
        'password'       => '密码',
        'is_admin'       => '是否管理员',
        'name'           => '真实姓名',
        'status'         => '账户状态',
        'create_id'      => '创建者id',
        'phone'          => '手机号',
        'unit_name'      => '单位名称',
        'duties'         => '职务',
        'province'       => '省id',
        'city'           => '市id',
        'area'           => '区/县id',
        'police_station' => '派出所id',
        'police_room'    => '警务室id',
    ];

    /** @var array 各诈骗类型劝阻金额指标 */
    public const STOP_STATUS = [
        1 => 1.32, //网购诈骗
        2 => 0, //信息欺诈
        5 => 4.45, //公检法诈骗
        6 => 0.88, //贷款诈骗
        7 => 1.32, //杀猪盘诈骗
        8 => 3.08, //仿冒他人诈骗
        9 => 0, //其他诈骗
    ];

    //模块排序字段固定增量
    public const MODULE_SORT_INCREMENT = 4096;

    //用户服务的访问前缀
//    public const USER_SERVICE_PREFIX = 'zh-user-service';

    /** @var string[] 开普勒对应的诈骗类型拼音 */
    public const KPL_FRAUD_TYPE = [
        '贷款诈骗'     => 'dkzp',
        '刷单诈骗'     => 'sdzp',
        '征婚交友'     => 'zhjy',
        '投资诈骗'     => 'tzzp',
        '赌博诈骗'     => 'dbzp',
        '网络招嫖'     => 'wlzp',
        '裸聊诈骗'     => 'llzp',
        '买卖游戏装备诈骗' => 'mmyxzbzp',
        '冒充公检法'    => 'mcgjf',
        '网购诈骗'     => 'wgzp',
        '冒充他人'     => 'mctr',
        '冒充客服退款'   => 'mckftk',
        '其他'       => 'qt',
    ];
}
