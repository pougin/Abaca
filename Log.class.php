<?php
class Log {
	protected static $levels = array (
			'critical' => 50,
			'error' => 40,
			'warn' => 30,
			'info' => 20,
			'debug' => 10 
	);
	
	// current log level
	protected static $_level = 0;
	
	// log or echo the message
	protected static $_echo = false;
	
	// set log or echo
	public static function console($echo) {
		self::$_echo = $echo;
	}
	
	// set log level
	public static function level($level) {
		if (isset ( self::$levels [$level] )) {
			self::$_level = self::$levels [$level];
		}
	}
	static public function critical($msg) {
		// TODO 添加手机推送提醒功能 可以是请求一个自己的推送服务的方式
		self::write ( 'critical', $msg );
	}
	static public function error($msg) {
		self::write ( 'error', $msg );
	}
	static public function warn($msg) {
		self::write ( 'warn', $msg );
	}
	static public function info($msg) {
		self::write ( 'info', $msg );
	}
	static public function debug($msg) {
		self::write ( 'debug', $msg );
	}
	
	/**
	 * 记录日志
	 */
	private static function write($level, $msg) {
		if (self::$_echo) {
			echo $msg . "\n";
			return;
		}
		
		if (self::$_level > self::$levels [$level]) {
			return;
		}
		
		if (! is_dir ( LogPath )) {
			return;
		}
		
		$info = date ( 'Y-m-d H:i:s' ) . "\t[{$level}]\t" . $msg . "\n";
		
		$filename = LogPath . 'log' . '.' . date ( 'Ymd' );
		if (! file_exists ( $filename )) {
			touch ( $filename );
			try {
				chmod ( $filename, 0777 );
			} catch ( Exception $e ) {
			}
		}
		$file = fopen ( $filename, 'a' );
		fwrite ( $file, $info );
		fclose ( $file );
	}
}
