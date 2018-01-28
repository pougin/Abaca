<?php
###############################
# 在社交网站分享信息
# 通过用户名+密码+curl方式
# 如果有简洁的手机版，优先使用手机版
# qq的未完成
# by pougin
###############################

class Share {
	protected $curl;
	
	/**
	 * 把数据分享到其他网站，只允许调用这个函数，以保证结束时候会删除cookie文件
	 *
	 */
	public function shareTo($para){
		$this->curl=new Curl();
		//添加操作
		if (isset($para['account']['sina'])) {
			$this->SinaT($para['account']['sina']['username'],
				$para['account']['sina']['password'],
				$para['content']
			);
		}
		if (isset($para['account']['kaixin'])) {
			$this->KaixinStatus($para['account']['kaixin']['username'],
				$para['account']['kaixin']['password'],
				$para['content']
			);
		}
		if (isset($para['account']['renren'])) {
			$this->RenrenStatus($para['account']['renren']['username'],
				$para['account']['renren']['password'],
				$para['content']
			);
		}
		if (isset($para['account']['sohu'])) {
			$this->SohuT($para['account']['sohu']['username'],
				$para['account']['sohu']['password'],
				$para['content']
			);
		}
		if (isset($para['account']['163'])) {
			$this->T163T($para['account']['163']['username'],
				$para['account']['163']['password'],
				$para['content']
			);
		}
		
		
		$this->curl->close();
	}
	
	/**
	 * 分享到新浪微博
	 *
	 * @param String $username
	 * @param String $password
	 * @param String $content
	 * @param String $appkey, 一般用企业申请的key，屌一点
	 */
	public function SinaT($username,$password,$content,$appkey='2605750438'){
		list($m,$s)=explode(' ',microtime());
		$loginUri='http://login.sina.com.cn/sso/login.php';
		$loginPara=array('entry'=>'sso',
			'encoding'=>'UTF-8',
			'gateway'=>1,
			'callback'=>'sinaSSOController.loginCallBack',
			'returntype'=>'TEXT',
			'from'=>'',
			'savestate'=>0,
			'useticket'=>0,
			'username'=>$username,
			'service'=>'sso',
			'password'=>$password,
			'client'=>'ssologin.js(v1.3.9)',
			'_'=>$s.substr($m,2,3)
		);
		//这个 CURLOPT_REFERER 是必要的，而且其中的appkey等于上面参数的最好
		$shareReferer=array(CURLOPT_REFERER=>'http://v.t.sina.com.cn/share/share.php?appkey='.$appkey);
		$shareUri='http://v.t.sina.com.cn/mblog/aj_share.php';
		$sharePara=array('content'=>$content,
			'styleid'=>1,
			'from'=>'share',
			'share_pic'=>'',
			'sourceUrl'=>'http://www.yoka.com',//添加图片的处理
			'source'=>'内容分享',
			'appkey'=>$appkey
		);
		$this->curl->setUrl($loginUri)->get($loginPara,true)
			->setUrl($shareUri)->setOption($shareReferer)->post($sharePara);
	}

	public function SinaTest($username,$password){
		$this->curl=new Curl();
		list($m,$s)=explode(' ',microtime());
		$loginUri='http://login.sina.com.cn/sso/login.php';
		$loginPara=array('entry'=>'sso',
			'encoding'=>'UTF-8',
			'gateway'=>1,
			'callback'=>'sinaSSOController.loginCallBack',
			'returntype'=>'TEXT',
			'from'=>'',
			'savestate'=>0,
			'useticket'=>0,
			'username'=>$username,
			'service'=>'sso',
			'password'=>$password,
			'client'=>'ssologin.js(v1.3.9)',
			'_'=>$s.substr($m,2,3)
		);
		$R=$this->curl->setUrl($loginUri)->get($loginPara);
		$this->curl->close();
		return strpos($R,'"retcode":"0"')>0?true:false;
	}
	
	/**
	 * 更新人人网状态，他们的form必须用http_build_query之后才能成功
	 *
	 */
	public function RenrenStatus($username,$password,$content){
		$loginUri='http://3g.renren.com/login.do?fx=0&autoLogin=true';
		$loginPara=array('origURL'=>'/home.do',
			'email'=>$username,
			'password'=>$password,
			'login'=>'登录'
		);
		$R=$this->curl->setUrl($loginUri)->post(http_build_query($loginPara));
		preg_match('|<form.*action="([^"]*)"|',$R,$matches);
		$statusUrl=$matches[1];
		$statusPara=array('sour'=>'home',
			'status'=>$content,
			'update'=>'发布'
		);
		$this->curl->setUrl($statusUrl)->post(http_build_query($statusPara));
	}
	
	public function RenrenTest($username,$password){
		$this->curl=new Curl();
		$loginUri='http://3g.renren.com/login.do?fx=0&autoLogin=true';
		$loginPara=array('origURL'=>'/home.do',
			'email'=>$username,
			'password'=>$password,
			'login'=>'登录'
		);
		$R=$this->curl->setUrl($loginUri)->post(http_build_query($loginPara));
		$this->curl->close();
		return strpos($R,'<div class="error">')===false?true:false;
	}
	
