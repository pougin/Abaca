<?php

/**
 * FTP - access to an FTP server.
 * http://code.google.com/p/ftp-php/
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2008 David Grudl
 * @license    New BSD License
 * @link       http://phpfashion.com/
 * @version    1.0
 */
class Ftp {
	/**
	 * #@+ FTP constant alias
	 */
	const ASCII = FTP_ASCII;
	const TEXT = FTP_TEXT;
	const BINARY = FTP_BINARY;
	const IMAGE = FTP_IMAGE;
	const TIMEOUT_SEC = FTP_TIMEOUT_SEC;
	const AUTOSEEK = FTP_AUTOSEEK;
	const AUTORESUME = FTP_AUTORESUME;
	const FAILED = FTP_FAILED;
	const FINISHED = FTP_FINISHED;
	const MOREDATA = FTP_MOREDATA;
	/**
	 * #@-
	 */
	private static $aliases = array (
			'sslconnect' => 'ssl_connect',
			'getoption' => 'get_option',
			'setoption' => 'set_option',
			'nbcontinue' => 'nb_continue',
			'nbfget' => 'nb_fget',
			'nbfput' => 'nb_fput',
			'nbget' => 'nb_get',
			'nbput' => 'nb_put' 
	);
	
	/**
	 * @var resource
	 */
	private $resource;
	
	/**
	 * @var array
	 */
	private $state;
	
	/**
	 * @var string
	 */
	private $errorMsg;
	
	/**
	 *
	 * @param
	 *        	string URL ftp://...
	 */
	public function __construct($url = NULL) {
		if (! extension_loaded ( 'ftp' )) {
			throw new /*\*/Exception ( "PHP extension FTP is not loaded." );
		}
		if ($url) {
			$parts = parse_url ( $url );
			$this->connect ( $parts ['host'], empty ( $parts ['port'] ) ? NULL : ( int ) $parts ['port'] );
			$this->login ( $parts ['user'], $parts ['pass'] );
			$this->pasv ( TRUE );
			if (isset ( $parts ['path'] )) {
				$this->chdir ( $parts ['path'] );
			}
		}
	}
	
	/**
	 * Magic method (do not call directly).
	 * 
	 * @param
	 *        	string method name
	 * @param
	 *        	array arguments
	 * @return mixed
	 * @throws Exception
	 * @throws FtpException
	 */
	public function __call($name, $args) {
		$name = strtolower ( $name );
		$silent = strncmp ( $name, 'try', 3 ) === 0;
		$func = $silent ? substr ( $name, 3 ) : $name;
		$func = 'ftp_' . (isset ( self::$aliases [$func] ) ? self::$aliases [$func] : $func);
		
		if (! function_exists ( $func )) {
			throw new Exception ( "Call to undefined method Ftp::$name()." );
		}
		
		$this->errorMsg = NULL;
		set_error_handler ( array (
				$this,
				'_errorHandler' 
		) );
		
		if ($func === 'ftp_connect' || $func === 'ftp_ssl_connect') {
			$this->state = array (
					$name => $args 
			);
			$this->resource = call_user_func_array ( $func, $args );
			$res = NULL;
		} elseif (! is_resource ( $this->resource )) {
			restore_error_handler ();
			throw new FtpException ( "Not connected to FTP server. Call connect() or ssl_connect() first." );
		} else {
			if ($func === 'ftp_login' || $func === 'ftp_pasv') {
				$this->state [$name] = $args;
			}
			
			array_unshift ( $args, $this->resource );
			$res = call_user_func_array ( $func, $args );
			
			if ($func === 'ftp_chdir' || $func === 'ftp_cdup') {
				$this->state ['chdir'] = array (
						ftp_pwd ( $this->resource ) 
				);
			}
		}
		
		restore_error_handler ();
		if (! $silent && $this->errorMsg !== NULL) {
			if (ini_get ( 'html_errors' )) {
				$this->errorMsg = html_entity_decode ( strip_tags ( $this->errorMsg ) );
			}
			
			if (($a = strpos ( $this->errorMsg, ': ' )) !== FALSE) {
				$this->errorMsg = substr ( $this->errorMsg, $a + 2 );
			}
			
			throw new FtpException ( $this->errorMsg );
		}
		
		return $res;
	}
	
	/**
	 * Internal error handler.
	 * Do not call directly.
	 */
	public function _errorHandler($code, $message) {
		$this->errorMsg = $message;
	}
	
