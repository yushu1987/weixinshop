<?php

/**
 *
 * @author Administrator
 *        
 */
class CoverageController extends BaseController {
	
	 public function infoAction() {
		$pn = intval($this->requestParams['pn']);
		$api = intval($this->requestParams['api']);
		$objCover = new CoverModel();
		$objTask = new TaskModel();
		$coverList = $objCover->getCoverageList($pn);
		foreach($coverList as &$item) {
			$ret = $objTask->getTaskInfoById($item['taskid']);
			if($ret) {
				$item ['product'] = $ret ['product'];
				$item ['owner']  = $ret ['owner'];
			}
			$item ['report'] = json_decode($item['report'] , true);
		}
		if($api == 1) {
			echo json_encode($coverList);
		}else {
	                $this->assign('data', $coverList);
        	        $this->display('page/coverage.tpl');
		}
        }

	public function listAction() {
		$pn = intval($this->requestParams['pn']);
		$objCover = new CoverModel() ;
		$objTask =  new TaskModel();
		$coverList = $objCover->getCoverageList($pn);
		foreach($coverList as &$item) {
			$ret = $objTask->getTaskInfoById($item['taskid']);
			if($ret) {
				$item ['product'] = $ret ['product'];
				$item ['owner'] = $ret['owner'];
			}
			$item ['report'] = json_decode($item['report'], true);
		}
		echo json_encode($coverList);
	}
	
	public function detailAction() {
		$product = $this->requestParams['product'];
		$taskId = intval($this->requestParams['taskId']);
		if(empty($product) || empty($taskId)) {
			throw new AppException(AppExceptionCodes::PARAM_ERROR);
		}
		$objTask = new TaskModel();
		$taskInfo = $objTask->getTaskInfoById($taskId);
		if(empty($taskInfo)) {
			throw new AppException(AppExceptionCodes::INVALID_TASKID);
		}
		$resultPath = '/home/rong/autoTest/taskspace';
		$taskId=  7;
		$product = 'daikuan';
		$detailFile = $resultPath . '/'. $product . '/' . $taskId . '/result.log';
		if(!file_exists($detailFile)) {
			echo "";
		}else {
			$detail = [];
			eval('$detailArr = '.file_get_contents($detailFile). ';');
			foreach($detailArr as $k => $v) {
				$v['summary']['filename'] = $k;
				$detail[] = $v['summary'];
			}
			echo json_encode($detail);
		}
	}

	public function showAction() {
		$filename = $this->requestParams['filename'];
		$product = $this->requestParams['product'];
		$taskId = $this->requestParams['taskId'];
		
		$data = array();
	     	$resultPath = '/home/rong/autoTest/taskspace';
        	$taskId=  7;
	        $product = 'daikuan';
       		$detailFile = $resultPath . '/'. $product . '/' . $taskId . '/result.log'; 
         
 	       if(!is_file($detailFile)) {
        	    return false;
       		}
		eval('$detailArr = '.file_get_contents($detailFile). ';');
	        $noteLine = Util::count_valid_code($filename);
        	if(!empty($noteLine)) {
			$data['note_array'] = $detailArr[$filename]['detail'] + $noteLine['note_array'];
		}
		$cnt_arr = file($filename);
            	for($i=0; $i<count($cnt_arr); $i++) {
                	$cnt_arr[$i] = str_replace("\n", '', $cnt_arr[$i]);
                	$this->_judgeCovered($data['note_array'], $cnt_arr, $i+1);
            	}
		$data ['file'] = $filename;
		$data ['code'] = $cnt_arr;
		CLogger::warning('data is '.var_export($data, true));
	//	echo json_encode($data);
		$this->assign('data', $data);
		$this->display('page/detail.tpl');
	}

	private function _judgeCovered( &$note_arr, $code_arr, $line_no) {
        	if(intval($note_arr[$line_no]) == 1) {
            		if(strpos($code_arr[$line_no-2], " function ") || strpos($code_arr[$line_no-2], "if") || strpos($code_arr[$line_no-2], "for") || strpos($code_arr[$line_no-2], "while") || strpos($code_arr[$line_no-2], " do{ ") || strpos($code_arr[$line_no-2], " case: ") || strpos($code_arr[$line_no-2], "switch")) {
                		$note_arr[$line_no-1] = 1;    
            		}
            		elseif(trim($code_arr[$line_no]) == "}") {
                		$note_arr[$line_no+1] = 1;
            		} else {
            		}
        	}
    	}	    
}

?>
