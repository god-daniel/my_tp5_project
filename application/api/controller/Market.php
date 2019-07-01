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
use app\common\model\MyFund;
use app\common\model\FundBaseList;
use app\common\model\FundBase;
use app\common\model\FundDayList;
use app\common\model\FundHistoryList;
use think\Db;
use think\facade\Cache;
use \think\View;

class Market extends Controller{
    private $pageNo;
    private $keywords;

	// 基础股票采集网址（东方财富）
	
	private $host_base = 'http://21.push2.eastmoney.com/api/qt/clist/get?cb=jQuery1124014069351677765463_1561970756781&pn=1&pz=10000&po=0&np=1&ut=bd1d9ddb04089700cf9c27f6f7426281&fltt=2&invt=2&fid=f2&fs=m:0+t:6,m:0+t:13,m:0+t:80,m:1+t:2&fields=f1,f2,f3,f4,f5,f6,f7,f8,f9,f10,f12,f13,f14,f15,f16,f17,f18,f20,f21,f23,f24,f25,f22,f11,f62,f128,f136,f115,f152&_=1561970756952';
	
	// 基础股票采集网址（雪球）
	private $host_two_base = 'https://xueqiu.com/service/screener/screen?category=CN&exchange=sh_sz&areacode=&indcode=&order_by=current&order=asc&page=1&size=30&only_count=0&current=0.15_1031.86&pct=&pettm=-92162.28_27609.46&pelyr=-1586.19_6279.68&pb=0_1163&fmc=41273971_1596104298231&bps.20190331=-6.17_98.76&eps.20190331=-0.82_8.93&psr=-1587.82_13301&mc=115775000_2109925041967&volume_ratio=0_15.79&pct_current_year=-88.22_522.11&tr=0_50.87&pct10=-43.48_239.51&pct5=-27.32_91.67&psf.20190331=-6.17_98.76&ocps.20190331=-30.65_9.59&epsdiluted.20190331=-0.82_8.93&_=1561973852099';
	// 股票资金流采集网址（东方财富）
	private $host_money = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx?type=ct&sr=-1&p=1&ps=10000&token=894050c76af8597a853f5b408b759f5d&cmd=C._AB&sty=DCFFITA&rt=52065637';
    private $type = ['定开债券'=>5,'债券型'=>6,'债券指数'=>7,'分级杠杆'=>8,'固定收益'=>9,'保本型'=>10,'货币型'=>11,'联接基金'=>12,'理财型'=>13,'混合-FOF'=>14,'QDII'=>15,'QDII-指数'=>16,'股票型'=>17,'股票指数'=>18,'其他创新'=>19,'ETF-场内'=>20,'混合型'=>21,'QDII-ETF'=>22];
    public function index(){
        var_dump(112);
    }
	
