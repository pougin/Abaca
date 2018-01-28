<?php
##################################################
# 在不开启Debug的情况下，加载这个小的Debug类
# 仍然对日志提供支持
##################################################

class Debug {
	public static $open=false;
	private static $startTime=0;
	public static function start(){
		self::$startTime=microtime(true);
	}
	public static function getTime(){
		return microtime(true)-self::$startTime;
	}
	public static function getInstance(){}
	public static function log($a='',$b='',$c=''){}
	public static function db($a='',$b='',$c='',$d='',$e=''){}
	public static function cache($a='',$b='',$c='',$d='',$e=''){}
	public static function time($a='',$b=''){}
	public static function fb(){}
	public static function show(){
		if (self::getTime()>SlowPageTime) {
			$log=date('Y-m-d h:i:s').' Slow page '.":\n";
			$log.= "File：" . $_SERVER['SCRIPT_FILENAME'] . "\n";
			$log.= "GET：" . var_export($_GET,true) . "\n";
			$log.= "Post：" . var_export($_POST,true) . "\n";
			$log.= "Argv：" . var_export($_SERVER['argv'],true) . "\n";
			$log.='Time Spent: '.self::getTime()."\n\n";
			self::write($log,'slow');
		}		
	}
	static public function write($log,$type='mysql'){
		if (!is_dir(LogPath)) {
			return ;
		}
		$filename=LogPath.$type.'.'.date('Ymd');
		$file=fopen($filename,'a');
		fwrite($file,$log);
		fclose($file);
	}
}
?>