<?php

namespace app\admin\service;

use app\admin\model\Spider;

class SpiderService
{
    use Base;

    /**
     * @var Spider
     */
    protected $model;

    /**
     * 初始化方法
     *
     * SpiderService constructor.
     * @param Spider $spider
     */
    public function __construct(Spider $spider)
    {
        $this->model = $spider;
    }

    /**
     * 爬虫分页
     *
     * @return \think\Paginator
     */
    function getList()
    {
        return $this->model->paginate(...$this->getPaginateDefault());
    }

    /**
     * 修改权限操作
     *
     * @param $id
     * @param $perms
     */
    function perm_store($id,$perms)
    {
        $spider = $this->getById($id);

        $spider->perms()->sync($perms);
    }

    /**
     * 获取爬虫配置列表
     *
     * @return array|\PDOStatement|string|\think\Collection
     */
    function getSpiderList()
    {
        $parent_list = $this->model->select();

        return $parent_list;
    }
}
