<?php
// #############################
// 环境默认配置，Abace的默认配置变量
// 类的自动包含，检查abaca配置、引入数据库和缓存配置
// #############################
date_default_timezone_set ( 'PRC' );

$_SERVER ['Abaca'] = array (
		'errorLevel' => 0,
		// &debug=hellobuddy
		'onlineDebugKey' => 'hellobuddy',
		'defaultEncryptKey' => '我什么也不想说了',
		'abacaClass' => array (
				'DB',
				'Cache',
				'ARedis',
				'Debug',
				'Crypt',
				'Curl',
				// 'Func',
				'Ftp',
				'Mail',
				'Log' 
		),
		'abacaExtClass' => array (
				'VerifyCode',
				'PHPMailer',
				'SMTP',
				'phpQuery',
				'Icon',
				'Prototype',
				'Press',
				'Pinyin',
				'TrieFilter',
				'Predis',
				// 'Privilege',
				'Reminder',
				'SmsOperator',
				'HTMLPurifierCreater' 
		) 
);
spl_autoload_register ( 'abacaInclude' );

abacaCheck ();

// #############################
// 输入转义
// #############################
if (AutoAddSlashes && ! get_magic_quotes_gpc ()) {
	foreach ( array (
			'_REQUEST',
			'_GET',
			'_POST',
			'_FILES',
			'_COOKIE' 
	) as $v ) {
		$$v = abacaAddslashes ( $$v );
	}
}

// #############################
// 函数开始
// #############################
/**
 * pt=puogin test
 *
 * @param V $var        	
 * @param B $die        	
 */
function pt($var, $die = false) {
	var_dump ( $var );
	echo '<br><br>';
	
	if ($die) {
		die ();
	}
}

/**
 * 字符串转义函数
 */
function abacaAddslashes($var) {
	if (! get_magic_quotes_gpc ()) {
		if (is_array ( $var )) {
			foreach ( $var as $key => $val ) {
				$var [$key] = abacaAddslashes ( $val );
			}
		} else {
			$var = addslashes ( $var );
		}
	}
	return $var;
}
/**
 * 类的引入函数，注册为自动加载，类被访问的时候才加载相应的文件
 *
 * @param String $className,
 *        	the name of some class
 */
function abacaInclude($className) {
	if (in_array ( $className, $_SERVER ['Abaca'] ['abacaClass'] )) {
		if ($className == 'Debug' && ! (defined ( 'DebugOn' ) && DebugOn == true || isset ( $_GET ['debug'] ) && $_GET ['debug'] == $_SERVER ['Abaca'] ['onlineDebugKey'])) {
			return include AbacaPath . 'ext/Debug.class.php';
		}
		return include AbacaPath . $className . '.class.php';
	} elseif (in_array ( $className, $_SERVER ['Abaca'] ['abacaExtClass'] )) {
		return include AbacaPath . '/ext/' . $className . '.class.php';
	} elseif (defined ( 'LocalClassPath' )) {
		$file = LocalClassPath . $className . '.class.php';
		if (file_exists ( $file )) {
			return include $file;
		}
	}
	return false;
}

/**
 * 显示视图的函数
 *
 * @param String $view,
 *        	be the file name or sub path and file name,'abc.html' or 'shop/index.php'
 * @param String $D,
 *        	the array containing all data
 * @param Boolean $returnHTML,
 *        	when we need to do after process of the html like dynamic url to static url, give true to get the html
 */
function abacaShow($view, $D = array(), $returnHTML = false) {
	if ($returnHTML) {
		ob_start ();
		include ViewPath . $view;
		$out = ob_get_clean ();
		return $out;
	} else {
		include ViewPath . $view;
		die ();
	}
}

