<?php

/**
 *
 * @author Administrator
 *        
 */
class AppExceptionCodes {
	const PARAM_ERROR 			= 1;
	const INVALID_PRODUCT		= 8;
	const CUSTOM_EXCEPTION		= 10;
	
	const INVALID_TASKID		= 12;
	const TOKEN_ERROR 			= 100;
	
	const ADD_TASK_FAILED	= 101;
	public static $errMsg = array (
			self::PARAM_ERROR => "参数错误",
			self::INVALID_PRODUCT => "产品号非法",
			self::CUSTOM_EXCEPTION => "通用错误",
			self::INVALID_TASKID => "任务ID非法",
			self::TOKEN_ERROR => "token错误",
			slef::ADD_TASK_FAILED => "提交任务失败"
	);
}

?>
