<?php
###################################
# 针对简单单个表操作的基础类，继承它可以使代码简洁
# 另外可添加其他方法可针对多表操作
# 此类本身也能对其他表做操作
###################################

class Prototype{
	protected $db;
	protected $sdb;
	protected $cache;
	protected $table;
	
	/**
	 * @param S $table, the table name which this class will act on
	 * @param B $useMemcache, whether enable memcache
	 * @param B $useSlaveDB, whether enable slave db
	 */
	public function __construct($table,$useMemcache=false,$useSlaveDB=false){
		$this->db=DB::newMysqli('master');
		if ($useMemcache) {
			$this->cache=Cache::newCache('memcache');
		}
		if (!$useSlaveDB) {
			$this->sdb=$this->db;
		}else{
			$this->sdb=DB::newMysqli('slave');
		}
		$this->table=$table;
	}
	
	//$info 关联数组，键名就是表的列名
	public function insert($info,$table=''){
		if (empty($table)) {
			$table=$this->table;
		}
		$this->db->insert($table, $info);
		return $this->db->insert_id; 
	}
	
	//$info 关联数组，其中必须包含键$identity
	public function update($info,$identity='id',$table=''){
		if (empty($info[$identity])) {
			return false;
		}
		if (empty($table)) {
			$table=$this->table;
		}
		$how="`{$identity}`='{$info[$identity]}'";
		unset($info[$identity]);
		if (empty($info)) {
			return true;
		}
		return $this->db->update($table,$info,$how);
	}
	
	/**
	 * 读取表里面的若干行
	 * @param S $order, like 'id ASC', note the style of this 
	 * @param S $filter, like 'id>100 AND addtime<19823433', note the style of this
	 * @param S $table, the table name to list
	 * @param B $onlyOne, just first row 
	 */
	public function get($filter='',$order='',$onlyOne=true,$table=''){
		if (empty($table)) {
			$table=$this->table;
		}
		$order=empty($order)?'':" ORDER BY $order";
		$how=empty($filter)?'':" WHERE $filter";
		$limit=$onlyOne?' LIMIT 1':'';
		$sql="SELECT * FROM `$table` $how $order $limit";
		if ($onlyOne) {
			return $this->sdb->getOne($sql);
		}else{
			return $this->sdb->getAll($sql);
		}
	}
	
	/**
	 * 生产内容数据和分页代码，参数提交都需要用get方式，可以针对其他表使用
	 * @param I $p, page index,begin from 1
	 * @param I $countPerPage, count of records of per page
	 * @param S $order, like 'id ASC', note the style of this 
	 * @param S $filter, like 'id>100 AND addtime<19823433', note the style of this
	 * @param S $table, the table name to list
	 * @param S $baseUri 
	 */
	public function select($p=1,$countPerPage=20,$order='',$filter='',$table='',$baseUri=''){
		if (empty($table)) {
			$table=$this->table;
		}
		$p=$p<1?1:$p;
		$order=empty($order)?'':" ORDER BY $order";
		$how=empty($filter)?'':" WHERE $filter";
		$limit="LIMIT ".($p-1)*$countPerPage.','.$countPerPage;
		$sql="SELECT * FROM `$table` $how $order $limit";
		$sqlCount="SELECT COUNT(*) FROM `$table` $how";
		$R=$this->sdb->getOne($sqlCount);
		$page=ceil($R[0]/$countPerPage);
		$R=$this->sdb->getAll($sql);
		if (empty($baseUri)) {
			$baseUri=preg_replace('|[\?&]{1}p=.*[$&]{1}|', '', $_SERVER['REQUEST_URI']);
		}
		$pager=Func::Pager($p, $page, $baseUri,'num');
		return array($R,$pager);
	}
	
	/**
	 * 删除数据，为防错误，这里table必须明确指定
	 * @param S $table, the table name to list
	 * @param S $filter, like 'id>100 AND addtime<19823433', note the style of this
	 */
	public function delete($filter,$table){
		if (empty($table) || empty($filter)){
			return false;
		}
		$sql="DELETE FROM `$table` WHERE $filter";
		return $this->db->query($sql);
	}
}