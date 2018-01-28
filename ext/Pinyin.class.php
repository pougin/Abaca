<?php
####################################
# 汉字转拼音的类，只为utf-8提供
# 现在只提供读取第一个音的功能
####################################
class Pinyin {
	private $table=array();
	
	public function __construct(){
		$tableFile=AbacaPath.'data/pinyin/table.txt';
		$table=file($tableFile,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($table as $v) {
			list($key,$value)=explode(':|',rtrim($v));
			$this->table[$key]=explode('|',$value);
		}
	}
	
	/**
	 * 汉字转化为拼音 返回驼峰式 中国=>ZhongGuo
	 *
	 * @param String $word
	 * @param Boolean $onlyFirst 是否只要第一个字母
	 */
	public function toPinyin($word,$onlyFirst=false){
		$return='';
		for ($i=0,$len=strlen($word);$i<$len;$i++){
			$charecter=ord($word[$i])>128?($word[$i].$word[++$i].$word[++$i]):$word[$i];
			if (isset($this->table[$charecter])) {
				$pinyin=$this->table[$charecter][0];
				if ($onlyFirst) {
					$return.=strtoupper(substr($pinyin,0,1));
				}else{
					$return.=strtoupper(substr($pinyin,0,1)).substr($pinyin,1);
				}
			}
		}
		return $return;
	}
}
?>