<?php
###############################
# 缓存，需要有mencache和文件两种
###############################

class Cache {
	//缓存的页面级连接池
	static $connectionPool=array();
	
	/**
	 * 各种缓存方法，主要是内存和文件两种，两种的用法基本保持一致，以便特殊情况下的切换
	 *
	 * @param String $type, can be memcache,file,memcached
	 * @param String $cacheDir, only for file cache
	 * @param String $dirLevel, cache data files are hashed as md5, 1 to have a4/datafile and 2 to have a4/d6/datafile
	 * @return 缓存实例
	 */
	static public function newCache($type='memcache',$cacheDir='',$dirLevel=1){
		$CONFIG=$_SERVER['CONFIG'];
		if (empty($CONFIG['cache'][$type])) {
			return false;
		}
		switch ($type){
			//添加文件缓存功能
			case 'file':
				if($cacheDir==''){
					$cacheDir=CachePath;
				}
				$cache=new FileCache($cacheDir,$dirLevel);
				return $cache;
			//默认都是memcache
			default:
				$key=$CONFIG['cache'][$type]['host'].':'.$CONFIG['cache'][$type]['port'];
				if (isset(self::$connectionPool[$key])) {
					return self::$connectionPool[$key];
				}else{
					$cache = new MyMemcache;
					$cache->connect($CONFIG['cache'][$type]['host'],$CONFIG['cache'][$type]['port']);
					self::$connectionPool[$key]=$cache;
					return $cache;
				}
		}
		return false;
	}
}

# 我添加这个缓存类仅仅是提供一个禁用缓存的功能
class MyMemcache extends Memcache {
	public function get($key){
		$beginTime=Debug::getTime();
		if (defined('DisableCache') && DisableCache==true) {
			$R=false;
		}else{
			$R=parent::get($key);
		}
    	Debug::cache('default',$key, Debug::getTime() - $beginTime, $R, 'get');
    	return $R;
	}
	//这里set调整了参数顺序，用的时候要注意
	public function set($key,$value,$expire=600,$flag=0){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			return parent::set($key,$value,$flag,$expire);
		}
	}
	public function delete($key,$timeout=0){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			return parent::delete($key,$timeout);
		}
	}
	public function flush(){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			return parent::flush();
		}
	}
}
?>