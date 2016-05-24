<?php
/**
 * 功能：
 * @date 2015-9-18 下午5:11:21
 * @author wangjian
 * @version 1.0.0
 */
class CLogger {
	const LOG_LEVEL_FATAL = 0x01;
	const LOG_LEVEL_WARNING = 0x02;
	const LOG_LEVEL_NOTICE = 0x04;
	const LOG_LEVEL_TRACE = 0x08;
	const LOG_LEVEL_DEBUG = 0x10;

	const LOG_FILE_NAME = "platform.log";
	public static $arrLogLevels = array (
			self::LOG_LEVEL_FATAL => 'FATAL',
			self::LOG_LEVEL_WARNING => 'WARNING',
			self::LOG_LEVEL_NOTICE => 'NOTICE',
			self::LOG_LEVEL_TRACE => 'TRACE',
			self::LOG_LEVEL_DEBUG => 'DEBUG'
	);

	protected $intLevel;
	protected $strLogFile;
	protected $arrSelfLogFiles;
	protected $intLogId;
	protected $strCurApp;
	protected $intMaxFileSize;
	protected $strClassName;

	private static $instance = null;

	private function __construct($arrLogConfig) {
		$this->intLevel = intval ( $arrLogConfig ['level'] );
		$this->strLogFile = $arrLogConfig ['logPath'] .  "/" . self::LOG_FILE_NAME;
		if(!is_dir( $arrLogConfig ['logPath'] )) {
			mkdir( $arrLogConfig ['logPath'] );
		}
		$this->arrSelfLogFiles = empty($arrLogConfig ['selfLogPath']) ? "" : $arrLogConfig ['selfLogPath'];
		$this->intLogId = 0;
		$this->intMaxFileSize = intval($arrLogConfig ['maxSize']);
	}

	public static function getInstance() {
		$logConf=Conf::getLogConf();
		file_put_contents('/home/rong/logconf', var_export($logConf, true));
		if (self::$instance === null) {
			self::$instance = new CLogger ( $logConf );
		}
		return self::$instance;
	}

	public function writeLog($intLevel, $str, $errno = 0, $arrArgs = null, $depth = 0) {
		if ($intLevel > $this->intLevel || ! isset ( self::$arrLogLevels [$intLevel] )) {
			return;
		}

		$strLevel = self::$arrLogLevels [$intLevel];
		$strLogFile = $this->strLogFile;
		if (($intLevel & self::LOG_LEVEL_WARNING) || ($intLevel & self::LOG_LEVEL_FATAL)) {
			$strLogFile .= '.wf';
		}

		$trace = debug_backtrace ();
		if ($depth >= count ( $trace )) {
			$depth = count ( $trace ) - 1;
		}
		$file = $trace [$depth] ['file'];
		$line = $trace [$depth] ['line'];

		$strArgs = '';
		if (is_array ( $arrArgs ) && count ( $arrArgs ) > 0) {
			foreach ( $arrArgs as $key => $value ) {
				$strArgs .= "{$key}[$value] ";
			}
		}

		$str = sprintf ( "%s: %s [%s:%d] errno[%d] ip[%s] logId[%u] app[%s] uri[%s] className[%s] %s%s\n", $strLevel, date ( 'm-d H:i:s:', time () ), $file, $line, $errno, self::getClientIP (), $this->intLogId, $this->strCurApp, isset ( $_SERVER ['REQUEST_URI'] ) ? $_SERVER ['REQUEST_URI'] : '', $this->strClassName, $strArgs, $str );
		if ($this->intMaxFileSize > 0) {
			clearstatcache ();
			if(file_exists($strLogFile)) {
				$arrFileStats = stat ( $strLogFile );
				if (is_array ( $arrFileStats ) && floatval ( $arrFileStats ['size'] ) > $this->intMaxFileSize) {
					unlink ( $strLogFile );
				}
			}
		}
		return file_put_contents ( $strLogFile, $str, FILE_APPEND );
	}

