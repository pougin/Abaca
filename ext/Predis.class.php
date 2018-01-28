<?php
/**
 * Redis类，封装Prdis
 */
require AbacaPath.'./predis/predis_0.7.2.phar';
//require AbacaPath.'ext/predis/Predis/Autoloader.php';
//Predis\Autoloader::register();

class Predis{
	private static $connections;
	private static $redisInstance;
	
	/* public function __construct(){
		$this->connections=array('master'=>array(),'slave'=>array());
		$this->redisInstance=array_keys($_SERVER['CONFIG']['redis']['master']);
	} */
	
	/**
	 * redis连接池，如果指定连接存在，返回该连接，否则创建新连接
	 * 
	 * @param S $masterOrSlave 主redis或者丛redis标记，取值为‘master’或者‘slave’
	 * @param S $connectionAlias 连接别名，对应redis实例
	 * @return resource 返回一个predis连接实例
	 */
	public static function pool($masterOrSlave, $connectionAlias){
		if(!isset(self::$redisInstance)){
			self::$redisInstance	= array_keys($_SERVER['CONFIG']['redis']['master']);
		}
		if(!isset(self::$connections)){
			self::$connections	= array('master'=>array(),'slave'=>array());
		}
		
		## 检查参数是否合法
		if(!in_array($masterOrSlave,array('master','slave'))){
			throw new \InvalidArgumentException("Invalid arguments: '$masterOrSlave'");
		}
		if(!in_array($connectionAlias,self::$redisInstance)){
			throw new \InvalidArgumentException("Invalid connection alias: '$connectionAlias'");
		}
		$config	= $_SERVER['CONFIG']['redis'][$masterOrSlave][$connectionAlias];
		if(!isset($GLOBALS['argv']))
		{
			$conn =  new Predis\Client($config);
			$conn->connect ();
			return $conn;
		}
		if(!isset(self::$connections[$masterOrSlave][$connectionAlias]) ||
				!self::$connections[$masterOrSlave][$connectionAlias] instanceof Predis\Client){
			$conn =  new Predis\Client($config);
			try {
				$conn->connect ();
			} catch ( Predis\Network\ConnectionException $e ) {
				$log=date('Y-m-d h:i:s').' Trace '.":\n" . $e->getTraceAsString()."\n";
				$log.='Redis Server: '.$config['host'].' '.$config['port'] . "\n";
				Debug::write($log,'redis');
				abacaShow("syserror.php");
				die();
			}
			self::$connections[$masterOrSlave][$connectionAlias]	= $conn;
		}
		$conn = self::$connections[$masterOrSlave][$connectionAlias];
		
		
		return $conn;
	}

	public static function printAllConnectionStatus(){
		foreach (self::$connections['master'] as $alias=>$connection){
			if($connection instanceof Predis\Client){
				echo $alias.' is connectted!<br />';
			}
		}
		foreach (self::$connections['slave'] as $alias=>$connection){
			if($connection instanceof IConnection){
				echo $alias.' is connectted!<br />';
			}
		}
	}
}