	/**
	 * Reconnects to FTP server.
	 * 
	 * @return void
	 */
	public function reconnect() {
		@ftp_close ( $this->resource ); // intentionally @
		foreach ( $this->state as $name => $args ) {
			call_user_func_array ( array (
					$this,
					$name 
			), $args );
		}
	}
	
	/**
	 * Checks if file or directory exists.
	 * 
	 * @param
	 *        	string
	 * @return bool
	 */
	public function fileExists($file) {
		return is_array ( $this->nlist ( $file ) );
	}
	
	/**
	 * Checks if directory exists.
	 * 
	 * @param
	 *        	string
	 * @return bool
	 */
	public function isDir($dir) {
		$current = $this->pwd ();
		try {
			$this->chdir ( $dir );
		} catch ( FtpException $e ) {
		}
		$this->chdir ( $current );
		return empty ( $e );
	}
	
	/**
	 * Recursive creates directories.
	 * 
	 * @param
	 *        	string
	 * @return void
	 */
	public function mkDirRecursive($dir) {
		$parts = explode ( '/', $dir );
		$path = '';
		while ( ! empty ( $parts ) ) {
			$path .= array_shift ( $parts );
			try {
				if ($path !== '')
					$this->mkdir ( $path );
			} catch ( FtpException $e ) {
				if (! $this->isDir ( $path )) {
					throw new FtpException ( "Cannot create directory '$path'." );
				}
			}
			$path .= '/';
		}
	}
	
	/**
	 * Recursive deletes path.
	 * 
	 * @param
	 *        	string
	 * @return void
	 */
	public function deleteRecursive($path) {
		if (! $this->tryDelete ( $path )) {
			foreach ( ( array ) $this->nlist ( $path ) as $file ) {
				if ($file !== '.' && $file !== '..') {
					$this->deleteRecursive ( strpos ( $file, '/' ) === FALSE ? "$path/$file" : $file );
				}
			}
			$this->rmdir ( $path );
		}
	}
	
	/**
	 * Generate a media path to store
	 * 
	 * @author pougin
	 * 
	 * @param S $site,
	 *        	the application name current running, for dividing different apps
	 * @param S $type,
	 *        	i for image, v for video, o for others
	 * @param I $hashLevel,
	 *        	0 as none, 1 as 0-f, 2 as 00-ff, 3 as 0/00-f/ff
	 * @param S $date
	 *        	Ymd type date string
	 * @return string
	 */
	public static function makeMediaPath($site, $type = 'i', $hashLevel = 1, $date = '') {
		$site = empty ( $site ) ? 'common' : $site;
		$type = $type != 'i' && $type != 'v' ? 'o' : $type;
		$date = empty ( $date ) ? date ( 'Ymd' ) : $date;
		
		$path = "/$type/$site/$date";
		
		$md5 = md5 ( mt_rand () );
		if ($hashLevel == 0) {
			// nothing to do
		}elseif ($hashLevel == 1) {
			$path .= '/' . substr ( $md5, 0, 1 );
		} elseif ($hashLevel == 2) {
			$path .= '/' . substr ( $md5, 0, 2 );
		} elseif ($hashLevel == 3) {
			$path .= '/' . substr ( $md5, 0, 1 ) . '/' . substr ( $md5, 1, 2 );
		}
		
		return $path;
	}
	
	/**
	 * Recursive upload, if detected, all files starts with .
	 * will be ignored
	 * 注意DIRECTORY_SEPARATOR，是根据本地环境来定的
	 * 
	 * @param $exclude, 不在上传列表中的文件，写绝对地址        	
	 */
	public function putRecursive($local, $remote, $exclude = array()) {
		if (in_array ( $local, $exclude ) || in_array ( rtrim ( $local, ' /\\' ) . '/', $exclude )) {
			return;
		}
		if (is_dir ( $local )) {
			if (! $this->isDir ( $remote )) {
				$this->mkDirRecursive ( $remote );
			}
			$list = scandir ( $local );
			foreach ( $list as $v ) {
				if ($v [0] != '.') {
					$this->putRecursive ( rtrim ( $local, ' /\\' ) . '/' . $v, rtrim ( $remote, '/' ) . '/' . $v, $exclude );
				}
			}
		} else {
			$this->put ( $remote, $local, FTP_BINARY );
		}
	}
}
class FtpException extends Exception {
}
