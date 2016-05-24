<?php

/**
 *
 * @author Administrator
 *        
 */
class AppException extends Exception {
	private $errno;
	private $errstr;
	public function __construct($errno, $arg = null, $level = 'warning', $errstr = "") {
		$this->errno = $errno;
		$this->arg = $arg;
		$errstr = ($errstr != "") ? $errstr : @AppExceptionCodes::$errMsg [$errno];
		if ($errstr == null) {
			$errstr = 'Errno msg not found . no:' . $errno;
		}
		$this->errstr = $errstr;
		
		$stackTrace = $this->getTrace ();
		$class = @$stackTrace [0] ['class'];
		$type = @$stackTrace [0] ['type'];
		$function = $stackTrace [0] ['function'];
		$file = $this->file;
		$line = $this->line;
		if ($class != null) {
			$function = "$class$type$function";
		}
		CLogger::$level ( "$errstr at [$function at $file:$line]", $errno, $arg );
		parent::__construct ( $errstr, $errno );
	}
	public function getErrNo() {
		return $this->errno;
	}
	public function getErrStr() {
		return $this->errstr;
	}
	public function getDebugInfo() {
		return "报错文件：{$this->file}:{$this->line}";
	}
}

?>
