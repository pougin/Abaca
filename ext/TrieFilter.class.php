<?php
###############################
# 敏感字过滤
###############################

//调用方式$res = TrieFilter::filter('你妈咪吼吼','bad');

class TrieFilter {
    static private $source = array();

	/**
     * 连接敏感词字典
     * @param string $dic
     */
    static private function loadDic($dic){
        if(!isset(self::$source[$dic])) {
            $dir = AbacaPath.'data/trieFilter/'.$dic.'.dic';
            self::$source[$dic] = trie_filter_load($dir);
        }
    }
	
	/**
	 * 返回制指定文本过滤结果
	 * @param string $text
	 * @param S $dic, can be 'bad'/'ad'
	 * @return string
	 */
	static public function test($text, $dic = 'bad') {
		self::loadDic ( $dic );
		$res = trie_filter_search ( self::$source [$dic], $text );
		if (empty ( $res )) {
			return '';
		} else {
			return substr ( $text, $res [0], $res [1] );
		}
	}
}