/*
 * function abacaShow($view,$returnHTML=false){ if ($returnHTML) { ob_start(); include ViewPath.$view; $out=ob_get_clean(); return $out; }else{ if(DIRECTORY_SEPARATOR=='\\') { ob_start(); include ViewPath.$view; $out=ob_get_clean(); $out = str_replace("http://jic.gexing.com/j/??",'http://jic.gexing.com/exportjs.php?file=',$out); $out = str_replace("http://jic.gexing.com/c/??",'http://jic.gexing.com/exportcss.php?file=',$out); $out = str_replace("?v=",'&v=',$out); echo $out; }else{ include ViewPath.$view; } die(); } }
 */

/**
 * 检查abaca配置，如果没有定义，则给默认值
 */
function abacaCheck() {
	if (! defined ( 'LocalClassPath' ) && is_dir ( LocalPath . 'abaca/class/' )) {
		define ( 'LocalClassPath', LocalPath . 'abaca/class/' );
	}
	if (! defined ( 'ViewPath' ) && is_dir ( LocalPath . 'abaca/view/' )) {
		define ( 'ViewPath', LocalPath . 'abaca/view/' );
	}
	if (! defined ( 'LogPath' ) && is_dir ( LocalPath . 'abaca/log/' )) {
		define ( 'LogPath', LocalPath . 'abaca/log/' );
	}
	if (! defined ( 'CachePath' ) && is_dir ( LocalPath . 'abaca/cache/' )) {
		define ( 'CachePath', LocalPath . 'abaca/cache/' );
	}
	if (! defined ( 'SlowPageTime' )) {
		define ( 'SlowPageTime', 1 );
	}
	if (! defined ( 'CookieDomain' ) && isset ( $_SERVER ['HTTP_HOST'] )) {
		define ( 'CookieDomain', $_SERVER ['HTTP_HOST'] );
	}
	if (! defined ( 'DisableConfig' )) {
		$config = '';
		if (is_dir ( LocalPath . 'abaca/config.local' )) {
			$config = LocalPath . 'abaca/config.local';
		} elseif (is_dir ( LocalPath . 'abaca/config' )) {
			$config = LocalPath . 'abaca/config';
		}
		
		if (! empty ( $config )) {
			$files = scandir ( $config );
			foreach ( $files as $v ) {
				if (substr ( $v, 0, 1 ) != '.' && file_exists ( $config . '/' . $v )) {
					include $config . '/' . $v;
				}
			}
		}
	}
	/*
	 * if (defined('DebugOn') && DebugOn==true || isset($_GET['debug']) && $_GET['debug']==$_SERVER['Abaca']['onlineDebugKey']) { ini_set('display_errors', true); if (defined('ErrorLevel')) { error_reporting(ErrorLevel); }else{ error_reporting($_SERVER['Abaca']['errorLevel']); } } if(!isset($GLOBALS['argv'])) { ob_start(); Debug::start(); register_shutdown_function(array('Debug', 'show')); }
	 */
}

/**
 * 读取一个输入变量为Int
 *
 * @param unknown $key        	
 * @param number $default        	
 * @param string $param        	
 */
function getAsInt($key, $default = 0, $var = 'request') {
	$v = $GLOBALS ['_' . strtoupper ( $var )];
	
	return isset ( $v [$key] ) ? intval ( $v [$key] ) : $default;
}

/**
 * 读取一个输入变量为Int
 *
 * @param S $key        	
 * @param S $default        	
 * @param S $param        	
 */
function getAsString($key, $default = '', $var = 'request') {
	$v = $GLOBALS ['_' . strtoupper ( $var )];
	
	return isset ( $v [$key] ) ? trim ( $v [$key] ) : $default;
}

/**
 * get an parameter as array
 * @param unknown $key
 * @param unknown $default
 * @param string $var
 * @return unknown
 */
function getAsArray($key, $default = array(), $var = 'request') {
	$v = $GLOBALS ['_' . strtoupper ( $var )];
	
	return isset ( $v [$key] ) && is_array ( $v [$key] ) ? $v [$key] : $default;
}

/**
 * 获取用户提交的信息
 *
 * @param String $key        	
 * @param String $method        	
 * @param Mix $default        	
 */
