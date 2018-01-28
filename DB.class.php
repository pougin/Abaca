<?php
###############################
# 数据库操作，基于mysqli
###############################

class DB {
	/**
	 * 新的数据库连接，默认调用丛库，进行读操作
	 *
	 * @param String $type, be master,slave,cluster
	 */
	public static function newMysqli($type='slave'){
		$CONFIG=$_SERVER['CONFIG'];
		if (empty($CONFIG['mysql'][$type])) {
			return false;
		}
		$mysqli = new DBmysqli($CONFIG['mysql'][$type]['host'],$CONFIG['mysql'][$type]['user'],$CONFIG['mysql'][$type]['password'],$CONFIG['mysql'][$type]['database'],$CONFIG['mysql'][$type]['port']);
// 		$mysqli->set_charset('utf8');
// 		$mysqli->query('SET NAMES utf8');
		$mysqli->query('SET NAMES utf8mb4');
		return $mysqli;
	}
}

class DBmysqli extends mysqli {
	private $host;
	private $database;
	
	public function __construct($host,$user,$password,$database,$port){
		$this->host=$host;
		$this->database=$database;
		return parent::__construct($host,$user,$password,$database,$port);
	}
	/**
	 * 读取查询的全部信息为关联数组
	 *
	 * @param String $sql
	 * @return Array 2 dimensions
	 */
	public function getAll($sql){
		$beginTime=Debug::getTime();
		$R=$this->query($sql);
		$All=array();
		if ($R->num_rows>0) {
			while ($v=$R->fetch_assoc()){
				$All[]=$v;
			}
		}
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $All);
		return $All;
	}
	
	/**
	 * 返回第一条结果
	 *
	 * @param String $sql
	 * @return Array
	 */
	public function getOne($sql){
		$beginTime=Debug::getTime();
		$R=$this->query($sql);
		$result=array();
		if ($R->num_rows>0) {
			$v=$R->fetch_assoc();
			$result=$v;
		}
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $result);
		return $result;
	}
	
	/**
	 * 返回查询结果第一行的第一列
	 *
	 * @param String $sql
	 * @return String
	 */
	public function getColumn($sql){
		$beginTime=Debug::getTime();
		$R=$this->query($sql);
		$result=false;
		if ($R->num_rows>0) {
			$v=$R->fetch_row();
			$result=$v[0];
		}
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $result);
		return $result;
	}

	/**
	 * 向表中插入批量数据
	 *
	 * @param String $table
	 * @param Array $data,2 dimensions Array, or 1 dimension
	 */
	public function insert($table,$data){
		$beginTime=Debug::getTime();
		if (!is_array($data)) {
			return false;
		}
		if (!isset($data[0]) || !is_array($data[0])) {
			$data=array(0=>$data);
		}
		$row=$data[0];
		$column=array_keys($row);
		$column=$this->makeColumnList($column);
		$rows=$this->makeRowString($data);
		$sql="INSERT IGNORE INTO `{$table}` ({$column}) VALUES {$rows}";
		$result=$this->query($sql);
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $result);
		return $result;
	}

	/**
	 * 向表中插入批量替换数据 $data = ('column1'=>'value1','column2'=>'value2')
	 *
	 * @param String $table
	 * @param Array $data,2 dimensions Array, or 1 dimension 
	 */
	public function replace($table,$data){
		$beginTime=Debug::getTime();
		if (!is_array($data)) {
			return false;
		}
		if (!isset($data[0]) || !is_array($data[0])) {
			$data=array(0=>$data);
		}
		$row=$data[0];
		$column=array_keys($row);
		$column=$this->makeColumnList($column);
		$rows=$this->makeRowString($data);
		$sql="REPLACE INTO `{$table}` ({$column}) VALUES {$rows}";
		$result=$this->query($sql);
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $result);
		return $result;
	}
	
	/**
	 * 更新表中的数据
	 *
	 * @param String $table
	 * @param Array $data
	 * @param String $how，不包含where，可以带limit
	 */
	public function update($table,$data,$how){
		$beginTime=Debug::getTime();
		$string='';
		foreach ($data as $k=>$v) {
			if(substr($v, 0, strlen($k)) == $k && in_array(substr($v, strlen($k),1),array('-','+'))){
				$string.="`$k`=$v,";
			}else{
				$string.="`$k`='$v',";
			}
		}
		$string=substr($string,0,-1);
		$sql="UPDATE `$table` SET $string WHERE $how";
		$result=$this->query($sql);
		Debug::db($this->host,$this->database, $sql, Debug::getTime() - $beginTime, $result);
		return $result;
	}

	/**
	 * 添加这个主要是用于处理错误发生时候的情况，便于调试
	 *
	 * @param String $sql
	 */
	public function query($sql){
		$R=parent::query($sql);
		if ($R===false){
			$e=new Exception();
			$log=date('Y-m-d h:i:s').' Trace '.":\n" . $e->getTraceAsString()."\n";
			$log.='Mysql Server: '.$this->host.' '.$this->database . "\n";
			$log.="Mysql Error: ".$this->errno." ".$this->error."\n";
			$log.="SQL: ".$sql."\n\n";
			Debug::write($log);
			if (Debug::$open) {
				die(nl2br($log));
			}
		}
		return $R;
	}
	
	/**
	 * 返回查询记录数
	 *
	 * @return int
	 */
	public function getRowsNum($sql){
		$R = $this->query($sql);
		return $R->num_rows;
	}	
	
	/**
	 * 把列列表数组做成字符串
	 *
	 * @param Array $array
	 * @return String
	 */
	private function makeColumnList($array,$quota='`'){
		foreach ($array as & $v) {
			$v=$quota.$v.$quota;
		}
		unset($v);
		return implode(',',$array);
	}

	/**
	 * 把数据二维数组做成插入用的字符串
	 *
	 * @param Array $data
	 * @return String
	 */
	private function makeRowString($data){
		$all='';
		foreach ($data as $list) {
			$row='';
			foreach ($list as $v) {
				//添加对'和\的处理     这个地方不能如此处理，否则出错
				//$v=str_replace(array("'"),array("\\'"),$v);
				$row.="'$v',";
			}
			$row=rtrim($row,',');
			$all.="($row),";
		}
		return rtrim($all,',');
	}
}
?>