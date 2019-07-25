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
	private $host_two_base = 'https://xueqiu.com/service/screener/screen?category=CN&exchange=sh_sz&areacode=&indcode=&order_by=current&order=asc&page=1&size=10000&only_count=0&current=0.14_987.9&pct=&tr=0_70.33&fmc=40493376_1528701245096&mc=114125000_2020823477695&bps.20190331=-6.17_98.76&eps.20190331=-0.82_8.93&volume_ratio=0_84.79&amount=0_9012422827.11&pct_current_year=-88.82_500&pct5=-26.41_110.86&pct10=-33.93_185.33&pct20=-76.58_310.81&pct60=-88.82_500&_=1562142373571';
	// 股票资金流采集网址（东方财富）
	private $host_money = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&sr=-1&p=1&ps=10000&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=52065637';
	
	// 股票历史资金流采集网址（东方财富）
	private $host_two_money = 'http://ff.eastmoney.com//EM_CapitalFlowInterface/api/js?type=hff&rtntype=2&js=({data:[(x)]})&cb=var%20aff_data=&check=TMLBMSPROCR&acces_token=1942f5da9b46b069953c873404aad4b5&id=$code$type&_=1562144862102';
    private $type = ['S1101'=>'种植业','S1102'=>'渔业','S1103'=>'林业','S1104'=>'饲料','S1105'=>'农产品加工','S1106'=>'农业综合','S1107'=>'禽畜养殖','S1108'=>'动物保健','S2101'=>'石油开采','S2102'=>'煤炭开采','S2103'=>'其它采掘','S2104'=>'采掘服务','S2201'=>'石油化工','S2202'=>'化学原料','S2203'=>'化学制品','S2204'=>'化学纤维','S2205'=>'塑料',''];
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
			$base = new AMarket;
			foreach ($res['data']['list'] as $k=>$v){
				//turnover_rate
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
				$arr[$k]['now_pr'] = 0;
				$ints = intval($arr[$k]['code']);
				if($ints>300000&&$ints<400000){
					$arr[$k]['buy_type'] = 2;
				}
				if($arr[$k]['pct']<=0){
					$c = 10;
					if(strpos($v['name'],'ST')){
						$c = 5;
						$arr[$k]['buy_type'] = 1;
					}
					$arr[$k]['now_pr'] = abs(($arr[$k]['pct']/$c));
				}
				if(input('param.type')==1){
					$base->save($arr[$k]);  								//添加操作
				}else{
					$base->where('code',$arr[$k]['code'])->update($arr[$k]);  //更新操作
				}
				// var_dump($arr);die;
			}
            //$day_mode->saveAll($day_arr);
		}
		return 1;
    }
    //  当日资金流数据
    public function dayList(){
        set_time_limit(0);
        $times = time()-86400;
        $is_gzr = $this->is_jiaoyi_day();
        if($is_gzr!=0){
			return 0;
        }
		return 1;
    }
	
	//  所有历史资金数据
	public function historyAllList(){
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day();
        if($is_gzr!=0){
			return 0;   // 非工作日直接返回
        }
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/api/Market/historyList?code=$code&name=$name';
		$base = new AMarket;
		$page_num = 10000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 200;
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

	//  计算连绿次数后红的概率
	public function cutNumPr(){
        set_time_limit(0);
		$base = new AMarketFundTemp;
		$where[] = array('d1','>',0);
		$start = 20;
		$num = 3;
		
        if(input('param.num')>2){
			$num = input('param.num');
        }
		if(input('param.start')>$num){
			$start = input('param.start');
        }
		$where[] = array('p'.$start,'>',0);
		for($i=$start-$num;$i<$start;$i++){
			$where[] = array('p'.$i,'<=',0);
		}
		$get = $start-$num-1;
		// var_dump($where);die;
		$data = $base::where($where)->field('id,code,name,p'.$get.',c'.$get)->select()->toArray();
		$dy = 0;$zs = count($data);
		foreach($data as $k=>$v){
			echo 'code:'.$v['code'].' name:'.$v['name'].' p'.$get.':'.$v['p'.$get].' | '.' c'.$get.':'.$v['c'.$get];
			echo '</br>';
			if($v['p'.$get]>=0){
				$dy +=1;
			}
		}
		if($zs>0){
			echo '开始:'.$start.'连绿天数:'.$num.'总数:'.$zs.' 红数:'.$dy.' 概率:'.round(($dy/$zs),2);
		}else{
			echo '开始:'.$start.'连绿天数:'.$num.'总数:'.$zs.' 红数:'.$dy;
		}
		echo '</br>';
		//return 1;
    }
	
	//  计算连绿过程中降幅比例后红概率  降幅/跌停幅度
	public function cutNumPTr(){
        set_time_limit(0);
		$base = new AMarketFundTemp;
		$where[] = array('d1','>',0);
		$start = 20;
		$num = 3;
		$pr = 0.1;
		
        if(input('param.num')>2){
			$num = input('param.num');
        }
		if(input('param.pr')>0.1){
			$pr = input('param.pr');
        }
		if(input('param.start')>$num){
			$start = input('param.start');
        }
		$where[] = array('p'.$start,'>',0);
		for($i=$start-$num;$i<$start;$i++){
			$where[] = array('p'.$i,'<=',0);
		}
		$get = $start-$num;
		$getP = $start-$num+1;
		$getN = $start-$num-1;
		// var_dump($where);die;
		$data = $base::where($where)->field('id,code,name,p'.$get.',c'.$get.',p'.$getP.',p'.$getN)->select()->toArray();
		$dy = 0;$zs = 0;
		$c = 10;
		foreach($data as $k=>$v){
			if($v['p'.$getN]>=0){
				$dy +=1;
			}
			if(strpos($v['name'],'ST')){
				$c = 5;
			}
			$tem = abs(($v['p'.$get]/$c));
			if($tem<=$pr){
				$zs+=1;
			}
			echo 'code:'.$v['code'].' name:'.$v['name'].' 当前p'.$get.':'.$v['p'.$get].' | '.' 当前c'.$get.':'.$v['c'.$get].' | '.' 前一天p'.$getP.':'.$v['p'.$getP].' | '.' 后一天p'.$getN.':'.$v['p'.$getN].' | 降幅比例: '.$tem;
			echo '</br>';
		}
		if($zs>0){
			echo '开始:'.$start.'连绿天数:'.$num.'比例:'.$$pr.'总数:'.$zs.' 红数:'.$dy.' 概率:'.round(($dy/$zs),2);
		}else{
			echo '开始:'.$start.'连绿天数:'.$num.'比例:'.$$pr.'总数:'.$zs.' 红数:'.$dy;
		}
		echo '</br>';
		//return 1;
    }	
	//  AMarketFund计算连绿次数和降幅比例
	public function cutNum(){
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day();
        if($is_gzr!=0){
			return 0;   // 非工作日直接返回
        }
		$base = new AMarketFund;
		$where[] = array('d1','>',0);
		$page_num = 10000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 500;
        }		
		
		$data = $base::where($where)->field('id,code,name,p1,p2,p3,p4,p5,p6,p7,p8,p9,now_pr,pre_pr,pre_pr2,green_num')->select()->toArray();
		$start = 1;

		if(input('param.start')>$num){
			$start = input('param.start');
        }
		foreach($data as $k=>$v){
			$ints = intval($v['code']);
			$data[$k]['buy_type'] = 0;
			if($ints>300000&&$ints<400000){
				$data[$k]['buy_type'] = 2;
			}
			$c = 10;
			if(strpos($v['name'],'ST')){
				$c = 5;
				$data[$k]['buy_type'] = 1;
			}
			$num = 0;
			$pre_pr = 0;
			$now_pr = 0;
			$pre_pr2 = 0;
			
			if($v['p'.$start]<=0){
				
				for($i=$start;$i<11;$i++){
					if($v['p'.$i]<=0){
						$num += 1;
					}else{
						$i = 11;
					}
					if($i==10&&$v['p10']<=0){
						$num = 0;
					}
				}
				$now_pr = abs(($v['p'.$start]/$c));
				$pre_pr = abs(($v['p'.($start+1)]/$c));
				$pre_pr2 = abs(($v['p'.($start+2)]/$c));
				
			}
			$data[$k]['now_pr'] = $now_pr;			
			$data[$k]['pre_pr'] = $pre_pr;
			$data[$k]['pre_pr2'] = $pre_pr2;
			$data[$k]['green_num'] = $num;
		}
		$base->saveAll($data);
		//return 1;
    }		
	//  AMarketFundTemp计算连绿次数和降幅比例
	public function cutNumTemp(){
        set_time_limit(0);
		$base = new AMarketFundTemp;
		$where[] = array('d1','>',0);
		$page_num = 10000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 500;
        }		
		
		$data = $base::where($where)->field('id,code,name,p1,p2,p3,p4,p5,p6,p7,p8,p9,p10,p11,p12,p13,p14,p15,p16,p17,p18,p19,p20,now_pr,pre_pr,green_num')->select()->toArray();
		$start = 1;

		if(input('param.start')>$num){
			$start = input('param.start');
        }
		foreach($data as $k=>$v){
			$ints = intval($v['code']);
			$data[$k]['buy_type'] = 0;
			if($ints>300000&&$ints<400000){
				$data[$k]['buy_type'] = 2;
			}
			$c = 10;
			if(strpos($v['name'],'ST')){
				$c = 5;
				$data[$k]['buy_type'] = 1;
			}
			$num = 0;
			$pre_pr = 0;
			$now_pr = 0;
			$pre_pr2 = 0;
			
			if($v['p'.$start]<=0){
				
				for($i=$start;$i<21;$i++){
					if($v['p'.$i]<=0){
						$num += 1;
					}else{
						$i = 21;
					}
					if($i==20&&$v['p20']<=0){
						$num = 0;
					}
				}
				$now_pr = abs(($v['p'.$start]/$c));
				$pre_pr = abs(($v['p'.($start+1)]/$c));
				$pre_pr2 = abs(($v['p'.($start+2)]/$c));
				
			}
			$data[$k]['now_pr'] = $now_pr;			
			$data[$k]['pre_pr'] = $pre_pr;
			$data[$k]['pre_pr2'] = $pre_pr2;
			$data[$k]['green_num'] = $num;
		}
		$base->saveAll($data);
		//return 1;
    }		
	//  保存到基准表连绿次数和降幅比例
	public function saveNum(){
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day();
        if($is_gzr!=0){
			return 0;   // 非工作日直接返回
        }
		$hbase = new AMarketFund;
		if(input('param.type')==1){
			$hbase = new AMarketFundTemp;
		}
		$nbase = new AMarket;
		$where[] = array('d1','>',0);	
		
		$data = $hbase::where($where)->field('code,name,now_pr,pre_pr,green_num')->select()->toArray();
		$arr = [];
		foreach($data as $k=>$v){
			$arr['green_num'] = $v['green_num'];
			$arr['pre_pr'] = $v['now_pr'];
			$arr['pre_pr2'] = $v['pre_pr'];
			$nbase->where('code',$v['code'])->update($arr);
		}
		//return 1;
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