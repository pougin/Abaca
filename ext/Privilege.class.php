<?php
###############################
# 权限设置，这个暂时只能用在数据库
###############################
class Privilege {
	protected $db;
	protected $cache;
	protected $isRemote;
	private $privilagePool;
	protected $cacheKeyPrefix = '';

	/**
	 * 使用构造方法调用配置好的数据库和缓存
	 * 注意看看下面的check函数
	 */
	public function __construct($privilage=array(),$required=array(),$errorUri='',$isRemote=false){
		$this->isRemote = $isRemote;
		if (!$this->isRemote) {
			//直接调用配置好的数据库
			$this->db=DB::newMysqli('master');
		}
		//调用配置好的缓存
 		$this->cache=Cache::newCache('memcache');
		## 临时禁用缓存 6.15
		//$this->cache=false;
		//直接检查权限
		if (!$this->check($privilage,$required)) {
			if (!empty($errorUri)) {
				Func::redirect($errorUri);
			}else{
				die('<h1>Privilage Check Failed!<br>Contact your webmaster to handle it.</h1>');
			}
		}
	}

	/**
	 * 根据参数检查用户权限，
	 * @param A $privilage, 需要用户符合的权限， like array(
	 * 	'group'=>array('group1','group2'),
	 * 	'name'=>array('text','image'),
	 * 	'level'=>array('text'=>'read','image'=>'delete')
	 * );//其中'group'/'name'/'level'对应的值也可以是字符串，所有的值都是或关系
	 * @param A $required, 参数格式同上，用户必备的权限，是与的关系，一般只用在要求用户是后天管理员
	 * @return B
	 */
	public function check($privilage=array(),$required=array(),$uid=0){
		$uid=empty($uid)?$_SESSION['uid']:$uid;
		//检查必备条件
		if (! empty ( $required )) {
			if (isset ( $required ['group'] )) {
				$required ['group'] = ! is_array ( $required ['group'] ) ? array ($required ['group'] ) : $required ['group'];
				foreach ( $required ['group'] as $v ) {
					if (! $this->inGroup ( $uid, $v )) {
						return false;
					}
				}
			}
			if (isset ( $required ['name'] )) {
				$required ['name'] = ! is_array ( $required ['name'] ) ? array ($required ['name'] ) : $required ['name'];
				foreach ( $required ['name'] as $v ) {
					if (! $this->hasPrivilegeName ( $uid, $v )) {
						return false;
					}
				}
			}
			if (isset ( $required ['level'] )) {
				foreach ( $required ['level'] as $key=>$v ) {
					if (! $this->testPrivilege ( $uid, $key,$v )) {
						return false;
					}
				}
			}
		}
		//匹配权限
		if (! empty ( $privilage )) {
			if (isset ( $privilage ['group'] )) {
				$privilage ['group'] = ! is_array ( $privilage ['group'] ) ? array ($privilage ['group'] ) : $privilage ['group'];
				foreach ( $privilage ['group'] as $v ) {
					if ( $this->inGroup ( $uid, $v )) {
						return true;
					}
				}
			}
			if (isset ( $privilage ['name'] )) {
				$privilage ['name'] = ! is_array ( $privilage ['name'] ) ? array ($privilage ['name'] ) : $privilage ['name'];
				foreach ( $privilage ['name'] as $v ) {
					if ( $this->hasPrivilegeName ( $uid, $v )) {
						return true;
					}
				}
			}
			if (isset ( $privilage ['level'] )) {
				foreach ( $privilage ['level'] as $key=>$v ) {
					if ( $this->testPrivilege ( $uid, $key,$v )) {
						return true;
					}
				}
			}
			return false;
		}else{
			return true;
		}
	}

