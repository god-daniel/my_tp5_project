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
	
	// 每日录入基金数据 每晚10点添加
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
    //  更新基金,购买费,周增长，月增长等数据净值 每晚10点30更新
    public function updateFundBase(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $base = new FundBase;
            if(input('param.code')){
                $where[] = array('code','=',input('param.code'));
            }
            $where[] = array('1','=',1);
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
    //  更新10日基金数据净值 每晚11点开始更新
    public function tenTodayFund(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
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
            $where[] = array('buy_status','=',0);
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
                $data[$k]['grow_status']=$weight['grow_status'];
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
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $data = Db::table('sp_my_fund')
                ->alias('m')
                ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
                ->field('group_concat(distinct(b.id)) as ids')->where($where)->select();
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
	//  更新基础基金数据 每晚10点20更新
	public function addFunList(){
		$is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
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
    public function updateFund(){
        set_time_limit(0);
        $is_gzr = 0;
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
        }
        $page_sd = 1000*($page-1)+1;
        $page_ed = 1000*$page;
        //$is_gzr = 0;
        if($is_gzr==0){

            $base_url = $this->host_info;
            $rules = [
                //一个规则只能包含一个function
                //采集class为pt30的div的第一个h1文本
                // 'all_html' => ['.r_cont','html'],.r_cont>.basic-new>.bs_jz>
                'unit_value' => ['#fund_gsz','text'],
                'grow' => ['#fund_gszf','text'],
                'buy_desc' => ['p.row:eq(1) span:eq(0)','text'],
                'sell_desc' => ['p.row:eq(1) span:eq(2)','text'],
                'fee' => ['p.row:eq(2) b:eq(1)','text'],
                'create_date' => ['.bs_gl p:eq(0) span:eq(0)','text'],
                'type_desc' => ['.bs_gl p:eq(0) span:eq(1)','text'],
                'unit_pile_size_desc' => ['.bs_gl p:eq(0) span:eq(2)','text'],
                'year_manage_fee' => ['table.w770:eq(4) td:eq(1)','text'],
                'year_deposit_fee' => ['table.w770:eq(4) td:eq(3)','text'],
                'year_sell_fee' => ['table.w770:eq(4) td:eq(5)','text'],
                'sell_1_fee' => ['table.w650:eq(2) tr:eq(1) td:eq(2)','text'],
                'sell_1_day' => ['table.w650:eq(2) tr:eq(1) td:eq(1)','text'],
                'sell_2_fee' => ['table.w650:eq(2) tr:eq(2) td:eq(2)','text'],
                'sell_2_day' => ['table.w650:eq(2) tr:eq(2) td:eq(1)','text'],
            ];
            // $data = $ql->rules($rules)->query()->getData()->all();

            $base = new FundBase;
            $all_data = $base::where('id','>=',$page_sd)->where('id','<=',$page_ed)->order('code asc')->select()->toArray();
            $arr = [];
            foreach ($all_data as $k=>$v){
                $code = $all_data[$k]['code'];
                //$code = '000794';
                $arr[$k]['create_time'] = 1551577703;  //创建时间
                $arr[$k]['update_time'] = time();  //更新时间
                $url = str_replace('$code',$code,$base_url);
                $ql = QueryList::get($url,null,[
                    'timeout' => 3600]);
                $data = $ql->rules($rules)->query()->getData()->all();
                $ql->destruct();
                $arr[$k] = $data[0];
                $arr[$k]['url'] = $url;
                $arr[$k]['id'] = $v['id'];
                $arr[$k]['code'] = $v['code'];
                if(((int)$arr[$k]['unit_value'])){
                    $arr[$k]['unit_value'] = $arr[$k]['unit_value']*10000;
                }else{
                    $arr[$k]['unit_value'] = 0;
                }
                if(((int)$arr[$k]['grow'])){
                    $arr[$k]['grow'] = $arr[$k]['grow']*10000;
                }else{
                    $arr[$k]['grow'] = 0;
                }
                if(((int)$arr[$k]['fee'])){
                    $arr[$k]['fee'] = $arr[$k]['fee']*10000;
                }else{
                    $arr[$k]['fee'] = 10000;
                }
                if(((int)$arr[$k]['sell_1_fee'])){
                    $arr[$k]['sell_1_fee'] = $arr[$k]['sell_1_fee']*10000;
                }else{
                    $arr[$k]['sell_1_fee'] = 10000;
                }
                if(((int)$arr[$k]['sell_2_fee'])){
                    $arr[$k]['sell_2_fee'] = $arr[$k]['sell_2_fee']*10000;
                }else{
                    $arr[$k]['sell_2_fee'] = 10000;
                }

                if($arr[$k]['unit_pile_size']){
                    $arr[$k]['unit_pile_size'] =substr($arr[$k]['unit_pile_size_desc'],0,strpos($arr[$k]['unit_pile_size_desc'],'亿'))*100;
                }else{
                    $arr[$k]['unit_pile_size'] = 0;
                }

                if($arr[$k]['year_manage_fee']){
                    $test = substr($arr[$k]['year_manage_fee'],0,strpos($arr[$k]['year_manage_fee'],'%'));
                    if(strlen($test)>10||!$test ){
                        $arr[$k]['year_manage_fee'] =10000;
                    }else{
                        $arr[$k]['year_manage_fee'] =$test*100;
                    }
                }else{
                    $arr[$k]['year_manage_fee'] = 10000;
                }
                if($arr[$k]['year_deposit_fee']){
                    $arr[$k]['year_deposit_fee'] =substr($arr[$k]['year_deposit_fee'],0,strpos($arr[$k]['year_deposit_fee'],'%'))*100;
                }else{
                    $arr[$k]['year_deposit_fee'] = 10000;
                }
                if(strpos($arr[$k]['year_sell_fee'], '%')!== false){
                    $arr[$k]['year_sell_fee'] =substr($arr[$k]['year_sell_fee'],0,strpos($arr[$k]['year_sell_fee'],'%'))*100;
                }else{
                    $arr[$k]['year_sell_fee'] = 0;
                }
                if(mb_substr($arr[$k]['sell_1_day'], -1)=='年'){
                    $arr[$k]['sell_1_day'] = mb_strrchr($arr[$k]['sell_1_day'],'于');
                    $arr[$k]['sell_1_day'] = mb_substr($arr[$k]['sell_1_day'],1, -1)*365;
                }else{
                    if(strpos($arr[$k]['sell_1_day'], '于')!== false){
                        if(mb_substr($arr[$k]['sell_1_day'], -1)=='月'){// 个月
                            $arr[$k]['sell_1_day'] = mb_strrchr($arr[$k]['sell_1_day'],'于');
                            $arr[$k]['sell_1_day'] = mb_substr($arr[$k]['sell_1_day'],1, -2)*30;
                        }elseif(mb_substr($arr[$k]['sell_1_day'], -1)=='天'){
                            $arr[$k]['sell_1_day'] = mb_strrchr($arr[$k]['sell_1_day'],'于');
                            $arr[$k]['sell_1_day'] = mb_substr($arr[$k]['sell_1_day'],1, -1)*1;
                        }else{
                            $arr[$k]['sell_1_day'] = 10000;
                        }
                    }else{
                        $arr[$k]['sell_1_day'] = 10000;
                    }
                }
                if(mb_substr($arr[$k]['sell_2_day'], -1)=='年'){
                    $arr[$k]['sell_2_day'] = mb_strrchr($arr[$k]['sell_2_day'],'于');
                    if($arr[$k]['sell_2_day']){
                        $arr[$k]['sell_2_day'] = mb_substr($arr[$k]['sell_2_day'],1, -1)*365;
                    }else{
                        $arr[$k]['sell_2_day'] = 365;
                    }
                }else{
                    if(strpos($arr[$k]['sell_2_day'], '于')!== false){
                        if(mb_substr($arr[$k]['sell_2_day'], -1)=='月'){// 个月
                            $arr[$k]['sell_2_day'] = mb_strrchr($arr[$k]['sell_2_day'],'于');
                            $arr[$k]['sell_2_day'] = mb_substr($arr[$k]['sell_2_day'],1, -2)*30;
                        }elseif(mb_substr($arr[$k]['sell_2_day'], -1)=='天'){
                            $arr[$k]['sell_2_day'] = mb_strrchr($arr[$k]['sell_2_day'],'于');
                            $arr[$k]['sell_2_day'] = mb_substr($arr[$k]['sell_2_day'],1, -1)*1;
                        }else{
                            $arr[$k]['sell_2_day'] = 10000;
                        }
                    }else{
                        $arr[$k]['sell_2_day'] = 10000;
                    }
                }
                $arr[$k]['buy_status'] = 1;
                if($arr[$k]['buy_desc']=='开放申购') {
                    $arr[$k]['buy_status'] = 0;
                }
            }
            $base->saveAll($arr);
        }
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
    //  更新我持有的基金数据  持有天数，收益率
    public function todayMyFund(){
        // http://www.daniel.com/api/fund/todayMyFund
        $base = new MyFund;
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.my_id,m.my_fund_code,m.buy_date,m.day_nums,m.buy_fund_value,m.buy_fund_num,m.buy_fund_money,m.yields,b.num_1_value,b.num_1_date')
            ->where('m.my_fund_status','=',1)
            ->select();
        $today = date("Y-m-d");
        foreach ($data as $k => $v){
            if($v['num_1_date'] == $v['buy_date']){
                $data[$k]['buy_fund_value'] = $v['num_1_value'];
                $t = ($data[$k]['buy_fund_money']/$data[$k]['buy_fund_value'])*10000;
                $data[$k]['buy_fund_num'] = round($t,2);
            }
            $data[$k]['day_nums'] = (strtotime($today)-strtotime($v['buy_date']))/86400;
            $yields = ($v['num_1_value']-$data[$k]['buy_fund_value'])/$data[$k]['buy_fund_value'];
            $data[$k]['yields'] = round($yields,4)*100;
        }
        $base->saveAll($data);
    }
    //  测试
    public function test(){
        var_dump(111);die;
        set_time_limit(0);
        $base = new FundBase;
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        $where[] = array('buy_status','=',0);
        $all_data = $base::where($where)->order('code asc')
            ->select()->toArray();
        foreach ($all_data as $k=>$v){
            $cahe = Cache::get($v['code']);
            $all_data[$k]['create_time'] = 1551577703;  //创建时间
            $all_data[$k]['update_time'] = time();  //更新时间
            $all_data[$k]['weight'] = 0;
            $all_data[$k]['buy_weight'] = 0;
            $all_data[$k]['sell_weight'] = 0;
            $all_data[$k]['amend_weight'] = 0;
            $all_data[$k]['grow_status'] = 1;
            if($cahe||1){
                //$temp = json_decode($cahe,true);
                $weight = $this->get_weight($v);

                $now_grow = $v['day_grow'];
                //$now_grow = 0;
                //if($all_data[$k]['num_2_value']>0){
                 //   $now_grow = floor(($all_data[$k]['num_1_value']-$all_data[$k]['num_2_value'])/$all_data[$k]['num_2_value']*10000);
                //}
                //$temp['week_grow']+=$now_grow;
                //$temp['month_grow']+=$now_grow;
                //$temp['month_three_grow']+=$now_grow;
                //$amend_weight = $this->get_weight($temp);
                $all_data[$k]['weight']=$weight['weight'];
                $all_data[$k]['buy_weight']+=$weight['buy_weight'];
                $all_data[$k]['sell_weight']+=$weight['sell_weight'];
                $all_data[$k]['grow_status']=$weight['grow_status'];
                //$all_data[$k]['amend_weight']+=$amend_weight['weight'];
            }
        }
        $base->saveAll($all_data);
    }

    //  jsonp 页面
    public function showFund(){
        $pq_bool = $this->is_open_date();
        $pq_bool = 1;
        $pq_url = 'http://api.fund.eastmoney.com/FundGuZhi/GetFundGZList?type=1&sort=1&orderType=asc&canbuy=1&pageIndex=1&pageSize=20000'; // 请求地址 爬取数据
        $do_url = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/doFund'; // 请求地址 处理数据
        $this->assign('pq_bool', $pq_bool);
        $this->assign('pq_url', $pq_url);
        $this->assign('do_url', $do_url);
        return $this->fetch();
    }
    //  jsonp 页面
    public function doFund(){
        $list = input('param.list');
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
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

                        $temp['day_grow']=$v['day_grow'];
                        $temp['week_grow']=$all_data[$k]['grow']+$v['week_grow'];
                        $temp['month_grow']=$all_data[$k]['grow']+$v['month_grow'];
                        $temp['month_three_grow']=$all_data[$k]['grow']+$v['month_three_grow'];
                        $amend_weight = $this->get_weight($temp);
                        $all_data[$k]['amend_weight']+=$amend_weight['weight'];
                        $all_data[$k]['grow_weight']=$vv['gszzl']*1;
                        $all_data[$k]['diff_weight'] = $v['weight']-$all_data[$k]['amend_weight'];
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
    //  缓存抓取实时数据，保存到数据库
    public function saveFundBase(){
        set_time_limit(0);
        $base = new FundBase;
        $str = Cache::get('base_data');
        $arr = json_decode($str,true);
        $chunk_result = array_chunk($arr, 500, true);
        foreach ($chunk_result as $k=>$v) {
            $base->saveAll($v);
        }
        echo 1;
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

    //  设置缓存
    public function setCache(){
        $date = date("Y-m-d");
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
        $cahe = Cache::get('001553');
        var_dump($cahe);
    }
    //  我的基金列表
    public function myFund(){
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.*,b.name,b.fee,b.unit_value,b.grow,b.sell_1_fee,b.sell_2_fee,b.sell_1_day,b.sell_2_day,b.num_1_grow,b.num_2_grow,b.num_3_grow,b.num_4_grow,b.num_5_grow')
            ->select();
        var_dump($data);
    }
    //
    public function getTesst(){
        set_time_limit(0);
        $str = Cache::get('base_data');
        $arr_all = json_decode($str,true);
        $where[] = array('diff_weight','>',0.1);
        $where[] = array('diff_weight','<=',6);
        $where[] = array('buy_status','=',0);
        $where[] = array('weight','>',3);
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
        if(input('param.w')){
            $where[] = array('weight','>',input('param.w'));
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
        // http://www.daniel.com/api/fund/getBuyFund?w=3.3&aw=4&bw=1&sw=9&sd=diff_weight
        $base = new FundBase;
        $where[] = array('diff_weight','>',0.1);
        $where[] = array('diff_weight','<=',1);
        $where[] = array('buy_status','=',0);
        //$where[] = array('weight','>',2.5);
        //$where[] = array('sell_diff_buy_weight','<',2.5);
        $where[] = array('amend_weight','>',3);
        $sort_code = 'weight';
        $sort_type = 'desc';
        if(input('param.sd')){
            $sort_code = input('param.sd');
        }
        if(input('param.st')){
            $sort_type = input('param.st');
        }
        $sort = $sort_code.' '.$sort_type;
        if(input('param.code')){
            $where[] = array('code','=',input('param.code'));
        }
        if(input('param.bw')){
            $where[] = array('buy_weight','>',input('param.bw'));
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
            echo '编码: '.$v['code'].'  权重: '.$v['weight'].'  修正差值: '.$v['diff_weight'].'  修正买权重: '.$v['buy_weight'].'  卖权重: '.$v['sell_weight'].'  费率%: '.($v['fee']/10000).'  今日预增长%: '.$v['grow_weight'].' '.$v['name'];
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
            ->field('m.*,b.name,b.fee,b.day_grow,b.grow_status,b.grow,b.unit_value,b.weight,b.amend_weight,b.sell_weight,b.grow_weight')
            ->where('m.my_fund_status','=',1)
            ->order('sell_weight desc')
            ->select();
        echo '总数据:'.count($data);
        echo '</br>';
        echo '</br>';
        foreach ($data as $k=>$v){
            $t = ($v['unit_value']-$v['buy_fund_value'])/$v['buy_fund_value'];
            $yields = round($t,4)*100;
            $date = date("Y-m-d");
            if($date == $v['buy_date']){
                $yields = 0;
            }
            echo '编码: '.$v['my_fund_code'].'   购买日期: '.$v['buy_date'].'   权重: '.$v['weight'].'   修正卖权重: '.round(($v['sell_weight']+$v['grow_weight']),2).'    费率%: '.($v['fee']/10000).'    持有天数: '.$v['day_nums'].'   今日预增长%: '.$v['grow_weight'].'    今日预收益%: '.$yields;
            echo '</br>';
            echo '</br>';
        }
    }
    //  得到基准权重 $data 一维数组
    public function get_weight($data){
        $weight = array();
        $weight['grow_status'] = 1;
        if($data['week_grow']<0||$data['month_grow']<0||$data['month_three_grow']<0){
            $weight['grow_status'] = 0;  //下降趋势
        }
        if(0<$data['week_grow']&&(2*$data['week_grow'])<$data['month_grow']){
            $weight['grow_status'] = 2;  //上升趋势
        }
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
        $res = file_get_contents($url);
        $res = json_decode($res,true);
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