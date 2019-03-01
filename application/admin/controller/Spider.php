<?php
namespace app\admin\controller;

use app\admin\controller\Base as BaseController;
use think\facade\Request;
use app\admin\service\SpiderService;
use app\admin\validate\SpiderValidate;

class Spider extends BaseController
{
    /**
     * 初始化方法
     *
     * @param  \app\admin\service\SpiderService $spider
     */
    public function __construct(SpiderService $spider)
    {
        parent::__construct();

        $this->service = $spider;
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
    public function store(SpiderValidate $SpiderValidate)
    {
        $param = Request::param();

        if(!$SpiderValidate->check($param)){
            $this->error('提交数据错误',null,$SpiderValidate->getError());
        }

        $this->service->store($param);

        return redirect('Spider/index')->with(
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
    public function update(SpiderValidate $SpiderValidate)
    {
        $param = Request::param();

        if(!$SpiderValidate->check($param)){
            $this->error('提交数据错误',null,$SpiderValidate->getError());
        }

        $this->service->update($param);

        return redirect('Spider/index')->with(
            ['success'=>true,'msg'=>'修改成功！']
        );
    }
}