	// 每日录入基金数据 每晚11点40添加
	public function funList(){
        set_time_limit(0);
		$is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
		if($is_gzr==0){
			$url3 = $this->host3;
			$sd = date("Y-m-d",strtotime("-1 day"));
			$ed = date("Y-m-d",strtotime("-0 day"));
			$url3 = str_replace('$sd',$sd,$url3);
			$url3 = str_replace('$ed',$ed,$url3);
			$infoList = file_get_contents($url3);
			$year = date("Y");
			$infoList = substr($infoList,strpos($infoList,'[')+1);
			$infoList = substr($infoList,0,strpos($infoList, ']'));
			$arr2 = explode(',',$infoList);
			$history = new FundHistoryList;
			$arr = [];
			$day_arr = [];
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
				Cache::set($data['code'],json_encode($dayArr),7200);
                $day_arr[$x]['code'] = $data['code'];
                $day_arr[$x]['name'] = $data['name'];
                $day_arr[$x]['unit_value'] = $data['unit_value'];
                $day_arr[$x]['update_date'] = $data['update_date'];
                $day_arr[$x]['unit_pile_value'] = $data['unit_pile_value'];
                $day_arr[$x]['day_grow'] = $data['day_grow'];
			}
            $day_mode = new FundDayList;
			$history->saveAll($arr);
            $day_mode->saveAll($day_arr);
		}		
    }
    //  更新基金,购买费,周增长，月增长等数据净值 每天0点30更新
    public function updateFundBase(){
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
    //  更新10日基金数据净值 每晚1点开始更新
    public function tenTodayFund(){
        set_time_limit(0);
        $times = time()-86400;
        $is_gzr = $this->is_jiaoyi_day($times);
        if($is_gzr==0){
            if(input('param.code')){
                $where[] = array('code','=',input('param.code'));
            }
            $page_num = 20000;
            $page = 1;
            if(input('param.page')){
                $page = input('param.page');
                $page_num = 2000;
            }
            $page_sd = $page_num*($page-1)+1;
            $page_ed = $page_num*$page;
            $where[] = array('id','>=',$page_sd);
            $where[] = array('id','<=',$page_ed);
            $where[] = array('buy_status','in','0,2');
            $base = new FundBase;
            $data = $base::where($where)->order('code asc')->select()->toArray();
            $day_base = new FundDayList();
            $yugu_data[] = array();
            foreach ($data as $k=>$v){
                $map[] = array('code','=',$v['code']);
                $day_data = $day_base::where('code','=', $v['code'])->limit(10)
                    ->order('update_date desc')->select()->toArray();
                $data[$k]['create_time'] = 1551577703;  //创建时间
                $data[$k]['update_time'] = time();  //更新时间
                $weight = $this->get_weight($v);
                $data[$k]['weight']=$weight['weight'];
                $data[$k]['buy_weight']=$weight['buy_weight'];
                $data[$k]['sell_weight']=$weight['sell_weight'];
                $data[$k]['sell_diff_buy_weight'] = $data[$k]['sell_weight']-$data[$k]['buy_weight'];
                foreach ($day_data as $kk=>$vv){
                    $data[$k]['num_'.($kk+1).'_value'] = $vv['unit_value'];
                    $data[$k]['num_'.($kk+1).'_date'] = $vv['update_date'];
                    if($kk==0){
                        $yugu_data[$k]['id'] = $vv['id'];
                        $yugu_data[$k]['yugu_unit_value'] = $data[$k]['unit_value'];
                        $yugu_data[$k]['yugu_day_grow'] = $data[$k]['day_grow'];
                        $yugu_data[$k]['yugu_update_date'] = $data[$k]['update_date'];
                    }
                }
            }
            $base->saveAll($data);
            $day_base->saveAll($yugu_data);
        }
    }
    //  实时更新我持有的基金列表估值
    public function updateMyFund(){
        set_time_limit(0);
        $where[] = array('my_fund_status','=',1);
        $times = time();
        $is_gzr = $this->is_jiaoyi_day($times);
        if($is_gzr==0){
            $data = Db::table('sp_my_fund')
                ->alias('m')
                ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
                ->field('group_concat(distinct(b.id)) as ids')->where($where)->select();
            if (!$data[0]['ids']) {
                return 1;
            }
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/updateTodayFund?bs=0&ids='.$data[0]['ids'];
            $str = HttpGet($host);
        }
        var_dump($data[0]['ids']);
    }
    //  更新基金最新估值(详情中更新(请求))
    public function updateTodayFund(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        $base_url = $this->info_now_host;
        if($is_gzr==0){
            $base = new FundBase;
            $page = 1;
            $page_num = 20000;
            if(input('param.page')){
                $page = input('param.page');
                $page_num = 1000;
            }
            if(input('param.ids')){
                $where[] = array('id','in',input('param.ids'));
            }
            if(input('param.grow')){
                $where[] = array('grow','<',input('param.grow'));
            }
            if(input('param.uv')){
                $where[] = array('unit_value','<',input('param.uv'));
            }
            $page_sd = $page_num*($page-1)+1;
            $page_ed = $page_num*$page;
            $where[] = array('buy_status','=',0);
            $where[] = array('id','>=',$page_sd);
            $where[] = array('id','<=',$page_ed);
            $all_data = $base::where($where)->order('code asc')
                ->select()->toArray();
            $arr = [];
            foreach ($all_data as $k=>$v){
                $code = $all_data[$k]['code'];
                $arr[$k]['create_time'] = 1551577703;  //创建时间
                $arr[$k]['update_time'] = time();  //更新时间
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['code'] = $v['code'];
                $arr[$k]['buy_status'] = 1;
                $arr[$k]['amend_weight'] = 0;
                $arr[$k]['grow_weight']= 0;
                $url = str_replace('$code',$code,$base_url);
                $str = HttpGet($url);
                if($str){
                    $str = substr($str,strpos($str,'{'));
                    $str = substr($str,0,strlen($str)-2);
                    $data = json_decode($str,true);
                    $arr[$k]['buy_status'] = 0;
                    $arr[$k]['update_date'] = $data['gztime'];
                    $arr[$k]['unit_value'] = $data['gsz']*10000;
                    $arr[$k]['grow'] = $data['gszzl']*10000;

                    $temp['day_grow']=$v['day_grow'];
                    $temp['week_grow']=$arr[$k]['grow']+$v['week_grow'];
                    $temp['month_grow']=$arr[$k]['grow']+$v['month_grow'];
                    $temp['month_three_grow']=$arr[$k]['grow']+$v['month_three_grow'];
                    $amend_weight = $this->get_weight($temp);
                    $arr[$k]['amend_weight']+=$amend_weight['weight'];
                    $arr[$k]['grow_weight']=$data['gszzl'];
                    $arr[$k]['diff_weight'] = $v['weight']-$arr[$k]['amend_weight'];
                    $arr[$k]['sell_diff_buy_weight'] = $v['sell_weight']-$v['buy_weight'];

                }
            }
            $base->saveAll($arr);
        }
    }
	//  更新基础基金数据 每晚0点10更新
	public function addFunList(){
        $times = time()-86400;
		$is_gzr = $this->is_jiaoyi_day($times);
		if($is_gzr==0){
			Db::query("truncate table sp_fund_base_list");
			$nowDate = date("Ymd",$times);
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
    //  添加今日基金数据
    public function addTodayFund(){
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $date = date('Y-m-d');
            if(input('param.date')){
                $date = input('param.date');
            }
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/addHistoryFund?sdate='.$date.'&edate='.$date;
            $str = HttpGet($host);
            var_dump($host);
        }
    }

    //  每周6更新基金数据
    public function getCronFee(){
        set_time_limit(0);
        for($i=1;$i<302;$i++){
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/getFundFee?page='.$i;
            $str = HttpGet($host);
        }
    }
    //  每周6更新基金数据
    public function getFundFee(){
        set_time_limit(0);
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        $page_num = 20000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 30;
        }
        $page_sd = $page_num*($page-1)+1;
        $page_ed = $page_num*$page;
        $where[] = array('id','>=',$page_sd);
        $where[] = array('id','<=',$page_ed);
        $base_url = $this->host_info;
        $rules = [
            //一个规则只能包含一个function
            //采集class为pt30的div的第一个h1文本
            'buy_fee' => ['p.row:eq(2) b:eq(1)','text'],
            'div' => ['div.boxitem','html','',function($content){
                //$content是元素
                $runs = [
                    'h4' => ['h4','text'],
                    //得到a标签的链接属性
                    // 'table' => ['table:eq(0)','html'],
                    'tr1' => ['table:eq(0) tbody tr:eq(0)','text'],
                    'tr2' => ['table:eq(0) tbody tr:eq(1)','text'],
                ];
                $num = QueryList::html($content)->rules($runs)->query()->getData()->all();
                return $num;
            }],
        ];

        $base = new FundBase;
        $all_data = $base::where($where)->order('code asc')->select()->toArray();
        $arr = [];
        foreach ($all_data as $k=>$v){
            $code = $all_data[$k]['code'];
            //$code = '000794';
            $arr[$k]['id'] = $v['id'];
            $arr[$k]['code'] = $v['code'];
            $arr[$k]['create_time'] = 1551577703;  //创建时间
            $arr[$k]['update_time'] = time();  //更新时间
            $url = str_replace('$code',$code,$base_url);
            $ql = QueryList::get($url,null,[
                'timeout' => 3600]);
            $data = $ql->rules($rules)->query()->getData()->all();
            $ql->destruct();
            // echo $v['code'];
            // echo '</br>';
            // var_dump($data);die;
            $num = count($data);
            $arr[$k]['fee'] = 10000;
            if(isset($data[0]['buy_fee'])){
                $arr[$k]['fee'] = $data[0]['buy_fee']*10000;
            }
            $bool = 0;
            if($data[$num-2]['div'][0]['h4']=='赎回费率'||$data[$num-3]['div'][0]['h4']=='赎回费率（前端）'){
                $bool = 1;
                $d_num = 2;
                if($data[$num-3]['div'][0]['h4']=='赎回费率（前端）'){
                    $d_num = 3;
                }
            }
            if ($bool) {
                $arr[$k]['sell_1_fee'] = 0;
                $arr[$k]['sell_2_fee'] = 0;
                $arr[$k]['sell_1_day'] = 0;
                $arr[$k]['sell_2_day'] = 0;
                if(isset($data[$num-$d_num]['div'][0]['tr1'])){
                    $arr[$k]['sell_1_fee'] = substr($data[$num-$d_num]['div'][0]['tr1'],-5)*1;
                    $index_1_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr1'], '于');
                    if($index_1_num){
                        $diff_day_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr1'], '天');
                        $diff_m_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr1'], '月');
                        $diff_y_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr1'], '年');
                        if($diff_day_num>$index_1_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$diff_day_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$index_1_num+1, $diff_day_num-$index_1_num-1);
                        }
                        if($diff_m_num>$index_1_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$diff_m_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$index_1_num+1, $diff_m_num-$index_1_num-1);
                        }
                        if($diff_y_num>$index_1_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$diff_y_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr1'],$index_1_num+1, $diff_y_num-$index_1_num-1);
                        }
                        switch ($day_type){
                            case '年':
                                $day_num = 365;
                                break;
                            case '月' :
                                $day_num = 30;
                                break;
                            case '天' :
                                $day_num = 1;
                                break;
                            default :
                                $day_num = 0;
                                echo $v['code'];
                                echo '</br>';
                                break;
                        }
                        $arr[$k]['sell_1_day'] = $day_value*$day_num;
                    }
                }
                if(isset($data[$num-$d_num]['div'][0]['tr2'])){
                    $arr[$k]['sell_2_fee'] = substr($data[$num-$d_num]['div'][0]['tr2'],-5)*1;
                    $index_2_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr2'], '于');
                    if($index_2_num){
                        $diff_day_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr2'], '天');
                        $diff_m_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr2'], '月');
                        $diff_y_num = mb_strrpos($data[$num-$d_num]['div'][0]['tr2'], '年');
                        if($diff_day_num>$index_2_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$diff_day_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$index_2_num+1, $diff_day_num-$index_2_num-1);
                        }
                        if($diff_m_num>$index_2_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$diff_m_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$index_2_num+1, $diff_m_num-$index_2_num-1);
                        }
                        if($diff_y_num>$index_2_num){
                            $day_type = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$diff_y_num, 1);
                            $day_value = mb_substr($data[$num-$d_num]['div'][0]['tr2'],$index_2_num+1, $diff_y_num-$index_2_num-1);
                        }
                        switch ($day_type){
                            case '年':
                                $day_num = 365;
                                break;
                            case '月' :
                                $day_num = 30;
                                break;
                            case '天' :
                                $day_num = 1;
                                break;
                            default :
                                $day_num = 0;
                                echo $v['code'];
                                echo '</br>';
                                break;
                        }
                        $arr[$k]['sell_2_day'] = $day_value*$day_num;
                    }
                }
            }
            // var_dump($arr[$k]);
        }
        $base->saveAll($arr);
    }
    //  添加历史日基金数据
    public function addHistoryFund(){
        set_time_limit(0);
        $sd = date('Y-m-d',strtotime("-1 month"));
        $ed = date('Y-m-d');
        if(input('param.sdate')){
            $sd = input('param.sdate');
        }
        if(input('param.edate')){
            $ed = input('param.edate');
        }
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        $page_num = 20000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 500;
        }
        $page_sd = $page_num*($page-1)+1;
        $page_ed = $page_num*$page;
        $url = $this->jijin_history_host;
        $where[] = array('buy_status','=',0);
        $where[] = array('id','>=',$page_sd);
        $where[] = array('id','<=',$page_ed);
        $base = new FundBase;
        $day_list = new FundDayList();
        $all_data = $base::where($where)
            ->order('code asc')
            ->select()->toArray();
        $arr[] = array();
        $i = 0;
        foreach ($all_data as $k=>$v){
            echo $all_data[$k]['code'];
            echo '</br>';
            $code = $all_data[$k]['code'];
            $url = str_replace('$code',$code,$url);
            $url = str_replace('$sd',$sd,$url);
            $url = str_replace('$ed',$ed,$url);
            $str = HttpGet($url);
            if($str){
                $temp_arr=explode(',', $str);
                $temp_str = substr($temp_arr[0],strpos($temp_arr[0],'<'));
                $temp_str = substr($temp_str,0,strlen($temp_str)-1);
                $table_array = explode('<tr>',$temp_str);
                foreach ($table_array as $kk=>$vv){
                    if($kk>=2){
                        $td_array = explode('<td',$vv);
                        $update_date = substr($td_array[1],strpos($td_array[1],'>')+1);
                        $update_date = substr($update_date,0,strlen($update_date)-5);
                        $unit_value = substr($td_array[2],strpos($td_array[2],'>')+1);
                        $unit_pile_value = substr($td_array[3],strpos($td_array[3],'>')+1);
                        $buy_desc = substr($td_array[5],strpos($td_array[5],'>')+1);
                        $buy_desc = substr($buy_desc,0,strlen($buy_desc)-5);
                        $bool_desc = mb_substr($buy_desc,0,mb_strlen($buy_desc)-2);
                        $sell_desc = substr($td_array[6],strpos($td_array[6],'>')+1);
                        $sell_desc = substr($sell_desc,0,strlen($sell_desc)-5);
                        if($bool_desc=='暂停'||$bool_desc=='封'){
                            $day_grow = 0;
                        }else{
                            $day_grow = substr($td_array[4],strpos($td_array[4],'>')+1);
                        }
                        $arr[$i]['buy_desc'] = $buy_desc;
                        $arr[$i]['sell_desc'] = $sell_desc;
                        $arr[$i]['code'] = $all_data[$k]['code'];
                        $arr[$i]['name'] = $all_data[$k]['name'];
                        $arr[$i]['create_time'] = 1551577703;  //创建时间
                        $arr[$i]['update_time'] = time();  //更新时间
                        $arr[$i]['update_date'] = $update_date;
                        $arr[$i]['unit_value'] = $unit_value*10000;
                        $arr[$i]['unit_pile_value'] = $unit_pile_value*10000;
                        $arr[$i]['day_grow'] = $day_grow*10000;
                        $i+=1;
                    }
                }

            }
        }
        $day_list->saveAll($arr);
    }
    //  更新基础基金的日增长，周增长，权重, 上一个5日平均  上上个5日平均
    public function dealFundBase(){
        set_time_limit(0);
        // http://www.daniel.com/api/fund/
        $base = new FundBase;
        $data = Db::table('sp_fund_base')
            ->alias('b')
            ->leftJoin('sp_fund_base_list l','b.code = l.code')
            ->field('b.id,b.weight,((num_1_value+num_2_value+num_3_value+num_4_value+num_5_value)/5) as avg_value1,((num_6_value+num_7_value+num_8_value+num_9_value+num_10_value)/5) as avg_value2,l.day_grow,l.week_grow,l.month_grow,l.month_three_grow,l.month_six_grow,l.year_grow,l.year_one_grow,l.year_two_grow,l.year_three_grow')
            ->where('b.buy_status','=',0)
            ->select();
        foreach ($data as $k => $v){

            $data[$k]['create_time'] = 1551577703;  //创建时间
            $data[$k]['update_time'] = time();  //更新时间
            $weight = $this->get_weight($v);
            $data[$k]['avg_value1']=intval($data[$k]['avg_value1']);
            $data[$k]['avg_value2']=intval($data[$k]['avg_value2']);
            $data[$k]['weight']=$weight['weight'];
            $data[$k]['buy_weight']=$weight['buy_weight'];
            $data[$k]['sell_weight']=$weight['sell_weight'];
            $data[$k]['sell_diff_buy_weight'] = $data[$k]['sell_weight']-$data[$k]['buy_weight'];
        }
        $base->saveAll($data);
    }

    //  更新我持有的基金数据  持有天数，收益率
    public function todayMyFund(){
        // http://www.daniel.com/api/fund/todayMyFund
        $base = new MyFund;
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.my_id,m.my_fund_code,m.buy_date,m.sell_date,m.sure_date,m.day_nums,m.buy_fund_value,m.buy_fund_num,m.buy_fund_money,m.yields,b.num_1_value,b.num_1_date')
            ->where('m.my_fund_status','=',1)
            ->select();
        $today = date("Y-m-d");
        foreach ($data as $k => $v){
            if($v['num_1_date'] == $v['buy_date']){
                $data[$k]['buy_fund_value'] = $v['num_1_value'];
                $t = ($data[$k]['buy_fund_money']/$data[$k]['buy_fund_value'])*10000;
                $data[$k]['buy_fund_num'] = round($t,2);
            }
            if($v['sure_date']==0){
                for($i=1;$i<20;$i++){
                    $times = strtotime($v['buy_date'])+$i*86400;
                    $is_gzr = $this->is_jiaoyi_day($times);
                    if($is_gzr==0){
                        $i=21;
                        $v['sure_date'] = date("Y-m-d",$times);
                        $data[$k]['sure_date'] = $v['sure_date'];
                    }
                }
            }
            $data[$k]['day_nums'] = (strtotime($today)-strtotime($v['sure_date']))/86400+1;
            $yields = ($v['num_1_value']-$data[$k]['buy_fund_value'])/$data[$k]['buy_fund_value'];
            $data[$k]['yields'] = round($yields,4)*100;
        }
        $base->saveAll($data);
    }
    //  测试
    public function test(){
        $t1 = strtotime('2019-05-14');
        $t2 = strtotime('2018-05-14');
        $d = ($t1-$t2)/86400;
        var_dump($d);
    }
    //  setNowFundCahe
    public function setNowFundCahe(){
        set_time_limit(0);
        $pq_url = 'http://api.fund.eastmoney.com/FundGuZhi/GetFundGZList?type=1&sort=1&orderType=asc&canbuy=1&pageIndex=1&pageSize=20000'; // 请求地址 爬取数据
        $refer = 'http://localhost';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pq_url);
        //伪造来源refer
        curl_setopt($ch, CURLOPT_REFERER, $refer);
        //...各种curl属性参数设置
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);

        $out_put = curl_exec($ch);
        curl_close($ch);
        $arr_temp = json_decode($out_put,true);
        $list = $arr_temp['Data']['list'];
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));

        $pq_bool = $this->is_open_date();
        if($is_gzr==0&&$pq_bool==1){
            $base = new FundBase;
            $where[] = array('buy_status','in','0,2');
            $all_data = $base::where($where)->order('code asc')
                ->select()->toArray();
            foreach ($all_data as $k=>$v){
                $all_data[$k]['create_time'] = 1551577703;  //创建时间
                $all_data[$k]['update_time'] = time();  //更新时间
                $all_data[$k]['buy_status'] = 1;
                foreach ($list as $kk=>$vv) {
                    if($v['code']==$vv['bzdm']){
                        $all_data[$k]['buy_status'] = 0;
                        $all_data[$k]['type_desc'] = $vv['FType'];
                        $all_data[$k]['fee'] = $vv['Rate']*10000;
                        if($vv['gszzl']=='---'){
                            $all_data[$k]['buy_status'] = 2;
                            unset($list[$kk]);
                            break;
                        }
                        $all_data[$k]['update_date'] = $vv['gxrq'];
                        $all_data[$k]['unit_value'] = $vv['gsz']*10000;
                        $all_data[$k]['grow'] = $vv['gszzl']*10000;

                        $temp['day_grow']=$all_data[$k]['grow'];
                        $temp['week_grow']=$all_data[$k]['grow']+$v['week_grow'];
                        $temp['month_grow']=$all_data[$k]['grow']+$v['month_grow'];
                        $temp['month_three_grow']=$all_data[$k]['grow']+$v['month_three_grow'];
                        $amend_weight = $this->get_weight($temp);
                        $all_data[$k]['amend_weight']=$amend_weight['weight'];
                        $all_data[$k]['grow_weight']=$vv['gszzl']*1;
                        $all_data[$k]['diff_weight'] = $v['weight']-$all_data[$k]['amend_weight'];
                        $all_data[$k]['sell_weight'] = $all_data[$k]['amend_weight'];
                        $all_data[$k]['buy_weight']=$all_data[$k]['amend_weight'];
                        if($v['day_grow']>0){
                            $all_data[$k]['sell_weight']=$all_data[$k]['amend_weight']+$v['day_grow']/10000;
                        }else{
                            $all_data[$k]['buy_weight']=$all_data[$k]['amend_weight']-$v['day_grow']/10000;
                        }
                        $all_data[$k]['sell_weight']+=$vv['gszzl']*1;
                        $all_data[$k]['buy_weight']-=$vv['gszzl']*1;
                        $all_data[$k]['sell_diff_buy_weight'] = $all_data[$k]['sell_weight']-$all_data[$k]['buy_weight'];
                        unset($list[$kk]);
                        break;
                    }
                }
            }
            Cache::set('base_data',json_encode($all_data),7200);
        }
        return 1;
    }

    // 设置基金行业
    public function setFundHy(){
        set_time_limit(0);

        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        $page_num = 20000;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
            $page_num = 1000;
        }
        $page_sd = $page_num*($page-1)+1;
        $page_ed = $page_num*$page;
        $where[] = array('id','>=',$page_sd);
        $where[] = array('id','<=',$page_ed);
        $base = new FundBase;
        $all_data = $base::where($where)->order('code asc')
            ->select()->toArray();
        foreach ($all_data as $k=>$v) {
            $all_data[$k]['create_time'] = 1551577703;  //创建时间
            $all_data[$k]['update_time'] = time();  //更新时间
            $pq_url = 'http://api.fund.eastmoney.com/f10/HYPZ/?fundCode='.$v['code'].'&year='; // 请求地址 爬取数据
            $out_put = $this->pq_http_get($pq_url);
            $arr_temp = json_decode($out_put,true);
            $list = $arr_temp['Data']['QuarterInfos'][0]['HYPZInfo'];
            if($list){
                foreach ($list as $kk => $vv) {
                    $all_data[$k]['hy_'.($kk+1).'_value'] = $vv['ZJZBLDesc']*1;
                    $all_data[$k]['hy_'.($kk+1).'_type'] = $vv['HYDM'];
                    $all_data[$k]['hy_'.($kk+1).'_desc'] = $vv['HYMC'];
                    if ($kk == 2) {
                        break;
                    }
                }
            }
        }
        var_dump($all_data);
        $base->saveAll($all_data);
    }
    //  缓存抓取实时数据，保存到数据库
    //  之前的更新网址  */10 9-15 * * 1-5 curl -o /data/wwwlogs/crontabFundNow.log http://fund.mankkk.cn/api/fund/updateTodayFund?page=9
    public function saveFundBase(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));

        $pq_bool = $this->is_open_date();
        //if($is_gzr==0&&$pq_bool==1){
            $base = new FundBase;
            $str = Cache::get('base_data');
            $arr = json_decode($str,true);
            $chunk_result = array_chunk($arr, 500, true);
            foreach ($chunk_result as $k=>$v) {
                $base->saveAll($v);
            }
            return 1;
        //}
        echo 0;
    }
    //  删除无效的信息
    public function deletOther(){
        set_time_limit(0);
        $day_list = new FundDayList();
        $data = $day_list::where('update_date','like','%暂无%')->select();
    }
    //  更新基金类型
    public function changeType(){
        $base = new FundBase;
        $all_data = $base::field('id,code,type,type_desc')->order('code asc')
            ->select()->toArray();
        foreach ($all_data as $k=>$v){
            $all_data[$k]['type'] = $this->type[$v['type_desc']];
        }
        $base->saveAll($all_data);
    }

    //  统计逆回购金额
    public function countNhg(){
        //$where[] = array('count_money','=',0);
        $where[] = array('type','=',1);
        $date_data = Db::table('sp_nhg')
            ->field('start_dates')
            ->where($where)
            ->group('start_dates')
            ->select();
        foreach ($date_data as $k=>$v){
            $f_count = Db::table('sp_nhg')->where('start_dates','<=',$v['start_dates'])->where($where)->sum('money');
            $s_count = Db::table('sp_nhg')->where('end_dates','<=',$v['start_dates'])->where($where)->sum('money');
            $data[$k]['date'] = $v['start_dates'];
            $data[$k]['count_money'] = $f_count-$s_count;
        }
        var_dump($data);
    }
    //  统计日逆回购金额
    public function dayNhg(){
        $date = date("Y-m-d");
        $type = 1;
        if(input('param.date')){
            $date = input('param.date');
        }
        if(input('param.type')){
            $type = input('param.type');
        }
        $where[] = array('start_dates','<=',$date);
        $where[] = array('type','=',$type);
        $map[] = array('end_dates','<=',$date);
        $map[] = array('type','=',$type);
        $f_count = Db::table('sp_nhg')->where($where)->sum('money');
        $s_count = Db::table('sp_nhg')->where($map)->sum('money');
        $data['date'] = $date;
        $data['count_money'] = $f_count-$s_count;
        var_dump($data);
    }
    //  添加大盘数据
    public function addDp(){
        $str = "2019-05-06,-575.5947,-8.88%,-273.9591,-4.23%,-301.6356,-4.65%,96.063,1.48%,479.5317,7.4%,2906.46,-5.58%,8943.52,-7.56%,2019-05-07,-86.0504,-1.6%,-2.0048,-0.04%,-84.0456,-1.56%,-19.3844,-0.36%,105.4348,1.96%,2926.39,0.69%,9089.46,1.63%";
        $arr = explode(',',$str);
        $num = count($arr)/15;
        for ($x=0; $x<$num; $x++) {
            //$day_arr[$x]['id'] = '';
            $day_arr[$x]['dates'] = $arr[$x*15];
            $day_arr[$x]['zl_money'] = $arr[$x*15+1]*1;
            $day_arr[$x]['zl_grow'] = $arr[$x*15+2]*1;
            $day_arr[$x]['cdd_money'] = $arr[$x*15+3]*1;
            $day_arr[$x]['dd_money'] = $arr[$x*15+5]*1;
            $day_arr[$x]['zd_money'] = $arr[$x*15+7]*1;
            $day_arr[$x]['xd_money'] = $arr[$x*15+9]*1;
            $day_arr[$x]['shz_value'] = $arr[$x*15+11]*1;
            $day_arr[$x]['shz_grow'] = $arr[$x*15+12]*1;
            $day_arr[$x]['sz_value'] = $arr[$x*15+13]*1;
            $day_arr[$x]['sz_grow'] = $arr[$x*15+14]*1;
            $day_arr[$x]['week_num'] = date('w',strtotime($arr[$x*15]));
        }
        Db::name('dp')->data($day_arr)->insertAll();
    }
    //  设置缓存
    public function setCache(){
        $date = date("Y-m-d");
        Cache::set('001553','12345',3600);
        if(input('param.date')){
            $date = input('param.date');
        }
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        $where[] = array('update_date','=',$date);
        $base = new FundHistoryList;
        $data = $base::where($where)
            ->select()->toArray();
        foreach ($data as $k=>$v){
            Cache::set($v['code'],json_encode($v),3600);
        }
        var_dump($data);
        $cahe = Cache::get('doFund');
        var_dump($cahe);
    }
    //  得到当前的
    public function getNowFund(){
        // http://www.daniel.com/api/fund/getNowFund?w=3.3&aw=4&bw=1&sw=9&sd=diff_weight
        set_time_limit(0);
        $str = Cache::get('base_data');
        if(!$str){
            echo '没有缓存';
            return 1;
        }
        $arr_all = json_decode($str,true);
        $where[] = array('buy_status','=',0);

        $sort_code = 'weight';
        $sort_type = SORT_DESC;
        if(input('param.sd')){
            $sort_code = input('param.sd');
        }
        if(input('param.st')==0){
            $sort_type = SORT_ASC;
        }
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        if(input('param.dw')){
            $where[] = array('diff_weight','>',input('param.dw'));
        }else{
            $where[] = array('diff_weight','>',0);
        }
        if(input('param.w')){
            $where[] = array('weight','>',input('param.w'));
        }else{
            $where[] = array('weight','>',0.5);
        }
        if(input('param.aw')){
            $where[] = array('amend_weight','<',input('param.aw'));
        }
        if(input('param.sbw')){
            $where[] = array('sell_diff_buy_weight','<',input('param.sbw'));
        }
        if(input('param.fee')){
            $where[] = array('fee','<=',input('param.fee')*10000);
        }

        $i = 0;
        $arr = [];
        foreach ($arr_all as $k=>$v){
            $j = $this->my_where($where,$v);
            if($j){
                $i+=1;
                $arr[$i] = $v;
            }
        }
        $base = $this->my_sort($arr,$sort_code,$sort_type,SORT_NUMERIC);
        echo '总数据:'.count($base);
        echo '</br>';
        echo '</br>';
        //echo 1.' '.1.'与buy_weight desc和amend_weight asc 交集:'.implode($arr, ' ');
        echo '</br>';
        echo '</br>';
        foreach ($base as $k=>$v){
            echo '编码: '.$v['code'].'  权重: '.$v['weight'].'  修正差值: '.$v['diff_weight'].'  修正买权重: '.$v['buy_weight'].'  卖权重: '.$v['sell_weight'].'  费率%: '.($v['fee']/10000).'  今日预增长%: '.$v['grow_weight'].' '.$v['name'];
            echo '</br>';
            echo '</br>';
        }
    }
    //  得到今日可买基金列表
    public function getBuyFund(){
        // http://www.daniel.com/api/fund/getBuyFund?sd=buy_weight
        $base = new FundBase;

        //$where[] = array('diff_weight','<=',1.3);
        $where[] = array('buy_status','=',0);
        // $where[] = array('weight','>',0.8);
        //$where[] = array('sell_diff_buy_weight','<',2.5);
        // $where[] = array('week_grow','>=',15000);
        // $where[] = array('hy_1_desc','<>','行业说明');
        // $where[] = array('fee','<=',input('param.fee')*600);
        $sort_code = 'weight';
        $sort_type = 'desc';
        if(input('param.sd')){
            $sort_code = input('param.sd');
        }
        if(input('param.st')){
            $sort_type = input('param.st');
        }
        $sort = $sort_code.' '.$sort_type;
        if(input('param.bw')){
            $where[] = array('buy_weight','>',input('param.bw'));
        }
        if(input('param.dw')){
            $where[] = array('diff_weight','>',input('param.dw'));
        }else{
            $where[] = array('diff_weight','>',0.1);
        }
        if(input('param.w')){
            $where[] = array('weight','>',input('param.w'));
        }
        if(input('param.aw')){
            $where[] = array('amend_weight','<',input('param.aw'));
        }
        if(input('param.sw')){
            $where[] = array('sell_weight','<',input('param.sw'));
        }
        if(input('param.sbw')){
            $where[] = array('sell_diff_buy_weight','<',input('param.sbw'));
        }
        if(input('param.fee')){
            $where[] = array('fee','<=',input('param.fee')*10000);
        }
        if(input('param.code')){
            unset($where);
            $where[] = array('code','=',input('param.code'));
        }
        $temp_diff = $base::field('code')->where($where)->limit(20)->order('diff_weight desc')
            ->select()->toArray();
        $temp_ab = $base::field('code')->where($where)->limit(20)->order('buy_weight desc')
            ->select()->toArray();
        $temp_aw = $base::field('code')->where($where)->limit(20)->order('amend_weight asc')
            ->select()->toArray();
        $str_diff = '';$str_ab = '';$str_aw = '';
        foreach ($temp_diff as $kk=>$vv){
            $str_diff.=$vv['code'].'  ';
        }
        foreach ($temp_ab as $kk=>$vv){
            $str_ab.=$vv['code'].'  ';
        }
        foreach ($temp_aw as $kk=>$vv){
            $str_aw.=$vv['code'].'  ';
        }
        $str_diff = rtrim($str_diff);
        $str_ab = rtrim($str_ab);
        $str_aw = rtrim($str_aw);
        $diff_arr = explode('  ',$str_diff);
        $ab_arr = explode('  ',$str_ab);
        $aw_arr = explode('  ',$str_aw);
        $arr = array_intersect($diff_arr,$ab_arr,$aw_arr);
        //var_dump($arr);
        $all_data = $base::field('*')->where($where)->order($sort)
            ->select()->toArray();
        echo '总数据:'.count($all_data);
        echo '</br>';
        echo '</br>';
        echo $sort_code.' '.$sort_type.'与buy_weight desc和amend_weight asc 交集:'.implode($arr, ' ');
        echo '</br>';
        echo '</br>';
        foreach ($all_data as $k=>$v){
            $avg = '大于5日均值';
            if ($v['unit_value']<=$v['avg_value1']) {
                $avg = '小于5日均值';
            }
            echo '编码: '.$v['code'].'  权重: '.$v['weight'].'  修正差值: '.$v['diff_weight'].'  修正买权重: '.$v['buy_weight'].'  卖权重: '.$v['sell_weight'].'  买费率%: '.($v['fee']/10000).'% '.'  大于'.$v['sell_1_day'].'天卖费率'.$v['sell_2_fee'].'% '.'  今日预增长%: '.$v['grow_weight'].' &nbsp;&nbsp;'.$avg.' &nbsp;&nbsp;'.$v['hy_1_desc'].' &nbsp;&nbsp;'.$v['name'];
            echo '</br>';
            echo '</br>';
        }
    }

    //  得到今日可卖基金列表
    public function getSellFund(){
        // http://www.daniel.com/api/fund/getSellFund
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.*,b.name,b.hy_1_desc,b.fee,b.day_grow,b.grow_status,b.grow,b.unit_value,b.weight,b.amend_weight,b.sell_weight,b.grow_weight,b.avg_value1,b.avg_value2,b.sell_2_fee,b.sell_1_day')
            ->where('m.my_fund_status','=',1)
            ->order('day_nums desc,grow_weight desc')
            ->select();

        $all_money = 0;
        foreach ($data as $k=>$v){
            $t = ($v['unit_value']-$v['buy_fund_value'])/$v['buy_fund_value'];
            $yields = round($t,4)*100;
            $date = date("Y-m-d");
            if($date == $v['buy_date']){
                $yields = 0;
            }
            $st1 = 0;
            $st2 = 0;
            if($v['unit_value']>$v['avg_value1']){
                $st1 = 1;
            }
            if($v['avg_value1']>$v['avg_value2']){
                $st2 = 1;
            }
            $avg = '大于5日均值';
            if ($v['unit_value']<=$v['avg_value1']) {
                $avg = '小于5日均值';
            }
            $desc = $v['my_fund_status']==1?'持有': '卖出';
            $money = $yields*$v['buy_fund_money']/100;
            $all_money += $money;
            echo '编码: '.$v['my_fund_code'].'   购买日期: '.$v['buy_date'].'   权重: '.$v['weight'].'   修正卖权重: '.round(($v['sell_weight']+$v['grow_weight']),2).'    费率%: '.($v['fee']/10000).'% '.'  大于'.$v['sell_1_day'].'天卖费率'.$v['sell_2_fee'].'% '.'    持有天数: '.$v['day_nums'].'   今日预增长%: '.$v['grow_weight'].'    今日预收益%: '.$yields.' &nbsp;金额:'.$money.' &nbsp;st1-st2: &nbsp;'.$st1.'-'.$st2.' &nbsp;&nbsp;'.$avg.' &nbsp;&nbsp;'.$desc.' &nbsp;&nbsp;&nbsp;&nbsp;'.$v['hy_1_desc'].' &nbsp;&nbsp;&nbsp;&nbsp;'.$v['name'];
            echo '</br>';
            echo '</br>';
        }
        echo '总数据:'.count($data).' 总收益:'.$all_money;
        echo '</br>';
        echo '</br>';
    }
    //  计算我的收益金额
    public function countMyFund(){
        $where[] = array('my_fund_status','=',2);
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.*,b.fee,b.num_2_value,b.num_1_value,b.num_1_date,b.sell_1_fee,b.sell_2_fee,b.sell_1_day,b.sell_2_day')
            ->where($where)
            ->order('day_nums desc,grow_weight desc')
            ->select();

        $arr = [];
        foreach ($data as $k=>$v) {
            $date = date("Y-m-d",(strtotime($v['buy_date'])+3600*24*$v['day_nums']));
            $arr[$k]['my_id'] = $v['my_id'];
            $arr[$k]['sell_date'] = $date;
            if($date==$v['num_1_date']){
                $t = (($v['num_1_value'] - $v['buy_fund_value']) / $v['buy_fund_value'])*$v['buy_fund_money']+$v['buy_fund_money'];
                $sell_money = round($t, 2);
                $arr[$k]['sell_money'] = $sell_money;
                $fee_money = 0;
                $buy_fee = $v['fee']*$v['buy_fund_money']/1000000;

                if($v['day_nums']<$v['sell_1_day']){
                    $fee_money = $buy_fee+$v['sell_1_fee']*($sell_money-$buy_fee)/100;
                }
                if($v['day_nums']>=$v['sell_1_day']){
                    $fee_money = $buy_fee+$v['sell_2_fee']*($sell_money-$buy_fee)/100;
                }
                $profit = $sell_money - $fee_money - $v['buy_fund_money'];
                $yields = round($profit/$v['buy_fund_money'], 4)*100;
                $arr[$k]['yields'] = $yields;
                $arr[$k]['sell_fund_value'] = $v['num_1_value'];
                $arr[$k]['profit'] = round($profit,2);
                $arr[$k]['fee_money'] = round($fee_money,2);
                $arr[$k]['my_fund_status'] = 3;
            }
        }
        $base = new MyFund;
        $base->saveAll($arr);
    }
    //  计算我的收益金额
    public function countMyFundTwo(){
        $where[] = array('my_fund_status','=',2);
        $where[] = array('sell_date','>',0);
        $where[] = array('sell_fund_value','=',0);
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->leftJoin('sp_fund_day_list d','m.my_fund_code = d.code and m.sell_date = d.update_date')
            ->field('m.*,d.unit_value as num_1_value,d.update_date,b.fee,b.sell_1_fee,b.sell_2_fee,b.sell_1_day,b.sell_2_day')
            ->where($where)
            ->order('day_nums desc,grow_weight desc')
            ->select();

        $arr = [];
        foreach ($data as $k=>$v) {
            //;die;
            $date = date("Y-m-d",(strtotime($v['sure_date'])+3600*24*($v['day_nums']-1)));
            if($v['sell_1_fee']<10000&&$v['sell_2_fee']<10000&&$date==$v['update_date']){
                $t = (($v['num_1_value'] - $v['buy_fund_value']) / $v['buy_fund_value'])*$v['buy_fund_money']+$v['buy_fund_money'];
                $sell_money = round($t, 2);
                $arr[$k]['my_id'] = $v['my_id'];
                $arr[$k]['sell_date'] = $date;
                $arr[$k]['sell_money'] = $sell_money;
                $fee_money = 0;
                $buy_fee = $v['fee']*$v['buy_fund_money']/1000000;
                if($v['day_nums']<$v['sell_1_day']){
                    $fee_money = $buy_fee+$v['sell_1_fee']*($sell_money-$buy_fee)/100;
                }
                if($v['day_nums']>=$v['sell_1_day']){
                    $fee_money = $buy_fee+$v['sell_2_fee']*($sell_money-$buy_fee)/100;
                }
                $profit = $sell_money - $fee_money - $v['buy_fund_money'];
                $yields = round($profit/$v['buy_fund_money'], 4)*100;
                $arr[$k]['yields'] = $yields;
                $arr[$k]['sell_fund_value'] = $v['num_1_value'];
                $arr[$k]['profit'] = round($profit,2);
                $arr[$k]['fee_money'] = round($fee_money,2);
                $arr[$k]['my_fund_status'] = 3;
            }
        }
        $base = new MyFund;
        $base->saveAll($arr);
    }
    //  得到基准权重 $data 一维数组
    public function get_weight($data){
        $weight = array();
        $weight['weight'] = (floor($data['week_grow']/700)+floor($data['month_grow']/3000)+floor($data['month_three_grow']/9000))/100;

        $weight['sell_weight'] = $weight['weight'];
        $weight['buy_weight'] = $weight['weight'];
        if($data['day_grow']>0){
            $weight['sell_weight'] = $weight['weight']+floor($data['day_grow']/100)/100;
        }else{
            $weight['buy_weight'] = $weight['weight']-floor($data['day_grow']/100)/100;
        }
        return $weight;
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
    /**
     * 二维数组根据某个字段按指定排序方式排序
     * @param $arr array 二维数组
     * @param $field string 指定字段
     * @param int $sort_order string SORT_ASC 按照上升顺序排序， SORT_DESC 按照下降顺序排序(具体请参考array_multisort官方定义)
     * @param int $sort_flags string 排序类型标志(具体请参考array_multisort官方定义)
     * @return mixed
     *
     * demo
     * // 定义数组
     * $arr = [['name'=>'bbb'], ['name'=>'aaa'], ['name'=>'Ccc']];
     * // 需要按照name字段字符串升序排序
     * $arr = arraySort($arr, 'name', SORT_ASC, SORT_STRING);
     * // 需要按照name字段字符串升序排序,但忽略大小写
     * $arr = arraySort($arr, 'name', SORT_ASC, SORT_FLAG_CASE | SORT_STRING);
     */
    public function my_sort($arr, $field, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        // 异常判断
        if (!$arr || !is_array($arr) || !$field) {
            return $arr;
        }

        // 将指定字段的值存进数组
        $tmp = [];
        foreach ($arr as $k => $v) {
            $tmp[$k] = $v[$field];
        }
        if (!$tmp) {
            return $arr;
        }

        // 调用php内置array_multisort函数
        array_multisort($tmp, $sort_order, $sort_type, $arr);
        return $arr;
    }
    // where条件判断组
    public function my_where($where, $data)
    {
        $j = 1;
        foreach ($where as $kk=>$vv) {
            $bo = 0;
            switch ($vv[1])
            {
                case '=':
                    if($data[$vv[0]]==$vv[2]){
                        $bo = 1;
                    }
                    break;
                case '<':
                    if($data[$vv[0]]<$vv[2]){
                        $bo = 1;
                    }
                    break;
                case '<=':
                    if($data[$vv[0]]<=$vv[2]){
                        $bo = 1;
                    }
                    break;
                case '>':
                    if($data[$vv[0]]>$vv[2]){
                        $bo = 1;
                    }
                    break;
                case '>=':
                    if($data[$vv[0]]>=$vv[2]){
                        $bo = 1;
                    }
                    break;
                default:
                    $bo = 0;
            }
            $j = $j*$bo;
        }
        return $j;
    }
}