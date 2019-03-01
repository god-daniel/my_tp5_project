<?php

namespace app\admin\model;
use think\Model;

class SpiderCate extends Model
{
    protected $auto = [];
    protected $pk = 'sp_cateid';
    protected $insert = ['created_at','updated_at'];
    protected $update = ['updated_at'];
    protected $type = [
        'sp_cateid'             =>  'integer',
        'admins_id'             =>  'integer',
        'cate_name'             =>  'string',
        'cate_description'     =>  'string',
        'created_at'            =>  'datetime',
        'updated_at'            =>  'datetime',
    ];

    // 定义全局的查询范围
    protected function base($query)
    {
        $admins_id = session('admin');
        if($admins_id != 1){
            $query->where('admins_id',$admins_id);
        }
    }

    protected function setCreatedAtAttr()
    {
        return get_time();
    }

    protected function setUpdatedAtAttr()
    {
        return get_time();
    }
}
