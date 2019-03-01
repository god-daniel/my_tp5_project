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
use app\api\model\FundBaseList;
use app\api\model\FundDayList;
use app\api\model\FundHistoryList;
use think\Db;
use think\facade\Cache;

class Fund extends Controller{
    private $pageNo;
    private $keywords;
	// 基础基金网址
	private $host = 'http://fund.eastmoney.com/js/fundcode_search.js?v=$nowDate162803';
	private $host3 = 'http://fund.eastmoney.com/data/rankhandler.aspx?op=ph&dt=kf&ft=all&rs=&gs=0&sc=dm&st=desc&sd=$sd&ed=$ed&qdii=&tabSubtype=,,,,,&pi=1&pn=20000&dx=1&v=0.8059335981746323';
    public function index(){

    }
	
	// 每日录入基金数据
	public function funList(){
		$is_gzr = $this->is_jiaoyi_day(strtotime("-1 day"));
		if($is_gzr==0){
			$url3 = $this->host3;
			$sd = date("Y-m-d",strtotime("-2 day"));
			$ed = date("Y-m-d",strtotime("-1 day"));
			$url3 = str_replace('$sd',$sd,$url3);
			$url3 = str_replace('$ed',$ed,$url3);
			$infoList = file_get_contents($url3);
			$year = date("Y");
			$infoList = substr($infoList,strpos($infoList,'[')+1);
			$infoList = substr($infoList,0,strpos($infoList, ']'));
			$arr2 = explode(',',$infoList);
			$history = new FundHistoryList;
			$arr = [];
			$dayArr = [];
			
			$num = count($arr2)/25;
			for ($x=0; $x<$num; $x++) {
				$data['year'] = $year;
				$data['code'] = substr($arr2[$x*25],1);
				$data['jm'] = $arr2[$x*25+2];
				$data['name'] = $arr2[$x*25+1];
				$data['update_date'] = $arr2[$x*25+3];
				$data['fee'] = ($arr2[$x*25+20]?$arr2[$x*25+20]:0)*10000;
				$data['unit_value'] = ($arr2[$x*25+4]?$arr2[$x*25+4]:0)*10000;
				$data['unit_pile_value'] = ($arr2[$x*25+5]?$arr2[$x*25+5]:0)*10000;
				$data['day_grow'] = ($arr2[$x*25+6]?$arr2[$x*25+6]:0)*10000;
				$data['week_grow'] = ($arr2[$x*25+7]?$arr2[$x*25+7]:0)*10000;
				$data['month_grow'] = ($arr2[$x*25+8]?$arr2[$x*25+8]:0)*10000;
				$data['month_three_grow'] = ($arr2[$x*25+9]?$arr2[$x*25+9]:0)*10000;
				$data['month_six_grow'] = ($arr2[$x*25+10]?$arr2[$x*25+10]:0)*10000;
				$data['year_one_grow'] = ($arr2[$x*25+11]?$arr2[$x*25+11]:0)*10000;
				$data['year_two_grow'] = ($arr2[$x*25+12]?$arr2[$x*25+12]:0)*10000;
				$data['year_three_grow'] = ($arr2[$x*25+13]?$arr2[$x*25+13]:0)*10000;
				$data['year_grow'] = ($arr2[$x*25+14]?$arr2[$x*25+14]:0)*10000;
				$data['create_grow'] = ($arr2[$x*25+15]?$arr2[$x*25+15]:0)*10000;
				$arr[$x] = $data;
				$dayArr = $data;
				$dayArr['zdy'] = ($arr2[$x*25+18]?$arr2[$x*25+18]:0)*10000;// 当前代表 昨日的日增长率
				// 使用文件缓存
				Cache::set($data['code'],json_encode($dayArr),3600);
			}
			//$cc = json_decode(Cache::get('770001'),true);
			$history->saveAll($arr);			
		}		
    }
	
	//  添加基础基金数据
	public function addFunList(){
		$is_gzr = $this->is_jiaoyi_day(strtotime("-1 day"));
		if($is_gzr==0){
			Db::query("truncate table sp_fund_base_list");
			$nowDate = date("Ymd");
			$url2 = $this->host;
			$url2 = str_replace('$nowDate',$nowDate,$url2);
			$list = file_get_contents($url2);		
			$list = substr($list,strpos($list,'['));
			$list = substr($list,0,strlen($list)-1);
			$arr1 = json_decode($list);
			$year = date("Y");
			$base = new FundBaseList;
			$arr = [];
			foreach ($arr1 as $k=>$v){
				$temp = [];
				$temp = json_decode(Cache::get($v[0]),true);
				$data['buy_status'] = 1;
				if($temp){
					$data['update_date'] = $temp['update_date'];
					$data['fee'] = $temp['fee'];
					$data['unit_value'] = $temp['unit_value'];
					$data['unit_pile_value'] = $temp['unit_pile_value'];
					$data['day_grow'] = $temp['day_grow'];
					$data['week_grow'] = $temp['week_grow'];
					$data['month_grow'] = $temp['month_grow'];
					$data['month_three_grow'] = $temp['month_three_grow'];
					$data['month_six_grow'] = $temp['month_six_grow'];
					$data['year_one_grow'] = $temp['year_one_grow'];
					$data['year_two_grow'] = $temp['year_two_grow'];
					$data['year_three_grow'] = $temp['year_three_grow'];
					$data['year_grow'] = $temp['year_grow'];
					$data['create_grow'] = $temp['create_grow'];
					$data['buy_status'] = 0;				
				}
				$data['year'] = $year;
				$data['code'] = $v[0];
				$data['jm'] = $v[1];
				$data['name'] = $v[2];
				$data['type'] = $v[3];
				$data['bm'] = $v[4];
				$arr[$k] = $data;
			}
			$base->saveAll($arr);
		}
    }
	//  是否交易日
	public function is_jiaoyi_day($times=''){
		$date = date("Ymd",time());
		if($times){
			$date = date("Ymd",$times);
		}	
        $url = "http://api.goseek.cn/Tools/holiday?date=".$date;
        $res = file_get_contents($url);
        $res = json_decode($res,true);
		// 正常工作日对应结果为 0, 法定节假日对应结果为 1, 节假日调休补班对应的结果为 2，休息日对应结果为 3 
		return $res['data'];
    }		
}