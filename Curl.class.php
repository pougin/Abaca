<?php
###############################
# Curl,使用了cookie，使用完成后一定要使用close方法来删除cookie文件
# 支持链式写法
# by pougin
###############################

class Curl {
	private $curl;
	private $url;
	private $cookiefile;

	public function __construct($url = NULL) {
		$this->curl = curl_init ( $url );
		$this->url=$url;
		$this->cookiefile=tempnam ( sys_get_temp_dir (), 'curlCookie' );
	}
	
// 	public function __destruct(){
// 		curl_close($this->curl);
// 		if (file_exists($this->cookiefile)) {
// 			unlink($this->cookiefile);
// 		}
// 	}

	public function setUrl($url){
		curl_setopt($this->curl,CURLOPT_URL,$url);
		$this->url=$url;
		return $this;
	}

	public function setOption($option_array=array()){
		curl_setopt_array($this->curl,$option_array);
		return $this;
	}
	
	/**
	 * 直接使用CURLOPT_HTTPHEADER设置头信息
	 *
	 * @param String $para
	 */
	public function setHeader($header){
		$headers=explode("\r\n",str_replace("\t",":",$header));
		curl_setopt($this->curl,CURLOPT_HTTPHEADER,$headers);
		return $this;
	}

	public function post($fields=array(),$returnSelf=false){
		$option_array=array(CURLOPT_HEADER=>false,
			CURLOPT_POST=>true,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FOLLOWLOCATION=>true,
			CURLOPT_POSTFIELDS=>$fields,
			CURLOPT_COOKIEFILE=>$this->cookiefile,
			CURLOPT_COOKIEJAR=>$this->cookiefile
		);
		$this->setOption($option_array);
		$result=curl_exec($this->curl);
		return $returnSelf?$this:$result;
	}

	public function get($queries=array(),$returnSelf=false){
		if (parse_url($this->url,PHP_URL_QUERY)=='') {
			$url=rtrim($this->url,'?').'?'.http_build_query($queries);
		}else{
			$url=$this->url.'&'.http_build_query($queries);
		}
		$this->setUrl($url);

		$option_array=array(CURLOPT_HEADER=>false,
			CURLOPT_HTTPGET=>true,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_FOLLOWLOCATION=>true,
			CURLOPT_COOKIEFILE=>$this->cookiefile,
			CURLOPT_COOKIEJAR=>$this->cookiefile
		);
		$this->setOption($option_array);
		
		$result=curl_exec($this->curl);
		return $returnSelf?$this:$result;
	}
	
	public function showCookie(){
		return file_exists($this->cookiefile)?file_get_contents($this->cookiefile):'Cookie file not found.';
	}

	public function close(){
		curl_close($this->curl);
		if (file_exists($this->cookiefile)) {
			unlink($this->cookiefile);
		}
	}
}
?>