function getRequestValue($key, $method = 'request', $default = '') {
	switch ($method) {
		case 'post' :
			$var = $_POST;
			break;
		case 'get' :
			$var = $_GET;
			break;
		default :
			$var = $_REQUEST;
			break;
	}
	return isset ( $var [$key] ) ? trim ( $var [$key] ) : $default;
}
function getIntValue($key, $method = 'request', $default = '') {
	return ( int ) getRequestValue ( $key, $method, $default );
}

/**
 * 获取html标签编码的字符串
 */
function getEncodeHtmlString($key, $method = 'request', $default = '') {
	$value = getRequestValue ( $key, $method, $default );
	return htmlspecialchars ( $value );
}
function getNoHtmlString($key, $method = 'request', $default = '') {
	$value = strip_tags ( getRequestValue ( $key, $method, $default ) );
	return str_replace ( array (
			'<',
			'>' 
	), '', $value );
}

/**
 * 获取客户端ip
 */
function getClientIp() {
	return $_SERVER ['REMOTE_ADDR'];
	
	// if (getenv ( 'HTTP_CLIENT_IP' ) && strcasecmp ( getenv ( 'HTTP_CLIENT_IP' ), 'unknown' )) {
	// $onlineip = getenv ( 'HTTP_CLIENT_IP' );
	// } elseif (getenv ( 'HTTP_X_FORWARDED_FOR' ) && strcasecmp ( getenv ( 'HTTP_X_FORWARDED_FOR' ), 'unknown' )) {
	// $onlineip = getenv ( 'HTTP_X_FORWARDED_FOR' );
	// } elseif (getenv ( 'REMOTE_ADDR' ) && strcasecmp ( getenv ( 'REMOTE_ADDR' ), 'unknown' )) {
	// $onlineip = getenv ( 'REMOTE_ADDR' );
	// } elseif (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], 'unknown' )) {
	// $onlineip = $_SERVER ['REMOTE_ADDR'];
	// }
	// return $onlineip;
}
function isAndroidOrIOS() {
	$userAgent = $_SERVER ['HTTP_USER_AGENT'];
	if (strpos ( $userAgent, "iPhone" ) || strpos ( $userAgent, "iPad" ) || strpos ( $userAgent, "iPod" ) || strpos ( $userAgent, "Android" )) {
		return true;
	} else {
		return false;
	}
}

/**
 * 加密和解密功能
 *
 * @param String $string        	
 * @param String $operation,
 *        	when set to 'DECODE', try to decode the string
 * @param String $key,
 *        	encrypt key, we can set it when need
 * @param Integer $expiry,
 *        	the timestamp for the code to expire
 */
function abacaEncrypt($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	
	$key = md5 ( $key ? $key : $_SERVER ['Abaca'] ['defaultEncryptKey'] );
	$keya = md5 ( substr ( $key, 0, 16 ) );
	$keyb = md5 ( substr ( $key, 16, 16 ) );
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';
	
	$cryptkey = $keya . md5 ( $keya . $keyc );
	$key_length = strlen ( $cryptkey );
	
	$string = $operation == 'DECODE' ? base64_decode ( substr ( $string, $ckey_length ) ) : sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
	$string_length = strlen ( $string );
	
	$result = '';
	$box = range ( 0, 255 );
	
	$rndkey = array ();
	for($i = 0; $i <= 255; $i ++) {
		$rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
	}
	
	for($j = $i = 0; $i < 256; $i ++) {
		$j = ($j + $box [$i] + $rndkey [$i]) % 256;
		$tmp = $box [$i];
		$box [$i] = $box [$j];
		$box [$j] = $tmp;
	}
	
	for($a = $j = $i = 0; $i < $string_length; $i ++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box [$a]) % 256;
		$tmp = $box [$a];
		$box [$a] = $box [$j];
		$box [$j] = $tmp;
		$result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
	}
	
	if ($operation == 'DECODE') {
		if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
			return substr ( $result, 26 );
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace ( '=', '', base64_encode ( $result ) );
	}
}

