<?php
/**
 * 任务action
 * @author Administrator
 *        
 */
class TaskController  extends BaseController{
	
	public function queueAction() {
		$objTask = new TaskModel();
		$taskDoing = $objTask->getTaskList(0, TaskModel::DOING_STATUS);
		$ret = empty($taskDoing)?array():$taskDoing[0];
		echo json_encode($ret);
	}
	
	public function infoAction () {
		if(!$this->requestParams['taskId']) {
			throw new AppException(AppExceptionCodes::INVALID_TASKID);
		}
		$taskId= intval($this->requestParams['taskId']);
		$objTask = new TaskModel();
		$objCase = new CaseModel();
		
		$taskInfo = $objTask->getTaskInfoById($taskId);
		if(empty($taskInfo)) {
			throw new AppException(AppExceptionCodes::INVALID_TASKID);
		}
		$caseRet = $objTask->getTaskResult($taskId, $taskInfo['product']);
		$detail = $objCase->getCaseList($taskInfo['product'], 0 ,2);
		$caselist = json_decode($taskInfo['list'], true);
		$list = array();
		if(!empty($caselist)) {
			foreach($caselist as $case) {
				foreach($detail as $v) {
					if($v['file'] == $case) {
						$list[] = $v;
					}
				}
			}
		}else {
			$list = $detail;
		}
		foreach($list as &$v) {
			foreach($caseRet['list'] as $v1) {
				if ($v['file'] == basename($v1['name'])) {
					$v['timeCost'] = $v1['cost'];
					$v['assert'] = $v1['assert'];
					$v['success']  = $v1['success'];
				}
			}
		}
		unset($caseRet['list']);
		$data = array(
			'info' => $taskInfo,
			'list' => $list,
			'result' => $caseRet['summary']
		);
	//	echo json_encode($data);
		$this->assign('data', $data);
		$this->display('page/taskinfo.tpl');
	}
	
	public function listAction() {
		$status = isset($this->requestParams['status'])?intval($this->requestParams['status']):'';
		$pn = intval($this->requestParams['pn']);
		$objTask = new TaskModel();
		$objCase = new CaseModel();
		$objCover = new CoverModel();
		$list = $objTask->getTaskList($pn, $status);
		$total = $objTask->getTaskCount();
		$productCaseCnt = array();
		foreach($list as $k => &$v) {
			$caseRet = $objTask->getTaskResult($v['id'], $v['product']);
			$v['detail'] = $caseRet;
			if($v['type'] %2 !=0 ) {
				if(empty($productCaseCnt[$v['product']])) {
					$productCaseCnt[$v['product']] = count($objCase->getCaseList($v['product'], 0, 0));
				}
				$v['detail']['summary']['caseCnt'] = $productCaseCnt[$v['product']];
			} else {
				$v['detail']['summary']['caseCnt'] = count(json_decode($v['list'], true));
			}
			$coverage = $objCover->getCoverageData($v['id']);
			$v['coverage'] = floatval(json_decode($coverage['report'], true)['rate']);
		}
//		echo json_encode( array('list'=>$list, 'total' => $total));
		$this->assign('data', array('list'=>$list, 'total' => $total));
		$this->display('page/tasklist.tpl');
	}

	public function addAction() {
		$arrInput = self::_checkParam($this->requestParams);
		$objTask = new TaskModel();

		$taskId = $objTask->addTask($arrInput);
		if ($taskId<=0) { 
			throw new AppException ( AppExceptionCodes::ADD_TASK_FAILED );
		}
		 $data = array(
                        'ret' => true,
                        'jumpUrl'=> 'http://autotest.rong360.com/task/list'
                );
		echo json_encode($data);
	}
	
	private function _checkParam($arrInput) {
		if(empty($arrInput['owner'])||empty($arrInput['product'])||empty($arrInput['type'])){
			throw new AppException(AppExceptionCodes::PARAM_ERROR);
		}
		//因为覆盖率的原因 ，所以type好几类
		$list = json_decode($arrInput['list'], true);
		if( ($arrInput['type']%2 ==1 && !empty($list)) || ($arrInput['type']%2 == 0 && empty($list))) {
			throw new AppException(AppExceptionCodes::PARAM_ERROR);
		}
 		return $arrInput;
	}

	public static function executeAction() {
		$objTask = new TaskModel() ;
		global $argc, $argv;
		$doingTask = $objTask->getTaskList(0, TaskModel::DOING_STATUS); 
		if(count($doingTask) > 0) {
			CLogger::notice("current task[{$doingTask[0]['id']}] is doing ");
			return;
		}
		$undoTask = $objTask->getTaskList(0, TaskModel::NEW_STATUS)[0];
		if(!empty($undoTask)) {
			$id = intval($undoTask['id']);
			$type = intval($undoTask['type']);
			$product = strval($undoTask['product']);
			$list = implode(',',json_decode(strval($undoTask['list']), true));

			$objTask->setDoingTask($id);
			$cmd = "source ~/.bash_profile && sh /home/rong/autoTest/shell/taskBuild.sh $product $type '$list' $id";
			exec ( $cmd, $arr, $ret );
			if( $ret == 1) {
				$objTask->setFailTask($id);
				//发送失败邮件
				
				Util::sendMail(sprintf(Util::REPORT_TITLE, $product), Util::FAIL_CONTENT,  $undoTask['owner']. '@rong360.com');
			}  else {
				$taskRet = $objTask->getTaskResult($id, $product)['summary'];
				var_dump($taskRet);
				if($taskRet['testCnt'] == 0) {
					$status = TaskModel::CANCEL_STATUS;
				}else if( $taskRet['testSuccess'] == 0) {
					$status = TaskModel::FAIL_STATUS;
				}else if ($taskRet['testCnt'] > $taskRet['testSuccess']) {
					$status = TaskModel::DONE_STATUS;
				}else if(($taskRet['testCnt'] == $taskRet['testSuccess']) && $taskRet['testCnt'] > 0) {
					$status = TaskModel::PERFECT_STATUS;
				}else {
					$status = TaskModel::DONE_STATUS;
				}
				$arrFields = array(
					'status' => $status,
					'cost_time' => $taskRet['costTime'],
					'ratio' => $taskRet['succRate']
				);
				$objTask->updateTask($id, $arrFields);
				//发送结果邮件
				$msg = file_get_contents(dirname(sprintf(CaseModel::RESULT_PATH,$product,$id)). '/report.html');
				Util::sendMail(sprintf(Util::REPORT_TITLE, $product), $msg, $undoTask['owner']. '@rong360.com');
			}
		}
		
	}
	
}

?>