	public function writeSelfLog($strKey, $str, $arrArgs = null) {
		if (isset ( $this->arrSelfLogFiles [$strKey] )) {
			$strLogFile = $this->arrSelfLogFiles [$strKey];
		} else {
			return;
		}

		$strArgs = '';
		if (is_array ( $arrArgs ) && count ( $arrArgs ) > 0) {
			foreach ( $arrArgs as $key => $value ) {
				$strArgs .= "{$key}[$value] ";
			}
		}

		$str = sprintf ( "%s: %s ip[%s] logId[%u] uri[%s] %s%s\n", $strKey, date ( 'm-d H:i:s:', time () ), self::getClientIP (), $this->intLogId, isset ( $_SERVER ['REQUEST_URI'] ) ? $_SERVER ['REQUEST_URI'] : '', $strArgs, $str );
		if ($this->intMaxFileSize > 0) {
			clearstatcache ();
			if(file_exists($strLogFile)) {
				$arrFileStats = stat ( $strLogFile );
				if (is_array ( $arrFileStats ) && floatval ( $arrFileStats ['size'] ) > $this->intMaxFileSize) {
					unlink ( $strLogFile );
				}
			}
		}
		return file_put_contents ( $strLogFile, $str, FILE_APPEND );
	}

	public static function selflog($strKey, $str, $arrArgs = null) {
		return CLogger::getInstance ()->writeSelfLog ( $strKey, $str, $arrArgs );
	}

	public static function debug($str, $errno = 0, $arrArgs = null, $depth = 0) {
		return CLogger::getInstance ()->writeLog ( self::LOG_LEVEL_DEBUG, $str, $errno, $arrArgs, $depth + 1 );
	}

	public static function trace($str, $errno = 0, $arrArgs = null, $depth = 0) {
		return CLogger::getInstance ()->writeLog ( self::LOG_LEVEL_TRACE, $str, $errno, $arrArgs, $depth + 1 );
	}

	public static function notice($str, $errno = 0, $arrArgs = null, $depth = 0) {
		return CLogger::getInstance ()->writeLog ( self::LOG_LEVEL_NOTICE, $str, $errno, $arrArgs, $depth + 1 );
	}

	public static function warning($str, $errno = 0, $arrArgs = null, $depth = 0) {
		return CLogger::getInstance ()->writeLog ( self::LOG_LEVEL_WARNING, $str, $errno, $arrArgs, $depth + 1 );
	}

	public static function fatal($str, $errno = 0, $arrArgs = null, $depth = 0) {
		return CLogger::getInstance ()->writeLog ( self::LOG_LEVEL_FATAL, $str, $errno, $arrArgs, $depth + 1 );
	}

	public static function setLogId($intLogId) {
		CLogger::getInstance ()->intLogId = $intLogId;
	}

	public static function setClassName($strClassName) {
		CLogger::getInstance ()->strClassName = $strClassName;
	}

	private static function getClientIP() {
		if (isset ( $_SERVER ['HTTP_X_FORWARDED_FOR'] )) {
			$ip = $_SERVER ['HTTP_X_FORWARDED_FOR'];
		} elseif (isset ( $_SERVER ['HTTP_CLIENTIP'] )) {
			$ip = $_SERVER ['HTTP_CLIENTIP'];
		} elseif (isset ( $_SERVER ['REMOTE_ADDR'] )) {
			$ip = $_SERVER ['REMOTE_ADDR'];
		} elseif (getenv ( 'HTTP_X_FORWARDED_FOR' )) {
			$ip = getenv ( 'HTTP_X_FORWARDED_FOR' );
		} elseif (getenv ( 'HTTP_CLIENTIP' )) {
			$ip = getenv ( 'HTTP_CLIENTIP' );
		} elseif (getenv ( 'REMOTE_ADDR' )) {
			$ip = getenv ( 'REMOTE_ADDR' );
		} else {
			$ip = '127.0.0.1';
		}

		$pos = strpos ( $ip, ',' );
		if ($pos > 0) {
			$ip = substr ( $ip, 0, $pos );
		}
		return trim ( $ip );
	}

}
?>
