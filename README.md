# php_curl_post_data_AND_files_together

cURL的常规流程是 

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
  
$postdata如果是一维数组没啥问题，但如果是二维数组的话，直接POST会报 “PHP Notice:  Array to string conversion in ……”，网上常用的解决方案是把
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
修改成
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));

如果只是 POST 文本倒没什么问题，可一旦要同时POST文本和文件，这个方案就抓瞎了。

在网上找到的解决方案是把
  $data=array('key'=>array('value_1','value_2'));
这样的二维数组降维成
  $data=array('key[0]'=>'value_1','key[1]'=>'value_2');
这样的一维数组。

于是就有了Class_my_cURL_post。
