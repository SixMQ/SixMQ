<?php
namespace SixMQ\Api\Enums;

use Imi\Enum\BaseEnum;
use Imi\Enum\Annotation\EnumItem;

class MessageStatus extends BaseEnum
{
    /**
     * @EnumItem("空闲")
     */
    const FREE = 1;

    /**
     * @EnumItem("工作中")
     */
    const WORKING = 2;

    /**
     * @EnumItem("成功")
     */
    const SUCCESS = 3;

    /**
     * @EnumItem("消费失败")
     */
    const FAIL = 4;

    /**
     * @EnumItem("超时失败")
     */
    const TIMEOUT = 5;

}