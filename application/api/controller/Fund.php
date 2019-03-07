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
use app\common\model\FundBaseList;
use app\common\model\FundBase;
use app\common\model\FundDayList;
use app\common\model\FundHistoryList;
use think\Db;
use think\facade\Cache;

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
			}
			$history->saveAll($arr);			
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
    //  添加今日基金数据  每晚10点10更新
    public function addTodayFund(){
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $date = date('Y-m-d');
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/addHistoryFund?sdate='.$date.'&edate='.$date;
            $str = HttpGet($host);
            var_dump($host);
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
            foreach ($data as $k=>$v){
                $map[] = array('code','=',$v['code']);
                $day_data = $day_base::where('code','=', $v['code'])->limit(10)
                    ->order('update_date desc')->select()->toArray();
                $data[$k]['create_time'] = 1551577703;  //创建时间
                $data[$k]['update_time'] = time();  //更新时间
                foreach ($day_data as $kk=>$vv){
                    $data[$k]['num_'.($kk+1).'_value'] = $vv['unit_value'];
                    $data[$k]['num_'.($kk+1).'_date'] = $vv['update_date'];
                }
            }
            $base->saveAll($data);
        }
    }
    //  我的基金列表
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
                $page_num = 200;
            }
            if(input('param.ids')){
                $where[] = array('id','in',input('param.ids'));
            }
            if(input('param.bs')){
                $where[] = array('buy_status','=',input('param.bs'));
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
    //  更新基金类型
    public function test(){
        $url = 'http://fundgz.1234567.com.cn/js/000001.js?rt=1551755226377';
        Cache::set('zb','111111',7200);
        //$ch = HttpGet($url);
        $temp = json_decode(Cache::get('001553'),true);
        var_dump($temp);
        if($temp){
            var_dump(22222);die;
        }
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
    //  我的基金列表
    public function myFund(){
        $data = Db::table('sp_my_fund')
            ->alias('m')
            ->leftJoin('sp_fund_base b','m.my_fund_code = b.code')
            ->field('m.*,b.name,b.fee,b.unit_value,b.grow,b.sell_1_fee,b.sell_2_fee,b.sell_1_day,b.sell_2_day,b.num_1_grow,b.num_2_grow,b.num_3_grow,b.num_4_grow,b.num_5_grow')
            ->select();
        var_dump($data);
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