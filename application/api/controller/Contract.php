<?php
/**
 * Created by ecitlm.
 * User: ecitlm
 * Date: 2017/9/23
 * Time: 00:18
 */


namespace app\api\controller;
use think\Controller;
use think\facade\Cache;
use app\api\model\ContractQueue;
use app\api\model\Contract as ContractMode;
class Contract extends Controller{

    //测试环境
    private $_developerId = 'xxx';
    private $_pem = 'xxx';
	private $_host = 'xxx';        //上上签请求域名

    private $_contract_host = 'http://test.zl.mankkk.cn';                //合同展示域名
    private $_contract_path = '/Distributor/Contracts/show/cnumber/';   //合同展示路径
    private $_contract_pdf_path = 'http://ssq.mankkk.cn/pdf/';   //合同展示路径
    private $_contract_reback_host = 'http://ssq.mankkk.cn/api/contract/reback';   //合同手签回调地址
	private $_contract_reshow_host = 'http://ssq.mankkk.cn/api/contract/reshow';   //合同手签跳转地址
    private static $_instances;
    private $_default_user_agent = '';
    private $_response_headers = '';

    const DEFAULT_CONNECT_TIMEOUT = 60; //默认连接超时
    const DEFAULT_READ_TIMEOUT = 6000; //默认读取超时
    const MAX_REDIRECT_COUNT = 10;

    public function setDefaultUserAgent($user_agent) {
        $this->_default_user_agent = $user_agent;
        return $this;
    }
    
    public function get($url, array $headers = array(), $auto_redirect = true, $cookie_file = null) 
    {
        return $this->_request($url, "GET", null, null, $headers, $auto_redirect, $cookie_file);
    }
    
    public function post($url, $post_data = null, $post_files = null, array $headers = array(), $cookie_file = null)
    {
        return $this->_request($url, "POST", $post_data, $post_files, $headers, $cookie_file);
    }
    
    private function _headerCallback($ch, $data)
    {
        $this->_response_headers .= $data;
        return strlen($data);
    }
    
