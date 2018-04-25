<?php
class Class_my_cURL_post
{
	private $cookie_jar='jar.txt';// 指定保存cookie内容的文件路径

	/**
	 * @param $login_url 登陆URL
	 * @param array $param_array 向$login_url POST的数据，通常是用户名、密码
     */
	public function get_cookie($login_url, array $param_array)
	{
		if(!file_exists($this->cookie_jar)) {
			$ch=curl_init($login_url);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar); 
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param_array));
			curl_exec($ch);
			curl_close($ch);
		}
	}

	/**
	 * POSTFIELDS 可能是二维数组，直接上传会报错，需要对二维数组进行处理
	 * 效果：将 array('test'=>array('value_1','value_2')) 转换为 array('test[0]'=>'value_1','test[1]'=>'value_2')
	 * @param array $arr 转换前的 POSTFIELDS 数组
	 * @return mixed 转换后的 POSTFIELDS 数组
     */
	private function array_plain(array $arr)
	{
		foreach($arr as $key => $r){
			if(is_array($r)){
				$i=0;
				foreach($r as $v){
					$k="{$key}[$i]";
					$out[$k]=$v;
					++$i;
				}
			}else{
				$out[$key]=$r;
			}
		}
		return $out;
	}

	/**
	 * 貌似文件名含中文、空格等时无法上传，用 rawurlencode 转义好像也没用，
	 * 所以干脆将原文件以随机文件名复制一份到当前文件夹，规避此问题
	 * @param array $file_list
	 * @return array
     */
	private function set_files(array $file_list)
	{
		if(count($file_list)==0){
			return [];
		}
		$out=[];
		foreach($file_list as $key=>$value){
			if(is_array($value)){
				$i=0;
				foreach($value as $v){
					if(!file_exists($v)){
						die('Please check your file path.');
					}
					$suf=pathinfo($v,PATHINFO_EXTENSION);
					$fname=uniqid('tmp_').'.'.$suf;
					copy($v,$fname);
					$k="{$key}[$i]";
					$out[$k]=$fname;
					++$i;
				}
			}else{
				if(!file_exists($value)){
					die('Please check your file path.');
				}
				$suf=pathinfo($value,PATHINFO_EXTENSION);
				$fname=uniqid('tmp_').'.'.$suf;
				copy($value,$fname);
				$out[$key]=$fname;
			}
		}
		return $out;
	}
	
	/**
	 * 后缀与MIME对应关系请自行添加
	 * 
	 * @param $suf
	 * @return string 后缀对应的MIME
     */
	private function get_mime($suf)
	{
		$suf=strtolower($suf);
		$mimes=[
			'jpg'=>'image/jpeg',
			'gif'=>'image/gif',
			'png'=>'image/png',
			'pdf'=>'application/pdf',
			'xls'=>'application/vnd.ms-excel',
			'doc'=>'application/msword',
		];
		if(array_key_exists($suf,$mimes)){
			return $mimes[$suf];
		}else{
			return 'application/octet-stream';
		}
	}

	/**
	 * @param $url
	 * @param array $param
	 * @param array $file
	 * @param bool $with_cookie ：POST 时是否带cookie，需要先调用 get_cookie 取得cookie
	 * @return mixed
     */
	public function post_data($url, array $param, array $file, $with_cookie=false)
	{
		$post_param=$this->array_plain($param);
		$file_list=$this->set_files($file);
		foreach($file_list as $f){
			$mime=$this->get_mime(pathinfo($f,PATHINFO_EXTENSION));
			$post_file[]=new CURLFile($f,$mime);
		}
		$post_data=array_merge($post_param,$post_file);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		if($with_cookie){
			curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
		}
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$cc = curl_exec($ch);
		curl_close($ch);
		foreach($file_list as $f){
			unlink($f);
		}
		return $cc;
	}	
}

// Demo:

$url='http://127.0.0.1/action.php';
$data=[
	   'txt'=>['hello','world'],
	   'name'=>'mrtaiki',
	   'tel'=>['0116','7400']
	  ];
$file=[
	   'file'=>['Stars.jpg','tt.jpg']
	  ];

$cc=new Class_my_cURL_post();
echo $cc->post_data($url,$data,$file);

// output
/*

array (size=3)
  'txt' => 
    array (size=2)
      0 => string 'hello' (length=5)
      1 => string 'world' (length=5)
  'name' => string 'mrtaiki' (length=7)
  'tel' => 
    array (size=2)
      0 => string '0116' (length=4)
      1 => string '7400' (length=4)
	  
	  
array (size=2)
  0 => 
    array (size=5)
      'name' => string 'tmp_5adff5558a829.jpg' (length=21)
      'type' => string 'image/jpeg' (length=10)
      'tmp_name' => string 'C:\Windows\phpADED.tmp' (length=22)
      'error' => int 0
      'size' => int 7505
  1 => 
    array (size=5)
      'name' => string 'tmp_5adff5558ac11.jpg' (length=21)
      'type' => string 'image/jpeg' (length=10)
      'tmp_name' => string 'C:\Windows\phpADEE.tmp' (length=22)
      'error' => int 0
      'size' => int 7505
	  
*/