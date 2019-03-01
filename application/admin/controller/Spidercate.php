<?php
namespace app\admin\controller;

use app\admin\controller\Base as BaseController;
use think\facade\Request;
use app\admin\service\SpiderCateService;
use app\admin\validate\SpiderCateValidate;

class Spidercate extends BaseController
{
    /**
     * 初始化方法
     *
     * @param  \app\admin\service\SpiderCateService $spiderCate
     */
    public function __construct(SpiderCateService $spiderCate)
    {
        parent::__construct();

        $this->service = $spiderCate;
    }

    /**
     * 主页
     *
     * @return \think\response\View
     */
    public function index()
    {
        return view();
    }

    /**
     * 添加页面
     *
     * @return \think\response\View
     */
    public function add()
    {
        return view();
    }

    /**
     * 新增操作
     *
     * @return \think\response\Redirect
     */
    public function store(SpiderCateValidate $SpiderCateValidate)
    {
        $param = Request::param();

        if(!$SpiderCateValidate->check($param)){
            $this->error('提交数据错误',null,$SpiderCateValidate->getError());
        }

        $this->service->store($param);

        return redirect('spidercate/index')->with(
            ['success'=>true,'msg'=>'新增成功！']
        );
    }

    /**
     * 编辑页面
     *
     * @param $id
     *
     * @return \think\response\View
     */
    public function edit($id)
    {
        $data = $this->service->getById($id);

        return view('',compact('data'));
    }

    /**
     * 更新操作
     *
     * @return \think\response\Redirect
     */
    public function update(SpiderCateValidate $SpiderCateValidate)
    {
        $param = Request::param();

        if(!$SpiderCateValidate->check($param)){
            $this->error('提交数据错误',null,$SpiderCateValidate->getError());
        }

        $this->service->update($param);

        return redirect('spidercate/index')->with(
            ['success'=>true,'msg'=>'修改成功！']
        );
    }
}