	/**
	 * 分享到开心网我的状态
	 *
	 * @param String $username
	 * @param String $password
	 * @param String $content
	 */
	public function KaixinStatus($username,$password,$content){
		$loginUri='http://wml.kaixin001.com/login/login.php';
		$loginPara=array('email'=>$username,
			'password'=>$password,
			'remember'=>1,
			'from'=>'',
			'refuid'=>0,
			'refcode'=>'',
			'bind'=>'',
			'gotourl'=>'',
			'submit'=>'true'
		);
		$statusUrl='http://wml.kaixin001.com/home/state_submit.php';
		$R=$this->curl->setUrl($loginUri)->post($loginPara);
		preg_match('|<a href="/home/\?verify=([^"]+)">|',$R,$matches);
		$statusPara=array('state'=>$content,
			'verify'=>$matches[1]
		);
		$this->curl->setUrl($statusUrl)->post($statusPara);
	}
		
	public function KaixinTest($username,$password){
		$this->curl=new Curl();
		$loginUri='http://wml.kaixin001.com/login/login.php';
		//开心网3g版，处理麻烦些，先用旧版
		//$loginUri='http://wap.kaixin001.com/home/';
		$loginPara=array('email'=>$username,
			'password'=>$password,
			'remember'=>1,
			'from'=>'',
			'refuid'=>0,
			'refcode'=>'',
			'bind'=>'',
			'gotourl'=>'',
			'submit'=>'true'
		);
		$R=$this->curl->setUrl($loginUri)->post($loginPara);
		$this->curl->close();
		return strpos($R,'href="/home/?verify=')>0?true:false;
	}
	
	/**
	 * 分享到搜狐微博，用的是api，可以换成用wap的
	 *
	 * @param String $username
	 * @param String $password
	 * @param String $content
	 */
	public function SohuT($username,$password,$content){
		$apiUri='http://api.t.sohu.com/statuses/update.xml';
		$apiPara=array('status'=>$content);
		$apiOption=array(CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,
			CURLOPT_USERPWD=>"$username:$password"
		);
		$this->curl->setOption($apiOption)->setUrl($apiUri)->post(http_build_query($apiPara));
	}
	public function SohuTest($username,$password){
		$this->curl=new Curl();
		$loginUri='https://passport.sohu.com/sso/login.jsp';
		$loginPara=array('userid'=>$username,
			'password'=>md5($password),
			'appid'=>1073,
			'persistentcookie'=>1,
			'pwdtype'=>1
		);
		$loginOption=array(CURLOPT_SSL_VERIFYPEER=>false);
		$R=$this->curl->setOption($loginOption)->setUrl($loginUri)->get($loginPara);
		$this->curl->close();
		return strpos($R,'success')>0?true:false;
	}
	
	/**
	 * 分享到163微博，手机版，分享的提交一定要用 enctype="multipart/form-data"
	 *
	 * @param unknown_type $username
	 * @param unknown_type $password
	 * @param unknown_type $content
	 */
	public function T163T($username,$password,$content){
		$loginUri='http://3g.163.com/t/account/tologin';
		$loginPara=array('username'=>$username,
			'password'=>$password
		);
		$shareUri='http://3g.163.com/t/statuses/update.do';
		$sharePara=array('url'=>'/t/#p',
			'status'=>$content
		);
		$this->curl->setUrl($loginUri)->post(http_build_query($loginPara),true)
			->setUrl($shareUri)->post($sharePara);
	}
	public function T163Test($username,$password){
		$this->curl=new Curl();
		$loginUri='http://3g.163.com/t/account/tologin';
		$loginPara=array('username'=>$username,
			'password'=>$password
		);
		$R=$this->curl->setUrl($loginUri)->post(http_build_query($loginPara));
		$this->curl->close();
		return strpos($R,'account/tologout')>0?true:false;
	}
	
	function QQTest($username,$password){
		$this->curl=new Curl();
		$verifyUri = 'http://ptlogin2.qq.com/check?uin='.$username.'&appid=46000101';
		$R=$this->curl->setUrl($verifyUri)->get();
		$verifyCode = strtoupper(substr($R, 18, 4));
		
		$loginUri = 'http://ptlogin2.qq.com/login';
		$loginPara=array('u'=>$username,
			'p'=>strtoupper(md5($password.$verifyCode)),
			'verifycode'=>$verifyCode,
			'aid'=>46000101,
			'u1'=>'http://t.qq.com',//是u一，不是uL，我操
			'ptredirect'=>1,
			'h'=>1,
			'from_ui'=>1,
			'dumy'=>'',
			'fp'=>'loginerroralert'
		);
		$R=$this->curl
			->setUrl($loginUri)->get($loginPara,true)
			->setUrl('http://t.qq.com')->get();
		echo $R;
		die();
	}
	
	function unGzip($content) {
		$singal = "\x1F\x8B\x08";
		$slen = strlen ( $singal );
		if (substr ( $content, 0, $slen ) == $singal) {
			$content = substr ( $content, 10 );
			$content = gzinflate ( $content );
		}
		return $content;
	}
}
?>