<?php
/*
/**
* Index 模块服务接口545   176
*/

namespace app\api\controller;
use \think\View;
class Index extends Common
{
    /**
     * 首页
     */
    public function index()
    {
		$str = '246,248,266,269,279,304,310,314,315,316,317,318,319,320,321,323,324,325,326,327,328,329,330,331,332,333,334,335,336,337,338,339,340,342,343,344,345,346,347,348,349,350,351,352,353,354,355,356,357,358,359,360,361,362,363,364,365,366,367,368,369,370,371,372,373,374,433,419,412,408,405,398,404,397,396,395,394,393,392,391,390,389,388,387,386,385,384,383,382,381,380,379,378,377,376,375,435,447,456,455,454,453,449,457,446,444,341,459,470,468,486,487,537,553,556,557,563,567,571,579,583,584,585,590,593,606,601,600,610,608,613,624,627,638,648,655,656,659,675,662,672,676,678,679,682,683,686,689,697,699,704,709,712,713,714,716,721,722,729,728,734,739,740,742,750,754,755,756,761,765,762,768,772,776,777,780';
		$arr = explode(',',$str);
		foreach($arr as $k => $v){
		echo "INSERT INTO `mk_contract_price` VALUES ('','CS112813335908',$v,243,'2');";
		echo "</br>";
		}
        //return view();
    }
	public function article()
    {
		
		$article_cate_id = 2;//市中旅分类
		if($_GET['article_cate_id']){
			$article_cate_id = $_GET['article_cate_id'];
		}
		//初始化
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_URL, 'http://cms.mankkk.cn/port/article/getarticlelist.html');
		 //设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_HEADER, 0);
		 //设置获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		 //设置post方式提交
		curl_setopt($curl, CURLOPT_POST, 1);
		 //设置post数据
		$post_data = array(
			 "article_cate_id" => $article_cate_id
			 );
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		 //执行命令
		$list = curl_exec($curl);
		$temp = json_decode($list, true);
		 //关闭URL请求
		curl_close($curl);
		$info['title'] = '';
		$info['desc'] = '';
		$info['content'] = '';
		foreach($temp['data']['data'] as $k=>$v){
			if($v['id'] == 2 && $_GET['q'] == 't'){
				//投诉
				$info = $v;
			}
			if($v['id'] == 3 && $_GET['q'] == 'j'){
				//公司简介
				$info = $v;
			}
		}
		var_dump($info);
    }
}
