<?php
namespace app\admin\validate;

use think\Validate;

class SpiderCateValidate extends Validate
{
    protected $batch = true; //开启批量验证

    protected $rule = [
        'cate_name'  => 'require|max:25',
    ];

    protected $message  =   [
        'cate_name.require' => '名称必须',
    ];
}