	/**
	 * 得到指定用户的权限
	 *
	 * @param Int $uid
	 * @param boolean $refresh 是否刷新权限
	 * return array
	 */
	public function getPrivilege($uid,$refresh=false) {
		if (isset($this->privilagePool[$uid]) && $refresh==false) {
			return $this->privilagePool[$uid];
		}
		$key = $this->cacheKeyPrefix.'Privilege_User_'.$uid;
		$privilege_cache = false;
// 		if (!$refresh)
		## 临时禁用缓存 6.15
		if (!$refresh && false)
			$privilege_cache = $this->cache->get($key);
		//缓存中没有权限信息
		if ($privilege_cache==false){
			if ($this->isRemote){
				//远程获得权限
				$privilege_to_cache = $this->loadPrivilege($uid);
				//将用户权限信息存入缓存
				$this->cache->set($key,serialize($privilege_to_cache),60);
			}else{
				$privilege_to_cache = array();	//预定义存入cache的数组
				$privilege = array();			//预定义用户的个人和所属组的组合权限,数据库获取

				//获取用户个人权限
				$sql = "SELECT privilege FROM `privilege_user` WHERE uid='".intval($uid)."'";
				$privilege_user = $this->db->getColumn($sql);
				if ($privilege_user){
					$privilege[] = unserialize($privilege_user);
				}

				//获取用户所属组，及其组权限
				$sql = "SELECT `g`.`groupid`,`g`.`groupname` from `privilege_groupuser` AS `gu` RIGHT JOIN `privilege_group` AS `g` ON `gu`.`groupid`=`g`.`groupid` where `uid`=".$uid;
				$groups = $this->db->getAll($sql);
				$privilege_to_cache['group'] = array();
				if(is_array($groups)){
					foreach ($groups as $value){
						//用户所属组信息
						$privilege_to_cache['group'][$value['groupid']] = $value['groupname'];
						$sql = "SELECT `privilege` from `privilege_group` where `groupid`=".$value['groupid'];
						$privilege_group = $this->db->getColumn($sql);
						if ($privilege_group){
							$privilege[] = unserialize($privilege_group);
						}
					}
				}
				//组合用户个人和组权限
				$privilege_unit = array();	//临时变量
				foreach ($privilege as $value){
					if (!empty($value)){
						foreach ($value as $name=>$level){
							if(array_key_exists($name,$privilege_unit)){
								$privilege_unit[$name] = array_merge($privilege_unit[$name],$level);
							}else{
								$privilege_unit[$name] = $level;
							}
						}
					}
				}
				foreach ($privilege_unit as $name=>$level){
					$privilege_unit[$name] = array_unique($level);
				}
				$privilege_to_cache['privilege'] = $privilege_unit;
				//将用户权限信息存入缓存
				$this->cache->set($key,serialize($privilege_to_cache),60);
			}
		}
		$privilege_cache = $this->cache->get($key);
		//放入池子
		$this->privilagePool[$uid]=$privilege_cache;
		return $privilege_cache;
	}

	/**
	 * 测试用户是否有此权限等级
	 * @param Int $uid
	 * @param String $privilege
	 * @param String $level
	 * @return boolean
	 */
	public function testPrivilege($uid,$privilege_name,$privilege_level) {
		if($this->inGroup($uid, 'administrator'))	return true;
		$privilege_cache = $this->getPrivilege($uid);
		if ($privilege_cache){
			$privilege = unserialize($privilege_cache);
			if (!empty($privilege['privilege'])){
				foreach ( $privilege['privilege'] as $key=>$value ) {
					if ( strcmp($key,$privilege_name) == 0) {
						if ( in_array($privilege_level, $value ))
							return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * 判断一个用户是否在这个组中
	 * @param Int $uid
	 * @param Int $group
	 * @param boolean isInt	当isInt为true的时候，将group当做groupid，反之，将group当做groupname。
	 * @return Boolean
	 */
	public function inGroup ($uid,$group,$isInt=false) {
		$privilege_cache = $this->getPrivilege($uid);
		if ($privilege_cache){
			$privilege = unserialize($privilege_cache);
			if (array_key_exists('group', $privilege)){
				if (in_array('administrator', $privilege['group']))	return true;	//超级管理员直接返回true
			}
			if ($isInt){
				if (array_key_exists($group, $privilege['group']))	return true;
			}else {
				if (in_array($group, $privilege['group']))	return true;
			}
		}
		return false;
	}

    /**
     * 验证用户的权限里是否有此权限名，而不关心其值是什么
     *
     * @param Int $uid
     * @param String $privilege
     * @return boolean
     */
	public function hasPrivilegeName ($uid,$privilege_name) {
		if($this->inGroup($uid, 'administrator'))	return true;
		$privilege_cache = $this->getPrivilege($uid);
		if ($privilege_cache){
			$privilege = unserialize($privilege_cache);
			if (is_array($privilege['privilege'])){
				if (array_key_exists($privilege_name, $privilege['privilege'])){
					return true;
				}
			}
		}
		return false;
	}

	/**
     * 远程连接权限
     * url manage.xinqing.com
     * @param Int $uid
     */
	public function loadPrivilege ($uid) {
		if(!is_numeric($uid)){
			return false;
		}
		$url = PrivilegeApiUri.'index.php?action=getPrivilege&uid='.$uid.'&md5key='.md5('key');
		$json_str = file_get_contents($url);
		$privilegeArr = json_decode($json_str,true);
		return (isset($privilegeArr['result'])&&$privilegeArr['result']=='ok')?$privilegeArr['data']:false;
	}

}