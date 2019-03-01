<?php

namespace app\admin\service;

use app\admin\model\SpiderCate;

class SpiderCateService
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
    public function __construct(SpiderCate $SpiderCate)
    {
        $this->model = $SpiderCate;
    }

    /**
     * 爬虫分类分页
     *
     * @return \think\Paginator
     */
    function getList()
    {
        return $this->model->paginate(...$this->getPaginateDefault());
    }

    /**
     * 获取爬虫分类列表
     *
     * @return array|\PDOStatement|string|\think\Collection
     */
    function getSpiderCateList()
    {
        $parent_list = $this->model->select();

        return $parent_list;
    }
}
