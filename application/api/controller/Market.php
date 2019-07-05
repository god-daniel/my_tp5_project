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
use app\common\model\AMarket;
use app\common\model\AMarketFund;
use app\common\model\AMarketFundTemp;
use think\Db;
use think\facade\Cache;
use \think\View;

class Market extends Controller{
    private $pageNo;
    private $keywords;

	// 基础股票采集网址（东方财富）
	
	private $host_base = 'http://21.push2.eastmoney.com/api/qt/clist/get?cb=jQuery1124014069351677765463_1561970756781&pn=1&pz=10000&po=0&np=1&ut=bd1d9ddb04089700cf9c27f6f7426281&fltt=2&invt=2&fid=f2&fs=m:0+t:6,m:0+t:13,m:0+t:80,m:1+t:2&fields=f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f12,f13,f14,f15,f16,f17,f18,f20,f21,f23,f24,f25,f22,f11,f62,f128,f136,f115,f152&_=1561970756952';
	
	// 基础股票采集网址（雪球）
	private $host_two_base = 'https://xueqiu.com/service/screener/screen?category=CN&exchange=sh_sz&areacode=&indcode=&order_by=current&order=asc&page=1&size=10000&only_count=0&current=0.14_987.9&pct=&fmc=40493376_1528701245096&mc=114125000_2020823477695&bps.20190331=-6.17_98.76&eps.20190331=-0.82_8.93&volume_ratio=0_84.79&amount=0_9012422827.11&pct_current_year=-88.82_500&pct5=-26.41_110.86&pct10=-33.93_185.33&pct20=-76.58_310.81&pct60=-88.82_500&_=1562142373571';
	// 股票资金流采集网址（东方财富）
	private $host_money = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&sr=-1&p=1&ps=10000&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=52065637';
	
	// 股票历史资金流采集网址（东方财富）
	private $host_two_money = 'http://ff.eastmoney.com//EM_CapitalFlowInterface/api/js?type=hff&rtntype=2&js=({data:[(x)]})&cb=var%20aff_data=&check=TMLBMSPROCR&acces_token=1942f5da9b46b069953c873404aad4b5&id=$code$type&_=1562144862102';
    private $type = ['定开债券'=>5,'债券型'=>6,'债券指数'=>7,'分级杠杆'=>8,'固定收益'=>9,'保本型'=>10,'货币型'=>11,'联接基金'=>12,'理财型'=>13,'混合-FOF'=>14,'QDII'=>15,'QDII-指数'=>16,'股票型'=>17,'股票指数'=>18,'其他创新'=>19,'ETF-场内'=>20,'混合型'=>21,'QDII-ETF'=>22];
    public function index(){
        var_dump(112);
    }
	
