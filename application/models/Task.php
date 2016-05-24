<?php

/**
 * @name Task
 * @desc Tasks数据获取类, 可以访问数据库，文件，其它系统等
 * @author wangjian
 */
class TaskModel extends Dao_BaseModel {
	const TABLE = 'task';
	const NEW_STATUS = 0;
	const DOING_STATUS =1 ;
	const CANCEL_STATUS = 2;
	const PERFECT_STATUS = 3;
	const FAIL_STATUS = 4;
	const DONE_STATUS = 5;
	public static $arrFields = array (
			'id',
			'product',
			'status',
			'type',
			'list',
			'owner',
			'ratio',
			'coverage',
			'cost_time',
			'create_time',
	);
	public function getTaskInfoById($taskId) {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$arrConds = self::getConds ( [ 
				'id' => $taskId 
		] );
		$ret = $this->_db->select ( self::TABLE, self::$arrFields, $arrConds, null, null );
		return count ( $ret ) > 0 ? $ret[0] : array ();
	}

	public function getTaskCount() {
		if (empty ( $this->_db )) {
                        $this->_db = self::getDB ( self::DATABASE );
                }
		return $this->_db->selectCount(self::TABLE);
	}
	public function getTaskList($pn = 0, $status='', $orderType=1) {
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		if($status!=='') {
			$arrConds = self::getConds(['status' => $status]);
		}
		if($orderType == 1) {
			$arrAppends[] = 'order by id desc ';
		}
		$arrAppends[] = "limit $pn, 10";
		$ret = $this->_db->select(self::TABLE, self::$arrFields, $arrConds, null,$arrAppends);
		return count ( $ret ) > 0 ? $ret : array ();
	}
	public function addTask($arrInput) {
		$arrFields = array (
				'product' => trim ( $arrInput ['product'] ),
				'status' => self::NEW_STATUS,
				'type' => intval ( $arrInput ['type'] ),
				'list' => $arrInput ['list'] ,
				'owner' => trim ( $arrInput ['owner'] ),
				'ratio' => 0,
				'coverage' => 0,
				'filter' => json_encode(array(
					'filterdir' => $arrInput['filterdir'],
					'containdir' => $arrInput['containdir']
				)),
				'svn_path' => trim($arrInput['svnPath']),
				'create_time' => time (),
		);
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		$ret = $this->_db->insert ( self::TABLE, $arrFields, null, null );
		return $ret ? $this->_db->getInsertID(): 0;
	}
	
	public function getTaskResult($taskId, $product) {
		$caseRet = [];
		$resultPath = sprintf(CaseModel::RESULT_PATH, $product, $taskId);
		if(!file_exists($resultPath)) {
			CLogger::warning("result path[$resultPath] not exists");
			return [];
		}else {
			$resultStr = file_get_contents($resultPath);
			foreach ( explode("\n", $resultStr) as $v) {
				if(strlen(trim($v)) == 0 ) {
					continue;
				}
				$caseArr = explode(" ", $v);
				if(strpos($v, 'php')) {
					$caseRet ['list'][] = array(
						'name' => $caseArr[0],
						'cost' => $caseArr[1],
						'case' => $caseArr[2],
						'assert' => $caseArr[3],
						'success' => $caseArr[4]
					);
				}else {
					$caseRet['summary'] = array(
						'testCnt' => intval($caseArr[0]),
						'testSuccess' => intval($caseArr[1]),
						'testFail' => intval($caseArr[2]),
						'succRate' => floatval($caseArr[3]),
						'costTime' => intval($caseArr[4])
					);
				}
			}
			return $caseRet;
		}
	} 
	public function setDoneTask($taskId) {
		$arrFields=['status' => self::DONE_STATUS];
		return $this->updateTask($taskId, $arrFields);
	}
	public function setCancelTask($taskId) {
		$arrFields=['status' => self::CANCEL_STATUS];
		return $this->updateTask($taskId, $arrFields);
	}
	public function setPerfectTask($taskId) {
		$arrFields=['status' => self::PERFECT_STATUS];
		return $this->updateTask($taskId, $arrFields);
	}
	public function setFailTask($taskId) {
		$arrFields=['status' => self::OVER_STATUS];
		return $this->updateTask($taskId, $arrFields);
	}
	public function setDoingTask($taskId) {
		$arrFields=['status' => self::DOING_STATUS];
		return $this->updateTask($taskId, $arrFields);
	}
	public function updateTask($taskId, $arrFields) {
		$arrConds = self::getConds ( [ 
				'id' => $taskId 
		] );
		if (empty ( $this->_db )) {
			$this->_db = self::getDB ( self::DATABASE );
		}
		return $this->_db->update ( self::TABLE, $arrFields, $arrConds );
	}
}

?>
