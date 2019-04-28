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

class Fund extends Controller{
    private $pageNo;
    private $keywords;
    // 央行公开逆回购网址  http://www.pbc.gov.cn/zhengcehuobisi/125207/125213/125431/125475/17081/index1.html
	// 基础基金网址
	private $host = 'http://fund.eastmoney.com/js/fundcode_search.js?v=$nowDate162803';
	private $host3 = 'http://fund.eastmoney.com/data/rankhandler.aspx?op=ph&dt=kf&ft=all&rs=&gs=0&sc=dm&st=desc&sd=$sd&ed=$ed&qdii=&tabSubtype=,,,,,&pi=1&pn=20000&dx=1&v=0.8059335981746323';
    private $now_host = 'http://fund.eastmoney.com/Data/Fund_JJJZ_Data.aspx?t=1&lx=1&letter=&gsid=&text=&sort=bzdm,asc&page=1,19999&feature=|&dt=$dt471&atfc=&onlySale=1';
    private $info_now_host = 'http://fundgz.1234567.com.cn/js/$code.js?rt=1551755226377';
    private $jijin_history_host = 'http://fund.eastmoney.com/f10/F10DataApi.aspx?type=lsjz&code=$code&page=1&per=100&sdate=$sd&edate=$ed';
    private $host_info = 'http://fundf10.eastmoney.com/jjfl_$code.html';
    private $type = ['定开债券'=>5,'债券型'=>6,'债券指数'=>7,'分级杠杆'=>8,'固定收益'=>9,'保本型'=>10,'货币型'=>11,'联接基金'=>12,'理财型'=>13,'混合-FOF'=>14,'QDII'=>15,'QDII-指数'=>16,'股票型'=>17,'股票指数'=>18,'其他创新'=>19,'ETF-场内'=>20,'混合型'=>21,'QDII-ETF'=>22];
    private $type_nums = [7,14,15,16,17,18,19,20,21,22];
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
        set_time_limit(0);
        $data = Db::table('sp_fund_base')->field('code')->where('sell_1_fee','=',10000)
            ->select();
        foreach ($data as $k => $v){
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/getFundFee?code='.$v['code'];
            $str = HttpGet($host);
        }
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
        $str = "2019-01-02,-86.5195,-3.86%,-22.0158,-0.98%,-64.5037,-2.88%,-15.1527,-0.68%,101.6721,4.54%,2465.29,-1.15%,7149.27,-1.25%,2019-01-03,-74.7161,-2.99%,-10.3535,-0.41%,-64.3626,-2.57%,-23.3834,-0.93%,98.0995,3.92%,2464.36,-0.04%,7089.44,-0.84%,2019-01-04,115.98,3.62%,85.615,2.67%,30.3651,0.95%,-50.4422,-1.57%,-65.5378,-2.04%,2514.87,2.05%,7284.84,2.76%,2019-01-07,-4.168,-0.12%,17.225,0.5%,-21.393,-0.62%,-16.5585,-0.48%,20.7265,0.6%,2533.09,0.72%,7400.20,1.58%,2019-01-08,-79.8823,-2.69%,-6.8008,-0.23%,-73.0816,-2.47%,-18.2411,-0.62%,98.1234,3.31%,2526.46,-0.26%,7391.65,-0.12%,2019-01-09,31.9615,0.85%,60.5026,1.61%,-28.5411,-0.76%,-54.043,-1.44%,22.0815,0.59%,2544.34,0.71%,7447.93,0.76%,2019-01-10,-90.2444,-2.92%,-15.3957,-0.5%,-74.8487,-2.42%,-15.4669,-0.5%,105.7113,3.42%,2535.10,-0.36%,7428.61,-0.26%,2019-01-11,-21.7656,-0.75%,18.6737,0.64%,-40.4393,-1.39%,-35.6811,-1.22%,57.4467,1.97%,2553.83,0.74%,7474.01,0.61%,2019-01-14,-151.0749,-5.41%,-46.4645,-1.66%,-104.6104,-3.75%,-13.1678,-0.47%,164.2428,5.88%,2535.77,-0.71%,7409.20,-0.87%,2019-01-15,46.1831,1.46%,57.3123,1.81%,-11.1292,-0.35%,-50.186,-1.59%,4.0029,0.13%,2570.34,1.36%,7547.35,1.86%,2019-01-16,-92.2079,-3.06%,-18.1703,-0.6%,-74.0377,-2.46%,-15.8495,-0.53%,108.0575,3.59%,2570.42,0.00%,7540.45,-0.09%,2019-01-17,-97.3485,-3.34%,-8.6861,-0.3%,-88.6624,-3.04%,-22.0331,-0.76%,119.3816,4.1%,2559.64,-0.42%,7470.36,-0.93%,2019-01-18,-15.8916,-0.49%,33.8379,1.05%,-49.7295,-1.54%,-41.0154,-1.27%,56.907,1.76%,2596.01,1.42%,7581.39,1.49%,2019-01-21,-32.1447,-1.04%,1.752,0.06%,-33.8968,-1.09%,-13.2318,-0.43%,45.3766,1.46%,2610.51,0.56%,7626.24,0.59%,2019-01-22,-152.5434,-5.52%,-52.6032,-1.9%,-99.9402,-3.62%,6.5234,0.24%,146.02,5.29%,2579.70,-1.18%,7516.79,-1.44%,2019-01-23,-66.2504,-2.76%,-9.2044,-0.38%,-57.046,-2.37%,-18.2605,-0.76%,84.5109,3.52%,2581.00,0.05%,7523.77,0.09%,2019-01-24,-18.0758,-0.61%,19.1375,0.64%,-37.2133,-1.25%,-24.4806,-0.82%,42.5564,1.43%,2591.69,0.41%,7573.52,0.66%,2019-01-25,-54.2882,-1.76%,18.1147,0.59%,-72.4029,-2.34%,-40.5531,-1.31%,94.8412,3.07%,2601.72,0.39%,7595.45,0.29%,2019-01-28,-100.8632,-3.41%,-23.8518,-0.81%,-77.0114,-2.6%,-13.5865,-0.46%,114.4497,3.87%,2596.98,-0.18%,7589.58,-0.08%,2019-01-29,-96.3855,-3.31%,-11.7505,-0.4%,-84.635,-2.9%,-36.4549,-1.25%,132.8404,4.56%,2594.25,-0.11%,7551.30,-0.50%,2019-01-30,-83.6696,-3.58%,-19.8924,-0.85%,-63.7772,-2.73%,-15.6601,-0.67%,99.3297,4.26%,2575.58,-0.72%,7470.47,-1.07%,2019-01-31,-55.3876,-1.96%,5.3872,0.19%,-60.7748,-2.16%,-39.3235,-1.39%,94.7111,3.36%,2584.57,0.35%,7479.22,0.12%,2019-02-01,64.0961,2.47%,48.7777,1.88%,15.3183,0.59%,-32.8358,-1.27%,-31.2603,-1.21%,2618.23,1.30%,7684.00,2.74%,2019-02-11,55.2823,1.74%,52.6002,1.66%,2.6821,0.08%,-26.5561,-0.84%,-28.7262,-0.91%,2653.90,1.36%,7919.05,3.06%,2019-02-12,-13.2923,-0.37%,15.7807,0.44%,-29.0731,-0.8%,-15.0248,-0.41%,28.3171,0.78%,2671.89,0.68%,8010.07,1.15%,2019-02-13,96.5891,2.05%,102.2784,2.17%,-5.6893,-0.12%,-67.5437,-1.43%,-29.0454,-0.62%,2721.07,1.84%,8171.21,2.01%,2019-02-14,-68.3605,-1.66%,-13.4693,-0.33%,-54.8913,-1.33%,-9.9101,-0.24%,78.2706,1.9%,2719.70,-0.05%,8219.96,0.60%,2019-02-15,-154.8038,-3.74%,-72.4831,-1.75%,-82.3208,-1.99%,13.9,0.34%,140.9038,3.4%,2682.39,-1.37%,8125.63,-1.15%,2019-02-18,139.8284,2.58%,124.1001,2.29%,15.7283,0.29%,-73.031,-1.35%,-66.7975,-1.23%,2754.36,2.68%,8446.92,3.95%,2019-02-19,-177.1215,-2.95%,-49.866,-0.83%,-127.2554,-2.12%,1.9889,0.03%,175.1325,2.92%,2755.65,0.05%,8440.87,-0.07%,2019-02-20,-147.1327,-2.98%,-28.6167,-0.58%,-118.516,-2.4%,-7.2004,-0.15%,154.3331,3.13%,2761.22,0.20%,8473.43,0.39%,2019-02-21,-93.2586,-1.52%,24.2247,0.39%,-117.4833,-1.91%,-37.1599,-0.6%,130.4185,2.12%,2751.80,-0.34%,8451.71,-0.26%,2019-02-22,71.7305,1.17%,136.7058,2.22%,-64.9753,-1.06%,-86.1252,-1.4%,14.3948,0.23%,2804.23,1.91%,8651.20,2.36%,2019-02-25,82.7475,0.81%,185.5801,1.81%,-102.8326,-1%,-82.9898,-0.81%,0.2422,0%,2961.28,5.60%,9134.58,5.59%,2019-02-26,-436.8021,-4.03%,-196.6787,-1.81%,-240.1234,-2.22%,76.7277,0.71%,360.0744,3.32%,2941.52,-0.67%,9089.04,-0.50%,2019-02-27,-383.0584,-4.34%,-150.6853,-1.71%,-232.373,-2.63%,41.9018,0.47%,341.1566,3.87%,2953.82,0.42%,9005.77,-0.92%,2019-02-28,-245.4874,-3.76%,-101.5511,-1.55%,-143.9363,-2.2%,28.0295,0.43%,217.4579,3.33%,2940.95,-0.44%,9031.93,0.29%,2019-03-01,-126.7374,-1.94%,7.3435,0.11%,-134.0808,-2.05%,-25.5509,-0.39%,152.2882,2.33%,2994.01,1.80%,9167.65,1.50%,2019-03-04,-72.9662,-0.7%,51.2104,0.49%,-124.1766,-1.2%,-40.9097,-0.39%,113.8759,1.1%,3027.58,1.12%,9384.42,2.36%,2019-03-05,-36.8449,-0.42%,66.8902,0.76%,-103.7352,-1.17%,-57.0426,-0.64%,93.8874,1.06%,3054.25,0.88%,9595.74,2.25%,2019-03-06,-278.6672,-2.55%,-77.0867,-0.71%,-201.5806,-1.85%,31.7181,0.29%,246.9492,2.26%,3102.10,1.57%,9700.49,1.09%,2019-03-07,-321.7656,-2.79%,-104.817,-0.91%,-216.9486,-1.88%,40.7341,0.35%,281.0315,2.44%,3106.42,0.14%,9678.11,-0.23%,2019-03-08,-519.2254,-4.47%,-171.2376,-1.47%,-347.9878,-3%,32.1281,0.28%,487.0973,4.19%,2969.86,-4.40%,9363.72,-3.25%,2019-03-11,-21.9986,-0.24%,38.9474,0.42%,-60.946,-0.65%,-15.8792,-0.17%,37.8779,0.41%,3026.99,1.92%,9704.33,3.64%,2019-03-12,-213.3942,-1.9%,-20.3649,-0.18%,-193.0294,-1.72%,7.9235,0.07%,205.4708,1.83%,3060.31,1.10%,9841.24,1.41%,2019-03-13,-611.0087,-5.86%,-289.8221,-2.78%,-321.1865,-3.08%,116.9425,1.12%,494.0662,4.74%,3026.95,-1.09%,9592.06,-2.53%,2019-03-14,-437.5407,-5.44%,-177.6826,-2.21%,-259.8581,-3.23%,32.3256,0.4%,405.215,5.03%,2990.69,-1.20%,9417.93,-1.82%,2019-03-15,-47.0425,-0.63%,31.2288,0.42%,-78.2713,-1.05%,-29.8528,-0.4%,76.8953,1.04%,3021.75,1.04%,9550.54,1.41%,2019-03-18,31.2597,0.38%,111.3189,1.35%,-80.0592,-0.97%,-69.9692,-0.85%,38.7095,0.47%,3096.42,2.47%,9843.43,3.07%,2019-03-19,-182.4698,-2.38%,-57.911,-0.76%,-124.5588,-1.63%,2.5518,0.03%,179.918,2.35%,3090.98,-0.18%,9839.74,-0.04%,2019-03-20,-312.9666,-4.06%,-90.7526,-1.18%,-222.214,-2.88%,15.2837,0.2%,297.683,3.86%,3090.64,-0.01%,9800.60,-0.40%,2019-03-21,26.6497,0.3%,115.7164,1.32%,-89.0667,-1.02%,-85.308,-0.97%,58.6584,0.67%,3101.46,0.35%,9869.80,0.71%,2019-03-22,-287.8867,-3.65%,-98.6167,-1.25%,-189.27,-2.4%,24.4778,0.31%,263.4088,3.34%,3104.15,0.09%,9879.22,0.10%,2019-03-25,-371.7591,-4.62%,-147.1582,-1.83%,-224.6008,-2.79%,63.1203,0.78%,308.6388,3.83%,3043.03,-1.97%,9701.70,-1.80%,2019-03-26,-526.9555,-6.93%,-229.4933,-3.02%,-297.4622,-3.91%,79.6071,1.05%,447.3484,5.88%,2997.10,-1.51%,9513.00,-1.95%,2019-03-27,-120.4675,-1.83%,20.4331,0.31%,-140.9006,-2.14%,-38.1538,-0.58%,158.6213,2.41%,3022.72,0.85%,9609.44,1.01%,2019-03-28,-252.5202,-3.85%,-77.3173,-1.18%,-175.2029,-2.67%,10.9234,0.17%,241.5967,3.69%,2994.94,-0.92%,9546.51,-0.65%,2019-03-29,154.4139,1.87%,200.0598,2.42%,-45.6458,-0.55%,-138.1161,-1.67%,-16.2979,-0.2%,3090.76,3.20%,9906.86,3.77%,2019-04-01,110.3893,1.07%,160.1223,1.55%,-49.7329,-0.48%,-77.935,-0.76%,-32.4543,-0.31%,3170.36,2.58%,10267.70,3.64%,2019-04-02,-249.6115,-2.44%,-64.613,-0.63%,-184.9985,-1.81%,25.8332,0.25%,223.7782,2.18%,3176.82,0.20%,10260.36,-0.07%,2019-04-03,-158.6129,-1.72%,11.232,0.12%,-169.8449,-1.84%,-37.9513,-0.41%,196.5642,2.13%,3216.30,1.24%,10340.51,0.78%,2019-04-04,-198.9257,-2.01%,5.7139,0.06%,-204.6397,-2.06%,-34.2176,-0.35%,233.1434,2.35%,3246.57,0.94%,10415.80,0.73%,2019-04-08,-498.8125,-4.73%,-178.3095,-1.69%,-320.503,-3.04%,73.1214,0.69%,425.6911,4.04%,3244.81,-0.05%,10351.87,-0.61%,2019-04-09,-286.2012,-3.44%,-110.2165,-1.33%,-175.9847,-2.12%,31.8654,0.38%,254.3357,3.06%,3239.66,-0.16%,10436.62,0.82%,2019-04-10,-250.3064,-2.81%,-51.3412,-0.58%,-198.9652,-2.24%,0.9725,0.01%,249.3339,2.8%,3241.93,0.07%,10435.08,-0.01%,2019-04-11,-537.391,-6.63%,-235.001,-2.9%,-302.39,-3.73%,70.0088,0.86%,467.3822,5.77%,3189.96,-1.60%,10158.40,-2.65%,2019-04-12,-261.5219,-4.03%,-84.0926,-1.3%,-177.4293,-2.73%,-2.965,-0.05%,264.4869,4.07%,3188.63,-0.04%,10132.34,-0.26%,2019-04-15,-295.6884,-3.87%,-102.3728,-1.34%,-193.3156,-2.53%,45.8461,0.6%,249.8424,3.27%,3177.79,-0.34%,10053.76,-0.78%,2019-04-16,53.7181,0.68%,151.7691,1.93%,-98.0509,-1.25%,-107.022,-1.36%,53.3038,0.68%,3253.60,2.39%,10287.64,2.33%,2019-04-17,-109.145,-1.33%,10.5552,0.13%,-119.7002,-1.46%,-15.0805,-0.18%,124.2255,1.51%,3263.12,0.29%,10344.43,0.55%,2019-04-18,-302.3863,-4.07%,-90.435,-1.22%,-211.9513,-2.85%,18.3384,0.25%,284.048,3.82%,3250.20,-0.40%,10287.67,-0.55%,2019-04-19,-125.6709,-1.77%,5.8433,0.08%,-131.5142,-1.86%,-18.4703,-0.26%,144.1412,2.03%,3270.80,0.63%,10418.24,1.27%,2019-04-22,-538.6673,-6.8%,-254.2079,-3.21%,-284.4594,-3.59%,93.0766,1.17%,445.5908,5.62%,3215.04,-1.70%,10224.31,-1.86%,2019-04-23,-373.4194,-5.13%,-120.0637,-1.65%,-253.3557,-3.48%,1.6197,0.02%,371.7998,5.11%,3198.59,-0.51%,10124.66,-0.97%,2019-04-24,-135.5103,-2.09%,5.4752,0.08%,-140.9855,-2.17%,-38.902,-0.6%,174.4122,2.69%,3201.61,0.09%,10236.27,1.10%,2019-04-25,-572.2992,-7.9%,-247.9066,-3.42%,-324.3926,-4.48%,50.7746,0.7%,521.5246,7.2%,3123.83,-2.43%,9907.62,-3.21%,2019-04-26,-216.2613,-3.51%,-40.9746,-0.66%,-175.2867,-2.84%,-20.8527,-0.34%,237.114,3.85%,3086.40,-1.20%,9780.82,-1.28%";
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