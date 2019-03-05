<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
if (!function_exists('auth')) {
    /**
     * 实例化auth'
     *
     * @param string  $name auth名称，如果为数组表示进行auth设置
     *
     * @return mixed
     */
    function auth($name='admin')
    {
        return \lib\Auth::guard('admin');
    }
}


/**
 * 取出数组|对象中的第一个元素
 *
 * @param  array|object  $arr
 *
 * @return array|object
 */
function array_first($arr)
{
    return $arr[0];
}

/**
 * 获取时间格式YYYYmmdd
 *
 * @return string
 */
function get_time()
{
    return date('Y-m-d H:i:s');
}

/*
 *
* @param unknown $url
*/
function HttpGet($url,$status=false){
    $curl = curl_init ();
    curl_setopt ( $curl, CURLOPT_URL, $url);
    curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt ( $curl, CURLOPT_TIMEOUT,1000 );
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36');
    //curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 8_0 like Mac OS X) AppleWebKit/600.1.3 (KHTML, like Gecko) Version/8.0 Mobile/12A4345d Safari/600.1.4');
    if($status){
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept:application','X-Request:JSON','X-Requested-With:XMLHttpRequest'));
    }

    //如果用的协议是https则打开鞋面这个注释
    curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    $res = curl_exec ( $curl );
    $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close ( $curl );
    if ($httpCode!=200) {
        return false;
    }
    return $res;
}





function Http_Spider($url) {
    $ch = curl_init();
    $ip = '115.239.211.112';  //百度蜘蛛
    $timeout = 15;
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);
    //伪造百度蜘蛛IP
    curl_setopt($ch,CURLOPT_HTTPHEADER,array('X-FORWARDED-FOR:'.$ip.'','CLIENT-IP:'.$ip.''));
    //伪造百度蜘蛛头部
    curl_setopt($ch,CURLOPT_USERAGENT,"Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt ($ch, CURLOPT_REFERER, "http://www.baidu.com/");   //构造来路
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $content = curl_exec($ch);
    return $content;
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);


/**
    * 数组 转 对象
    *
    * @param array $arr 数组
    * @return object
    */
   function array_to_object($arr) {
       if (gettype($arr) != 'array') {
           return;
       }
       foreach ($arr as $k => $v) {
           if (gettype($v) == 'array' || getType($v) == 'object') {
               $arr[$k] = (object)array_to_object($v);
           }
       }

       return (object)$arr;
   }

   /**
    * 对象 转 数组
    *
    * @param object $obj 对象
    * @return array
    */
   function object_to_array($obj) {
       $obj = (array)$obj;
       foreach ($obj as $k => $v) {
           if (gettype($v) == 'resource') {
               return;
           }
           if (gettype($v) == 'object' || gettype($v) == 'array') {
               $obj[$k] = (array)object_to_array($v);
           }
       }

       return $obj;
   }

	function p($str) {
		echo '<pre>';
		print_r($str);
	}

	function nodeTree($arr, $id = 0, $level = 0) {
		static $array = array();
		foreach ($arr as $v) {
			if ($v['parentid'] == $id) {
				$v['level'] = $level;
				$array[] = $v;
				nodeTree($arr, $v['id'], $level + 1);
			}
		}
		return $array;
	}

	/**
	 * 数组转树
	 * @param type $list
	 * @param type $root
	 * @param type $pk
	 * @param type $pid
	 * @param type $child
	 * @return type
	 */
	function list_to_tree($list, $root = 0, $pk = 'id', $pid = 'parentid', $child = '_child') {
		// 创建Tree
		$tree = array();
		if (is_array($list)) {
			// 创建基于主键的数组引用
			$refer = array();
			foreach ($list as $key => $data) {
				$refer[$data[$pk]] = &$list[$key];
			}
			foreach ($list as $key => $data) {
				// 判断是否存在parent
				$parentId = 0;
				if (isset($data[$pid])) {
					$parentId = $data[$pid];
				}
				if ((string) $root == $parentId) {
					$tree[] = &$list[$key];
				} else {
					if (isset($refer[$parentId])) {
						$parent = &$refer[$parentId];
						$parent[$child][] = &$list[$key];
					}
				}
			}
		}
		return $tree;
	}

	/**
	 * 下拉选择框
	 */
	function select($array = array(), $id = 0, $str = '', $default_option = '') {
		$string = '<select ' . $str . '>';
		$default_selected = (empty($id) && $default_option) ? 'selected' : '';
		if ($default_option)
			$string .= "<option value='' $default_selected>$default_option</option>";
		if (!is_array($array) || count($array) == 0)
			return false;
		$ids = array();
		if (isset($id))
			$ids = explode(',', $id);
		foreach ($array as $key => $value) {
			$selected = in_array($key, $ids) ? 'selected' : '';
			$string .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
		}
		$string .= '</select>';
		return $string;
	}

	/**
	 * 复选框
	 * 
	 * @param $array 选项 二维数组
	 * @param $id 默认选中值，多个用 '逗号'分割
	 * @param $str 属性
	 * @param $defaultvalue 是否增加默认值 默认值为 -99
	 * @param $width 宽度
	 */
	function checkbox($array = array(), $id = '', $str = '', $defaultvalue = '', $width = 0, $field = '') {
		$string = '';
		$id = trim($id);
		if ($id != '')
			$id = strpos($id, ',') ? explode(',', $id) : array($id);
		if ($defaultvalue)
			$string .= '<input type="hidden" ' . $str . ' value="-99">';
		$i = 1;
		foreach ($array as $key => $value) {
			$key = trim($key);
			$checked = ($id && in_array($key, $id)) ? 'checked' : '';
			if ($width)
				$string .= '<label class="ib" style="width:' . $width . 'px">';
			$string .= '<input type="checkbox" ' . $str . ' id="' . $field . '_' . $i . '" ' . $checked . ' value="' . $key . '"> ' . $value;
			if ($width)
				$string .= '</label>';
			$i++;
		}
		return $string;
	}

	/**
	 * 单选框
	 * 
	 * @param $array 选项 二维数组
	 * @param $id 默认选中值
	 * @param $str 属性
	 */
	function radio($array = array(), $id = 0, $str = '', $width = 0, $field = '') {
		$string = '';
		foreach ($array as $key => $value) {
			$checked = trim($id) == trim($key) ? 'checked' : '';
			if ($width)
				$string .= '<label class="ib" style="width:' . $width . 'px">';
			$string .= '<input type="radio" ' . $str . ' id="' . $field . '_' . $key . '" ' . $checked . ' value="' . $key . '"> ' . $value;
			if ($width)
				$string .= '</label>';
		}
		return $string;
	}

	/**
	 * 字符串加密、解密函数
	 *
	 *
	 * @param	string	$txt		字符串
	 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
	 * @param	string	$key		密钥：数字、字母、下划线
	 * @param	string	$expiry		过期时间
	 * @return	string
	 */
	function encry_code($string, $operation = 'ENCODE', $key = '', $expiry = 0) {
		$ckey_length = 4;
		$key = md5($key != '' ? $key : config('encry_key'));
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

		$cryptkey = $keya . md5($keya . $keyc);
		$key_length = strlen($cryptkey);

		$string = $operation == 'DECODE' ? base64_decode(strtr(substr($string, $ckey_length), '-_', '+/')) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
		$string_length = strlen($string);

		$result = '';
		$box = range(0, 255);

		$rndkey = array();
		for ($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}

		for ($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}

		for ($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}

		if ($operation == 'DECODE') {
			if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc . rtrim(strtr(base64_encode($result), '+/', '-_'), '=');
		}
	}
