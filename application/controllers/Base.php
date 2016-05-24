<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author wangjian
 */
class BaseController extends Yaf_Controller_Abstract {
	public $requestParams;
	public function apiResponse($data, $errno = 0, $errmsg = '') {
		if (! is_array ( $data )) {
			throw new AppException ( AppExceptionCodes::CUSTOM_EXCEPTION );
		}
		$response = array (
				'errno' => $errno,
				'errmsg' => $errmsg,
				'data' => $data 
		);
		Yaf_Dispatcher::getInstance ()->autoRender ( false ); //关闭视图
		echo json_encode ( $response );
	}
	public function init() {
		$this->requestParams = array_merge ( $_GET, $_POST );
		CLogger::notice('request param '.json_encode($this->requestParams));
		if (Conf::isApiUrl() && !self::_checkToken ()) {
			throw new AppException ( AppExceptionCodes::TOKEN_ERROR );
		}
	}
	private function _checkToken() {
		$token = $this->requestParams ['token'];
		unset ( $this->requestParams ['token'] );
		ksort ( $this->requestParams );
		foreach ( $this->requestParams as $k => $v ) {
			$str .= $v;
		}
		$str .= self::SECRET;
		return $token == md5($str);
	}
	
	public function assign($k ,$v) {
		$this->getView()->assign($k, $v);
	}
	
	public function display($tpl){
		$this->getView()->display($tpl);
	}
}

?>
