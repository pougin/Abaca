<?php
##########################################
# 验证码，提供GD和IM两种图片，一般使用GD
# 验证码都在10分钟内过期
# copy right reserved by pougin@qq.com
##########################################

class VerifyCode {
	private $cache;
	private $length;
	private $type;
	
	/**
	 * 初始化
	 * @param I $length, lenght of the code in image
	 * @param S $type, which library to use, can be 'gd'||'im'
	 */
	public function __construct($length=4,$type='gd'){
		$this->cache=Cache::newCache('memcache');
		$this->length=$length;
		$this->type=$type;
	}

	/**
	 * generate a verify key and its code, then store them in memcache for 10 minutes
	 */
	public function getKey(){
		$key=uniqid();
		$code=substr(md5($key.rand(1, 1000)),7,$this->length);
		$code=strtoupper($code);
		if($this->cache->set($key,$code,600)){
			return $key;
		}else{
			return false;
		}
	}
	
	/**
	 * output the image. Caution! here binary data output
	 * @param S $key
	 */
	public function outputImage($key,$size=""){
		if (empty($key)) {
			return $this->errorImage();
		}
		header ( "Cache-Control: no-cache, must-revalidate" );
		header ( "Content-type: image/gif" );
		$code=$this->cache->get($key);
		if (empty($code)) {
			return $this->errorImage();
		}
		//make image according to type
		if ($this->type=='im') {
			return $this->imImage($key,$code,$size="");
		}else{
			return $this->gdImage($code,$size);
		}
	}
	
	/**
	 * check whether key and userCode matches
	 * @param S $key
	 * @param S $userCode, user input code
	 */
	public function verify($key,$userCode){
		if (empty($key) || empty($userCode)) {
			return false;
		}
		$code=$this->cache->get($key);
		return $code==strtoupper($userCode);
	}
	
	/**
	 * make image by im
	 */
	private function imImage($key,$code,$size=""){
		if(!empty($size) && strpos($size,"x")){
			list($width,$height)=explode('x',$size);
		}else{
			$width=strlen($code)*16+12;
			$height=30;
		}
		$filename=LocalPath.'abaca/tmp/'.$key.'.gif';
		$min=rand(1, 9);
		$max=rand(1, 9);
		$c="convert -size {$width}x{$height} gradient:gray{$min}0-gray{$max}0 -font Arial -pointsize 24
			-fill none -stroke gray40 -strokewidth 7  -annotate +6+23 $code
			-fill none -stroke white -strokewidth 5  -annotate +6+23 $code
			-fill none -stroke gray20 -strokewidth 3  -annotate +6+23 $code
			-fill none -stroke white -strokewidth 1  -annotate +6+23 $code
     	   {$filename}";
     	exec(str_replace("\n", '', $c));
     	echo file_get_contents($filename);
     	unlink($filename);
	}
	
	/**
	 * make image by gd
	 */
	private function gdImage($code,$size="") {
		if(!empty($size) && strpos($size,"x")){
			list($width,$height)=explode('x',$size);
			
		}else{
			$width=strlen($code)*10+12;
			$height = 30;
		}
		$im = @imagecreatetruecolor ( $width, $height );
		$r = Array (225, 211, 255, 223 );
		$g = Array (225, 236, 237, 215 );
		$b = Array (225, 236, 166, 125 );
		$key = rand ( 0, 3 );
		$backColor = imagecolorallocate ( $im, $r [$key], $g [$key], $b [$key] ); //背景色（随机）^M
		$borderColor = imagecolorallocate ( $im, 192, 192, 192 ); //边框色^M
		$pointColor = imagecolorallocate ( $im, 255, 170, 255 ); //点颜色^M
		
		@imagefilledrectangle ( $im, 0, 0, $width - 1, $height - 1, $backColor ); //背景位置^M
		@imagerectangle ( $im, 0, 0, $width - 1, $height - 1, $borderColor ); //边框位置^M
		$stringColor = imagecolorallocate ( $im, 255, 51, 153 );
		
		for($i = 0; $i <= 100; $i ++) {
			$pointX = rand ( 2, $width - 2 );
			$pointY = rand ( 2, $height - 2 );
			imagesetpixel ( $im, $pointX, $pointY, $pointColor );
		}
		
		imagestring ( $im, 5, 10, 7, $code, $stringColor );
		imagegif ( $im );
		imagedestroy ( $im );
	}
	
	/**
	 * give a error image if key empty or wrong
	 */
	private function errorImage(){
		echo file_get_contents(AbacaPath.'data/verifyCode/error.gif');
	}
}