	// 录入股票基础数据
	public function baseList(){
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
		if($is_gzr==0){
			$url = $this->host_two_base;
			$out_put = $this->pq_http_get($url);
			$res = json_decode($out_put,true);
			$arr = [];
			foreach ($res['data']['list'] as $k=>$v){
				$arr[$k]['date'] = date("Y-m-d");
				$arr[$k]['code'] = substr($v['symbol'],2);
				$arr[$k]['type'] = substr($v['symbol'],0,2);
				$arr[$k]['name'] = $v['name'];
				$arr[$k]['current'] = $v['current'];
				$arr[$k]['pct'] = $v['pct'];
				$arr[$k]['volume_ratio'] = $v['volume_ratio'];
				$arr[$k]['amount'] = round(($v['amount']/10000),2);
				$arr[$k]['mc'] = round(($v['mc']/100000000),2);;
				$arr[$k]['fmc'] = round(($v['fmc']/100000000),2);;
				$arr[$k]['bps'] = $v['bps'];
				$arr[$k]['c_bps'] = round((($v['current']-$v['bps'])/$v['bps']),2);
				$arr[$k]['bps'] = round($v['bps'],2);
				$arr[$k]['eps'] = round($v['eps'],2);
				$arr[$k]['pct_current_year'] = $v['pct_current_year'];
				$arr[$k]['pct5'] = round($v['pct5'],2);
				$arr[$k]['pct10'] = round($v['pct10'],2);
				$arr[$k]['pct20'] = round($v['pct20'],2);
				$arr[$k]['pct60'] = round($v['pct60'],2);
				$arr[$k]['areacode'] = $v['areacode'];
				$arr[$k]['indcode'] = $v['indcode'];
				// var_dump($arr);die;
			}
			$base = new AMarket;
			$base->saveAll($arr);
            //$day_mode->saveAll($day_arr);
		}		
    }
    //  当日资金流数据
    public function dayList(){
        set_time_limit(0);
        $times = time()-86400;
        $is_gzr = $this->is_jiaoyi_day($times);
        if($is_gzr==0){
            $base = new FundBase;
            if(input('param.code')){
                $where[] = array('code','=',input('param.code'));
            }
            $where[] = array('buy_status','in','0,1,2');
            $data = $base::where($where)->order('code asc')->select()->toArray();
            foreach ($data as $k=>$v){
                $cahe = Cache::get($v['code']);
                $data[$k]['create_time'] = 1551577703;  //创建时间
                $data[$k]['update_time'] = time();  //更新时间
                $data[$k]['buy_status'] = 1;
                $data[$k]['buy_not_num'] += 1;
                if($cahe){
                    $temp = json_decode($cahe,true);
                    $data[$k]['buy_not_num'] -= 1;
                    $data[$k]['fee'] = $temp['fee'];
                    $data[$k]['unit_value'] = $temp['unit_value'];
                    $data[$k]['unit_pile_value'] = $temp['unit_pile_value'];
                    $data[$k]['day_grow'] = $temp['day_grow'];
                    $data[$k]['week_grow'] = $temp['week_grow'];
                    $data[$k]['month_grow'] = $temp['month_grow'];
                    $data[$k]['month_three_grow'] = $temp['month_three_grow'];
                    $data[$k]['month_six_grow'] = $temp['month_six_grow'];
                    $data[$k]['year_one_grow'] = $temp['year_one_grow'];
                    $data[$k]['year_two_grow'] = $temp['year_two_grow'];
                    $data[$k]['year_three_grow'] = $temp['year_three_grow'];
                    $data[$k]['year_grow'] = $temp['year_grow'];
                    $data[$k]['create_grow'] = $temp['create_grow'];
                    $data[$k]['buy_status'] = 0;
                }
            }
            $base->saveAll($data);
        }
    }
		//  循环请求所有历史资金数据
	public function qtAllList(){
		$j = 20;
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/api/Market/historyAllList?page=';
		if(input('param.j')){
			$j= input('param.j');
			$url = 'http://'.$_SERVER['SERVER_NAME'].'/api/Market/historyAllList?j='.$j.'&page=';
        }
		for($i=1;$i<45;$i++){
			$url .= $i;
			$this->pq_http_get($url);
			echo $i;
			echo '</br>';
		}
		return '运行完毕';
	}
	
