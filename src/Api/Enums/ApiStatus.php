<?php
namespace SixMQ\Api\Enums;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;

/**
 * 接口状态码
 */
class ApiStatus extends BaseEnum
{
    /**
     * @EnumItem("成功")
     */
    const SUCCESS = 0;

    /**
     * @EnumItem("未找到资源")
     */
    const NOT_FOUND = 404;

    /**
     * @EnumItem("系统错误")
     */
    const ERROR = 500;

    /**
     * @EnumItem("未登录授权")
     */
    const NOT_LOGIN = 50001;

}