    private function _request($url, $method = "GET", $post_data = null, $post_files = null, array $headers = array(), $auto_redirect = true, $cookie_file = null)
    {
        //$url = 'http://localhost/ssq/test.php';
        if (strcasecmp($method, "POST") == 0) {
            $method = 'POST';
        }
        else {
            $method = 'GET';
        }

        if (!empty($post_files) && !is_array($post_files))
        {
            $post_files = array();
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::DEFAULT_CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::DEFAULT_READ_TIMEOUT);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        
        if (!empty($cookie_file))
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);     
        }
        
        // set location
        if ($auto_redirect)
        {
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, self::MAX_REDIRECT_COUNT);
        }
        
        // set callback
        $this->_response_headers = '';
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, '_headerCallback'));
        
        // set https
        if (0 == strcasecmp('https://', substr($url, 0, 8)))
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    
        }
        
        // set headers
        if (!is_array($headers))
        {
            $headers = array();
        }
        if (!empty($this->_default_user_agent))
        {
            $has_user_agent = false;
            foreach ($headers as $line)
            {
                $row = explode(':', $line);
                $name = trim($row[0]);
                if (strcasecmp($name, 'User-Agent') == 0)
                {
                    $has_user_agent = true;
                    break;
                }
            }
            if (!$has_user_agent)
            {
                $headers[] = "User-Agent: " . $this->_default_user_agent;
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // set post
        if ($method == 'POST')
        {

            curl_setopt($ch, CURLOPT_POST, 1);
            if (!empty($post_data) || !empty($post_files))
            {
                $post = array();
                if (!empty($post_files)) {
                    foreach ($post_files as $name => $file_path) {
                        if (is_file($file_path)) {
                            $post[$name] = "@{$file_path}";    
                        }
                    }
                    if (!is_array($post_data)) {
                        $tmp_post_data_list = implode('&', $post_data);
                        $post_data = array();
                        foreach ($tmp_post_data_list as $line) {
                            $item = explode('=', $line);
                            $name = $item[0];
                            $value = isset($item[1]) ? rawurldecode($item[1]) : '';
                            $post[$name] = $value;
                        }
                    }
                }
                else {
                    $post = $post_data;
                }
                
                if (!empty($post)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                }
            }
        }
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];
        $errno = 0;
        $errmsg = '';
        $errno = curl_errno($ch);
            $errmsg = curl_error($ch);
        
        if (false === $response) {
            $errno = curl_errno($ch);
            $errmsg = curl_error($ch);
        }
        curl_close($ch);
        
        if ($errno != 0) {
            throw new \Exception("Http Request Wrong: {$errno} - {$errmsg}");
        }
        
        $result = array(
            'http_code' => $http_code,
            'errno' => $errno,
            'errmsg' => $errmsg,
            'headers' => $this->_response_headers,
            'response' => $response,
        );
        
        return $result;
    }	
    /**
     * @param $path：接口名
     * @param $url_params: get请求需要放进参数中的参数
     * @param $rtick：随机生成，标识当前请求
     * @param $post_md5：post请求时，body的md5值
     * @return string
     */
    private function _genSignData($path, $url_params, $rtick, $post_md5)
    {
        $request_path = parse_url($this->_host . $path)['path'];

        $url_params['developerId'] = $this -> _developerId;
        $url_params['rtick'] = $rtick;
        $url_params['signType'] = 'rsa';

        ksort($url_params);

        $sign_data = '';
        foreach ($url_params as $key => $value)
        {
            $sign_data = $sign_data . $key . '=' . $value;
        }
        $sign_data = $sign_data . $request_path;

        if (null != $post_md5)
        {
            $sign_data = $sign_data . $post_md5;
        }
        return $sign_data;
    }

    private function _getRequestUrl($path, $url_params, $sign, $rtick)
    {
        $url = $this->_host .$path . '?';

        //url
        $url_params['sign'] = $sign;
        $url_params['developerId'] = $this -> _developerId;
        $url_params['rtick'] = $rtick;
        $url_params['signType'] = 'rsa';

        foreach ($url_params as $key => $value)
        {
            $value = urlencode($value);
            $url = $url . $key . '=' . $value . '&';
        }

        $url = substr($url, 0, -1);
        return $url;
    }

    private function _formatPem($rsa_pem, $pem_type = '')
    {
        //如果是文件, 返回内容
        if (is_file($rsa_pem))
        {
            return file_get_contents($rsa_pem);
        }

        //如果是完整的证书文件内容, 直接返回
        $rsa_pem = trim($rsa_pem);
        $lines = explode("\n", $rsa_pem);
        if (count($lines) > 1)
        {
            return $rsa_pem;
        }

        //只有证书内容, 需要格式化成证书格式
        $pem = '';
        for ($i = 0; $i < strlen($rsa_pem); $i++)
        {
            $ch = substr($rsa_pem, $i, 1);
            $pem .= $ch;
            if (($i + 1) % 64 == 0)
            {
                $pem .= "\n";
            }
        }
        $pem = trim($pem);
        if (0 == strcasecmp('RSA', $pem_type))
        {
            $pem = "-----BEGIN RSA PRIVATE KEY-----\n{$pem}\n-----END RSA PRIVATE KEY-----\n";
        }
        else
        {
            $pem = "-----BEGIN PRIVATE KEY-----\n{$pem}\n-----END PRIVATE KEY-----\n";
        }
        return $pem;
    }
    /**
     * 获取签名串
     * @param $args
     * @return
     */
    public function getRsaSign()
    {
        $pkeyid = openssl_pkey_get_private($this->_pem);
        if (!$pkeyid)
        {
            throw new \Exception("openssl_pkey_get_private wrong!", -1);
        }

        if (func_num_args() == 0) {
            throw new \Exception('no args');
        }
        $sign_data = func_get_args();
        $sign_data = trim(implode("\n", $sign_data));

        openssl_sign($sign_data, $sign, $this->_pem);
        openssl_free_key($pkeyid);
        return base64_encode($sign);
    }
    //执行请求
    public function execute($method, $url, $request_body = null, array $header_data = array(), $auto_redirect = true, $cookie_file = null)
    {
        $response = $this->request($method, $url, $request_body, $header_data, $auto_redirect, $cookie_file);

        $http_code = $response['http_code'];
        if ($http_code != 200)
        {
            throw new \Exception("Request err, code: " . $http_code . "\nmsg: " . $response['response'] );
        }

        return $response['response'];
    }

    public function request($method, $url, $post_data = null, array $header_data = array(), $auto_redirect = true, $cookie_file = null)
    {
        $headers = array();
        $headers[] = 'Content-Type: application/json; charset=UTF-8';
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Pragma: no-cache';
        $headers[] = 'Connection: keep-alive';

        foreach ($header_data as $name => $value)
        {
            $line = $name . ': ' . rawurlencode($value);
            $headers[] = $line;
        }

        if (strcasecmp('POST', $method) == 0)
        {
            $ret = $this->post($url, $post_data, null, $headers, $auto_redirect, $cookie_file);
        }
        else
        {
            $ret = $this->get($url, $headers, $auto_redirect, $cookie_file);
        }
        return $ret;
    }	
    //********************************************************************************
    // 接口
    //********************************************************************************
    public function regBaseUser($account, $mail, $mobile, $name, $userType, $credential=null, $applyCert='1')
    {

        $path = "/user/reg/";

        //post data
        $post_data['email'] = $mail;
        $post_data['mobile'] = $mobile;
        $post_data['name'] = $name;
        $post_data['userType'] = $userType;
        $post_data['account'] = $account;
        $post_data['credential'] = $credential;
        $post_data['applyCert'] = $applyCert;

		$response = $this->basePara($path, $post_data);
        return $response;
    }
	
	//基础jsonArr数据封装
    /**
     * @param $post_data: 请求的参数
     * @param $data_para：要处理的参数键值
     * @return string
     */	
    public function getjsonArr($post_data = array(), $data_param = '')
    {
		
		$post_data[$data_param] = '['.json_encode($post_data[$data_param]).']';		
        //$response = json_encode($post_data);
		$content = '';
		foreach($post_data as $k=>$v){
			if($k==$data_param){
				$content.='"'.$k.'":'.$v.',';
			}else{
				$content.='"'.$k.'":"'.$v.'",';
			}
		}
		$content = rtrim($content, ',');
		$response = '{'.$content.'}';
        return $response;
    }
	//基础数据封装
    /**
     * @param $path：接口名
     * @param $post_data: 请求的参数
     * @param $method： 请求方式POST或GET
	 * @param $jsonStr  是否是json格式
     * @return string
     */	
    public function basePara($path = '', $post_data = array(), $method = '', $jsonStr = false)
    {

        $path = $path;
		
		$url_params = $post_data;
		if(!$jsonStr){
			$post_data = json_encode($post_data);
		}
        //rtick
        $rtick = time().rand(1000, 9999);

        //header data
        $header_data = array();
		if(!$method){
			$method = 'POST';
			//sign data
			$sign_data = $this->_genSignData($path, null, $rtick, md5($post_data));

			//sign
			$sign = $this->getRsaSign($sign_data);

			$params['developerId'] = $this -> _developerId;
			$params['rtick'] = $rtick;
			$params['signType'] = 'rsa';
			$params['sign'] =$sign;

			//url
			$url = $this->_getRequestUrl($path, null, $sign, $rtick);
			//var_dump($url);
			//var_dump($post_data);die;
			$response = $this->execute($method, $url, $post_data, $header_data, true);
		}else{
			//sign
			$sign_data = $this->_genSignData($path, $url_params, $rtick, null);
			$sign = $this->getRsaSign($sign_data);

			$url = $this->_getRequestUrl($path, $url_params, $sign, $rtick);

			//content
			$response = $this->execute('GET', $url, null, $header_data, true);			
		}
        //content
        
        return $response;
    }
	//下载签名/公章
    public function downloadSignatureImage($account, $image_name)
    {
        $path = "/signatureImage/user/download/";

        $url_params['account'] = $account;
        $url_params['imageName'] = $image_name;

        //rtick
        $rtick = time() . rand(1000, 9999);

        //sign
        $sign_data = $this->_genSignData($path, $url_params, $rtick, null);
        $sign = $this->getRsaSign($sign_data);

        $url = $this->_getRequestUrl($path, $url_params, $sign, $rtick);

        //header data
        $header_data = array();

        //content
        $response = $this->execute('GET', $url, null, $header_data, true);

        return $response;
    }
	/**
	* 获取PDF的页数
	*/
	public function getPageTotal($path){
		// 打开文件
		if (!$fp = @fopen($path,"r")) {
		  $error = "打开文件{$path}失败";
		  return false;
		}
		else {
		  $max=0;
		  while(!feof($fp)) {
			$line = fgets($fp,255);
			if (preg_match('/\/Count [0-9]+/', $line, $matches)){
			  preg_match('/[0-9]+/',$matches[0], $matches2);
			  if ($max<$matches2[0]) $max=$matches2[0];
			}
		  }
		  fclose($fp);
		  // 返回页数
		  return $max;
		}
	}
	/**
	* 几个月后的时间戳
	*/
	public function getMonthTimes($month){
      return strtotime("+".$month." months");
	}

    /**
     * curlPost请求
     */
    public function curlPost($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data); //data为json串时  需要开启头部设置
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //设置头部信息
        //$headers = array('Content-Type:application/json; charset=utf-8','Content-Length: '.strlen($data));//data为二维数组，需要注释头部注释 为json串时开启头部配置
        //curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        //执行请求
        $output = curl_exec($ch);
        return $output;
    }
    /**
     * 转化短连接
     * long_url 长连接
     * time 到期时间戳
     */
    public function shortUrl($long_url, $times='0')
    {
        $path = "/notice/shorturl/create/";
        //post data
        $post_data['longUrl'] = $long_url;
        if($times!='0'){
            $post_data['expireTime'] = $times.'';
        }else{
            $post_data['expireTime'] = strtotime("+6 day").'';
        }
        $response = $this->basePara($path, $post_data);
        return $response;
    }
	//****************************************************************************************************
	// demo functions
	//****************************************************************************************************
    //得到短连接
    function getShortUrl()
    {
        $path = "/notice/shorturl/create/";
        //post data
        $post_data['longUrl'] = input('param.long_url');
        if(!empty(input('param.times'))){
            $post_data['expireTime'] = input('param.times').'';
        }else{
            $post_data['expireTime'] = strtotime("+6 day").'';
        }
        //var_dump($post_data);die;
        $response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//注册个人用户
	function regUser()
	{
		$mail = input('param.mail');
		$identity = input('param.identity');
        $account = input('param.account');
		if(empty($account)){
            $account = $identity;
        }
		$mobile = input('param.mobile');
		$name = input('param.name');
		$user_type = '1';   //个人
        $credential['identityType'] = input('param.identity_type');           //0身份证
		$credential['identity'] = $identity;
		$credential['contactMobile'] = $mobile;
		$credential['contactMail'] = $mail;
		$credential['province']= input('param.province');
		$credential['city'] = input('param.city');
		$credential['address'] = input('param.address');

		$applyCert = '1';

		$response = $this->regBaseUser($account, $mail, $mobile, $name, $user_type, $credential, $applyCert);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//注册企业用户
	function regUserWithCredential()
	{
        $mail = input('param.mail');
        $identity = input('param.identity');   //法人证件号
        $identity_type = input('param.identity_type');  //法人证件类型
        $account = input('param.account');
        if(empty($account)){
            $account = $identity;
        }
        $mobile = input('param.mobile');
        $name = input('param.name');      //企业名称
        $person_name = input('param.person_name');  //法人名称
        $user_type = "2";
        $credential['legalPerson'] = $person_name;   //法人名称
        $credential['legalPersonIdentity'] = $identity;
        $credential['legalPersonIdentityType'] = $identity_type;
        $credential['legalPersonMobile'] = $mobile;
        $credential['regCode'] = input('param.reg_code');
        $credential['orgCode'] = input('param.org_code');
        $credential['taxCode'] = input('param.tax_code');
        $credential['contactMobile'] = $mobile;
        $credential['contactMail'] = $mail;
        $credential['province']= input('param.province');
        $credential['city'] = input('param.city');
        $credential['address'] = input('param.address');

		$applyCert = '1';
		$response = $this->regBaseUser($account, $mail, $mobile, $name, $user_type, $credential, $applyCert);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}		
	//异步查询证书状态
	function checkTaskStatus()
	{
        $path = "/user/async/applyCert/status/";
        //post data
        $post_data['account'] = input('param.account');
		$post_data['taskId'] = input('param.task_id');

		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	
	//查询证书编号
	function getCert()
	{
        $path = "/user/getCert/";
        //post data
        $post_data['account'] = input('param.account');
        
		$response = $this->basePara($path, $post_data, 'POST');
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//查询个人用户证件信息
	function getPersonalCredential()
	{
        $path = "/user/getPersonalCredential/";
        //post data
		$post_data['account'] = input('param.account');
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}	
	//查询企业用户证件信息
	function getEnterpriseCredential()
	{
        $path = "/user/getEnterpriseCredential/";
        //post data
        $post_data['account'] = input('param.account');
        
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//获取证书详细信息
	function getCertInfo()
	{
        $path = "/user/cert/info/";
        //post data
        $post_data['account'] = input('param.account');
		$post_data['certId'] = input('param.cert_id');
        
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//上传企业公章
	function qyUpSignImage()
	{
		$img_base64 = '';
        $app_img_file = 'gzl.png';
        $app_img_file = input('param.img_path');   //绝对路径
		$img_info = getimagesize($app_img_file);//取得图片的大小，类型等
		$fp = fopen($app_img_file, "r");     //图片是否可读权限
		if ($fp) {
			$file_content = chunk_split(base64_encode(fread($fp, filesize($app_img_file))));//base64编码
			switch ($img_info[2]) {  //判读图片类型
				case 1:
					$img_type = "gif";
					break;
				case 2:
					$img_type = "jpg";
					break;
				case 3:
					$img_type = "png";
					break;
			}
		}
		$img_base64 = 'data:image/' . $img_type .';base64,' . $file_content;//合成图片的base64编码
		//var_dump($img_base64);
	    fclose($fp);		
        $path = "/signatureImage/user/upload/";
        //post data
        $post_data['account'] = input('param.account');//港中旅
		$post_data['imageData'] = $file_content;
		$post_data['imageName'] = $post_data['account'];
        
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//下载企业公章
	function qyDlSignImage()
	{		
        $path = "/signatureImage/user/download/";
        //post data
        $post_data['account'] = input('param.account');
		$post_data['imageName'] = $post_data['account'];
		$response = $this->basePara($path, $post_data, "GET");
		$fp = fopen ( $post_data['account'].'.png', 'w+' );//新建png文件
		if($fp){
			fwrite ( $fp, $response );  //二进制流写入文件
			fclose ( $fp );  
		}
        return $response;
	}
		//上传doc ，docx, pdf文件
	function upFile()
	{	
		//$filename = "cc.docx";
		$filename = input('param.file_name');
		$filepath = input('param.file_path');
		//$filepath = "http://101.201.70.35/test.pdf";   //远程文件
        $md5file = md5_file($filepath); //得到文件的md5
		$ftype = substr($filepath,strripos($filepath,".")+1);
		$fp = fopen($filepath, "r");     //文件是否可读权限
		if ($fp) {
			//$file_content = chunk_split(base64_encode(fread($fp, filesize($filepath))));//base64编码
			
			$file_content = chunk_split(base64_encode(file_get_contents($filepath)));   //远程文件得到base64编码
		}
		fclose ( $fp );
		$path = "/storage/upload/";
        //post data
        $post_data['account'] = input('param.account');
		$post_data['fdata'] = $file_content;
		$post_data['fmd5'] = $md5file;
		$post_data['ftype'] = $ftype;
		$post_data['fname'] = $filename;
		$post_data['fpages'] = 100;  //此处的页码数只要大于实际页码数就没问题
        
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);

	}
	//doc ，docx文件并转化为pdf
	function toPdf()
	{	
		$path = "/storage/convert/";
        //post data
		$post_data['fid'] = input('param.fid');
		$post_data['ftype'] = "PDF";
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//下载pdf
	function dolPdf()
	{
	    $download_path = input('param.download_path');
        if(empty($download_path)){
            $download_path = './pdf/';
        }
		$path = "/storage/download/";
        //post data
		$post_data['fid'] = input('param.fid');
		$file_type = ".pdf";
		$response = $this->basePara($path, $post_data, "GET");
		$fp = fopen ( $download_path.$post_data['fid'].$file_type, 'w+' );//新建文件
		if($fp){
			fwrite ( $fp, $response );  //二进制流写入文件
			fclose ( $fp );
			return 1;
		}
        return 0;

	}
	//得到pdf的页码
	function ss()
	{
		$filepath = input('param.pdf_path');
		$response = $this->getPageTotal($filepath);
        return $response;		

	}
	//创建单文件合同
	function createContract()
	{	
		$path = "/contract/create/";
        //post data
        $post_data['account'] = input('param.account');
		$post_data['fid'] = input('param.fid');
		$post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
		$post_data['title'] = input('param.title');
		$post_data['description'] = "";
		$post_data['hotStoragePeriod'] = "31536000";
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
	}
	//得到单文件合同预览网址
	function getContractView()
	{	
		$path = "/contract/getPreviewURL/";
        //post data
        $post_data['contractId'] = input('param.contract_id');
		$post_data['account'] = input('param.account');
		$post_data['dpi'] = '160';
		$post_data['expireTime'] = '0';  //1个月后的时间戳
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);

	}
	//自动签署单文件合同(旅行社)
	function signContract()
	{	
		$path = "/storage/contract/sign/cert/";
        //post data
		$fid = input('param.fid');  //合同对应的合同文件
		$filepath = './pdf/'.$fid.'.pdf';
		$pagenum = $this->getPageTotal($filepath);
		$arr = array();
	    $arr['pageNum'] = '1';
        $arr['x'] = '0.15';
        $arr['y'] = '0.15';
		$arr['rptPageNums'] = '0';
        $post_data['contractId'] = input('param.contract_id');
		$post_data['signer'] = input('param.account');
		$post_data['signatureImageName'] = $post_data['signer'];
		$post_data['signaturePositions'] = $arr; 
		$jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式

		$response = $this->basePara($path, $jsonStr, '', true);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);

	}

	//得到合同签署者状态
    function getSignerStatus()
    {
        $path = "/contract/getSignerStatus/";

        $url_params['contractId'] = input('param.contract_id');
		$response = $this->basePara($path, $url_params);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//撤销单文件合同
    function cancelContract()
    {
        $path = "/contract/cancel/";

        $url_params['contractId'] = input('param.contract_id');
		$response = $this->basePara($path, $url_params);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//锁定并结束单文件合同
    function lockContract()
    {
        $path = "/storage/contract/lock/";

        $url_params['contractId'] = input('param.contract_id');
		$response = $this->basePara($path, $url_params);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }	
	//下载单文件合同
    function downloadContract()
    {
        $path = "/storage/contract/download/";
        $pdf_path = './pdf/';
        if(!empty(input('param.pdf_path'))){
            $pdf_path = input('param.pdf_path');
        }
        $url_params['contractId'] = input('param.contract_id');
		$response = $this->basePara($path, $url_params, 'GET');
		file_put_contents($pdf_path.$url_params['contractId'].".pdf",$response);
        return $response;
    }
	//创建合同目录
    function createCatalog()
    {
		$path = "/catalog/create/";
        //post data
        $post_data['senderAccount'] = input('param.account');
		$post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
		$post_data['catalogName'] = input('param.contract_id');
		$post_data['description'] = "";
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//合同目录添加合同文件
    function addContract()
    {
		$path = "/catalog/uploadContract/";
        //post data
        $post_data['senderAccount'] = input('param.account');
		$post_data['catalogName'] = input('param.contract_id');   //合同目录唯一标识
		$post_data['fid'] = input('param.fid');       //6663789385475722304   8082542010827255142
		$post_data['title'] = input('param.title');
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//得到合同目录的合同列表
    function getContracts()
    {
		$path = "/catalog/getContracts/";
        //post data
		$post_data['catalogName'] = input('param.contract_id');   //合同目录唯一标识
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);
    }
	//自动签署多文件合同(旅行社)
	function signCatalog()
	{	
		$orderPath = "/catalog/getContracts/";
        //post data
		$order_post_data['catalogName'] = input('param.contract_id');   //合同目录唯一标识
		$res = $this->basePara($orderPath, $order_post_data);
		$arrs = json_decode($res,true);
		$path = "/contract/sign/cert/";
        //post data

	    $arr['pageNum'] = '1';
        $arr['x'] = '0.15';
        $arr['y'] = '0.15';
		$arr['rptPageNums'] = '0';
		foreach($arrs['data']['contracts'] as $k=>$v){
			$post_data['contractId'] = $v['contractId'];
			$post_data['signerAccount'] = input('param.account');
			$post_data['signatureImageName'] = $post_data['signerAccount'];
			$post_data['signaturePositions'] = $arr; 
			$jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
			$response = $this->basePara($path, $jsonStr, '', true);
            $arrs = json_decode($response,true);
            $res['response'] = $arrs;
            $res['data'] = '';
            $res['msg'] = $arrs['errmsg'];
            $res['type'] = $arrs['errno'];
			$arrss[] = $res;
		}
        return json($arrss);
	}

	//得到多文件合同预览网址
	function getCatalogView()
	{	
		$path = "/catalog/getPreviewURL/";
        //post data
        $post_data['catalogName'] = input('param.contract_id');
		$post_data['signerAccount'] = input('param.account');
		$post_data['dpi'] = '160';
		$post_data['expireTime'] = '0';  //1个月后的时间戳
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);

	}
	//结束多文件合同
	function lockCatalog()
	{	
		$path = "/catalog/lock/";
        //post data
        $post_data['catalogName'] = input('param.contract_id');
		$response = $this->basePara($path, $post_data);
        $arrs = json_decode($response,true);
        $res['response'] = $arrs;
        $res['data'] = '';
        $res['msg'] = $arrs['errmsg'];
        $res['type'] = $arrs['errno'];
        return json($res);

	}
    //上传doc ，docx, pdf并转为pdf
    function upFileToPdf($file_name, $file_path, $account)
    {
        $filename = $file_name;   //'aa.doc','aa.pdf'
        $filepath = $file_path;   //'http://xxx.com/cc.doc'
        $md5file = md5_file($filepath); //得到文件的md5
        $ftype = substr($filepath,strripos($filepath,".")+1);
        $fp = fopen($filepath, "r");     //文件是否可读权限
        if ($fp) {
            $file_content = chunk_split(base64_encode(file_get_contents($filepath)));   //远程文件得到base64编码
        }
        fclose ( $fp );
        $path = "/storage/upload/";
        //post data
        $post_data['account'] = $account;
        $post_data['fdata'] = $file_content;
        $post_data['fmd5'] = $md5file;
        $post_data['ftype'] = $ftype;
        $post_data['fname'] = $filename;
        $post_data['fpages'] = 999;  //此处的页码数只要大于实际页码数就没问题
        $response = $this->basePara($path, $post_data);
        $resArr = json_decode($response,true);
        if($resArr['errno']==0&&$ftype!='pdf'){//上传doc成功并且文件类型不是pdf
            $path_two = "/storage/convert/";
            //post data
            $post_data_two['account'] = $account;
            $post_data_two['fid'] = $resArr['data']['fid'];
            $post_data_two['ftype'] = "PDF";
            $res = $this->basePara($path_two, $post_data_two);
            return $res;
        }

        return $response;

    }
    //手动更新状态
    function changeStatus()
    {
        if(empty(input('param.c_number'))){
            $res['type'] = '0';
            $res['code'] = '10001';
            $res['data'] = input('param');
            $res['msg'] = '参数缺失';
            return json($res);
        }
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        $dataInfo = ContractMode::where($where)->find();
        if(empty($dataObj)){
            $res['type'] = '0';
            $res['code'] = '10002';
            $res['data'] = $dataObj;
            $res['msg'] = '找不到数据';
            return json($res);
        }
        $boolReg = Cache::get('reg'.$dataObj->c_number);  //注册用户
        $boolUp = Cache::get('up'.$dataObj->c_number);    //上传文件
        $boolCreate = Cache::get('create'.$dataObj->c_number);    //生成合同
        $boolSign = Cache::get('sign'.$dataObj->c_number);    //自动签署
        if($dataObj->is_reg_user==0){//注册用户
            if(empty($boolReg)){
                Cache::set('reg'.$dataObj->c_number,'1');
                $mail = $dataObj->user_info->mail;
                $identity = $dataObj->user_info->identity;
                $account = $identity;
                $mobile = $dataObj->user_info->mobile;
                $name = $dataObj->user_info->name;
                $user_type = "1";

                $credential['identity'] = $identity;
                $credential['identityType'] = $dataObj->account_type;
                $credential['contactMobile'] = '';
                $credential['contactMail'] = $mail;
                $credential['province']= '';
                $credential['city'] = '';
                $credential['address'] = '';
                $applyCert = '1';
                $response = $this->regBaseUser($account, $mail, $mobile, $name, $user_type, $credential, $applyCert);
                Cache::rm('reg'.$dataObj->c_number);
                $resArr = json_decode($response,true);
                $res['type'] = '0';
                $res['msg'] = '注册用户失败,请重试';
                if($resArr['errno']==0){//用户注册成功
                    $res['type'] = '1';
                    $res['msg'] ='注册用户成功';
                    $dataObj->is_reg_user = 1;
                    $dataObj->user_account = $identity;
                    $dataObj->save();
                }
                $res['data'] = $resArr;
                $res['code'] = $resArr['cost'];
                return json($res);
            }
        }

        if($dataObj->is_upload==0){//上传文件
            if(empty($boolUp)){
                Cache::set('up'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '上传文件失败,请重试';
                if($dataObj->ssq_fid_one=='0'){
                    $base_dir = './pdf';
                    $shell = 'wkhtmltopdf '.$this->_contract_host.$this->_contract_path.$dataObj->c_number.' '.$base_dir.'/'.$dataObj->c_number.'.pdf';
                    system($shell, $status);//本地生成合同主体pdf
                    if($status){ //执行失败
                        Cache::rm('up'.$dataObj->c_number);
                        $res['type'] = '0';
                        $res['data'] = $shell;
                        $res['code'] = '10003';
                        $res['msg'] = '生成合同主体文件失败';
                        return json($res);
                    }
                    //上传合同主体文件开始
                    $file_name = $dataObj->c_number.'.pdf';
                    $file_path = $this->_contract_pdf_path.$file_name;
                    $file_res = $this->upFileToPdf($file_name,$file_path,$dataObj->unit_account);  //上传合同主体文件
                    $file_res_arr = json_decode($file_res,true);

                    if($file_res_arr['errno']==0){ //上传成功
                        $dataObj->ssq_fid_one = $file_res_arr['data']['fid'];
                        $dataObj->is_upload = $dataObj->type;     //自由合同type 值为1
                        $dataObj->save();
                        $res['type'] = '1';
                        $res['msg'] = '上传文件成功';
                    }
                    //上传合同主体文件结束
                }

                if($dataObj->type==0&&$dataObj->ssq_fid_two=='0'){//订单合同 即多文件合同
                    //上传合同行程文件开始
                    $file_name = substr($dataObj->line_file_path,strripos($dataObj->line_file_path,"/")+1);
                    $file_path = $dataObj->line_file_path;
                    $file_res = $this->upFileToPdf($file_name,$file_path,$dataObj->unit_account);  //上传合同主体文件
                    $file_res_arr = json_decode($file_res,true);

                    if($file_res_arr['errno']==0){ //上传成功
                        $dataObj->ssq_fid_two = $file_res_arr['data']['fid'];
                        $dataObj->is_upload = 1;
                        $dataObj->save();
                        $res['type'] = '1';
                        $res['msg'] = '上传文件成功';
                    }
                    //上传合同行程文件结束
                }
                Cache::rm('up'.$dataObj->c_number);
                $res['code'] = '10004';
                $res['data'] = $file_res;
                return json($res);
            }
        }

        if($dataObj->is_upload==1 && $dataObj->is_creat==0){//生成合同
            if(empty($boolCreate)){
                Cache::set('create'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '生成合同失败,请重试';
                if($dataObj->type==0){//订单合同 即多文件合同
                    $path = "/catalog/create/";
                    //post data
                    $post_data['senderAccount'] = $dataObj->unit_account;
                    $post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
                    $post_data['catalogName'] = $dataObj->c_number;
                    $post_data['description'] = "";
                    $res_create = $this->basePara($path, $post_data);
                    $create_arr = json_decode($res_create,true);

                    if($create_arr['errno']==0||$create_arr['errno']==242008){ //生成目录成功
                        $path = "/catalog/uploadContract/";
                        //post data
                        $post_data_add['senderAccount'] = $dataObj->unit_account;
                        $post_data_add['catalogName'] = $dataObj->c_number;   //合同目录唯一标识
                        $post_data_add['fid'] = $dataObj->ssq_fid_one;
                        $post_data_add['title'] = "合同主体";
                        $file_add_one = $this->basePara($path, $post_data_add);
                        $post_data_add['fid'] = $dataObj->ssq_fid_two;
                        $post_data_add['title'] = "合同行程单";
                        $file_add_two = $this->basePara($path, $post_data_add);
                        $create_arr_one = json_decode($file_add_one,true);
                        $create_arr_two = json_decode($file_add_two,true);
                        if(($create_arr_one['errno']==0||$create_arr_one['errno']==242008)&&($create_arr_two['errno']==0||$create_arr_two['errno']==242008)){ //生成成功
                            $dataObj->is_creat = 1;
                            $dataObj->contract_id = $dataObj->c_number;
                            $dataObj->save();
                            $res['type'] = '1';
                            $res['msg'] = '生成合同成功';
                        }
                    }
                }else{
                    //自由合同开始
                    $path = "/contract/create/";
                    //post data
                    $post_data['account'] = $dataObj->unit_account;
                    $post_data['fid'] = $dataObj->ssq_fid_one;
                    $post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
                    $post_data['title'] = "合同主体";
                    $post_data['description'] = "";
                    $post_data['hotStoragePeriod'] = "31536000";
                    $res_create = $this->basePara($path, $post_data);
                    $create_arr = json_decode($res_create,true);
                    //自由合同结束
                    if($create_arr['errno']==0){ //生成成功
                        $dataObj->is_creat = 1;
                        $dataObj->contract_id = $create_arr['data']['contractId'];
                        $dataObj->save();
                        $res['type'] = '1';
                        $res['msg'] = '生成合同成功';
                    }
                }
                Cache::rm('create'.$dataObj->c_number);
                $res['code'] = '10005';
                $res['data'] = $res_create;
                return json($res);
            }
        }

        if($dataObj->is_creat==1 && $dataObj->is_sign==0) {//自动签署
            if(empty($boolSign)){
                Cache::set('sign'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '自动盖章失败,请重试';
                if($dataObj->type==0){//订单合同 即多文件合同
                    $orderPath = "/catalog/getContracts/";
                    //post data
                    $order_post_data['catalogName'] = $dataObj->c_number;   //合同目录唯一标识
                    $res_list = $this->basePara($orderPath, $order_post_data);
                    $arrs = json_decode($res_list,true);
                    $path = "/contract/sign/cert/";
                    //post data

                    $arr['pageNum'] = '1';
                    $arr['x'] = '0.15';
                    $arr['y'] = '0.12';
                    $arr['rptPageNums'] = '0';
                    $res_sign_bool = 1;
                    foreach($arrs['data']['contracts'] as $k=>$v){
                        $post_data['contractId'] = $v['contractId'];
                        $post_data['signerAccount'] = $dataObj->unit_account;
                        $post_data['signatureImageName'] = $post_data['signerAccount'];
                        $post_data['signaturePositions'] = $arr;
                        $jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
                        $reso_sign = $this->basePara($path, $jsonStr, '', true);
                        $arrss[] = json_decode($reso_sign,true);
                        $res_sign = json_encode($arrss);
                        if(!($arrss[$k]['errno']==0||$arrss[$k]['errno']==241424)){
                            $res_sign_bool = $res_sign_bool*0;
                        }
                    }
                    if($res_sign_bool==1){ //盖章成功
                        $dataObj->is_sign = 1;
                        $dataObj->save();
                        //更新合同状态
                        $dataInfo->is_create = 1;
                        $dataInfo->tra_status = 1;
                        $dataInfo->contract_status = 4;
                        $dataInfo->save();
                        $res['type'] = '1';
                        $res['msg'] = '自动盖章成功';
                    }
                }else{
                    //自由合同开始
                    $path = "/storage/contract/sign/cert/";
                    //post data
                    $arr = array();
                    $arr['pageNum'] = '1';
                    $arr['x'] = '0.15';
                    $arr['y'] = '0.12';
                    $arr['rptPageNums'] = '0';
                    $post_data['contractId'] = $dataObj->contract_id;
                    $post_data['signer'] = $dataObj->unit_account;
                    $post_data['signatureImageName'] = $post_data['signer'];
                    $post_data['signaturePositions'] = $arr;
                    $jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
                    $res_sign = $this->basePara($path, $jsonStr, '', true);
                    $sign_arr = json_decode($res_sign,true);
                    //自由合同结束
                    if($sign_arr['errno']==0||$sign_arr['errno']==241424){ //盖章成功
                        $dataObj->is_sign = 1;
                        $dataObj->save();
                        //更新合同状态
                        $dataInfo->is_create = 1;
                        $dataInfo->tra_status = 1;
                        $dataInfo->contract_status = 4;
                        $dataInfo->save();
                        $res['type'] = '1';
                        $res['msg'] = '自动盖章成功';
                    }
                }

                Cache::rm('sign'.$dataObj->c_number);
                $res['code'] = '10006';
                $res['data'] = $res_sign;
                return json($res);
            }
        }
        if($dataObj->is_sign==1 && ($dataInfo->contract_status==4 || $dataObj->is_sign_two==0)) {//更新游客签署状态
            if($dataInfo->contract_status==4 && $dataObj->is_sign_two==1){
                //更新合同状态
                $dataInfo->user_status = 1;
                $dataInfo->contract_status = 5;
                $dataInfo->save();
                $res['type'] = '1';
                $res['msg'] = '更新成功';
                return json($res);
            }
            //队列状态未改变
            if($dataObj->type==0) {//订单合同 即多文件合同
                $orderPath = "/catalog/getContracts/";
                //post data
                $order_post_data['catalogName'] = $dataObj->c_number;   //合同目录唯一标识
                $res_list = $this->basePara($orderPath, $order_post_data);
                $arrs = json_decode($res_list,true);
                $path = "/contract/getSignerStatus/";
                //post data
                $res_sign_bool = 0;
                foreach($arrs['data']['contracts'] as $k=>$v){
                    $post_data['contractId'] = $v['contractId'];
                    $res_create = $this->basePara($path, $post_data);
                    $create_arr = json_decode($res_create,true);
                    $das[] = $create_arr['data'];
                    $rss[] = $create_arr;
                    if($create_arr['errno']==0){
                        if($create_arr['data'][$dataObj->user_account]=='2'){
                            $res_sign_bool = 1;
                        }
                    }
                }
                $res['data'] = $das;
                $res['res'] = $rss;
                if($res_sign_bool==1){ //更新成功
                    $dataObj->is_sign_two = 1;
                    $dataObj->save();
                    //更新合同状态
                    $dataInfo->user_status = 1;
                    $dataInfo->contract_status = 5;
                    $dataInfo->save();
                    $res['type'] = '1';
                    $res['msg'] = '更新成功';
                }else{
                    $res['type'] = '0';
                    $res['code'] = '10007';
                    $res['msg'] = '更新失败，请重试';
                }
                return json($res);
            }else{//自由合同
                $path = "/contract/getSignerStatus/";
                //post data
                $post_data['contractId'] = $dataObj->contract_id;
                $res_create = $this->basePara($path, $post_data);
                $create_arr = json_decode($res_create,true);
                //自由合同结束
                $res['res'] = $create_arr;
                $res['data'] = $create_arr['data'];
                if($create_arr['errno']==0){ //请求成功
                    if($create_arr['data'][$dataObj->user_account]=='2'){
                        $dataObj->is_sign_two = 1;
                        $dataObj->save();
                        //更新合同状态
                        $dataInfo->user_status = 1;
                        $dataInfo->contract_status = 5;
                        $dataInfo->save();
                        $res['type'] = '1';
                        $res['msg'] = '更新成功';
                    }else{
                        $res['type'] = '0';
                        $res['code'] = '10007';
                        $res['msg'] = '更新失败，请重试';
                    }
                    return json($res);
                }
            }
        }
        $res['type'] = '0';
        $res['code'] = '10000';
        $res['data'] = '';
        $res['msg'] = '任务正在处理中，请稍后';
        return json($res);

    }

    //手动签署单文件合同(游客)
    function sendContract()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();

        $path = "/contract/send/";
        //post data
        $fid = $dataObj->c_number;  //合同对应的合同文件
        $filepath = './pdf/'.$fid.'.pdf';
        $pagenum = $this->getPageTotal($filepath);
        $arr['pageNum'] = $pagenum;
        if(!$pagenum){
            $arr['rptPageNums'] = '0';
            $arr['pageNum'] = '1';
        }
        $arr['x'] = '0.65';
        $arr['y'] = '0.12';
        $post_data['contractId'] = $dataObj->contract_id;
        $post_data['signer'] = $dataObj->user_account;
        $post_data['returnUrl'] = $this->_contract_reshow_host.'.html?c_number='.$dataObj->c_number;
        $post_data['dpi'] = '240';
        $post_data['isAllowChangeSignaturePosition'] = '1';
        $post_data['vcodeMobile'] = '';      		 //手写签名收验证码手机号，可不填即不收取验证码
        $post_data['isDrawSignatureImage'] = '1';    //1点击签名图片能触发手写面板 2强制必须手绘签名
        $post_data['sid'] = $dataObj->c_number;    					 //平台流水号
        $post_data['pushUrl'] = $this->_contract_reback_host;    				 //平台接收回调地址，不填选默认
        $post_data['signaturePositions'] = $arr;
        $jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
        $response = $this->basePara($path, $jsonStr, '', true);
        $arrs = json_decode($response,true);
        $res['file_page'] = $pagenum;
        $res['type'] = '0';
        $res['res'] = $response;
        $res['msg'] = '请求失败，请重试';
        if($arrs['errno']==0){
            $url_res = $this->shortUrl($arrs['data']['url']);
            $url_arrs = json_decode($url_res,true);
            if($url_arrs['errno']==0){
                $res['type'] = '1';
                $res['msg'] = '';
                $data['pic'] ='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.$url_arrs['data']['shortUrl'];
                $data['pic'] ='http://qr.liantu.com/api.php?w=180&text='.$url_arrs['data']['shortUrl'];
                $data['url'] = $url_arrs['data']['shortUrl'];
                $res['data'] = $data;
            }
            $res['url_res'] = $url_arrs;
        }
        return json($res);

    }
    //手动签署多文件合同(游客)
    function sendCatalog()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        $path = "/catalog/send/";
        $fid = $dataObj->c_number;  //合同对应的合同文件
        $filepath = './pdf/'.$fid.'.pdf';
        $pagenum = $this->getPageTotal($filepath);
        $arr['pageNum'] = $pagenum;
        if(!$pagenum){
            $arr['rptPageNums'] = '0';
            $arr['pageNum'] = '1';
        }
		$arr['pageNum'] = '1';
        $arr['x'] = '0.65';
        $arr['y'] = '0.12';
        $post_data['catalogName'] = $dataObj->contract_id;
        $post_data['signerAccount'] = $dataObj->user_account;
        $post_data['dpi'] = '240';
        $post_data['returnUrl'] = $this->_contract_reshow_host.'.html?c_number='.$dataObj->c_number;
        $post_data['vcodeMobile'] = '';      		 //手写签名收验证码手机号，可不填即不收取验证码
        $post_data['isDrawSignatureImage'] = '1';    //1点击签名图片能触发手写面板 2强制必须手绘签名
        $post_data['contractParams']['合同主体']['signaturePositions'] = '-';
        $post_data['contractParams']['合同行程单']['signaturePositions'] = '|';
        $temp = '['.json_encode($arr).']';
        $str = json_encode($post_data);
        $str = str_replace('"-"',$temp,$str);
        $str = str_replace('"|"',$temp,$str);
        $jsonStr = $str;  //提前处理为jsonArr格式
        $response = $this->basePara($path, $jsonStr, '', true);
        $arrs = json_decode($response,true);
        $res['file_page'] = $pagenum;
        $res['type'] = '0';
		$res['jsonStr'] = $jsonStr;
        $res['res'] = $response;
        $res['msg'] = '请求失败，请重试';
        if($arrs['errno']==0){
            $url_res = $this->shortUrl($arrs['data']['url']);
            $url_arrs = json_decode($url_res,true);
            if($url_arrs['errno']==0){
                $res['type'] = '1';
                $res['msg'] = '';
                $data['pic'] ='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.$url_arrs['data']['shortUrl'];
                $data['pic'] ='http://qr.liantu.com/api.php?w=180&text='.$url_arrs['data']['shortUrl'];
                $data['url'] = $url_arrs['data']['shortUrl'];
                $res['data'] = $data;
            }
            $res['url_res'] = $url_arrs;
        }
        return json($res);
    }
    //手签回调更新状态
    function reback()
    {
        return 1;
    }
    //得到合同预览网址
    function getShowUrl()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        if($dataObj->type==0){//订单合同
            $path = "/catalog/getPreviewURL/";
            //post data
            $post_data['catalogName'] = $dataObj->contract_id;
            $post_data['signerAccount'] = $dataObj->unit_account;
            $post_data['dpi'] = '240';
            $post_data['expireTime'] = '0';  //1个月后的时间戳
            $response = $this->basePara($path, $post_data);
        }else{
            $path = "/contract/getPreviewURL/";
            //post data
            $post_data['contractId'] = $dataObj->contract_id;
            $post_data['account'] = $dataObj->unit_account;
            $post_data['dpi'] = '240';
            $post_data['expireTime'] = '0';  //1个月后的时间戳
            $response = $this->basePara($path, $post_data);
        }
        $arrs = json_decode($response,true);
        $res['type'] = '0';
        $res['res'] = $response;
        $res['msg'] = '请求失败，请重试';
        if($arrs['errno']==0){
            $url_res = $this->shortUrl($arrs['data']['url']);
            $url_arrs = json_decode($url_res,true);
            if($url_arrs['errno']==0){
                $res['type'] = '1';
                $res['msg'] = '';
                $data['pic'] ='https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='.$url_arrs['data']['shortUrl'];
                $data['pic'] ='http://qr.liantu.com/api.php?w=180&text='.$url_arrs['data']['shortUrl'];
                $data['url'] = $url_arrs['data']['shortUrl'];
                $res['data'] = $data;
            }
            $res['url_res'] = $url_arrs;
        }
        return json($res);
    }
    //更改用户
    function editUsers()
    {
        if(empty(input('param.c_number'))||empty(input('param.mail'))||empty(input('param.identity'))||empty(input('param.mobile'))||empty(input('param.name'))){
            $res['type'] = '0';
            $res['code'] = '90000';
            $res['data'] = '';
            $res['msg'] = '缺少必要的参数';
            return json($res);
        }
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        $dataInfo = ContractMode::where($where)->find();
        if(empty(input('param.account_type'))){
            $account_type = '0';
        }else{
            $account_type = input('param.account_type');
        }
        if(empty($dataObj)){
            $res['type'] = '0';
            $res['code'] = '10002';
            $res['data'] = '';
            $res['msg'] = '找不到合同数据';
            return json($res);
        }
        if($dataObj->is_reg_user==1){
            $res['type'] = '0';
            $res['code'] = '10002';
            $res['data'] = '';
            $res['msg'] = '当前状态不能修改用户信息，请重做合同';
            return json($res);
        }
        $dataObj->user_info->mail = input('param.mail');
        $dataObj->user_info->identity = input('param.identity');
        $dataObj->user_info->mobile = input('param.mobile');
        $dataObj->user_info->name = input('param.name');
        $dataObj->account_type = $account_type;
        $dataObj->is_deal = 0;
        $dataObj->user_account = $dataObj->user_info->identity;
        $res_q = $dataObj->save();

        $dataInfo->info->loops1 = $dataObj->user_info->name;
        $dataInfo->info->loops4 = $dataObj->user_info->identity;
        $dataInfo->info->loops5 = $dataObj->user_info->mobile;
        $dataInfo->info->loops3 = $dataObj->user_info->mail;
        $dataInfo->account_type = $dataObj->account_type;
        $res_c = $dataInfo->save();
        $res['res_c'] = $res_c;
        $res['res_q'] = $res_q;
        if($res_q&&$res_c){
            $res['msg'] = '更新成功';
        }else{
            $res['msg'] = '更新失败，请重试';
        }
        return json($res);
    }
    //得到当前合同状态
    function getStatus()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        $res['type'] = '1';
        $res['msg'] = '';
        $data['is_reg_user'] =$dataObj->is_reg_user;
        $data['is_upload'] =$dataObj->is_upload;
        $data['is_creat'] =$dataObj->is_creat;
        $data['is_sign'] =$dataObj->is_sign;
        $data['is_sign_two'] =$dataObj->is_sign_two;
        $data['is_lock'] =$dataObj->is_lock;
        $data['is_cancel'] =$dataObj->is_cancel;
        $res['data'] = $data;
        return json($res);
    }
	
    //下载合同
    function downloadsUrl()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        if($dataObj->type==0){//订单合同
            $path = "/catalog/getContracts/";  //得到合同列表
            //post data
            $post_data['catalogName'] = $dataObj->contract_id;   //合同目录唯一标识
            $response = $this->basePara($path, $post_data);
            $arrs = json_decode($response,true);
            if($arrs['errno']==0){
                $num = 0;
                foreach ($arrs['data']['contracts'] as $k=>$v){
                    $path = "/storage/contract/download/";
                    $url_params['contractId'] = $v['contractId'];
                    $response = $this->basePara($path, $url_params, 'GET');
                    $file_path = './pdf/';
                    $file_name = 'd_'.$dataObj->c_number.'_'.$num.'.pdf';
                    file_put_contents($file_path.$file_name,$response);
                    $num+=1;
                    $url[] = $this->_contract_pdf_path.$file_name;
                    $title[] = $k;
                }
            }
        }else{
            $path = "/storage/contract/download/";
            $url_params['contractId'] = $dataObj->contract_id;
            $response = $this->basePara($path, $url_params, 'GET');
            $file_path = './pdf/';
            $file_name = 'd_'.$dataObj->c_number.'_1.pdf';
            file_put_contents($file_path.$file_name,$response);
            $url[] = $this->_contract_pdf_path.$file_name;
            $title[] = '合同主体';
        }
        $res['type'] = '1';
        $res['msg'] = '';
        $data['url'] = $url;
        $data['title'] = $title;
        $res['data'] = $data;
        return json($res);
    }
	
    //签约成功更新状态并跳转
    function reshow()
    {
        $where['c_number'] = input('param.c_number');
        $dataObj = ContractQueue::where($where)->find();
        $dataInfo = ContractMode::where($where)->find();
		
		$dataObj->is_sign_two = 1;
		$dataObj->save();
		//更新合同状态
		$dataInfo->contract_status = 5;
		$dataInfo->user_status = 1;
		$dataInfo->save();
        if($dataObj->type==0){//订单合同
            $path = "/catalog/getPreviewURL/";
            //post data
            $post_data['catalogName'] = $dataObj->contract_id;
            $post_data['signerAccount'] = $dataObj->unit_account;
            $post_data['dpi'] = '240';
            $post_data['expireTime'] = '0';  //1个月后的时间戳
            $response = $this->basePara($path, $post_data);
        }else{
            $path = "/contract/getPreviewURL/";
            //post data
            $post_data['contractId'] = $dataObj->contract_id;
            $post_data['account'] = $dataObj->unit_account;
            $post_data['dpi'] = '240';
            $post_data['expireTime'] = '0';  //1个月后的时间戳
            $response = $this->basePara($path, $post_data);
        }
        $arrs = json_decode($response,true);
		return redirect($arrs['data']['url']);
    }

    //定时注册用户
    function conReg()
    {
        set_time_limit(0);
        $bool =0;
        $id = 1;
        while($bool<=5) {
            $where[] = ['cq_id','>',$id];
            $where[] = ['is_reg_user','=',0];
            $dataObj = ContractQueue::where($where)->find();
            if(empty($dataObj)){
                $res['type'] = '0';
                $res['code'] = '20000';
                $res['bool'] = $bool;
                $res['msg'] = '找不到数据';
                return json($res);
            }
            $boolReg = Cache::get('reg'.$dataObj->c_number);  //注册用户
            if(empty($boolReg)){
                Cache::set('reg'.$dataObj->c_number,'1');
                $mail = $dataObj->user_info->mail;
                $identity = $dataObj->user_info->identity;
                $account = $identity;
                $mobile = $dataObj->user_info->mobile;
                $name = $dataObj->user_info->name;
                $user_type = "1";    //个人

                $credential['identity'] = $identity;
                $credential['identityType'] = $dataObj->account_type;   //证件类型
                $credential['contactMobile'] = '';
                $credential['contactMail'] = $mail;
                $credential['province']= '';
                $credential['city'] = '';
                $credential['address'] = '';
                $applyCert = '1';
                $response = $this->regBaseUser($account, $mail, $mobile, $name, $user_type, $credential, $applyCert);
                $resArr = json_decode($response,true);
                if($resArr['errno']==0){//用户注册成功
                    $res['type'] = '1';
                    $res['msg'] ='注册用户成功';
                    $dataObj->is_reg_user = 1;
                    $dataObj->is_deal = 1;
                    $dataObj->user_account = $identity;
                    $dataObj->save();
                }else{
                    $dataObj->is_deal = 1;
                    $dataObj->save();
                    $id = $dataObj->cq_id;
                    $bool+=1;
                }
                Cache::rm('reg'.$dataObj->c_number);
            }else{
                $id = $dataObj->cq_id;
                $bool+=1;
            }
        }
    }
    //定时上传文件
    function conUp()
    {
        set_time_limit(0);
        $bool =0;
        $id = 1;
        while($bool<=5) {
            $where[] = ['cq_id','>',$id];
            $where[] = ['is_upload','=',0];
            $where[] = ['is_reg_user','=',1];
            $dataObj = ContractQueue::where($where)->find();
            if(empty($dataObj)){
                $res['type'] = '0';
                $res['code'] = '20000';
                $res['bool'] = $bool;
                $res['msg'] = '找不到数据';
                return json($res);
            }
            $boolUp = Cache::get('up'.$dataObj->c_number);    //上传文件
            if(empty($boolUp)){
                Cache::set('up'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '上传文件失败,请重试';
                if($dataObj->ssq_fid_one=='0'){
                    $base_dir = './pdf';
                    $shell = 'wkhtmltopdf '.$this->_contract_host.$this->_contract_path.$dataObj->c_number.' '.$base_dir.'/'.$dataObj->c_number.'.pdf';
                    system($shell, $status);//本地生成合同主体pdf
                    if($status){ //执行失败
                        Cache::rm('up'.$dataObj->c_number);
                    }
                    //上传合同主体文件开始
                    $file_name = $dataObj->c_number.'.pdf';
                    $file_path = $this->_contract_pdf_path.$file_name;
                    $file_res = $this->upFileToPdf($file_name,$file_path,$dataObj->unit_account);  //上传合同主体文件
                    $file_res_arr = json_decode($file_res,true);

                    if($file_res_arr['errno']==0){ //上传成功
                        $dataObj->ssq_fid_one = $file_res_arr['data']['fid'];
                        $dataObj->is_upload = $dataObj->type;     //自由合同type 值为1
                        $dataObj->save();
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                    //上传合同主体文件结束
                }

                if($dataObj->type==0&&$dataObj->ssq_fid_two=='0'){//订单合同 即多文件合同
                    //上传合同行程文件开始
                    $file_name = substr($dataObj->line_file_path,strripos($dataObj->line_file_path,"/")+1);
                    $file_path = $dataObj->line_file_path;
                    $file_res = $this->upFileToPdf($file_name,$file_path,$dataObj->unit_account);  //上传合同主体文件
                    $file_res_arr = json_decode($file_res,true);
                    if($file_res_arr['errno']==0){ //上传成功
                        $dataObj->ssq_fid_two = $file_res_arr['data']['fid'];
                        $dataObj->is_upload = 1;
                        $dataObj->save();
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                    //上传合同行程文件结束
                }
                Cache::rm('up'.$dataObj->c_number);
            }else{
                $id = $dataObj->cq_id;
                $bool+=1;
            }
        }
    }

    //定时生成合同
    function conCreate()
    {
        set_time_limit(0);
        $bool =0;
        $id = 1;
        while($bool<=5) {
            $where[] = ['cq_id','>',$id];
            $where[] = ['is_creat','=',0];
            $where[] = ['is_upload','=',1];
            $dataObj = ContractQueue::where($where)->find();
            if(empty($dataObj)){
                $res['type'] = '0';
                $res['code'] = '20000';
                $res['bool'] = $bool;
                $res['msg'] = '找不到数据';
                return json($res);
            }
            $boolCreate = Cache::get('create'.$dataObj->c_number);    //生成合同
            if(empty($boolCreate)){
                Cache::set('create'.$dataObj->c_number,'1');
                if($dataObj->type==0){//订单合同 即多文件合同
                    $path = "/catalog/create/";
                    //post data
                    $post_data['senderAccount'] = $dataObj->unit_account;
                    $post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
                    $post_data['catalogName'] = $dataObj->c_number;
                    $post_data['description'] = "";
                    $res_create = $this->basePara($path, $post_data);
                    $create_arr = json_decode($res_create,true);

                    if($create_arr['errno']==0||$create_arr['errno']==242008){ //生成目录成功
                        $path = "/catalog/uploadContract/";
                        //post data
                        $post_data_add['senderAccount'] = $dataObj->unit_account;
                        $post_data_add['catalogName'] = $dataObj->c_number;   //合同目录唯一标识
                        $post_data_add['fid'] = $dataObj->ssq_fid_one;
                        $post_data_add['title'] = "合同主体";
                        $file_add_one = $this->basePara($path, $post_data_add);
                        $post_data_add['fid'] = $dataObj->ssq_fid_two;
                        $post_data_add['title'] = "合同行程单";
                        $file_add_two = $this->basePara($path, $post_data_add);
                        $create_arr_one = json_decode($file_add_one,true);
                        $create_arr_two = json_decode($file_add_two,true);
                        if(($create_arr_one['errno']==0||$create_arr_one['errno']==242008)&&($create_arr_two['errno']==0||$create_arr_two['errno']==242008)){ //生成成功
                            $dataObj->is_creat = 1;
                            $dataObj->contract_id = $dataObj->c_number;
                            $dataObj->save();
                        }
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                }else{
                    //自由合同开始
                    $path = "/contract/create/";
                    //post data
                    $post_data['account'] = $dataObj->unit_account;
                    $post_data['fid'] = $dataObj->ssq_fid_one;
                    $post_data['expireTime'] = $this->getMonthTimes(1).'';  //1个月后的时间戳
                    $post_data['title'] = "合同主体";
                    $post_data['description'] = "";
                    $post_data['hotStoragePeriod'] = "31536000";
                    $res_create = $this->basePara($path, $post_data);
                    $create_arr = json_decode($res_create,true);
                    //自由合同结束
                    if($create_arr['errno']==0){ //生成成功
                        $dataObj->is_creat = 1;
                        $dataObj->contract_id = $create_arr['data']['contractId'];
                        $dataObj->save();
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                }
                Cache::rm('create'.$dataObj->c_number);
            }else{
                $id = $dataObj->cq_id;
                $bool+=1;
            }
        }
    }
    //定时自动签署
    function conSign()
    {
        set_time_limit(0);
        $bool =0;
        $id = 1;
        while($bool<=10) {
            $where[] = ['cq_id','>',$id];
            $where[] = ['is_creat','=',1];
            $where[] = ['is_sign','=',0];
            $dataObj = ContractQueue::where($where)->find();
            $map['c_number'] = $dataObj->c_number;
            $dataInfo = ContractMode::where($map)->find();
            if(empty($dataObj)){
                $res['type'] = '0';
                $res['code'] = '20000';
                $res['bool'] = $bool;
                $res['msg'] = '找不到数据';
                return json($res);
            }
            $boolSign = Cache::get('sign'.$dataObj->c_number);    //自动签署
            if(empty($boolSign)){
                Cache::set('sign'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '自动盖章失败,请重试';
                if($dataObj->type==0){//订单合同 即多文件合同
                    $orderPath = "/catalog/getContracts/";
                    //post data
                    $order_post_data['catalogName'] = $dataObj->c_number;   //合同目录唯一标识
                    $res_list = $this->basePara($orderPath, $order_post_data);
                    $arrs = json_decode($res_list,true);
                    $path = "/contract/sign/cert/";
                    //post data

                    $arr['pageNum'] = '1';
                    $arr['x'] = '0.15';
                    $arr['y'] = '0.12';
                    $arr['rptPageNums'] = '0';
                    $res_sign_bool = 1;
                    foreach($arrs['data']['contracts'] as $k=>$v){
                        $post_data['contractId'] = $v['contractId'];
                        $post_data['signerAccount'] = $dataObj->unit_account;
                        $post_data['signatureImageName'] = $post_data['signerAccount'];
                        $post_data['signaturePositions'] = $arr;
                        $jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
                        $reso_sign = $this->basePara($path, $jsonStr, '', true);
                        $arrss[] = json_decode($reso_sign,true);
                        $res_sign = json_encode($arrss);
                        if(!($arrss[$k]['errno']==0||$arrss[$k]['errno']==241424)){
                            $res_sign_bool = $res_sign_bool*0;
                        }
                    }
                    if($res_sign_bool==1){ //盖章成功
                        $dataObj->is_sign = 1;
                        $dataObj->save();
                        //更新合同状态
                        $dataInfo->is_create = 1;
                        $dataInfo->tra_status = 1;
                        $dataInfo->contract_status = 4;
                        $dataInfo->save();
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                }else{
                    //自由合同开始
                    $path = "/storage/contract/sign/cert/";
                    //post data
                    $arr = array();
                    $arr['pageNum'] = '1';
                    $arr['x'] = '0.15';
                    $arr['y'] = '0.12';
                    $arr['rptPageNums'] = '0';
                    $post_data['contractId'] = $dataObj->contract_id;
                    $post_data['signer'] = $dataObj->unit_account;
                    $post_data['signatureImageName'] = $post_data['signer'];
                    $post_data['signaturePositions'] = $arr;
                    $jsonStr = $this->getJsonArr($post_data,'signaturePositions');  //提前处理为jsonArr格式
                    $res_sign = $this->basePara($path, $jsonStr, '', true);
                    $sign_arr = json_decode($res_sign,true);
                    //自由合同结束
                    if($sign_arr['errno']==0||$sign_arr['errno']==241424){ //盖章成功
                        $dataObj->is_sign = 1;
                        $dataObj->save();
                        //更新合同状态
                        $dataInfo->is_create = 1;
                        $dataInfo->tra_status = 1;
                        $dataInfo->contract_status = 4;
                        $dataInfo->save();
                    }else{
                        $id = $dataObj->cq_id;
                        $bool+=1;
                    }
                }
                Cache::rm('sign'.$dataObj->c_number);
            }else{
                $id = $dataObj->cq_id;
                $bool+=1;
            }
        }
    }
    //定时结束合同
    function conLock()
    {
        set_time_limit(0);
        $bool =0;
        $id = 1;
        while($bool<=10) {
            $where[] = ['cq_id','>',$id];
            $where[] = ['is_lock','=',0];
            $where[] = ['is_sign_two','=',1];
            $dataObj = ContractQueue::where($where)->find();
            $map['c_number'] = $dataObj->c_number;
            $dataInfo = ContractMode::where($map)->find();
            if(empty($dataObj)){
                $res['type'] = '0';
                $res['code'] = '20000';
                $res['bool'] = $bool;
                $res['msg'] = '找不到数据';
                return json($res);
            }
            $boolLock = Cache::get('lock'.$dataObj->c_number);    //结束合同
            if(empty($boolLock)){
                Cache::set('lock'.$dataObj->c_number,'1');
                $res['type'] = '0';
                $res['msg'] = '结束合同失败，请重试';
                if($dataObj->type==0){//订单合同 即多文件合同
                    $path = "/catalog/lock/";
                    //post data
                    $post_data['catalogName'] = $dataObj->contract_id;
                    $response = $this->basePara($path, $post_data);
                    $arr = json_decode($response,true);
                }else{
                    //自由合同开始
                    $path = "/storage/contract/lock/";

                    $url_params['contractId'] = $dataObj->contract_id;
                    $response = $this->basePara($path, $url_params);
                    $arr = json_decode($response,true);
                    //自由合同结束
                }
                if($arr['errno']==0||$arr['errno']==241423){ //结束成功
                    $dataObj->is_lock = 1;
                    $dataObj->save();
                    //更新合同状态
                    $dataInfo->is_lock = 1;
                    $dataInfo->save();
                }else{
                    $id = $dataObj->cq_id;
                    $bool+=1;
                }
                Cache::rm('lock'.$dataObj->c_number);
            }else{
                $id = $dataObj->cq_id;
                $bool+=1;
            }
        }
    }
    //
    function cs()
    {
        if(empty(input('param.c_number'))){
            Cache::clear();
            return '清空全部缓存';
        }
        Cache::rm('lock'.input('param.c_number'));
        Cache::rm('sign'.input('param.c_number'));
        Cache::rm('create'.input('param.c_number'));
        Cache::rm('up'.input('param.c_number'));
        Cache::rm('reg'.input('param.c_number'));
        return '清空'.input('param.c_number').'缓存';
    }
    //
    function conTest()
    {
        return 1;
    }
}