<?php

/**
 *
 * @author Administrator
 *        
 */
class CaseController extends BaseController {
	
	public function briefAction() {
		$data = array(
			'usage' => '当前已经超过100次任务执行，覆盖率五个产品线，qa和rd在项目中测试使用，包括自测，daily-run，回归测试.',
			'product' => '支持pay2、理财、贷款、信用卡、rc；未来将支持其他产品和系统模块.',
			'effect' => '综合提升了测试效率100%，平均减少回归时间1-2工作日.'
		);
		$this->assign('data', $data);
		$this->display('page/brief.tpl');
	}
	
	public function listAction() {
		$product = $this->requestParams['product'];
		$pn = intval($this->requestParams['pn']);
		$products= self::_checkParam($product);
		
		$caselist = (new CaseModel()) ->getCaseList($product, $pn);
		$data = array(
			'products' => $products['list'],
			'owner' => $products['owner'][array_search($product, $products['list'])],
			'caselist' => $caselist
		);
		if(intval($this->requestParams['api'])  == 1) {
			echo json_encode($data);
		}else {
			$this->assign('data', $data);
			$this->display('page/caselist.tpl');
		}
	}
	
	public function helpAction() {
		$help = Conf::getHelpConf();
		$this->assign('data', $help);
		$this->display('page/help.tpl');
	}
	
	private function _checkParam($product) {
		$prouducts = Conf::getProductConf();
		if(!in_array($product, $prouducts['list'])) {
			throw new AppException(AppExceptionCodes::INVALID_PRODUCT);
		}
		return $prouducts;
	}
}

?>
