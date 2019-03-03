<?php

namespace app\common\model;

use think\Model;

class FundDayList extends Model
{
	
	protected $pk = 'id';
	protected $autoWriteTimestamp = true;  //开启自动写入时间  create_time和update_time两个字段的值，默认为整型（int）

    // 模型初始化
    protected static function init()
    {
        //TODO:初始化内容
    }	
}
