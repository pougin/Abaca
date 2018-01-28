<?php
/**
 * encrypt and decrypt
 * 
 * used for cookie encrypt and some other occasions
 * @author pougin
 * 
 * todo
 * copy this https://github.com/serpro/Android-PHP-Encrypt-Decrypt/blob/master/PHP/MCrypt.php
 * install this on macbook http://sourceforge.net/projects/mcrypt/
 * 		or php-mcrypt module on server
 * manual http://php.net/manual/zh/mcrypt.requirements.php
 * 
 * we may add RSA for other purpose here
 *
 */
class Crypt {
	private static $key = 'default key is just this';
	private static $iv = 'what is iv';
	
	/**
	 * set key and iv
	 * @param string $key
	 * @param string $iv
	 */
	public static function initialize($key = 'hey i am the key', $iv = 'oh i cannot be empty') {
		self::$key = $key;
		self::$iv = $iv;
	}
	
	/**
	 * encrypt Modify this to use mcrypt with AES
	 * 
	 * @param unknown $string        	
	 * @param string $isBinary        	
	 * @return string
	 */
	public static function encrypt($string, $isBinary = false) {
		return abacaEncrypt ( $string, 'ENCODE', self::$key );
	}
	
	/**
	 * decrypt Modify this to use mcrypt with AES
	 * 
	 * @param unknown $string        	
	 * @param string $isBinary        	
	 * @return string
	 */
	public static function decrypt($string) {
		return abacaEncrypt ( $string, 'DECODE', self::$key );
	}
}