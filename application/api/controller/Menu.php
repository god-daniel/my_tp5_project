<?php
/**
 * Created by ecitlm.
 * User: ecitlm
 * Date: 2017/9/23
 * Time: 00:18
 */

namespace app\api\controller;
use QL\QueryList;
use think\Controller;

class Menu extends Controller{
    private $pageNo;
    private $keywords;
	private $host = "https://www.xinshipu.com";
    public function index(){
		$url = $this->host;
		$ql = QueryList::get($url);
		//获取class为w632元素下的所有元素
		//$data = $ql->find('.w632')->htmls();
		//获取class为w632元素下的所有h2元素
		//$data = $ql->find('.w632 h2')->htmls();
		//获取class为w632元素下的第二个h2元素孩子节点
		//$data = $ql->find('.w632 h2:eq(1)')->htmls();
		$data = $ql->find('.w632 h2')->htmls();
/* 		$data = $ql->find('.w632')->children()->map(function ($item){
			//用is判断节点类型
			if($item->is('a')){
				return $item->text();
			}elseif($item->is('img'))
			{
				return $item->alt;
			}
		}); */
       //打印结果
       print_r($data->all());
    }
	
	//示例
	public function example(){
		
		$url = $this->host;
		$ql = QueryList::get($url);
		//获取class为w632元素下的所有元素
		//$data = $ql->find('.w632')->htmls();
		//获取class为w632元素下的所有h2元素
		//$data = $ql->find('.w632 h2')->htmls();
		//获取class为w632元素下的第二个h2元素孩子节点
		//$data = $ql->find('.w632 h2:eq(1)')->htmls();
		$data = $ql->find('.w632 h2')->htmls();
/* 		$data = $ql->find('.w632')->children()->map(function ($item){
			//用is判断节点类型
			if($item->is('a')){
				return $item->text();
			}elseif($item->is('img'))
			{
				return $item->alt;
			}
		}); */
       //打印结果
       print_r($data->all());
    }
	
	//搜索
    public function search(){
		$keywords = '';
		if(!empty($_REQUEST['k'])){
			$keywords = $_REQUEST['k'];
		}
		$rules = [
			//一个规则只能包含一个function
			//采集class为pt30的div的第一个h1文本
			'name' => ['div.pt30 h1:eq(0)','text'],
			'page' => ['div.pt30>.w632>.paging ul','html','',function($content){
				//$content是元素
				$runs = [
				//得到a标签的文本信息
				'pageNum' => ['a','text'],
				//得到a标签的链接属性
				'pageLink' => ['a','href'],
				];
				$num = QueryList::html($content)->rules($runs)->query()->getData()->all();
				return $num;
			}],
			'list' => ['div.pt30>.w632>.search-menu-list','html','',function($content1){
				//$content是元素
				$runs1 = [
				//得到a标签的文本信息
				'title' => ['a','title'],
				//得到a标签的链接属性
				'link' => ['a','href'],
				'img' => ['img','src'],
				];
				$num1 = QueryList::html($content1)->rules($runs1)->query()->getData()->all();
				return $num1;
			}],
			//并移除内容中的a标签内容，移除id为footer标签的内容，保留img标签
			//'list' => ['div.pt30>.w632','html','-a -#footer img'],
			//采集第二个div的html内容，并在内容中追加了一些自定义内容
/* 			'name3' => ['div:eq(1)','html','',function($content){
				$content += 'some str...';
				return $content;
			}] */
		];
		$url = $this->host.'/doSearch.html?q='.$keywords;
		$ql = QueryList::get($url);
		$data = $ql->rules($rules)->query()->getData()->all();
		$ql->destruct();
		var_dump($data);
		
	}
}