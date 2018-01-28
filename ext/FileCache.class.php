<?php
###############################
# 文件缓存，不太常用，放到ext目录
###############################

class FileCache {
	private $dataDir='';
	private $dirLeval=1;
	
	public function __construct($dataDir,$dirLevel){
		$this->dataDir=rtrim($dataDir,'/').'/';
		$this->dirLeval=$dirLevel;
	}
	
	public function get($key){
		$beginTime=Debug::getTime();
		if (defined('DisableCache') && DisableCache==true) {
			$R=false;
		}else{
			$file=$this->locateFile($key);
			if (!file_exists($file)) {
				$R=false;
			}else{
				$data=unserialize(file_get_contents($file));
				if ($data['time']>time()) {
					$R=$data['data'];
				}else{
					$R=false;
					unlink($file);
				}
			}
		}
    	Debug::cache('file',$key, Debug::getTime() - $beginTime, $R, 'get');
    	return $R;
	}
	public function set($key,$value,$expire=600){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			if ($value===false) {
				return false;
			}
			$data=array('time'=>time()+$expire,'data'=>$value);
			return file_put_contents($this->locateFile($key),serialize($data));
		}
	}
	public function delete($key){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			return unlink($this->locateFile($key));
		}
	}
	public function flush(){
		if (defined('DisableCache') && DisableCache==true) {
			return true;
		}else{
			exec('rm '.$this->dataDir.'* -drf');
			return true;
		}
	}
	private function locateFile($key){
		$key=md5($key);
		if ($this->dirLeval==0) {
			$file=$this->dataDir.$key;
		}elseif ($this->dirLeval==1) {
			$file=$this->dataDir.substr($key,0,2).'/'.$key;
		}else{
			$file=$this->dataDir.substr($key,0,2).'/'.substr($key,2,2).'/'.$key;
		}
		return $file;
	}
}
?>