	//  所有历史资金数据
	public function historyAllList(){
        set_time_limit(0);
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/api/Market/historyList?code=$code&name=$name';
		$base = new AMarket;
		$page_num = 10000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 100;
        }
		if(input('param.j')){
			$tem_str = '$code&j='.input('param.j');
            $url = str_replace('$code',$tem_str,$url); //增加j参数  j代表采集的天数
        }
        $page_sd = $page_num*($page-1)+1;
        $page_ed = $page_num*$page;
        $where[] = array('id','>=',$page_sd);
        $where[] = array('id','<=',$page_ed);
		$data = $base::where($where)->field('id,code,name')->select()->toArray();
		foreach($data as $k=>$v){
			$url_new = str_replace('$code',$v['code'],$url);
			$url_new = str_replace('$name',$v['name'],$url_new);
			$res = $this->pq_http_get($url_new);
		}
		return 1;
    }
	
	//  历史资金数据
	public function historyList(){
		if(!input('param.code')){
            return '没有传入参数';
        }
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
		if($is_gzr==0){
			$url = $this->host_two_money;
			$type = 1;
			if(input('param.code')<600000){
				$type = 2;
			}
			$url = str_replace('$type',$type,$url);
			$url = str_replace('$code',input('param.code'),$url);
			$out_put = $this->pq_http_get($url);
			
			$out_put = substr($out_put,22);
			$out_put = str_replace(')','',$out_put);
			$out_put = str_replace(']','',$out_put);
			$out_put = str_replace('}','',$out_put);
			$arr1 = explode('","',$out_put);
			$arr1 = array_reverse($arr1); 
			$arr = [];
			$j = 10;
			$base = new AMarketFund;  // 默认基准数据
			if(input('param.j')){
				$j = input('param.j');  // 存在值转为计算数据
				$base = new AMarketFundTemp;
			}
			for($i=0;$i<$j;$i++){
				$str = str_replace('"','',$arr1[$i]);
				$list[$i] = explode(',',$str);
				$arr['code'] = input('param.code');
				$arr['name'] = input('param.name');
				$arr['d'.($i+1)] = $list[$i][0];
				$arr['c'.($i+1)] = $list[$i][11]*1;
				$arr['p'.($i+1)] = $list[$i][12]*1;
				$arr['f'.(10*($i+1)+0)] = round($list[$i][1]/10000,2);
				$arr['f'.(10*($i+1)+1)] = $list[$i][2]*1;
				$arr['f'.(10*($i+1)+2)] = round($list[$i][3]/10000,2);
				$arr['f'.(10*($i+1)+3)] = $list[$i][4]*1;
				$arr['f'.(10*($i+1)+4)] = round($list[$i][5]/10000,2);
				$arr['f'.(10*($i+1)+5)] = $list[$i][6]*1;
				$arr['f'.(10*($i+1)+6)] = round($list[$i][7]/10000,2);
				$arr['f'.(10*($i+1)+7)] = $list[$i][8]*1;
				$arr['f'.(10*($i+1)+8)] = round($list[$i][9]/10000,2);
				$arr['f'.(10*($i+1)+9)] = $list[$i][10]*1;
			}
			
			$where[] = array('code','=',input('param.code'));
            $data = $base::where($where)->find();
			
			if($data){
				$base->save($arr, ['id' => $data['id']]);
				return $arr;
			}
			$base->save($arr);
			return $arr;
		}
    }


	//  是否交易日
	public function is_jiaoyi_day($times=''){
		$date = date("Ymd",time());
		if($times){
			$date = date("Ymd",$times);
		}

        $url = "http://api.goseek.cn/Tools/holiday?date=".$date;
        $out_put = $this->pq_http_get($url);
        $res = json_decode($out_put,true);
        //$res = file_get_contents($url);
        //$res = json_decode($res,true);
		// 正常工作日对应结果为 0, 法定节假日对应结果为 1, 节假日调休补班对应的结果为 2，休息日对应结果为 3 
		return $res['data'];
    }
    //  是否交易时间
    public function is_open_date(){
        $times = time();
        $beginTimes1=mktime(9,30,0,date('m'),date('d'),date('Y'));
        $beginTimes2=mktime(13,0,0,date('m'),date('d'),date('Y'));
        $endTimes1=mktime(11,40,0,date('m'),date('d'),date('Y'));
        $endTimes2=mktime(15,11,0,date('m'),date('d'),date('Y'));
        if($times<$endTimes1){
            if($times>$beginTimes1){
                return 1;    // 是交易时间
            }
        }
        if($times>$beginTimes2){
            if($times<$endTimes2){
                return 1;
            }
        }
        return 0;
    }
    //  重定向get请求
    public function pq_http_get($url=''){
        $refer = 'http://localhost';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        //伪造来源refer
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        //...各种curl属性参数设置
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

        $out_put = curl_exec($ch);
        curl_close($ch);
        return $out_put;
    }

}