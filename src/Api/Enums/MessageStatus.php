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
     * @EnumItem("失败")
     */
    const FAIL = 3;

}