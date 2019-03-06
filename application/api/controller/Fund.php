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
    //  更新基金估值(详情中更新(请求))
    public function updateTodayFund(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        $base_url = $this->info_now_host;
        //$is_gzr = 0;
        if($is_gzr==0){
            $base = new FundBase;
/*             $all_data = $base::where('type','in',$this->type_nums)
                ->where('year_manage_fee','<',10000)
                ->where('sell_2_day','<=',30)
                ->where('buy_status','=',0)
                ->order('code asc')
                ->select()->toArray(); */
            $all_data = $base::order('code asc')
                ->select()->toArray();
            $arr = [];
            foreach ($all_data as $k=>$v){
                $code = $all_data[$k]['code'];
                // $code = '002877';
                //echo $code;echo '</br>';echo '</br>';
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
                    // var_dump($data);die;
                    $arr[$k]['buy_status'] = 0;
                    $arr[$k]['update_date'] = $data['gztime'];
                    $arr[$k]['unit_value'] = $data['gsz']*10000;
                    $arr[$k]['grow'] = $data['gszzl']*10000;
                }
            }
            $base->saveAll($arr);
        }
    }
    //  更新基金估值(详情中更新爬虫)
    public function updateNowFund(){
        set_time_limit(0);
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        $page = 1;
        if(input('param.page')){
            $page = input('param.page');
        }
        $page_sd = 2000*($page-1)+1;
        $page_ed = 2000*$page;
        //$is_gzr = 0;
        if($is_gzr==0){

            $base_url = $this->host_info;
            $rules = [
                //一个规则只能包含一个function
                //采集class为pt30的div的第一个h1文本
                // 'all_html' => ['.r_cont','html'],.r_cont>.basic-new>.bs_jz>
                'unit_value' => ['#fund_gsz','text'],
                'grow' => ['#fund_gszf','text'],
                'fee' => ['p.row:eq(2) b:eq(1)','text'],
            ];
            // $data = $ql->rules($rules)->query()->getData()->all();

            $base = new FundBase;
            $all_data = $base::where('id','>=',$page_sd)
                ->where('id','<=',$page_ed)
                ->where('type','in',$this->type_nums)
                ->where('year_manage_fee','<',10000)
                ->where('sell_2_day','<=',30)
                ->where('buy_status','=',0)
                ->order('code asc')
                ->select()->toArray();
            $arr = [];
            foreach ($all_data as $k=>$v){
                $code = $all_data[$k]['code'];
                // $code = '002877';
                //echo $code;echo '</br>';echo '</br>';
                $arr[$k]['create_time'] = 1551577703;  //创建时间
                $arr[$k]['update_time'] = time();  //更新时间
                $url = str_replace('$code',$code,$base_url);
                $ql = QueryList::get($url,null,[
                    'timeout' => 3600]);
                $data = $ql->rules($rules)->query()->getData()->all();
                if($code=='002877'){
                    // var_dump($data[0]);
                }
                $ql->destruct();
                $arr[$k] = $data[0];
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
            }
            $base->saveAll($arr);
        }
    }
    //  添加今日基金数据  每晚10点更新
    public function addTodayFund(){
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $date = date('Y-m-d');
            $host = 'http://'.$_SERVER['HTTP_HOST'].'/api/fund/addHistoryFund?sdate='.$date.'&edate='.$date;
            $str = HttpGet($host);
            var_dump($host);
        }
    }
    //  更新10日基金数据净值 每晚11点更新
    public function tenTodayFund(){
        $is_gzr = $this->is_jiaoyi_day(strtotime("-0 day"));
        if($is_gzr==0){
            $where[] = array('buy_status','=',0);
            $base = new FundBase;
            $data = $base::where($where)
                ->order('code asc')
                ->limit(10)->select()->toArray();
            $day_base = new FundDayList();
            foreach ($data as $k=>$v){
                $map[] = array('code','=',$v['code']);
                $day_data = $day_base::where($map)
                    ->order('code asc,update_date desc')
                    ->limit(10)->select()->toArray();
                foreach ($day_data as $kk=>$vv){
                    $data[$k]['num_'.($kk+1).'_value'] = $vv['unit_value'];
                    $data[$k]['num_'.($kk+1).'_date'] = $vv['update_date'];
                }

  /*               $data[$k]['num_2_value'] = $day_data[1]['unit_value']?$day_data[1]['unit_value']:0;
                $data[$k]['num_2_date'] = $day_data[1]['update_date']?$day_data[1]['update_date']:0;
                $data[$k]['num_3_value'] = $day_data[2]['unit_value']?$day_data[2]['unit_value']:0;
                $data[$k]['num_3_date'] = $day_data[2]['update_date']?$day_data[2]['update_date']:0;
                $data[$k]['num_4_value'] = $day_data[3]['unit_value']?$day_data[3]['unit_value']:0;
                $data[$k]['num_4_date'] = $day_data[3]['update_date']?$day_data[3]['update_date']:0;
                $data[$k]['num_5_value'] = $day_data[4]['unit_value']?$day_data[4]['unit_value']:0;
                $data[$k]['num_5_date'] = $day_data[5]['update_date']?$day_data[5]['update_date']:0;
                $data[$k]['num_6_value'] = $day_data[0]['unit_value']?$day_data[0]['unit_value']:0;
                $data[$k]['num_6_date'] = $day_data[0]['update_date']?$day_data[0]['update_date']:0;
                $data[$k]['num_7_value'] = $day_data[0]['unit_value']?$day_data[0]['unit_value']:0;
                $data[$k]['num_7_date'] = $day_data[0]['update_date']?$day_data[0]['update_date']:0;
                $data[$k]['num_8_value'] = $day_data[0]['unit_value']?$day_data[0]['unit_value']:0;
                $data[$k]['num_8_date'] = $day_data[0]['update_date']?$day_data[0]['update_date']:0;
                $data[$k]['num_9_value'] = $day_data[0]['unit_value']?$day_data[0]['unit_value']:0;
                $data[$k]['num_9_date'] = $day_data[0]['update_date']?$day_data[0]['update_date']:0;
                $data[$k]['num_10_value'] = $day_data[0]['unit_value']?$day_data[0]['unit_value']:0;
                $data[$k]['num_10_date'] = $day_data[0]['update_date']?$day_data[0]['update_date']:0; */
            }
            $base->saveAll($data);
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

        $url = $this->jijin_history_host;
        $where[] = array('buy_status','=',0);
        $base = new FundBase;
        $day_list = new FundDayList();
        $all_data = $base::where($where)
            ->order('code asc')
            ->select()->toArray();
        $arr = [];
        foreach ($all_data as $k=>$v){
            $code = $all_data[$k]['code'];
            $arr[$k]['code'] = $all_data[$k]['code'];
            $arr[$k]['name'] = $all_data[$k]['name'];
            $arr[$k]['create_time'] = 1551577703;  //创建时间
            $arr[$k]['update_time'] = time();  //更新时间
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
                    if($table_array>2&&$kk==2){
                        $td_array = explode('<td',$vv);
                        $update_date = substr($td_array[1],strpos($td_array[1],'>')+1);
                        $update_date = substr($update_date,0,strlen($update_date)-5);
                        $unit_value = substr($td_array[2],strpos($td_array[2],'>')+1);;
                        $unit_pile_value = substr($td_array[3],strpos($td_array[3],'>')+1);;
                        $day_grow = substr($td_array[4],strpos($td_array[4],'>')+1);;
                        $arr[$k]['update_date'] = $update_date;
                        $arr[$k]['unit_value'] = $unit_value*10000;
                        $arr[$k]['unit_pile_value'] = $unit_pile_value*10000;
                        $arr[$k]['day_grow'] = $day_grow*10000;

                    }
                }

            }
        }
        $day_list->saveAll($arr);
    }
    //  更新基金类型
    public function test(){
        $url = 'http://fundgz.1234567.com.cn/js/000001.js?rt=1551755226377';

        $ch = HttpGet($url);
        var_dump($ch);
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
    //  更新基金状态  每天晚上10点运行
    public function changeStatus(){
        $date = date('Y-m-d');
        $base = new FundBase;
        $all_data = $base::field('id,code,buy_status,buy_not_num,update_date')->order('code asc')
            ->select()->toArray();
        foreach ($all_data as $k=>$v){
            $all_data[$k]['buy_status'] = 0;
            if($date>=$v['update_date']){
                $all_data[$k]['buy_status'] = 1;
                $all_data[$k]['buy_not_num'] = 1+$v['buy_not_num'];
            }
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