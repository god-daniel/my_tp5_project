<?php

namespace app\admin\service;

use app\common\model\Fund;

class FundService
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
     * @param Fund $Fund
     */
    public function __construct(Fund $Fund)
    {
        $this->model = $Fund;
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
    function getFundList()
    {
        $parent_list = $this->model->select();

        return $parent_list;
    }
}
