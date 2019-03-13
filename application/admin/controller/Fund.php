<?php
namespace app\admin\controller;

use app\admin\controller\Base as BaseController;
use think\facade\Request;
use app\admin\service\FundService;
use app\admin\validate\FundValidate;

class Fund extends BaseController
{
    /**
     * 初始化方法
     *
     * @param  \app\admin\service\FundService $Fund
     */
    public function __construct(FundService $Fund)
    {
        parent::__construct();

        $this->service = $Fund;
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
    public function store(FundValidate $FundValidate)
    {
        $param = Request::param();

        if(!$FundValidate->check($param)){
            $this->error('提交数据错误',null,$FundValidate->getError());
        }

        $this->service->store($param);

        return redirect('Fund/index')->with(
            ['success'=>true,'msg'=>'新增成功！']
        );
    }

    /**
     * 买操作
     *
     * @return \think\response\Redirect
     */
    public function buys(FundValidate $FundValidate)
    {
        $param = Request::param();

        if(!$FundValidate->check($param)){
            $this->error('提交数据错误',null,$FundValidate->getError());
        }

        $this->service->store($param);

        return redirect('Fund/index')->with(
            ['success'=>true,'msg'=>'新增成功！']
        );
    }
	
    /**
     * 操作
     *
     * @return \think\response\Redirect
     */
    public function sells(FundValidate $FundValidate)
    {
        $param = Request::param();

        if(!$FundValidate->check($param)){
            $this->error('提交数据错误',null,$FundValidate->getError());
        }

        $this->service->update($param);

        return redirect('Fund/index')->with(
            ['success'=>true,'msg'=>'修改成功！']
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
    public function update(FundValidate $FundValidate)
    {
        $param = Request::param();

        if(!$FundValidate->check($param)){
            $this->error('提交数据错误',null,$FundValidate->getError());
        }

        $this->service->update($param);

        return redirect('Fund/index')->with(
            ['success'=>true,'msg'=>'修改成功！']
        );
    }
}
