<?php
###############################
# redis，基于phpredis扩展
###############################

class ARedis {
	/**
	 * 新的数据库连接，默认调用丛库，进行读操作
	 *
	 * @param String $type, be master,slave,cluster
	 */
	public static function newRedis($name){
		$CONFIG=$_SERVER['CONFIG']['redis'];
		if (empty($CONFIG[$name])) {
			return false;
		}
		$redis = new Redis();
		
		$redis->connect($CONFIG[$name]['host'], $CONFIG[$name]['port'], 1, NULL, 100); 
		
		return $redis;
	}
}
